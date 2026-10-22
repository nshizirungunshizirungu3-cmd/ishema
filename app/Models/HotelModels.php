<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class RoomType extends BaseModel
{
    protected static string $table = 'room_types';

    public static function forBusiness(int $businessId): array
    {
        return static::where('business_id', $businessId)->where('is_active', 1)->orderBy('name')->get();
    }
}

class Room extends BaseModel
{
    protected static string $table = 'rooms';

    public static function forBusiness(int $businessId, ?string $status = null): array
    {
        $q = static::where('business_id', $businessId)->where('is_active', 1)->whereNull('deleted_at');
        if ($status !== null) {
            $q->where('status', $status);
        }
        return $q->orderBy('room_number')->get();
    }

    public static function availableBetween(int $businessId, string $checkIn, string $checkOut, ?int $excludeBookingId = null): array
    {
        // Rooms that do NOT overlap with any non-cancelled booking in the given range
        $sql = 'SELECT r.* FROM rooms r
                WHERE r.business_id = ?
                  AND r.is_active = 1
                  AND r.deleted_at IS NULL
                  AND r.status NOT IN ("out_of_service", "maintenance")
                  AND NOT EXISTS (
                    SELECT 1 FROM bookings b
                    WHERE b.room_id = r.id
                      AND b.status NOT IN ("cancelled", "checked_out")
                      AND b.check_in_date < ?
                      AND b.check_out_date > ?'
            . ($excludeBookingId !== null ? ' AND b.id <> ' . (int)$excludeBookingId : '')
            . '  )
                ORDER BY r.room_number';
        return Database::run($sql, [$businessId, $checkOut, $checkIn])->fetchAll();
    }

    public static function isAvailable(int $roomId, string $checkIn, string $checkOut, ?int $excludeBookingId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM bookings
                WHERE room_id = ?
                  AND status NOT IN ("cancelled", "checked_out")
                  AND check_in_date < ?
                  AND check_out_date > ?'
            . ($excludeBookingId !== null ? ' AND id <> ' . (int)$excludeBookingId : '');
        $row = Database::run($sql, [$roomId, $checkOut, $checkIn])->fetch();
        return (int)$row['c'] === 0;
    }

    public static function stats(int $businessId): array
    {
        $total = static::where('business_id', $businessId)->where('is_active', 1)->whereNull('deleted_at')->count();
        $available = static::where('business_id', $businessId)->where('status', 'available')->whereNull('deleted_at')->count();
        $occupied = static::where('business_id', $businessId)->where('status', 'occupied')->whereNull('deleted_at')->count();
        $reserved = static::where('business_id', $businessId)->where('status', 'reserved')->whereNull('deleted_at')->count();
        $cleaning = static::where('business_id', $businessId)->where('status', 'cleaning')->whereNull('deleted_at')->count();
        $maintenance = static::where('business_id', $businessId)->where('status', 'maintenance')->whereNull('deleted_at')->count();
        return compact('total', 'available', 'occupied', 'reserved', 'cleaning', 'maintenance');
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

class Guest extends BaseModel
{
    protected static string $table = 'guests';

    public static function forBusiness(int $businessId, string $search = ''): array
    {
        $q = static::where('business_id', $businessId)->orderBy('id', 'DESC');
        if ($search !== '') {
            $q = (new Query('guests'))->where('business_id', $businessId)
                ->whereLike('first_name', $search)
                ->orWhereLike('last_name', $search)
                ->orWhereLike('email', $search)
                ->orWhereLike('phone', $search)
                ->orderBy('id', 'DESC');
        }
        return $q->limit(200)->get();
    }

    public static function search(int $businessId, string $term, int $limit = 10): array
    {
        return Database::run(
            'SELECT * FROM guests
             WHERE business_id = ?
               AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)
             ORDER BY id DESC LIMIT ' . (int)$limit,
            [$businessId, "%$term%", "%$term%", "%$term%", "%$term%"]
        )->fetchAll();
    }
}

class Booking extends BaseModel
{
    protected static string $table = 'bookings';

    public static function forBusiness(int $businessId, ?string $status = null, int $limit = 100): array
    {
        $q = static::queryAs('b')
            ->select('b.*, g.first_name, g.last_name, g.phone, g.email, r.room_number, rt.name AS room_type_name')
            ->leftJoin('guests g', 'g.id = b.guest_id')
            ->leftJoin('rooms r', 'r.id = b.room_id')
            ->leftJoin('room_types rt', 'rt.id = r.room_type_id')
            ->where('b.business_id', $businessId)
            ->orderBy('b.id', 'DESC');
        if ($status !== null) {
            $q->where('b.status', $status);
        }
        return $q->limit($limit)->get();
    }

    public static function byReference(string $reference): ?array
    {
        return static::where('booking_reference', $reference)->first();
    }

    public static function byReferenceForBusiness(int $businessId, string $reference): ?array
    {
        return static::where('business_id', $businessId)->where('booking_reference', $reference)->first();
    }

    public static function forGuestUser(int $userId, int $limit = 30): array
    {
        return static::queryAs('b')
            ->select('b.*, b.business_id, bs.name AS business_name, bs.type AS business_type, r.room_number, rt.name AS room_type_name')
            ->leftJoin('businesses bs', 'bs.id = b.business_id')
            ->leftJoin('rooms r', 'r.id = b.room_id')
            ->leftJoin('room_types rt', 'rt.id = r.room_type_id')
            ->where('b.guest_user_id', $userId)
            ->orderBy('b.id', 'DESC')
            ->limit($limit)
            ->get();
    }

    public static function stats(int $businessId): array
    {
        $total = static::where('business_id', $businessId)->count();
        $pending = static::where('business_id', $businessId)->where('status', 'pending')->count();
        $confirmed = static::where('business_id', $businessId)->where('status', 'confirmed')->count();
        $inHouse = static::where('business_id', $businessId)->where('status', 'checked_in')->count();
        $todayIn = static::query()->where('business_id', $businessId)
            ->where('check_in_date', date('Y-m-d'))
            ->whereIn('status', ['confirmed', 'checked_in'])->count();
        $todayOut = static::query()->where('business_id', $businessId)
            ->where('check_out_date', date('Y-m-d'))
            ->where('status', 'checked_in')->count();
        $revenue = static::query()->where('business_id', $businessId)
            ->where('status', '<>', 'cancelled')->sum('total_amount');
        $pendingPayments = static::query()->where('business_id', $businessId)
            ->where('payment_status', 'pending')
            ->where('status', '<>', 'cancelled')->sum('total_amount');
        return compact('total', 'pending', 'confirmed', 'inHouse', 'todayIn', 'todayOut', 'revenue', 'pendingPayments');
    }
}

class Housekeeping extends BaseModel
{
    protected static string $table = 'housekeeping';

    public static function forBusiness(int $businessId, ?string $status = null): array
    {
        $q = static::queryAs('h')
            ->select('h.*, r.room_number, u.name AS assigned_name')
            ->leftJoin('rooms r', 'r.id = h.room_id')
            ->leftJoin('users u', 'u.id = h.assigned_to_user_id')
            ->where('h.business_id', $businessId)
            ->orderBy('h.id', 'DESC');
        if ($status !== null) {
            $q->where('h.status', $status);
        }
        return $q->limit(100)->get();
    }

    public static function forRoom(int $roomId, ?string $status = null): array
    {
        $q = static::where('room_id', $roomId)->orderBy('id', 'DESC');
        if ($status !== null) {
            $q->where('status', $status);
        }
        return $q->get();
    }

    public static function stats(int $businessId): array
    {
        return [
            'pending' => static::where('business_id', $businessId)->where('status', 'pending')->count(),
            'in_progress' => static::where('business_id', $businessId)->where('status', 'in_progress')->count(),
            'completed' => static::where('business_id', $businessId)->where('status', 'completed')->count(),
        ];
    }
}

class HotelService extends BaseModel
{
    protected static string $table = 'hotel_services';

    public static function forBusiness(int $businessId, ?string $status = null): array
    {
        $q = static::queryAs('hs')
            ->select('hs.*, g.first_name, g.last_name, r.room_number')
            ->leftJoin('guests g', 'g.id = hs.guest_id')
            ->leftJoin('rooms r', 'r.id = hs.booking_id')
            ->where('hs.business_id', $businessId)
            ->orderBy('hs.id', 'DESC');
        if ($status !== null) {
            $q->where('hs.status', $status);
        }
        return $q->limit(100)->get();
    }

    public static function forGuestUser(int $userId): array
    {
        return static::queryAs('hs')
            ->select('hs.*, bs.name AS business_name')
            ->leftJoin('businesses bs', 'bs.id = hs.business_id')
            ->where('hs.guest_user_id', $userId)
            ->orderBy('hs.id', 'DESC')
            ->limit(50)
            ->get();
    }
}

class Payment extends BaseModel
{
    protected static string $table = 'payments';

    public static function forBusiness(int $businessId, int $limit = 100): array
    {
        return static::where('business_id', $businessId)->orderBy('id', 'DESC')->limit($limit)->get();
    }

    public static function stats(int $businessId): array
    {
        $total = static::query()->where('business_id', $businessId)->where('status', 'success')->sum('amount');
        $pending = static::query()->where('business_id', $businessId)->where('status', 'pending')->sum('amount');
        $refunded = static::query()->where('business_id', $businessId)->where('status', 'refunded')->sum('amount');
        return compact('total', 'pending', 'refunded');
    }

    public static function byReference(string $reference): ?array
    {
        return static::where('reference', $reference)->first();
    }

    public static function generateReference(string $prefix = 'PAY'): string
    {
        do {
            $ref = $prefix . '-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $exists = Database::run('SELECT id FROM payments WHERE reference = ? LIMIT 1', [$ref])->fetch();
        } while ($exists);
        return $ref;
    }
}

class PaymentTransaction extends BaseModel
{
    protected static string $table = 'payment_transactions';

    public static function forPayment(int $paymentId): array
    {
        return static::where('payment_id', $paymentId)->orderBy('id', 'DESC')->get();
    }
}

class Review extends BaseModel
{
    protected static string $table = 'reviews';

    public static function forBusiness(int $businessId, int $limit = 50): array
    {
        return static::where('business_id', $businessId)->orderBy('id', 'DESC')->limit($limit)->get();
    }

    public static function average(int $businessId): array
    {
        $row = Database::run(
            'SELECT AVG(food_quality) AS food, AVG(service_rating) AS service, AVG(speed_rating) AS speed, AVG(overall_rating) AS overall, COUNT(*) AS total
             FROM reviews WHERE business_id = ?',
            [$businessId]
        )->fetch();
        return $row ?: ['food' => 0, 'service' => 0, 'speed' => 0, 'overall' => 0, 'total' => 0];
    }

    public static function forGuestUser(int $userId): array
    {
        return static::queryAs('rv')
            ->select('rv.*, bs.name AS business_name')
            ->leftJoin('businesses bs', 'bs.id = rv.business_id')
            ->where('rv.customer_user_id', $userId)
            ->orderBy('rv.id', 'DESC')
            ->limit(50)
            ->get();
    }
}

class Notification extends BaseModel
{
    protected static string $table = 'notifications';
}

class Shift extends BaseModel
{
    protected static string $table = 'shifts';

    public static function forBusiness(int $businessId): array
    {
        return static::where('business_id', $businessId)->where('is_active', 1)->orderBy('start_time')->get();
    }
}

class StaffAssignment extends BaseModel
{
    protected static string $table = 'staff_assignments';

    public static function forDate(int $businessId, string $date): array
    {
        return static::queryAs('sa')
            ->select('sa.*, u.name AS staff_name, s.name AS shift_name, rt.table_number')
            ->leftJoin('users u', 'u.id = sa.business_user_id')
            ->leftJoin('shifts s', 's.id = sa.shift_id')
            ->leftJoin('restaurant_tables rt', 'rt.id = sa.table_id')
            ->where('sa.business_id', $businessId)
            ->where('sa.assignment_date', $date)
            ->orderBy('sa.id')
            ->get();
    }
}

class Branch extends BaseModel
{
    protected static string $table = 'branches';

    public static function forBusiness(int $businessId): array
    {
        return static::where('business_id', $businessId)->where('is_active', 1)->get();
    }
}

class Tutorial extends BaseModel
{
    protected static string $table = 'tutorials';

    public static function active(?string $category = null, string $language = 'en', string $search = ''): array
    {
        $q = static::where('is_active', 1);
        if ($category !== null && $category !== 'all') {
            $q->where('category', $category);
        }
        if ($search !== '') {
            $q->whereLike('title', $search);
        }
        $items = $q->orderBy('sort_order')->orderBy('id')->get();
        // Prefer user language, fall back to English
        $preferred = array_values(array_filter($items, fn($i) => strtolower($i['language']) === $language));
        return !empty($preferred) ? $preferred : $items;
    }
}

class ContactMessage extends BaseModel
{
    protected static string $table = 'contact_messages';

    public static function unreadCount(): int
    {
        return static::where('is_read', 0)->count();
    }
}

class SettingsModel extends BaseModel
{
    protected static string $table = 'settings';

    public static function get(string $key, ?int $businessId = null, mixed $default = null): mixed
    {
        $row = Database::run(
            'SELECT setting_value FROM settings WHERE setting_key = ? AND (business_id = ? OR business_id IS NULL) ORDER BY business_id DESC LIMIT 1',
            [$key, $businessId]
        )->fetch();
        if (!$row) {
            return $default;
        }
        $decoded = json_decode($row['setting_value'], true);
        return $decoded !== null ? $decoded : $row['setting_value'];
    }

    public static function set(string $key, mixed $value, ?int $businessId = null): void
    {
        $encoded = is_array($value) || is_bool($value) ? json_encode($value) : (string)$value;
        Database::run(
            'INSERT INTO settings (business_id, setting_key, setting_value) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()',
            [$businessId, $key, $encoded]
        );
    }
}

class PasswordReset extends BaseModel
{
    protected static string $table = 'password_resets';

    public static function create(string $email, string $token, int $ttlMinutes = 60): void
    {
        static::insert([
            'email' => $email,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlMinutes * 60),
        ]);
    }

    public static function valid(string $token): ?array
    {
        return Database::run(
            'SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1',
            [$token]
        )->fetch();
    }

    public static function consume(int $id): void
    {
        static::update($id, ['used_at' => date('Y-m-d H:i:s')]);
    }
}
