<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Core\Translator;
use App\Models\Booking;
use App\Models\HotelService;
use App\Models\Review;
use App\Services\BookingService;

/**
 * Hotel guest portal: browse rooms, book, manage requests, review.
 */
class GuestPortalController extends Controller
{
    /** Public room listing for a business (linked from booking links). */
    public function rooms(string $businessId): void
    {
        $business = Database::run(
            'SELECT * FROM businesses WHERE business_id = ? AND deleted_at IS NULL AND status = "active" LIMIT 1',
            [$businessId]
        )->fetch();
        if (!$business) {
            echo $this->view('customer/invalid-qr', [], false);
            return;
        }
        $types = Database::run('SELECT * FROM room_types WHERE business_id = ? AND is_active = 1', [(int)$business['id']])->fetchAll();
        $rooms = Database::run(
            'SELECT r.*, rt.name AS type_name, rt.capacity FROM rooms r
             LEFT JOIN room_types rt ON rt.id = r.room_type_id
             WHERE r.business_id = ? AND r.is_active = 1 AND r.deleted_at IS NULL AND r.status = "available"
             ORDER BY r.room_number',
            [(int)$business['id']]
        )->fetchAll();
        echo $this->view('guest/rooms', [
            'business' => $business,
            'types' => $types,
            'rooms' => $rooms,
        ], false);
    }

    /** Public booking form (guest checkout without account). */
    public function book(string $businessId): void
    {
        $business = Database::run(
            'SELECT * FROM businesses WHERE business_id = ? AND deleted_at IS NULL AND status = "active" LIMIT 1',
            [$businessId]
        )->fetch();
        if (!$business) {
            echo $this->view('customer/invalid-qr', [], false);
            return;
        }
        $checkIn = (string)Security::input('check_in', date('Y-m-d'));
        $checkOut = (string)Security::input('check_out', date('Y-m-d', strtotime('+1 day')));
        $rooms = \App\Models\Room::availableBetween((int)$business['id'], $checkIn, $checkOut);
        $types = Database::run('SELECT * FROM room_types WHERE business_id = ? AND is_active = 1', [(int)$business['id']])->fetchAll();
        $message = Session::flash('message');
        $error = Session::flash('error');

        echo $this->view('guest/book', [
            'business' => $business,
            'rooms' => $rooms,
            'types' => $types,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'message' => $message,
            'error' => $error,
        ], false);
    }

    public function doBook(string $businessId): void
    {
        $business = Database::run(
            'SELECT * FROM businesses WHERE business_id = ? AND deleted_at IS NULL AND status = "active" LIMIT 1',
            [$businessId]
        )->fetch();
        if (!$business) {
            $this->json(['ok' => false, 'error' => 'Business not found.'], 404);
            return;
        }
        $checkIn = (string)Security::input('check_in_date', '');
        $checkOut = (string)Security::input('check_out_date', '');
        $roomId = (int)Security::input('room_id', 0);
        if ($checkIn === '' || $checkOut === '' || $roomId <= 0) {
            Session::flash('error', Translator::t('common.please_fill_required'));
            $this->redirect('/book/' . $businessId . '?check_in=' . $checkIn . '&check_out=' . $checkOut);
            return;
        }
        try {
            $booking = BookingService::createBooking([
                'business_id' => (int)$business['id'],
                'room_id' => $roomId,
                'first_name' => (string)Security::input('first_name', ''),
                'last_name' => (string)Security::input('last_name', ''),
                'email' => (string)Security::input('email', ''),
                'phone' => (string)Security::input('phone', ''),
                'country' => (string)Security::input('country', ''),
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'adults' => (int)Security::input('adults', 1),
                'children' => (int)Security::input('children', 0),
                'special_requests' => (string)Security::input('special_requests', ''),
                'status' => 'confirmed',
            ]);
            Session::flash('message', Translator::t('hotel.booking_created') . ' - ' . $booking['booking_reference']);
            $this->redirect('/book/' . $businessId . '?check_in=' . $checkIn . '&check_out=' . $checkOut);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/book/' . $businessId . '?check_in=' . $checkIn . '&check_out=' . $checkOut);
        }
    }

