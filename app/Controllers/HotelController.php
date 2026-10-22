<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Core\Tenant;
use App\Core\Translator;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Housekeeping;
use App\Models\HotelService;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\BookingService;
use App\Services\ReportService;

class HotelController extends Controller
{
    public function overview(): void
    {
        $business = Tenant::requireBusiness();
        $stats = ReportService::businessOverview((int)$business['id'], (string)$business['type']);
        $occupancy = ReportService::occupancyTrend((int)$business['id'], 8);
        echo $this->view('hotel/overview', [
            'business' => $business,
            'stats' => $stats,
            'occupancy' => $occupancy,
        ]);
    }

    // -------------------- ROOM TYPES --------------------

    public function roomTypes(): void
    {
        $business = Tenant::requireBusiness();
        $types = RoomType::forBusiness((int)$business['id']);
        // include inactive for editing completeness
        $all = Database::run('SELECT * FROM room_types WHERE business_id = ? ORDER BY name', [(int)$business['id']])->fetchAll();
        echo $this->view('hotel/room-types', ['types' => $all]);
    }

    public function storeRoomType(): void
    {
        $this->saveRoomType(0);
    }

    public function updateRoomType(int $id): void
    {
        $this->saveRoomType($id);
    }

    private function saveRoomType(int $id): void
    {
        $business = Tenant::requireBusiness();
        $name = (string)Security::input('name', '');
        if ($name === '') {
            $this->json(['ok' => false, 'error' => Translator::t('common.please_fill_required')], 422);
            return;
        }
        $slug = \App\Core\Security::slug($name);
        $amenities = (array)Security::input('amenities', []);
        $data = [
            'business_id' => (int)$business['id'],
            'name' => $name,
            'slug' => $slug ?: 'type-' . uniqid(),
            'description' => (string)Security::input('description', ''),
            'capacity' => (int)Security::input('capacity', 1),
            'default_price' => (float)Security::input('default_price', 0),
            'amenities' => json_encode($amenities),
            'is_active' => Security::input('is_active') ? 1 : 0,
        ];
        if ($id === 0) {
            RoomType::insert($data);
            $id = (int)Database::lastId();
        } else {
            $type = RoomType::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
            if (!$type) {
                $this->json(['ok' => false, 'error' => 'Not found.'], 404);
                return;
            }
            RoomType::update($id, $data);
        }
        $this->json(['ok' => true, 'message' => Translator::t('hotel.room_type_saved'), 'id' => $id]);
    }

    public function deleteRoomType(int $id): void
    {
        $business = Tenant::requireBusiness();
        $inUse = Room::query()->where('room_type_id', $id)->where('is_active', 1)->count();
        if ($inUse > 0) {
            $this->json(['ok' => false, 'error' => 'This room type has rooms assigned.'], 400);
            return;
        }
        RoomType::delete($id);
        $this->json(['ok' => true]);
    }

    // -------------------- ROOMS --------------------

    public function rooms(): void
    {
        $business = Tenant::requireBusiness();
        $status = Security::input('status', 'all');
        $q = Room::query()->where('business_id', (int)$business['id'])->where('is_active', 1)->whereNull('deleted_at')->orderBy('room_number');
        if ($status !== 'all') {
            $q->where('status', $status);
        }
        $rooms = $q->limit(300)->get();
        $types = RoomType::forBusiness((int)$business['id']);
        echo $this->view('hotel/rooms', ['rooms' => $rooms, 'types' => $types, 'status' => $status]);
    }

    public function createRoom(): void
    {
        $business = Tenant::requireBusiness();
        $types = RoomType::forBusiness((int)$business['id']);
        echo $this->view('hotel/room-form', ['types' => $types, 'room' => null]);
    }

