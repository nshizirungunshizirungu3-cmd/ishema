<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\Database;
use App\Core\NotificationService;
use App\Core\Translator;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;

/**
 * Hotel booking workflow with double-booking prevention.
 */
class BookingService
{
    /**
     * Check whether a room is available for a date range (exclusive of checkout date).
     */
    public static function isRoomAvailable(int $roomId, string $checkIn, string $checkOut, ?int $excludeBookingId = null): bool
    {
        if (strtotime($checkOut) <= strtotime($checkIn)) {
            throw new \RuntimeException('Check-out date must be after check-in date.');
        }
        return Room::isAvailable($roomId, $checkIn, $checkOut, $excludeBookingId);
    }

    /**
     * Create a booking with double-booking prevention.
     */
    public static function createBooking(array $data): array
    {
        Database::beginTransaction();
        try {
            $businessId = (int)$data['business_id'];
            $room = Room::find((int)$data['room_id']);
            if (!$room || (int)$room['business_id'] !== $businessId) {
                throw new \RuntimeException('Invalid room selected.');
            }
            if ($room['status'] === 'out_of_service' || $room['status'] === 'maintenance') {
                throw new \RuntimeException('This room is currently unavailable.');
            }

            $checkIn = $data['check_in_date'];
            $checkOut = $data['check_out_date'];

            if (!self::isRoomAvailable((int)$room['id'], $checkIn, $checkOut)) {
                throw new \RuntimeException(Translator::t('hotel.double_booking_prevented'));
            }

            // Guest
            $guestId = (int)($data['guest_id'] ?? 0);
            if ($guestId <= 0) {
                $guestId = Guest::insert([
                    'business_id' => $businessId,
                    'first_name' => $data['first_name'] ?? '',
                    'last_name' => $data['last_name'] ?? '',
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'id_number' => $data['id_number'] ?? null,
                    'id_type' => $data['id_type'] ?? null,
                    'country' => $data['country'] ?? null,
                    'address' => $data['address'] ?? null,
                ]);
            }

            $nights = max(1, (int)((strtotime($checkOut) - strtotime($checkIn)) / 86400));
            $pricePerNight = (float)($data['price'] ?? $room['price']);
            $total = $nights * $pricePerNight;

            $reference = self::generateReference();
            $bookingId = Booking::insert([
                'business_id' => $businessId,
                'booking_reference' => $reference,
                'guest_id' => $guestId,
                'room_id' => (int)$room['id'],
                'guest_user_id' => $data['guest_user_id'] ?? null,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'adults' => (int)($data['adults'] ?? 1),
                'children' => (int)($data['children'] ?? 0),
                'nights' => $nights,
                'status' => $data['status'] ?? 'confirmed',
                'total_amount' => $total,
                'payment_status' => 'pending',
                'special_requests' => $data['special_requests'] ?? null,
            ]);

            // Reserve the room
            Room::update((int)$room['id'], ['status' => 'reserved']);

            Database::commit();

            NotificationService::sendToRole(
                $businessId,
                'RECEPTIONIST',
                NotificationService::TYPE_BOOKING_NEW,
                Translator::t('notifications.notif_booking_new'),
                Translator::t('notifications.notif_booking_new_msg', ['reference' => $reference, 'date' => $checkIn]),
                ['booking_id' => $bookingId, 'reference' => $reference]
            );

            Audit::record('create', 'booking', (string)$bookingId, null, $data, $businessId);
            return Booking::find($bookingId);
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollBack();
            }
            throw $e;
        }
    }

    public static function checkIn(int $bookingId): bool
    {
        $booking = Booking::find($bookingId);
        if (!$booking) {
            return false;
        }
        Booking::update($bookingId, ['status' => 'checked_in', 'checked_in_at' => date('Y-m-d H:i:s')]);
        Room::update((int)$booking['room_id'], ['status' => 'occupied']);
        Audit::record('check_in', 'booking', (string)$bookingId, null, null, (int)$booking['business_id']);
        return true;
    }

    public static function checkOut(int $bookingId): bool
    {
        $booking = Booking::find($bookingId);
        if (!$booking) {
            return false;
        }
        Booking::update($bookingId, ['status' => 'checked_out', 'checked_out_at' => date('Y-m-d H:i:s')]);
        Room::update((int)$booking['room_id'], ['status' => 'cleaning']);
        Audit::record('check_out', 'booking', (string)$bookingId, null, null, (int)$booking['business_id']);
        return true;
    }

    public static function cancel(int $bookingId): bool
    {
        $booking = Booking::find($bookingId);
        if (!$booking || in_array($booking['status'], ['checked_out', 'cancelled'], true)) {
            return false;
        }
        Booking::update($bookingId, ['status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s')]);
        // Release the room if it was reserved for this booking
        $room = Room::find((int)$booking['room_id']);
        if ($room && $room['status'] === 'reserved') {
            Room::update((int)$room['id'], ['status' => 'available']);
        }
        Audit::record('cancel', 'booking', (string)$bookingId, null, null, (int)$booking['business_id']);
        return true;
    }

    public static function generateReference(): string
    {
        do {
            $ref = 'BK-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $exists = Database::run('SELECT id FROM bookings WHERE booking_reference = ? LIMIT 1', [$ref])->fetch();
        } while ($exists);
        return $ref;
    }
}
