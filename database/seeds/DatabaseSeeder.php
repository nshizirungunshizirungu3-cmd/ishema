<?php
declare(strict_types=1);

namespace App\Seeds;

/**
 * Demo data seeder for ISHEMA.
 *
 * Creates a demonstration "Hotel & Restaurant" business with:
 *   - an owner account and staff members
 *   - room types + rooms (hotel module)
 *   - tables + categories + products (restaurant module)
 *   - a sample booking and a sample dine-in order
 *   - a couple of hotel service requests
 *
 * Idempotent: exits silently when the demo business already exists.
 */
class DatabaseSeeder
{
    public static function run(\PDO $pdo): void
    {
        $businessId = 'DEMO-HOTEL';

        $exists = $pdo->query('SELECT id FROM businesses WHERE business_id = ' . $pdo->quote($businessId))->fetch();
        if ($exists) {
            return; // already seeded
        }

        // ---- business -------------------------------------------------------
        $pdo->prepare('INSERT INTO businesses (business_id, name, type, email, phone, address, city, country, description, status, timezone) VALUES (?, ?, "HOTEL_AND_RESTAURANT", ?, ?, ?, ?, ?, ?, "active", "Africa/Kigali")')
            ->execute([
                $businessId,
                'Ishema Grand Hotel & Restaurant',
                'hello@ishema-grand.com',
                '+250 788 000 000',
                'KN 4 Ave, Kigali',
                'Kigali',
                'Rwanda',
                'A warm welcome in the heart of the city. Rooftop restaurant, spa and 40 elegant rooms.',
            ]);
        $bizId = (int)$pdo->lastInsertId();

        // ---- users ----------------------------------------------------------
        $users = [
            ['Demo Owner', 'owner@ishema-grand.com', '+250 788 000 001', 'BUSINESS_OWNER'],
            ['Alice Manager', 'manager@ishema-grand.com', '+250 788 000 002', 'MANAGER'],
            ['Paul Reception', 'reception@ishema-grand.com', '+250 788 000 003', 'RECEPTIONIST'],
            ['Marie Waiter', 'waiter@ishema-grand.com', '+250 788 000 004', 'WAITER'],
            ['John Kitchen', 'kitchen@ishema-grand.com', '+250 788 000 005', 'KITCHEN_STAFF'],
            ['Grace Cashier', 'cashier@ishema-grand.com', '+250 788 000 006', 'CASHIER'],
            ['Clean House', 'housekeeping@ishema-grand.com', '+250 788 000 007', 'HOUSEKEEPING'],
        ];
        $userIds = [];
        foreach ($users as [$name, $email, $phone, $role]) {
            $row = $pdo->query('SELECT id FROM users WHERE email = ' . $pdo->quote($email) . ' AND deleted_at IS NULL')->fetch();
            if (!$row) {
                $pdo->prepare('INSERT INTO users (name, email, phone, password, role, language, is_active, email_verified_at) VALUES (?, ?, ?, ?, ?, "en", 1, NOW())')
                    ->execute([$name, $email, $phone, password_hash('Staff12345!', PASSWORD_DEFAULT), $role]);
                $userIds[$role] = (int)$pdo->lastInsertId();
            } else {
                $userIds[$role] = (int)$row['id'];
            }
            $pdo->prepare('INSERT IGNORE INTO business_users (user_id, business_id, role, is_active) VALUES (?, ?, ?, 1)')
                ->execute([$userIds[$role], $bizId, $role]);
        }

        // ---- hotel: room types (default_price, is_active) --------------------
        $roomTypes = [
            ['Standard Room', 'standard', 80.00, 2, ['wifi', 'tv', 'minibar'], 'A comfortable room with a queen bed.', 12],
            ['Deluxe Room', 'deluxe', 140.00, 2, ['wifi', 'tv', 'minibar', 'safe'], 'Spacious room with a city-view balcony.', 10],
            ['Executive Suite', 'executive_suite', 260.00, 3, ['wifi', 'tv', 'minibar', 'safe', 'breakfast'], 'Separate living area and premium amenities.', 6],
            ['Family Room', 'family', 180.00, 4, ['wifi', 'tv', 'ac', 'breakfast'], 'Two queen beds, ideal for families.', 8],
        ];
        $typeIds = [];
        $typePrices = [];
        foreach ($roomTypes as [$name, $slug, $price, $cap, $amen, $desc, $count]) {
            $pdo->prepare('INSERT INTO room_types (business_id, name, slug, description, capacity, default_price, amenities, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)')
                ->execute([$bizId, $name, $slug, $desc, (string)$cap, $price, json_encode($amen)]);
            $typeIds[$slug] = (int)$pdo->lastInsertId();
            $typePrices[$slug] = $price;
        }

        // ---- hotel: rooms (standard 101+, deluxe 201+, suite 301+, family 401+)
        $roomBases = ['standard' => 100, 'deluxe' => 200, 'executive_suite' => 300, 'family' => 400];
        $roomCounts = ['standard' => 12, 'deluxe' => 10, 'executive_suite' => 6, 'family' => 8];
        foreach ($typeIds as $slug => $typeId) {
            $base = $roomBases[$slug];
            $floor = (int)($base / 100);
            for ($i = 1; $i <= $roomCounts[$slug]; $i++) {
                $status = $i === 1 ? 'occupied' : 'available';
                $pdo->prepare('INSERT INTO rooms (business_id, room_type_id, room_number, floor, price, status, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)')
                    ->execute([$bizId, $typeId, (string)($base + $i), (string)$floor, $typePrices[$slug], $status]);
            }
        }

        // ---- restaurant: tables ---------------------------------------------
        $tables = [
            ['T1', 1, 4, 'available'], ['T2', 1, 4, 'available'], ['T3', 1, 2, 'occupied'],
            ['T4', 1, 6, 'available'], ['T5', 2, 4, 'reserved'], ['T6', 2, 2, 'available'],
            ['T7', 2, 8, 'available'], ['T8', 3, 4, 'cleaning'],
        ];
        foreach ($tables as [$num, $floor, $seats, $status]) {
            $pdo->prepare('INSERT INTO restaurant_tables (business_id, table_number, name, seats, status, floor, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)')
                ->execute([$bizId, $num, 'Table ' . $num, (string)$seats, $status, (string)$floor]);
        }

        // ---- restaurant: categories (is_active) ------------------------------
        $categories = [
            ['Starters', 'starters', 1],
            ['Main Courses', 'main_courses', 2],
            ['Desserts', 'desserts', 3],
            ['Beverages', 'beverages', 4],
        ];
        $catIds = [];
        foreach ($categories as [$name, $slug, $ord]) {
            $pdo->prepare('INSERT INTO categories (business_id, name, slug, sort_order, is_active) VALUES (?, ?, ?, ?, 1)')
                ->execute([$bizId, $name, $slug, (string)$ord]);
            $catIds[] = (int)$pdo->lastInsertId();
        }

        // ---- restaurant: products (is_available) -----------------------------
        $products = [
            [$catIds[0], 'Garlic Bread', 5.00, 'Freshly baked garlic bread with herb butter.', 1],
            [$catIds[0], 'Spring Rolls', 7.50, 'Crispy vegetable spring rolls, 4 pieces.', 1],
            [$catIds[1], 'Grilled Tilapia', 18.00, 'Lake tilapia grilled with plantain and salad.', 1],
            [$catIds[1], 'Chicken Brochette', 14.00, 'Skewered chicken with fries and avocado.', 1],
            [$catIds[1], 'Vegetable Curry', 11.00, 'Seasonal vegetables in coconut curry with rice.', 1],
            [$catIds[2], 'Chocolate Cake', 6.50, 'Rich chocolate layer cake.', 1],
            [$catIds[3], 'Fresh Juice', 3.50, 'Seasonal fresh fruit juice.', 1],
            [$catIds[3], 'Local Tea', 2.00, 'Traditional Rwandan tea.', 1],
        ];
        $productIds = [];
        foreach ($products as [$catId, $name, $price, $desc, $ord]) {
            $pdo->prepare('INSERT INTO products (business_id, category_id, name, description, price, stock_quantity, min_stock, is_available, sort_order) VALUES (?, ?, ?, ?, ?, 100, 5, 1, ?)')
                ->execute([$bizId, $catId, $name, $desc, $price, (string)$ord]);
            $productIds[] = (int)$pdo->lastInsertId();
        }

        // ---- guest + booking (booking_reference, room_id) --------------------
        $pdo->prepare('INSERT INTO guests (business_id, first_name, last_name, email, phone, country) VALUES (?, "Jane", "Doe", "jane.doe@example.com", "+250 788 123 456", "Rwanda")')
            ->execute([$bizId]);
        $guestId = (int)$pdo->lastInsertId();

        // pick the first available deluxe room
        $roomRow = $pdo->query('SELECT id FROM rooms WHERE business_id = ' . $bizId . ' AND room_type_id = ' . $typeIds['deluxe'] . ' AND status = "available" LIMIT 1')->fetch();
        $roomId = $roomRow ? (int)$roomRow['id'] : 0;

        $checkIn = date('Y-m-d', strtotime('+1 day'));
        $checkOut = date('Y-m-d', strtotime('+3 day'));
        $pdo->prepare('INSERT INTO bookings (business_id, booking_reference, guest_id, room_id, check_in_date, check_out_date, adults, children, nights, status, total_amount, payment_status) VALUES (?, ?, ?, ?, ?, ?, 2, 0, 2, "confirmed", 280.00, "pending")')
            ->execute([$bizId, 'BKG-' . date('Ymd') . '-0001', $guestId, $roomId, $checkIn, $checkOut]);
        $bookingId = (int)$pdo->lastInsertId();

        // ---- hotel service requests (service log, not a catalog) -------------
        $services = [
            ['room_service', 'Room Service', 'Breakfast delivered to the room.', 1, 15.00],
            ['laundry', 'Laundry', 'Same-day wash, dry and fold.', 2, 10.00],
        ];
        foreach ($services as [$type, $title, $desc, $qty, $price]) {
            $pdo->prepare('INSERT INTO hotel_services (business_id, guest_id, booking_id, guest_user_id, service_type, title, description, quantity, unit_price, total_amount, status, requested_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", NOW())')
                ->execute([$bizId, $guestId, $bookingId, $userIds['RECEPTIONIST'], $type, $title, $desc, (string)$qty, $price, $qty * $price]);
        }

        // ---- sample dine-in order (waiter_user_id, single source) ------------
        $tableRow = $pdo->query('SELECT id FROM restaurant_tables WHERE business_id = ' . $bizId . ' ORDER BY id LIMIT 1')->fetch();
        $tableId = $tableRow ? (int)$tableRow['id'] : 0;
        $waiterId = $userIds['WAITER'];

        $pdo->prepare('INSERT INTO orders (business_id, order_number, table_id, guest_name, guest_phone, waiter_user_id, source, status, total_amount, ordered_at) VALUES (?, ?, ?, "Walk-in Customer", "+250 788 999 999", ?, "staff", "pending", 25.50, NOW())')
            ->execute([$bizId, 'ORD-' . date('Ymd') . '-0001', $tableId, $waiterId]);
        $orderId = (int)$pdo->lastInsertId();

        // two order items with real product names
        $itemRows = $pdo->query('SELECT id, name, price FROM products WHERE business_id = ' . $bizId . ' ORDER BY id LIMIT 2')->fetchAll();
        foreach ($itemRows as $pr) {
            $pdo->prepare('INSERT INTO order_items (order_id, business_id, product_id, product_name, quantity, unit_price, line_total, delivery_status) VALUES (?, ?, ?, ?, 1, ?, ?, "pending")')
                ->execute([$orderId, $bizId, $pr['id'], $pr['name'], $pr['price'], $pr['price']]);
        }
    }
}
