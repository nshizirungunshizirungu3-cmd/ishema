<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Multi-tenant context and isolation helpers.
 * The current business is ALWAYS derived from the authenticated session,
 * never from client-supplied input.
 */
class Tenant
{
    private static ?array $business = null;

    /**
     * Resolve the business the current user is operating in.
     * Returns the business row or null when not applicable.
     */
    public static function current(): ?array
    {
        if (self::$business !== null) {
            return self::$business['id'] === null ? null : self::$business;
        }

        $userId = Session::get('user_id');
        $businessId = Session::get('active_business_id');

        if (!$userId) {
            self::$business = ['id' => null];
            return null;
        }

        if (!$businessId) {
            // Resolve default business: first business_users row for this user
            $link = Database::run(
                'SELECT business_id FROM business_users WHERE user_id = ? AND is_active = 1 ORDER BY id ASC LIMIT 1',
                [$userId]
            )->fetch();
            if (!$link) {
                self::$business = ['id' => null];
                return null;
            }
            $businessId = (int)$link['business_id'];
        }

        // Verify the link exists and is active for this user (defense in depth)
        $valid = Database::run(
            'SELECT id FROM business_users WHERE user_id = ? AND business_id = ? AND is_active = 1 LIMIT 1',
            [$userId, $businessId]
        )->fetch();

        if (!$valid) {
            self::$business = ['id' => null];
            return null;
        }

        $business = Database::run(
            'SELECT * FROM businesses WHERE id = ? AND deleted_at IS NULL LIMIT 1',
            [$businessId]
        )->fetch();

        if (!$business || $business['status'] === 'suspended') {
            self::$business = ['id' => null];
            return null;
        }

        self::$business = $business;
        return $business;
    }

    public static function id(): ?int
    {
        $b = self::current();
        return $b ? (int)$b['id'] : null;
    }

    public static function businessId(): ?string
    {
        $b = self::current();
        return $b ? $b['business_id'] : null;
    }

    public static function type(): ?string
    {
        $b = self::current();
        return $b ? $b['type'] : null;
    }

    public static function name(): ?string
    {
        $b = self::current();
        return $b ? $b['name'] : null;
    }

    /** Require an active tenant context; throws otherwise. */
    public static function requireBusiness(): array
    {
        $business = self::current();
        if (!$business) {
            throw new \RuntimeException('No active business context.');
        }
        return $business;
    }

    /**
     * Build a permission-aware query fragment. All tenant queries must
     * pass business_id through this method.
     */
    public static function scope(string $alias = ''): string
    {
        $id = self::id();
        $prefix = $alias !== '' ? $alias . '.' : '';
        if ($id === null) {
            return $prefix . 'business_id IS NULL';
        }
        return $prefix . 'business_id = ' . (int)$id;
    }

    /** Assert a record belongs to the current tenant. Throws when violated. */
    public static function assertOwnership(string $table, string $idColumn, int|string $recordId, ?int $businessId = null): void
    {
        $bid = $businessId ?? self::id();
        if ($bid === null) {
            throw new \RuntimeException('Tenant context required.');
        }
        $row = Database::run(
            "SELECT id FROM `$table` WHERE `$idColumn` = ? AND business_id = ? LIMIT 1",
            [$recordId, $bid]
        )->fetch();
        if (!$row) {
            throw new \RuntimeException('Access denied: record not found in your business.');
        }
    }

    /** Switch the active business for the session (used by owners with multiple businesses). */
    public static function switchTo(int $businessId): bool
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            return false;
        }
        $valid = Database::run(
            'SELECT id FROM business_users WHERE user_id = ? AND business_id = ? AND is_active = 1 LIMIT 1',
            [$userId, $businessId]
        )->fetch();
        if (!$valid) {
            return false;
        }
        self::$business = null;
        Session::set('active_business_id', $businessId);
        return true;
    }
}
