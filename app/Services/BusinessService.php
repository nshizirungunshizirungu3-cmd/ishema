<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\Database;
use App\Core\NotificationService;
use App\Core\Security;
use App\Core\Tenant;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Subscription;
use App\Models\User;

/**
 * Business and owner lifecycle management.
 */
class BusinessService
{
    /**
     * Create a business, optionally with an owner account and subscription.
     * @return array{business:array, owner?:array, password?:string}
     */
    public static function createBusiness(array $data, bool $createOwner = true): array
    {
        Database::beginTransaction();
        try {
            $businessId = Business::insert([
                'business_id' => Business::generateBusinessId(),
                'name' => $data['name'],
                'type' => $data['type'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
                'description' => $data['description'] ?? null,
                'logo' => $data['logo'] ?? null,
                'cover_image' => $data['cover_image'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'timezone' => $data['timezone'] ?? 'Africa/Kigali',
            ]);

            $result = ['business' => Business::find($businessId)];

            if ($createOwner && !empty($data['owner_email'])) {
                $password = $data['owner_password'] ?? Security::randomString(12);
                $owner = User::insert([
                    'name' => $data['owner_name'] ?? $data['owner_email'],
                    'email' => mb_strtolower($data['owner_email']),
                    'phone' => $data['owner_phone'] ?? null,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'BUSINESS_OWNER',
                    'language' => 'en',
                    'is_active' => 1,
                ]);

                BusinessUser::insert([
                    'user_id' => $owner,
                    'business_id' => $businessId,
                    'role' => 'BUSINESS_OWNER',
                    'permissions' => json_encode(['*']),
                    'is_active' => 1,
                ]);

                $result['owner'] = User::find($owner);
                $result['password'] = $password;

                NotificationService::send(
                    (int)$owner,
                    NotificationService::TYPE_STAFF_ACCOUNT,
                    'Welcome to ISHEMA',
                    'Your business owner account has been created. Login with your email and the generated password.',
                    $businessId,
                    ['business_id' => $businessId]
                );
            }

            if (!empty($data['package_id'])) {
                self::assignSubscription(
                    $businessId,
                    (int)$data['package_id'],
                    $data['starts_at'] ?? date('Y-m-d H:i:s'),
                    $data['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+1 month')),
                    $data['billing_cycle'] ?? 'monthly',
                    $data['amount'] ?? 0
                );
            }

            Database::commit();
            Audit::record('create', 'business', (string)$businessId, null, $data);
            return $result;
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollBack();
            }
            throw $e;
        }
    }

    public static function assignSubscription(int $businessId, int $packageId, string $startsAt, string $expiresAt, string $cycle = 'monthly', float $amount = 0): int
    {
        // Expire previous active subscription
        Database::run(
            'UPDATE subscriptions SET status = "expired" WHERE business_id = ? AND status = "active"',
            [$businessId]
        );

        $package = \App\Models\Package::find($packageId);
        if ($package) {
            $amount = $cycle === 'yearly' ? (float)$package['price_yearly'] : (float)$package['price_monthly'];
        }

        return Subscription::insert([
            'business_id' => $businessId,
            'package_id' => $packageId,
            'billing_cycle' => $cycle,
            'amount' => $amount,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'status' => 'active',
            'auto_renew' => 0,
        ]);
    }

    public static function setStatus(int $businessId, string $status): bool
    {
        if (!in_array($status, ['active', 'suspended', 'pending'], true)) {
            return false;
        }
        $old = Business::find($businessId);
        Business::update($businessId, ['status' => $status]);
        Audit::record($status === 'suspended' ? 'suspend' : 'activate', 'business', (string)$businessId, $old, ['status' => $status]);
        return true;
    }

    public static function archive(int $businessId): bool
    {
        $old = Business::find($businessId);
        if (!$old) {
            return false;
        }
        Business::softDelete($businessId);
        Audit::record('archive', 'business', (string)$businessId, $old, null);
        return true;
    }
}
