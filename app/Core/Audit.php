<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Audit logging helper.
 */
class Audit
{
    public static function record(
        string $action,
        ?string $entity = null,
        ?string $entityId = null,
        mixed $oldValues = null,
        mixed $newValues = null,
        ?int $businessId = null
    ): void {
        try {
            $user = Auth::user();
            Database::run(
                'INSERT INTO audit_logs (user_id, business_id, action, entity, entity_id, old_values, new_values, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $user ? (int)$user['id'] : null,
                    $businessId ?? Tenant::id(),
                    $action,
                    $entity,
                    $entityId,
                    $oldValues !== null ? json_encode($oldValues) : null,
                    $newValues !== null ? json_encode($newValues) : null,
                    Security::ip(),
                    Security::userAgent(),
                ]
            );
        } catch (\Throwable $e) {
            // Audit failure must never break the request
        }
    }
}
