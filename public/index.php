<?php
declare(strict_types=1);

/**
 * ISHEMA public entry point.
 */

define('ISHEMA_PATH', dirname(__DIR__));
define('APP_PATH', ISHEMA_PATH . DIRECTORY_SEPARATOR . 'app');
define('CONFIG_PATH', ISHEMA_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR);
define('DATABASE_PATH', ISHEMA_PATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR);
define('PUBLIC_PATH', ISHEMA_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
define('RESOURCES_PATH', ISHEMA_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR);
define('VIEW_PATH', RESOURCES_PATH . 'views' . DIRECTORY_SEPARATOR);
define('LANGUAGE_PATH', RESOURCES_PATH . 'languages' . DIRECTORY_SEPARATOR);
define('ROUTES_PATH', ISHEMA_PATH . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR);
define('STORAGE_PATH', ISHEMA_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR);
define('TESTS_PATH', ISHEMA_PATH . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR);

// Environment configuration
$envFile = ISHEMA_PATH . '.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if (($v[0] ?? '') === '"' && substr($v, -1) === '"') {
                $v = substr($v, 1, -1);
            }
            if (!isset($_ENV[$k]) && !isset($_SERVER[$k])) {
                putenv("$k=$v");
                $_ENV[$k] = $v;
                $_SERVER[$k] = $v;
            }
        }
    }
}

define('APP_NAME', getenv('APP_NAME') ?: 'ISHEMA');
define('APP_URL', getenv('APP_URL') ?: '');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'UTC');

require_once ISHEMA_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once APP_PATH . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'helpers.php';

use App\Core\App;

App::bootstrap();

require ROUTES_PATH . 'web.php';

try {
    \App\Core\Router::dispatch();
} catch (\Throwable $e) {
    if (APP_DEBUG) {
        echo '<pre style="background:#102A43;color:#EAF3FF;padding:16px;border-radius:8px;font-family:monospace;">'
            . '<b>' . get_class($e) . '</b>: ' . htmlspecialchars($e->getMessage())
            . "\n\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo 'Internal server error.';
    }
}