    public function editRoom(int $id): void
    {
        $business = Tenant::requireBusiness();
        $room = Room::query()->where('id', $id)->where('business_id', (int)$business['id'])->whereNull('deleted_at')->first();
        if (!$room) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/hotel/rooms');
            return;
        }
        $types = RoomType::forBusiness((int)$business['id']);
        $images = json_decode((string)$room['images'], true) ?: [];
        echo $this->view('hotel/room-form', ['types' => $types, 'room' => $room, 'images' => $images]);
    }

    public function storeRoom(): void
    {
        $this->saveRoom(0);
    }

    public function updateRoom(int $id): void
    {
        $this->saveRoom($id);
    }

    private function saveRoom(int $id): void
    {
        $business = Tenant::requireBusiness();
        $roomNumber = (string)Security::input('room_number', '');
        if ($roomNumber === '') {
            Session::flash('error', Translator::t('common.please_fill_required'));
            $this->redirect($id === 0 ? '/hotel/rooms/create' : '/hotel/rooms/' . $id . '/edit');
            return;
        }

        // Image uploads
        $existingImages = [];
        if ($id > 0) {
            $current = Room::find($id);
            $existingImages = json_decode((string)($current['images'] ?? '[]'), true) ?: [];
        }
        $newImages = [];
        if (isset($_FILES['images'])) {
            $files = $_FILES['images'];
            foreach ($files['tmp_name'] as $i => $tmp) {
                if (empty($tmp) || $files['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                $single = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $tmp,
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
                try {
                    $newImages[] = Security::moveImageUpload($single, 'rooms');
                } catch (\Throwable $e) {
                    Session::flash('error', $e->getMessage());
                    $this->redirect($id === 0 ? '/hotel/rooms/create' : '/hotel/rooms/' . $id . '/edit');
                    return;
                }
            }
        }
        $allImages = array_merge($existingImages, $newImages);

        // Package limit
        $sub = \App\Models\Subscription::currentForBusiness((int)$business['id']);
        if ($sub) {
            $package = \App\Models\Package::find((int)$sub['package_id']);
            if ($package && (int)$package['max_rooms'] > 0 && $id === 0) {
                $count = Room::where('business_id', (int)$business['id'])->where('is_active', 1)->whereNull('deleted_at')->count();
                if ($count >= (int)$package['max_rooms']) {
                    Session::flash('error', 'Your package allows a maximum of ' . $package['max_rooms'] . ' rooms. Upgrade to add more.');
                    $this->redirect('/hotel/rooms/create');
                    return;
                }
            }
        }

        $data = [
            'room_type_id' => (int)Security::input('room_type_id', 0),
            'room_number' => $roomNumber,
            'floor' => (string)Security::input('floor', ''),
            'price' => (float)Security::input('price', 0),
            'description' => (string)Security::input('description', ''),
            'status' => in_array(Security::input('status', 'available'), ['available', 'occupied', 'reserved', 'cleaning', 'maintenance', 'out_of_service'], true) ? Security::input('status', 'available') : 'available',
            'is_active' => 1,
            'images' => json_encode($allImages),
        ];

        // Unique room number within business
        $dup = Database::run(
            'SELECT id FROM rooms WHERE business_id = ? AND room_number = ?' . ($id ? ' AND id <> ?' : '') . ' AND deleted_at IS NULL LIMIT 1',
            array_merge([(int)$business['id'], $roomNumber], $id ? [$id] : [])
        )->fetch();
        if ($dup) {
            Session::flash('error', 'A room with this number already exists.');
            $this->redirect($id === 0 ? '/hotel/rooms/create' : '/hotel/rooms/' . $id . '/edit');
            return;
        }

        if ($id === 0) {
            $data['business_id'] = (int)$business['id'];
            Room::insert($data);
            Session::flash('message', Translator::t('hotel.room_saved'));
        } else {
            Room::update($id, $data);
            Session::flash('message', Translator::t('hotel.room_saved'));
        }
        $this->redirect('/hotel/rooms');
    }

    public function deleteRoom(int $id): void
    {
        $business = Tenant::requireBusiness();
        $room = Room::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if ($room) {
            Room::softDelete($id);
            $this->json(['ok' => true]);
        }
        $this->json(['ok' => false, 'error' => 'Not found.'], 404);
    }

    public function removeRoomImage(int $id): void
    {
        $business = Tenant::requireBusiness();
        $room = Room::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$room) {
            $this->json(['ok' => false], 404);
            return;
        }
        $images = json_decode((string)$room['images'], true) ?: [];
        $path = (string)Security::input('path', '');
        $images = array_values(array_filter($images, fn($img) => $img !== $path));
        Room::update($id, ['images' => json_encode($images)]);
        $this->json(['ok' => true, 'images' => $images]);
    }

    // -------------------- BOOKINGS --------------------

    public function bookings(): void
    {
        $business = Tenant::requireBusiness();
        $status = Security::input('status', 'all');
        $q = Booking::queryAs('b')
            ->select('b.*, g.first_name, g.last_name, g.phone, r.room_number')
            ->leftJoin('guests g', 'g.id = b.guest_id')
            ->leftJoin('rooms r', 'r.id = b.room_id')
            ->where('b.business_id', (int)$business['id'])
            ->orderBy('b.id', 'DESC');
        if ($status !== 'all') {
            $q->where('b.status', $status);
        }
        $bookings = $q->limit(200)->get();
        echo $this->view('hotel/bookings', ['bookings' => $bookings, 'status' => $status]);
    }

    public function createBooking(): void
    {
        $business = Tenant::requireBusiness();
        $rooms = Room::forBusiness((int)$business['id']);
        $types = RoomType::forBusiness((int)$business['id']);
        echo $this->view('hotel/booking-form', [
            'business' => $business,
            'rooms' => $rooms,
            'types' => $types,
            'booking' => null,
            'availableRooms' => [],
        ]);
    }

    public function checkAvailability(): void
    {
        $business = Tenant::requireBusiness();
        $checkIn = (string)Security::input('check_in_date', date('Y-m-d'));
        $checkOut = (string)Security::input('check_out_date', date('Y-m-d', strtotime('+1 day')));
        if (strtotime($checkOut) <= strtotime($checkIn)) {
            $this->json(['ok' => false, 'error' => Translator::t('hotel.no_rooms_available')], 422);
            return;
        }
        $rooms = Room::availableBetween((int)$business['id'], $checkIn, $checkOut);
        $this->json([
            'ok' => true,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'nights' => max(1, (int)((strtotime($checkOut) - strtotime($checkIn)) / 86400)),
            'rooms' => $rooms,
        ]);
    }

    public function storeBooking(): void
    {
        $business = Tenant::requireBusiness();
        $checkIn = (string)Security::input('check_in_date', '');
        $checkOut = (string)Security::input('check_out_date', '');
        $roomId = (int)Security::input('room_id', 0);
        if ($checkIn === '' || $checkOut === '' || $roomId <= 0) {
            Session::flash('error', Translator::t('common.please_fill_required'));
            $this->redirect('/hotel/bookings/create');
            return;
        }
        try {
            $booking = BookingService::createBooking([
                'business_id' => (int)$business['id'],
                'room_id' => $roomId,
                'guest_id' => (int)Security::input('guest_id', 0),
                'first_name' => (string)Security::input('first_name', ''),
                'last_name' => (string)Security::input('last_name', ''),
                'email' => (string)Security::input('email', ''),
                'phone' => (string)Security::input('phone', ''),
                'id_number' => (string)Security::input('id_number', ''),
                'id_type' => (string)Security::input('id_type', ''),
                'country' => (string)Security::input('country', ''),
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'adults' => (int)Security::input('adults', 1),
                'children' => (int)Security::input('children', 0),
                'price' => (float)Security::input('price', 0) ?: null,
                'special_requests' => (string)Security::input('special_requests', ''),
                'guest_user_id' => \App\Core\Auth::user()['role'] === 'CUSTOMER' ? \App\Core\Auth::id() : null,
                'status' => Security::input('status', 'confirmed') === 'pending' ? 'pending' : 'confirmed',
            ]);
            Session::flash('message', Translator::t('hotel.booking_created') . ' - ' . $booking['booking_reference']);
            $this->redirect('/hotel/bookings');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/hotel/bookings/create');
        }
    }

    public function editBooking(int $id): void
    {
        $business = Tenant::requireBusiness();
        $booking = Booking::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$booking) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/hotel/bookings');
            return;
        }
        $guest = Guest::find((int)$booking['guest_id']);
        $rooms = Room::forBusiness((int)$business['id']);
        $types = RoomType::forBusiness((int)$business['id']);
        $availableRooms = Room::availableBetween((int)$business['id'], $booking['check_in_date'], $booking['check_out_date'], $id);
        echo $this->view('hotel/booking-form', [
            'business' => $business,
            'booking' => $booking,
            'guest' => $guest,
            'rooms' => $rooms,
            'types' => $types,
            'availableRooms' => $availableRooms,
        ]);
    }

    public function updateBooking(int $id): void
    {
        $business = Tenant::requireBusiness();
        $booking = Booking::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$booking) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/hotel/bookings');
            return;
        }
        $checkIn = (string)Security::input('check_in_date', $booking['check_in_date']);
        $checkOut = (string)Security::input('check_out_date', $booking['check_out_date']);
        $roomId = (int)Security::input('room_id', (int)$booking['room_id']);

        // Double-booking prevention on edit
        if (!BookingService::isRoomAvailable($roomId, $checkIn, $checkOut, $id)) {
            Session::flash('error', Translator::t('hotel.double_booking_prevented'));
            $this->redirect('/hotel/bookings/' . $id . '/edit');
            return;
        }

        $nights = max(1, (int)((strtotime($checkOut) - strtotime($checkIn)) / 86400));
        $price = (float)Security::input('price', (float)$booking['total_amount'] / max(1, (int)$booking['nights']));

        Booking::update($id, [
            'room_id' => $roomId,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'nights' => $nights,
            'adults' => (int)Security::input('adults', 1),
            'children' => (int)Security::input('children', 0),
            'total_amount' => $nights * $price,
            'status' => in_array(Security::input('status', $booking['status']), ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'], true) ? Security::input('status', $booking['status']) : $booking['status'],
            'payment_status' => in_array(Security::input('payment_status', $booking['payment_status']), ['pending', 'paid', 'partial', 'refunded'], true) ? Security::input('payment_status', $booking['payment_status']) : $booking['payment_status'],
            'special_requests' => (string)Security::input('special_requests', ''),
        ]);

        // Sync room status when dates changed
        $room = Room::find($roomId);
        if ($room) {
            if (in_array($booking['status'], ['checked_in'])) {
                Room::update($roomId, ['status' => 'occupied']);
            } elseif ($booking['status'] === 'cancelled' && $room['status'] === 'reserved') {
                Room::update($roomId, ['status' => 'available']);
            } elseif ($booking['status'] === 'confirmed' && !in_array($room['status'], ['occupied', 'maintenance', 'out_of_service'], true)) {
                Room::update($roomId, ['status' => 'reserved']);
            }
        }

        Session::flash('message', Translator::t('hotel.booking_updated'));
        $this->redirect('/hotel/bookings');
    }

    public function bookingAction(int $id, string $action): void
    {
        $business = Tenant::requireBusiness();
        $booking = Booking::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$booking) {
            $this->json(['ok' => false, 'error' => 'Not found.'], 404);
            return;
        }
        $result = match ($action) {
            'checkin' => BookingService::checkIn($id),
            'checkout' => BookingService::checkOut($id),
            'cancel' => BookingService::cancel($id),
            default => false,
        };
        if ($result) {
            $this->json(['ok' => true, 'message' => 'Updated.']);
        }
        $this->json(['ok' => false, 'error' => 'Unable to perform this action.'], 422);
    }

    public function viewBooking(int $id): void
    {
        $business = Tenant::requireBusiness();
        $booking = Booking::queryAs('b')
            ->select('b.*, g.first_name, g.last_name, g.email, g.phone, g.id_number, g.country, r.room_number, rt.name AS room_type_name, rt.capacity')
            ->leftJoin('guests g', 'g.id = b.guest_id')
            ->leftJoin('rooms r', 'r.id = b.room_id')
            ->leftJoin('room_types rt', 'rt.id = r.room_type_id')
            ->where('b.id', $id)
            ->where('b.business_id', (int)$business['id'])
            ->first();
        if (!$booking) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/hotel/bookings');
            return;
        }
        $services = HotelService::query()->where('booking_id', $id)->orderBy('id', 'DESC')->get();
        $payments = \App\Models\Payment::query()->where('business_id', (int)$business['id'])->where('payable_type', 'booking')->where('payable_id', $id)->orderBy('id', 'DESC')->get();
        echo $this->view('hotel/booking-view', [
            'booking' => $booking,
            'services' => $services,
            'payments' => $payments,
        ]);
    }

    // -------------------- GUESTS --------------------

    public function guests(): void
    {
        $business = Tenant::requireBusiness();
        $search = (string)Security::input('search', '');
        $guests = Guest::forBusiness((int)$business['id'], $search);
        echo $this->view('hotel/guests', ['guests' => $guests, 'search' => $search]);
    }

    public function storeGuest(): void
    {
        $this->saveGuest(0);
    }

    public function updateGuest(int $id): void
    {
        $this->saveGuest($id);
    }

    private function saveGuest(int $id): void
    {
        $business = Tenant::requireBusiness();
        $firstName = (string)Security::input('first_name', '');
        $lastName = (string)Security::input('last_name', '');
        if ($firstName === '' || $lastName === '') {
            $this->json(['ok' => false, 'error' => Translator::t('common.please_fill_required')], 422);
            return;
        }
        $data = [
            'business_id' => (int)$business['id'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => (string)Security::input('email', ''),
            'phone' => (string)Security::input('phone', ''),
            'id_number' => (string)Security::input('id_number', ''),
            'id_type' => (string)Security::input('id_type', ''),
            'country' => (string)Security::input('country', ''),
            'address' => (string)Security::input('address', ''),
            'notes' => (string)Security::input('notes', ''),
        ];
        if ($id === 0) {
            $id = Guest::insert($data);
        } else {
            Guest::update($id, $data);
        }
        $this->json(['ok' => true, 'message' => Translator::t('hotel.guest_saved'), 'id' => $id]);
    }

    public function viewGuest(int $id): void
    {
        $business = Tenant::requireBusiness();
        $guest = Guest::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$guest) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/hotel/guests');
            return;
        }
        $bookings = Booking::query()->where('guest_id', $id)->where('business_id', (int)$business['id'])->orderBy('id', 'DESC')->limit(20)->get();
        echo $this->view('hotel/guest-view', ['guest' => $guest, 'bookings' => $bookings]);
    }

    // -------------------- HOUSEKEEPING --------------------

    public function housekeeping(): void
    {
        $business = Tenant::requireBusiness();
        $status = Security::input('status', 'all');
        $tasks = Housekeeping::forBusiness((int)$business['id'], $status !== 'all' ? $status : null);
        $rooms = Room::forBusiness((int)$business['id']);
        $housekeepers = \App\Models\User::businessUsers((int)$business['id'], 'HOUSEKEEPING');
        echo $this->view('hotel/housekeeping', [
            'tasks' => $tasks,
            'rooms' => $rooms,
            'housekeepers' => $housekeepers,
            'status' => $status,
        ]);
    }

    public function storeHousekeeping(): void
    {
        $business = Tenant::requireBusiness();
        $roomId = (int)Security::input('room_id', 0);
        if ($roomId <= 0) {
            $this->json(['ok' => false, 'error' => 'Select a room.'], 422);
            return;
        }
        $type = in_array(Security::input('type', 'cleaning'), ['cleaning', 'maintenance', 'inspection'], true) ? Security::input('type', 'cleaning') : 'cleaning';
        $id = Housekeeping::insert([
            'business_id' => (int)$business['id'],
            'room_id' => $roomId,
            'type' => $type,
            'priority' => in_array(Security::input('priority', 'medium'), ['low', 'medium', 'high', 'urgent'], true) ? Security::input('priority', 'medium') : 'medium',
            'status' => 'pending',
            'assigned_to_user_id' => (int)Security::input('assigned_to_user_id', 0) ?: null,
            'notes' => (string)Security::input('notes', ''),
        ]);
        // If cleaning requested, mark room as cleaning
        if ($type === 'cleaning') {
            $room = Room::find($roomId);
            if ($room && $room['status'] === 'available') {
                Room::update($roomId, ['status' => 'cleaning']);
            }
        }
        \App\Core\NotificationService::sendToRole(
            (int)$business['id'],
            'HOUSEKEEPING',
            \App\Core\Translator::t('notifications.notif_room_cleaning'),
            \App\Core\Translator::t('notifications.notif_room_cleaning_msg', ['room' => $roomId]),
            ['task_id' => $id]
        );
        $this->json(['ok' => true, 'message' => Translator::t('hotel.housekeeping_saved')]);
    }

    public function housekeepingAction(int $id, string $action): void
    {
        $business = Tenant::requireBusiness();
        $task = Housekeeping::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$task) {
            $this->json(['ok' => false, 'error' => 'Not found.'], 404);
            return;
        }
        if ($action === 'complete') {
            Housekeeping::update($id, ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);
            // Room returns to available if it was being cleaned
            $room = Room::find((int)$task['room_id']);
            if ($room && $room['status'] === 'cleaning') {
                Room::update((int)$task['room_id'], ['status' => 'available']);
            }
        } elseif ($action === 'start') {
            Housekeeping::update($id, ['status' => 'in_progress']);
        } elseif ($action === 'cancel') {
            Housekeeping::update($id, ['status' => 'cancelled']);
        }
        $this->json(['ok' => true, 'message' => 'Updated.']);
    }

    // -------------------- HOTEL SERVICES --------------------

    public function services(): void
    {
        $business = Tenant::requireBusiness();
        $services = HotelService::forBusiness((int)$business['id']);
        $rooms = Room::forBusiness((int)$business['id']);
        $guests = Guest::forBusiness((int)$business['id']);
        echo $this->view('hotel/services', [
            'services' => $services,
            'rooms' => $rooms,
            'guests' => $guests,
        ]);
    }

    public function storeService(): void
    {
        $business = Tenant::requireBusiness();
        $title = (string)Security::input('title', '');
        if ($title === '') {
            $this->json(['ok' => false, 'error' => 'Title required.'], 422);
            return;
        }
        $quantity = (int)Security::input('quantity', 1);
        $unitPrice = (float)Security::input('unit_price', 0);
        $id = HotelService::insert([
            'business_id' => (int)$business['id'],
            'guest_id' => (int)Security::input('guest_id', 0) ?: null,
            'booking_id' => (int)Security::input('booking_id', 0) ?: null,
            'service_type' => in_array(Security::input('service_type', 'room_service'), ['room_service', 'restaurant_order', 'laundry', 'transport', 'other'], true) ? Security::input('service_type', 'room_service') : 'room_service',
            'title' => $title,
            'description' => (string)Security::input('description', ''),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $quantity * $unitPrice,
            'status' => 'pending',
            'notes' => (string)Security::input('notes', ''),
        ]);
        \App\Core\NotificationService::sendToRole(
            (int)$business['id'],
            'RECEPTIONIST',
            \App\Core\Translator::t('notifications.notif_service_request'),
            \App\Core\Translator::t('notifications.notif_service_request_msg', ['service' => $title]),
            ['service_id' => $id]
        );
        $this->json(['ok' => true, 'message' => Translator::t('hotel.service_saved')]);
    }

    public function serviceAction(int $id, string $action): void
    {
        $business = Tenant::requireBusiness();
        $service = HotelService::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$service) {
            $this->json(['ok' => false, 'error' => 'Not found.'], 404);
            return;
        }
        if ($action === 'complete') {
            HotelService::update($id, ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);
        } elseif ($action === 'start') {
            HotelService::update($id, ['status' => 'in_progress']);
        } elseif ($action === 'cancel') {
            HotelService::update($id, ['status' => 'cancelled']);
        }
        $this->json(['ok' => true, 'message' => 'Updated.']);
    }

    // -------------------- HOTEL REPORTS --------------------

    public function reports(): void
    {
        $business = Tenant::requireBusiness();
        $occupancy = ReportService::occupancyTrend((int)$business['id'], 12);
        $topRooms = Database::run(
            'SELECT r.room_number, rt.name AS type_name, COUNT(b.id) AS bookings, SUM(b.total_amount) AS revenue
             FROM bookings b
             JOIN rooms r ON r.id = b.room_id
             LEFT JOIN room_types rt ON rt.id = r.room_type_id
             WHERE b.business_id = ? AND b.status NOT IN ("cancelled")
             GROUP BY b.room_id ORDER BY bookings DESC LIMIT 10',
            [(int)$business['id']]
        )->fetchAll();
        $reviews = \App\Models\Review::average((int)$business['id']);
        echo $this->view('hotel/reports', [
            'business' => $business,
            'occupancy' => $occupancy,
            'topRooms' => $topRooms,
            'reviews' => $reviews,
        ]);
    }
}
