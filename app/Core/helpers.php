<?php
declare(strict_types=1);

/** Global view helpers. */

use App\Core\Router;
use App\Core\Translator;
use App\Core\View;

if (!function_exists('t')) {
    function t(string $key, array $params = []): string
    {
        return Translator::t($key, $params);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return View::e($value);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return Router::url($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return Router::asset($path);
    }
}

if (!function_exists('navLink')) {
    /** Sidebar navigation link with an optional notification badge. */
    function navLink(string $href, string $icon, string $label, bool $active = false, ?int $badge = null): string
    {
        $badgeHtml = $badge !== null && $badge > 0 ? '<span class="badge">' . $badge . '</span>' : '';
        return '<a href="' . url($href) . '" class="nav-link' . ($active ? ' active' : '') . '">'
            . '<span class="icon">' . $icon . '</span><span>' . $label . '</span>' . $badgeHtml . '</a>';
    }
}

if (!function_exists('isActive')) {
    function isActive(string $prefix): string
    {
        $current = $_SERVER['REQUEST_URI'] ?? '/';
        return str_starts_with($current, $prefix) ? 'active' : '';
    }
}

if (!function_exists('formatMoney')) {
    function formatMoney(mixed $amount, string $currency = 'RWF'): string
    {
        return number_format((float)$amount, 0, ',', '.') . ' ' . $currency;
    }
}

if (!function_exists('formatDate')) {
    function formatDate(mixed $date): string
    {
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            return '-';
        }
        try {
            return date('Y-m-d H:i', strtotime((string)$date));
        } catch (\Throwable $e) {
            return (string)$date;
        }
    }
}

if (!function_exists('statusBadge')) {
    function statusBadge(string $status): string
    {
        $map = [
            'active' => 'bg-success',
            'available' => 'bg-success',
            'success' => 'bg-success',
            'paid' => 'bg-success',
            'completed' => 'bg-success',
            'confirmed' => 'bg-success',
            'delivered' => 'bg-success',
            'checked_in' => 'bg-info',
            'ready' => 'bg-info',
            'preparing' => 'bg-info',
            'pending' => 'bg-warning text-dark',
            'occupied' => 'bg-warning text-dark',
            'partial' => 'bg-warning text-dark',
            'partially_delivered' => 'bg-warning text-dark',
            'cleaning' => 'bg-warning text-dark',
            'reserved' => 'bg-primary',
            'in_progress' => 'bg-primary',
            'suspended' => 'bg-danger',
            'expired' => 'bg-danger',
            'failed' => 'bg-danger',
            'cancelled' => 'bg-danger',
            'not_received' => 'bg-danger',
            'inactive' => 'bg-secondary',
            'cancelled ' => 'bg-secondary',
        ];
        $class = $map[$status] ?? 'bg-secondary';
        return '<span class="badge ' . $class . '">' . e($status) . '</span>';
    }
}
