<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User extends BaseModel
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return static::where('email', mb_strtolower(trim($email)))
            ->whereNull('deleted_at')
            ->first();
    }

    public static function countByRole(string $role): int
    {
        return static::where('role', $role)->whereNull('deleted_at')->count();
    }

    public static function businessUsers(int $businessId, ?string $role = null): array
    {
        $q = (new Query('business_users bu'))
            ->select('bu.*, u.name, u.email, u.phone, u.avatar, u.language')
            ->leftJoin('users u', 'u.id = bu.user_id')
            ->where('bu.business_id', $businessId)
            ->where('bu.is_active', 1)
            ->whereNull('u.deleted_at');
        if ($role !== null) {
            $q->where('bu.role', $role);
        }
        return $q->orderBy('u.name')->get();
    }
}

class Business extends BaseModel
{
    protected static string $table = 'businesses';

    public static function byBusinessId(string $businessId): ?array
    {
        return static::where('business_id', $businessId)->whereNull('deleted_at')->first();
    }

    public static function generateBusinessId(): string
    {
        do {
            $prefix = 'BIZ';
            $code = $prefix . '-' . strtoupper(substr(str_shuffle('0123456789ABCDEFGHJKMNPQRSTVWXYZ'), 0, 6));
        } while (static::byBusinessId($code) !== null);
        return $code;
    }

    public static function stats(): array
    {
        $total = static::query()->whereNull('deleted_at')->count();
        $hotels = static::query()->whereIn('type', ['HOTEL', 'HOTEL_AND_RESTAURANT'])->whereNull('deleted_at')->count();
        $restaurants = static::query()->whereIn('type', ['RESTAURANT', 'HOTEL_AND_RESTAURANT'])->whereNull('deleted_at')->count();
        $active = static::where('status', 'active')->whereNull('deleted_at')->count();
        $suspended = static::where('status', 'suspended')->whereNull('deleted_at')->count();
        return compact('total', 'hotels', 'restaurants', 'active', 'suspended');
    }

    public static function revenue(): array
    {
        $sql = 'SELECT COALESCE(SUM(p.amount),0) AS total, p.currency,
                SUM(CASE WHEN p.status = "success" THEN p.amount ELSE 0 END) AS successful
                FROM payments p
                JOIN businesses b ON b.id = p.business_id
                WHERE b.deleted_at IS NULL AND p.status IN ("success","refunded")';
        $row = Database::run($sql)->fetch();
        return $row ?: ['total' => '0', 'currency' => 'RWF', 'successful' => '0'];
    }
}

class BusinessUser extends BaseModel
{
    protected static string $table = 'business_users';

    public static function link(int $userId, int $businessId): ?array
    {
        return static::where('user_id', $userId)->where('business_id', $businessId)->first();
    }
}

class Package extends BaseModel
{
    protected static string $table = 'packages';

    public static function active(): array
    {
        return static::where('status', 'active')->orderBy('sort_order')->orderBy('id')->get();
    }

    public static function bySlug(string $slug): ?array
    {
        return static::where('slug', $slug)->first();
    }
}

class Subscription extends BaseModel
{
    protected static string $table = 'subscriptions';

    public static function forBusiness(int $businessId): array
    {
        return static::where('business_id', $businessId)->orderBy('id', 'DESC')->get();
    }

    public static function currentForBusiness(int $businessId): ?array
    {
        return static::where('business_id', $businessId)
            ->where('status', 'active')
            ->orderBy('expires_at', 'DESC')
            ->first();
    }

    public static function expiringWithin(int $days = 7): array
    {
        return static::query()
            ->where('status', 'active')
            ->whereBetween('expires_at', date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime("+$days days")))
            ->orderBy('expires_at')
            ->get();
    }

    public static function stats(): array
    {
        $active = static::where('status', 'active')->count();
        $expired = static::where('status', 'expired')->count();
        $pending = static::where('status', 'pending')->count();
        $cancelled = static::where('status', 'cancelled')->count();
        return compact('active', 'expired', 'pending', 'cancelled');
    }

    public static function refreshStatus(int $id): void
    {
        $sub = static::find($id);
        if (!$sub) {
            return;
        }
        if ($sub['status'] === 'active' && strtotime($sub['expires_at']) < time()) {
            static::update($id, ['status' => 'expired']);
        } elseif ($sub['status'] === 'expired' && strtotime($sub['expires_at']) >= time()) {
            static::update($id, ['status' => 'active']);
        }
    }
}

class Category extends BaseModel
{
    protected static string $table = 'categories';

