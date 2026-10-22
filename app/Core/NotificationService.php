<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Internal notification center with polling fallback.
 */
class NotificationService
{
    public const TYPE_ORDER_NEW          = 'order_new';
    public const TYPE_ORDER_STATUS       = 'order_status';
    public const TYPE_MISSING_ITEMS      = 'missing_items';
    public const TYPE_PAYMENT_SUCCESS    = 'payment_success';
    public const TYPE_PAYMENT_FAILED     = 'payment_failed';
    public const TYPE_TABLE_CLEARING     = 'table_clearing';
    public const TYPE_TABLE_CLEARED      = 'table_cleared';
    public const TYPE_BOOKING_NEW        = 'booking_new';
    public const TYPE_ROOM_CLEANING      = 'room_cleaning';
    public const TYPE_LOW_STOCK          = 'low_stock';
    public const TYPE_SUBSCRIPTION       = 'subscription';
    public const TYPE_STAFF_ACCOUNT      = 'staff_account';
    public const TYPE_REVIEW             = 'review';
    public const TYPE_SERVICE_REQUEST    = 'service_request';

    /**
     * Send a notification to a user.
     */
    public static function send(
        int $userId,
        string $type,
        string $title,
        string $message = '',
        ?int $businessId = null,
        array $data = []
    ): int {
        Database::run(
            'INSERT INTO notifications (business_id, user_id, type, title, message, data, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$businessId, $userId, $type, $title, $message, json_encode($data)]
        );
        return (int)Database::lastId();
    }

    /** Send the same notification to several users. */
    public static function sendToMany(array $userIds, string $type, string $title, string $message = '', ?int $businessId = null, array $data = []): void
    {
        if (empty($userIds)) {
            return;
        }
        Database::beginTransaction();
        try {
            $sql = 'INSERT INTO notifications (business_id, user_id, type, title, message, data, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())';
            foreach (array_unique($userIds) as $uid) {
                Database::run($sql, [$businessId, (int)$uid, $type, $title, $message, json_encode($data)]);
            }
            Database::commit();
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollBack();
            }
        }
    }

    /** Notify users by role inside a business (e.g. all waiters). */
    public static function sendToRole(int $businessId, string $role, string $type, string $title, string $message = '', array $data = []): void
    {
        $ids = Database::run(
            'SELECT user_id FROM business_users WHERE business_id = ? AND role = ? AND is_active = 1',
            [$businessId, $role]
        )->fetchAll(\PDO::FETCH_COLUMN);
        self::sendToMany($ids, $type, $title, $message, $businessId, $data);
    }

    public static function forUser(int $userId, int $limit = 30, bool $unreadOnly = false): array
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = ?' . ($unreadOnly ? ' AND is_read = 0' : '') . ' ORDER BY created_at DESC LIMIT ' . (int)$limit;
        return Database::run($sql, [$userId])->fetchAll();
    }

    public static function unreadCount(int $userId): int
    {
        $row = Database::run(
            'SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        )->fetch();
        return (int)($row['c'] ?? 0);
    }

    public static function markRead(int $id, int $userId): void
    {
        Database::run(
            'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public static function markAllRead(int $userId): void
    {
        Database::run(
            'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ?',
            [$userId]
        );
    }
}
