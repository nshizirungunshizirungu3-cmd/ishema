<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Role/permission evaluation service.
 */
class PermissionService
{
    /** Role => implied permissions (wildcard style). */
    private const ROLE_BASE = [
        'SUPER_ADMIN'    => ['*'],
        'BUSINESS_OWNER' => ['*'], // scoped to own business by tenant middleware
        'MANAGER'        => [
            'products.*', 'categories.*', 'tables.*', 'orders.*', 'inventory.*',
            'staff.*', 'payments.*', 'reports.*', 'settings.*', 'rooms.*',
            'room_types.*', 'bookings.*', 'guests.*', 'housekeeping.*',
            'services.*', 'reviews.*', 'notifications.*', 'qr.*',
        ],
        'RECEPTIONIST'   => ['bookings.*', 'guests.*', 'rooms.read', 'payments.*', 'checkin.*', 'checkout.*'],
        'WAITER'         => ['orders.*', 'tables.read', 'products.read', 'notifications.*', 'payments.read', 'tables.update_status'],
        'KITCHEN_STAFF'  => ['orders.read', 'orders.update_status', 'notifications.*', 'products.read'],
        'CASHIER'        => ['payments.*', 'orders.read', 'receipts.*'],
        'HOUSEKEEPING'   => ['housekeeping.*', 'rooms.read', 'notifications.*'],
        'CUSTOMER'       => ['own_orders.*', 'own_bookings.*', 'reviews.*', 'qr.*'],
    ];

    /** Can the user perform the permission in the given business? */
    public static function can(?int $userId, ?int $businessId, string $permission): bool
    {
        if ($userId === null) {
            return false;
        }
        $user = User::find($userId);
        if (!$user || !$user['is_active']) {
            return false;
        }

        // SUPER_ADMIN has platform-wide access
        if ($user['role'] === 'SUPER_ADMIN') {
            return true;
        }

        // BUSINESS_OWNER full access within tenant (already isolated)
        if ($user['role'] === 'BUSINESS_OWNER') {
            return true;
        }

        // Determine the role in context of this business
        $role = $user['role'];
        if ($businessId !== null) {
            $link = Database::run(
                'SELECT role FROM business_users WHERE user_id = ? AND business_id = ? AND is_active = 1 LIMIT 1',
                [$userId, $businessId]
            )->fetch();
            if ($link) {
                $role = $link['role'];
            }
        }

        // Explicit per-user grants/denies
        if ($businessId !== null) {
            $denied = Database::run(
                'SELECT granted FROM user_permissions WHERE user_id = ? AND business_id = ? AND permission_id IN
                    (SELECT id FROM permissions WHERE slug = ?) LIMIT 1',
                [$userId, $businessId, $permission]
            )->fetch();
            if ($denied) {
                return (int)$denied['granted'] === 1;
            }
        }

        // Role-implied permissions
        $grants = self::ROLE_BASE[$role] ?? [];
        if (in_array('*', $grants, true)) {
            return true;
        }
        foreach ($grants as $grant) {
            if (self::matches($grant, $permission)) {
                return true;
            }
        }

        // Custom role_permissions table
        $row = Database::run(
            'SELECT rp.permission_id FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE p.slug = ? LIMIT 1',
            [$permission]
        )->fetch();
        return false;
    }

    private static function matches(string $pattern, string $permission): bool
    {
        if ($pattern === $permission) {
            return true;
        }
        if (str_ends_with($pattern, '.*')) {
            $base = substr($pattern, 0, -2);
            return str_starts_with($permission, $base . '.');
        }
        return false;
    }
}
