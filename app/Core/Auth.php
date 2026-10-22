<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Authentication service.
 */
class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900; // 15 minutes

    /** Returns the logged-in user row or null. */
    public static function user(): ?array
    {
        $id = Session::get('user_id');
        if (!$id) {
            return null;
        }
        $user = User::find((int)$id);
        if (!$user || $user['deleted_at'] !== null || !$user['is_active']) {
            return null;
        }
        return $user;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u ? $u['role'] : null;
    }

    public static function isSuperAdmin(): bool
    {
        return self::role() === 'SUPER_ADMIN';
    }

    /**
     * Authenticate with email + password. Enforces login rate limiting.
     * @return array{ok:bool, user?:array, error?:string}
     */
    public static function attempt(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));

        $lockKey = 'login_lock_' . $email;
        $lockedUntil = Cache::get($lockKey);
        if ($lockedUntil && (int)$lockedUntil > time()) {
            $remaining = (int)$lockedUntil - time();
            return ['ok' => false, 'error' => "Too many failed attempts. Try again in $remaining seconds."];
        }

        $user = User::where('email', $email)->whereNull('deleted_at')->first();

        if (!$user || !password_verify($password, (string)$user['password'])) {
            self::registerAttempt($email);
            $attempts = (int)(Cache::get('login_attempts_' . $email) ?? 0) + 1;
            Cache::put('login_attempts_' . $email, $attempts, 900);
            if ($attempts >= self::MAX_ATTEMPTS) {
                Cache::put($lockKey, time() + self::LOCKOUT_SECONDS, self::LOCKOUT_SECONDS);
                return ['ok' => false, 'error' => 'Account temporarily locked due to too many failed attempts.'];
            }
            return ['ok' => false, 'error' => 'Invalid email or password.'];
        }

        if (!$user['is_active']) {
            return ['ok' => false, 'error' => 'This account is deactivated. Contact your administrator.'];
        }

        // Reset attempt counters on success
        Cache::delete('login_attempts_' . $email);
        Cache::delete($lockKey);

        Database::run(
            'INSERT INTO login_attempts (email, ip_address, attempted_at) VALUES (?, ?, NOW())',
            [$email, Security::ip()]
        );

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        Session::set('user_id', (int)$user['id']);
        Session::set('role', $user['role']);
        Session::set('locale', $user['language'] ?? 'en');
        Translator::setLocale($user['language'] ?? 'en');

        Database::run(
            'UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?',
            [Security::ip(), $user['id']]
        );

        Audit::record('login', 'user', (string)$user['id'], null, null, $user['role'] === 'SUPER_ADMIN' ? null : ((int)($user['business_id'] ?? 0) ?: null));

        return ['ok' => true, 'user' => $user];
    }

    public static function logout(): void
    {
        Audit::record('logout', 'user', (string)self::id());
        Session::destroy();
    }

    private static function registerAttempt(string $email): void
    {
        Database::run(
            'INSERT INTO login_attempts (email, ip_address, attempted_at) VALUES (?, ?, NOW())',
            [$email, Security::ip()]
        );
    }
}