    public static function forBusiness(int $businessId): array
    {
        return static::where('business_id', $businessId)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public static function slugExists(int $businessId, string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM categories WHERE business_id = ? AND slug = ?';
        $params = [$businessId, $slug];
        if ($excludeId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $row = Database::run($sql . ' LIMIT 1', $params)->fetch();
        return (bool)$row;
    }
}

class Product extends BaseModel
{
    protected static string $table = 'products';

    public static function forBusiness(int $businessId, ?int $categoryId = null, bool $availableOnly = false): array
    {
        $q = static::where('business_id', $businessId)->whereNull('deleted_at');
        if ($categoryId !== null) {
            $q->where('category_id', $categoryId);
        }
        if ($availableOnly) {
            $q->where('is_available', 1);
        }
        return $q->orderBy('sort_order')->orderBy('id')->get();
    }

    public static function lowStock(int $businessId): array
    {
        return Database::run(
            'SELECT * FROM products
             WHERE business_id = ? AND deleted_at IS NULL
               AND min_stock > 0 AND stock_quantity <= min_stock
             ORDER BY stock_quantity ASC',
            [$businessId]
        )->fetchAll();
    }

    public static function countForBusiness(int $businessId): int
    {
        return static::where('business_id', $businessId)->whereNull('deleted_at')->count();
    }
}

class Supplier extends BaseModel
{
    protected static string $table = 'suppliers';

    public static function forBusiness(int $businessId): array
    {
        return static::where('business_id', $businessId)->orderBy('name')->get();
    }
}

class InventoryMovement extends BaseModel
{
    protected static string $table = 'inventory_movements';

    public static function forProduct(int $businessId, int $productId, int $limit = 50): array
    {
        return static::where('business_id', $businessId)
            ->where('product_id', $productId)
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get();
    }
}

class RestaurantTable extends BaseModel
{
    protected static string $table = 'restaurant_tables';

    public static function forBusiness(int $businessId): array
    {
        return static::where('business_id', $businessId)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('table_number')
            ->get();
    }

    public static function stats(int $businessId): array
    {
        $total = static::where('business_id', $businessId)->where('is_active', 1)->count();
        $occupied = static::where('business_id', $businessId)->where('status', 'occupied')->count();
        $available = static::where('business_id', $businessId)->where('status', 'available')->count();
        $cleaning = static::where('business_id', $businessId)->where('status', 'cleaning')->count();
        return compact('total', 'occupied', 'available', 'cleaning');
    }
}

class QrCode extends BaseModel
{
    protected static string $table = 'qr_codes';

    public static function forTable(int $businessId, int $tableId): ?array
    {
        return static::where('business_id', $businessId)->where('table_id', $tableId)->first();
    }

    public static function byToken(string $token): ?array
    {
        return static::where('token', $token)->where('is_active', 1)->first();
    }

    public static function ensureForTable(int $businessId, int $tableId): array
    {
        $existing = self::forTable($businessId, $tableId);
        if ($existing) {
            return $existing;
        }
        $token = hash('sha256', $businessId . '|' . $tableId . '|' . random_bytes(16));
        $id = self::insert([
            'business_id' => $businessId,
            'table_id' => $tableId,
            'token' => $token,
            'is_active' => 1,
        ]);
        return self::find($id);
    }
}

class Order extends BaseModel
{
    protected static string $table = 'orders';

    public static function generateNumber(int $businessId, string $prefix = 'ORD'): string
    {
        $date = date('ymd');
        do {
            $number = $prefix . '-' . $date . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $exists = Database::run(
                'SELECT id FROM orders WHERE business_id = ? AND order_number = ? LIMIT 1',
                [$businessId, $number]
            )->fetch();
        } while ($exists);
        return $number;
    }

    public static function forBusiness(int $businessId, ?string $status = null, int $limit = 100): array
    {
        $q = static::where('business_id', $businessId)->orderBy('id', 'DESC');
        if ($status !== null) {
            $q->where('status', $status);
        }
        return $q->limit($limit)->get();
    }

    public static function stats(int $businessId): array
    {
        $total = static::where('business_id', $businessId)->count();
        $pending = static::where('business_id', $businessId)->where('status', 'pending')->count();
        $active = static::query()->where('business_id', $businessId)
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready'])->count();
        $completed = static::where('business_id', $businessId)
            ->whereIn('status', ['delivered', 'completed'])->count();
        $revenue = static::query()->where('business_id', $businessId)
            ->whereIn('status', ['delivered', 'completed', 'partially_delivered'])
            ->sum('total_amount');
        $today = static::query()->where('business_id', $businessId)
            ->where('ordered_at', '>=', date('Y-m-d 00:00:00'))->count();
        return compact('total', 'pending', 'active', 'completed', 'revenue', 'today');
    }

    public static function withItems(int $orderId): ?array
    {
        $order = static::find($orderId);
        if (!$order) {
            return null;
        }
        $order['items'] = OrderItem::forOrder($orderId);
        return $order;
    }

    public static function openForTable(int $tableId): ?array
    {
        return static::query()
            ->where('table_id', $tableId)
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready', 'partially_delivered', 'not_received'])
            ->orderBy('id', 'DESC')
            ->first();
    }
}

class OrderItem extends BaseModel
{
    protected static string $table = 'order_items';

    public static function forOrder(int $orderId): array
    {
        return static::where('order_id', $orderId)->orderBy('id')->get();
    }
}

class OrderDelivery extends BaseModel
{
    protected static string $table = 'order_deliveries';

    public static function forItem(int $orderItemId): array
    {
        return static::where('order_item_id', $orderItemId)->orderBy('id', 'DESC')->get();
    }
}

class DeliveryConfirmation extends BaseModel
{
    protected static string $table = 'delivery_confirmations';

    public static function forItem(int $orderItemId): array
    {
        return static::where('order_item_id', $orderItemId)->orderBy('id', 'DESC')->get();
    }

    public static function openComplaints(int $businessId): array
    {
        return Database::run(
            'SELECT dc.*, o.order_number, oi.product_name, oi.quantity, rt.table_number
             FROM delivery_confirmations dc
             JOIN order_items oi ON oi.id = dc.order_item_id
             JOIN orders o ON o.id = dc.order_id
             LEFT JOIN restaurant_tables rt ON rt.id = o.table_id
             WHERE dc.business_id = ? AND dc.status <> "full"
             ORDER BY dc.id DESC LIMIT 100',
            [$businessId]
        )->fetchAll();
    }
}