    /** Look up a booking by reference + email/phone. */
    public function lookup(): void
    {
        $reference = (string)Security::input('reference', '');
        $key = (string)Security::input('key', '');
        $booking = Booking::byReference($reference);
        $valid = false;
        if ($booking) {
            $guest = \App\Models\Guest::find((int)$booking['guest_id']);
            if ($guest && ($guest['email'] === $key || $guest['phone'] === $key)) {
                $valid = true;
            }
        }
        if (!$valid) {
            echo $this->view('guest/lookup', ['error' => Translator::t('hotel.booking_not_found')], false);
            return;
        }
        $business = Database::run('SELECT * FROM businesses WHERE id = ? LIMIT 1', [(int)$booking['business_id']])->fetch();
        $services = HotelService::query()->where('booking_id', (int)$booking['id'])->orderBy('id', 'DESC')->get();
        echo $this->view('guest/booking-detail', [
            'booking' => $booking,
            'business' => $business,
            'guest' => $guest,
            'services' => $services,
        ], false);
    }

    /** Authenticated guest portal pages. */
    public function myBookings(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
            return;
        }
        $bookings = Booking::forGuestUser((int)$user['id']);
        echo $this->view('guest/my-bookings', ['bookings' => $bookings], false);
    }

    public function myRequests(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
            return;
        }
        $services = HotelService::forGuestUser((int)$user['id']);
        echo $this->view('guest/my-requests', ['services' => $services], false);
    }

    public function requestService(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->json(['ok' => false], 401);
            return;
        }
        $title = (string)Security::input('title', '');
        if ($title === '') {
            $this->json(['ok' => false, 'error' => 'Title required.'], 422);
            return;
        }
        $bookingId = (int)Security::input('booking_id', 0);
        $booking = $bookingId ? Booking::find($bookingId) : null;
        $businessId = $booking ? (int)$booking['business_id'] : (int)Security::input('business_id', 0);
        if (!$businessId) {
            $this->json(['ok' => false, 'error' => 'Select a booking first.'], 422);
            return;
        }
        HotelService::insert([
            'business_id' => $businessId,
            'guest_id' => $booking ? (int)$booking['guest_id'] : null,
            'booking_id' => $bookingId ?: null,
            'guest_user_id' => (int)$user['id'],
            'service_type' => in_array(Security::input('service_type', 'room_service'), ['room_service', 'restaurant_order', 'laundry', 'transport', 'other'], true) ? Security::input('service_type', 'room_service') : 'room_service',
            'title' => $title,
            'description' => (string)Security::input('description', ''),
            'status' => 'pending',
        ]);
        $this->json(['ok' => true, 'message' => Translator::t('hotel.service_saved')]);
    }

    public function requestCheckout(int $bookingId): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->json(['ok' => false], 401);
            return;
        }
        $booking = Booking::query()->where('id', $bookingId)->where('guest_user_id', (int)$user['id'])->first();
        if (!$booking) {
            $this->json(['ok' => false, 'error' => 'Booking not found.'], 404);
            return;
        }
        // Notify reception
        \App\Core\NotificationService::sendToRole(
            (int)$booking['business_id'],
            'RECEPTIONIST',
            'Check-out requested',
            'Guest requested check-out for booking ' . $booking['booking_reference'],
            ['booking_id' => $bookingId]
        );
        $this->json(['ok' => true, 'message' => 'Check-out request sent to reception.']);
    }

    public function review(int $bookingId): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->json(['ok' => false], 401);
            return;
        }
        $booking = Booking::query()->where('id', $bookingId)->where('guest_user_id', (int)$user['id'])->first();
        if (!$booking) {
            $this->json(['ok' => false, 'error' => 'Booking not found.'], 404);
            return;
        }
        $overall = (int)Security::input('overall_rating', 0);
        if ($overall < 1 || $overall > 5) {
            $this->json(['ok' => false, 'error' => 'Invalid rating.'], 422);
            return;
        }
        Review::insert([
            'business_id' => (int)$booking['business_id'],
            'service_type' => 'hotel_stay',
            'service_id' => $bookingId,
            'customer_user_id' => (int)$user['id'],
            'guest_id' => (int)$booking['guest_id'],
            'food_quality' => (int)Security::input('food_quality', $overall),
            'service_rating' => (int)Security::input('service_rating', $overall),
            'speed_rating' => (int)Security::input('speed_rating', $overall),
            'overall_rating' => $overall,
            'comment' => (string)Security::input('comment', ''),
        ]);
        $this->json(['ok' => true, 'message' => Translator::t('hotel.review_submitted')]);
    }
}
