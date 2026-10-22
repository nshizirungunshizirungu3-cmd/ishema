<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Cross-Site Request Forgery protection.
 */
class CSRF
{
    public static function token(): string
    {
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('_csrf_token');
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $token = null): bool
    {
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }
        $expected = Session::get('_csrf_token');
        if (!$expected || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($expected, $token);
    }

    /** Throws on invalid token. */
    public static function requireValid(): void
    {
        if (!self::verify()) {
            throw new \RuntimeException('Invalid CSRF token.');
        }
    }
}
