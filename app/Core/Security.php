<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Input handling, output escaping and validation helpers.
 */
class Security
{
    public static function input(?string $key, mixed $default = null, bool $trim = true): mixed
    {
        $source = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
        if (!isset($source[$key])) {
            return $default;
        }
        $value = $source[$key];
        if (is_array($value)) {
            return array_map(fn($v) => is_string($v) ? ($trim ? trim($v) : $v) : $v, $value);
        }
        if (!is_string($value)) {
            return $value;
        }
        return $trim ? trim($value) : $value;
    }

    public static function post(?string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /** Escape output for HTML context. */
    public static function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_array($value) || is_object($value)) {
            return htmlspecialchars(json_encode($value), ENT_QUOTES, 'UTF-8');
        }
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function slug(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s\-_]/u', '', $text) ?? '';
        $text = preg_replace('/[\s\-_]+/', '-', $text);
        return trim($text, '-');
    }

    public static function randomString(int $length = 16): string
    {
        return bin2hex(random_bytes((int)ceil($length / 2)));
    }

    public static function generateToken(int $length = 32): string
    {
        return self::randomString($length);
    }

    public static function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function userAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 512);
    }

    public static function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isValidPhone(string $phone): bool
    {
        return preg_match('/^\+?[0-9\s\-()]{7,20}$/', $phone) === 1;
    }

    /** Validate an uploaded image file. Returns error message or null when valid. */
    public static function validateImageUpload(array $file, int $maxBytes = 5242880): ?string
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null; // no file uploaded - optional field
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'File upload error.';
        }
        if ($file['size'] > $maxBytes) {
            return 'File exceeds the maximum allowed size of 5MB.';
        }
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($info, $file['tmp_name']);
        finfo_close($info);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            return 'Only JPG, PNG, GIF and WebP images are allowed.';
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'Invalid file extension.';
        }
        return null;
    }

    /** Move an uploaded image into public/assets/uploads with a unique name. */
    public static function moveImageUpload(array $file, string $subfolder = 'general'): ?string
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $err = self::validateImageUpload($file);
        if ($err !== null) {
            throw new \RuntimeException($err);
        }
        $dir = PUBLIC_PATH . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $subfolder;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = self::randomString(20) . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $dest = $dir . DIRECTORY_SEPARATOR . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Failed to save uploaded file.');
        }
        return 'assets/uploads/' . $subfolder . '/' . $name;
    }
}
