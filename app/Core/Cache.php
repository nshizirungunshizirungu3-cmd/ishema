<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Tiny file-based cache for rate limiting, locks and counters.
 */
class Cache
{
    private static string $dir = '';

    private static function dir(): string
    {
        if (self::$dir === '') {
            self::$dir = STORAGE_PATH . 'cache';
            if (!is_dir(self::$dir)) {
                mkdir(self::$dir, 0755, true);
            }
        }
        return self::$dir;
    }

    private static function path(string $key): string
    {
        return self::dir() . DIRECTORY_SEPARATOR . 'cache_' . hash('sha256', $key) . '.json';
    }

    public static function put(string $key, mixed $value, int $ttlSeconds = 3600): void
    {
        $payload = json_encode([
            'expires' => $ttlSeconds > 0 ? time() + $ttlSeconds : 0,
            'value'   => $value,
        ]);
        file_put_contents(self::path($key), $payload, LOCK_EX);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $file = self::path($key);
        if (!is_file($file)) {
            return $default;
        }
        $raw = file_get_contents($file);
        if ($raw === false) {
            return $default;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return $default;
        }
        if ($data['expires'] !== 0 && $data['expires'] < time()) {
            @unlink($file);
            return $default;
        }
        return $data['value'];
    }

    public static function delete(string $key): void
    {
        @unlink(self::path($key));
    }
}
