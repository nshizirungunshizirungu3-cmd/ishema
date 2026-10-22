<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Router;
use App\Core\Session;

/**
 * Requires a logged-in user of any role.
 */
class AuthMiddleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'Session expired. Please log in again.']);
                return false;
            }
            header('Location: ' . Router::url('/login'));
            exit;
        }
        return true;
    }
}

class GuestMiddleware
{
    public function handle(): bool
    {
        if (Auth::check()) {
            $role = Auth::role();
            $target = $role === 'SUPER_ADMIN' ? '/admin' : '/dashboard';
            header('Location: ' . Router::url($target));
            exit;
        }
        return true;
    }
}

class SuperAdminMiddleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            header('Location: ' . Router::url('/login'));
            exit;
        }
        if (Auth::role() !== 'SUPER_ADMIN') {
            Router::error(403);
            return false;
        }
        return true;
    }
}

/**
 * Requires an authenticated tenant (business) context.
 * Validates subscription policy before allowing write operations.
 */
class TenantMiddleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            header('Location: ' . Router::url('/login'));
            exit;
        }

        $user = Auth::user();
        if ($user && $user['role'] === 'SUPER_ADMIN') {
            return true;
        }

        try {
            $business = \App\Core\Tenant::requireBusiness();
        } catch (\Throwable $e) {
            \App\Core\Session::flash('error', 'No active business assigned to your account. Contact the platform administrator.');
            header('Location: ' . Router::url('/dashboard'));
            exit;
        }

        // Subscription policy enforcement
        $policy = $business['subscription_policy'] ?? 'block';
        $isWrite = in_array(strtoupper($_SERVER['REQUEST_METHOD']), ['POST', 'PUT', 'DELETE'], true);

        if ($policy === 'block' && $this->subscriptionExpired($business['id'])) {
            if ($isWrite) {
                \App\Core\Session::flash('error', 'Your subscription has expired. Please renew to continue operations.');
                header('Location: ' . Router::url('/dashboard/subscription'));
                exit;
            }
        }

        return true;
    }

    private function subscriptionExpired(int $businessId): bool
    {
        $sub = \App\Models\Subscription::currentForBusiness($businessId);
        if (!$sub) {
            return true;
        }
        \App\Models\Subscription::refreshStatus($sub['id']);
        return $sub['status'] === 'expired' || strtotime($sub['expires_at']) < time();
    }
}

/**
 * Generic staff access for authenticated business users.
 */
class StaffMiddleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            header('Location: ' . Router::url('/login'));
            exit;
        }
        $user = Auth::user();
        if (!$user || $user['role'] === 'CUSTOMER') {
            Router::error(403);
            return false;
        }
        try {
            \App\Core\Tenant::requireBusiness();
        } catch (\Throwable $e) {
            if (Auth::role() !== 'SUPER_ADMIN') {
                \App\Core\Session::flash('error', 'No active business assigned to your account.');
                header('Location: ' . Router::url('/dashboard'));
                exit;
            }
        }
        return true;
    }
}

/**
 * Allows authenticated customers (portal access).
 */
class CustomerMiddleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            header('Location: ' . Router::url('/login'));
            exit;
        }
        return true;
    }
}

/**
 * Soft gate for subscription-expired tenants in read-only mode.
 */
class SubscriptionMiddleware
{
    public function handle(): bool
    {
        $user = Auth::user();
        if (!$user || $user['role'] === 'SUPER_ADMIN') {
            return true;
        }
        try {
            $business = \App\Core\Tenant::current();
            if ($business) {
                $sub = \App\Models\Subscription::currentForBusiness((int)$business['id']);
                if ($sub) {
                    \App\Models\Subscription::refreshStatus($sub['id']);
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return true;
    }
}
