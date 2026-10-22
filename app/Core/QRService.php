<?php
declare(strict_types=1);

namespace App\Core;

/**
 * QR code generation.
 * Strategy:
 *  1. Use chillerlan/php-qrcode when installed via Composer.
 *  2. Fall back to the free qrserver.org API (requires internet).
 *  3. Final fallback: a placeholder PNG rendered with GD.
 */
class QRService
{
    public static function png(string $text, int $size = 300, string $path = 'qr'): string
    {
        $dir = PUBLIC_PATH . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $path;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = 'qr_' . hash('sha256', $text . $size) . '.png';
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;
        $relative = 'assets/uploads/' . $path . '/' . $filename;

        if (is_file($fullPath)) {
            return $relative;
        }

        // 1) Composer library
        if (class_exists(\chillerlan\QRCode\QRCode::class)) {
            try {
                $qrcode = new \chillerlan\QRCode\QRCode();
                $qrcode->render($text, $fullPath);
                return $relative;
            } catch (\Throwable $e) {
                // fall through
            }
        }

        // 2) qrserver.org API
        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&format=png&data=' . rawurlencode($text);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'ISHEMA/1.0',
        ]);
        $image = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_errno($ch);
        curl_close($ch);
        if ($err === 0 && $httpCode === 200 && $image !== false && strlen($image) > 100) {
            file_put_contents($fullPath, $image);
            return $relative;
        }

        // 3) GD placeholder
        self::placeholderPng($fullPath, $size);
        return $relative;
    }

    /** SVG version (works offline without the library). */
    public static function svg(string $text): string
    {
        // Minimal placeholder SVG with encoded payload - replaced by real QR when a generator exists
        $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        return '<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" viewBox="0 0 240 240">'
            . '<rect width="240" height="240" fill="#ffffff"/>'
            . '<text x="120" y="112" font-family="monospace" font-size="10" text-anchor="middle" fill="#1056A0">ISHEMA QR</text>'
            . '<text x="120" y="140" font-family="monospace" font-size="8" text-anchor="middle" fill="#64748B">' . $safe . '</text>'
            . '</svg>';
    }

    private static function placeholderPng(string $path, int $size): void
    {
        $img = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($img, 255, 255, 255);
        $blue  = imagecolorallocate($img, 16, 86, 160);
        $gray  = imagecolorallocate($img, 100, 116, 139);
        imagefilledrectangle($img, 0, 0, $size, $size, $white);
        imagefilledrectangle($img, 10, 10, $size - 10, $size - 10, $white);
        imagerectangle($img, 10, 10, $size - 10, $size - 10, $blue);
        imagestring($img, 5, (int)($size / 2) - 35, (int)($size / 2) - 10, 'ISHEMA QR', $blue);
        imagedestroy($img);
        imagepng($img, $path);
    }
}
