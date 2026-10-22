<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight router with route model binding and middleware groups.
 */
class Router
{
    private static array $routes = [];
    private static array $current = [];

    public static function add(string $method, string $pattern, array $handler, array $middleware = []): void
    {
        $pattern = trim($pattern, '/');
        self::$routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $pattern,
            'handler'    => $handler, // [ControllerClass::class, 'method'] or closure
            'middleware' => $middleware,
        ];
    }

    public static function get(string $pattern, array|callable $handler, array $middleware = []): void
    {
        self::add('GET', $pattern, $handler, $middleware);
    }

    public static function post(string $pattern, array|callable $handler, array $middleware = []): void
    {
        self::add('POST', $pattern, $handler, $middleware);
    }

    public static function any(string $pattern, array|callable $handler, array $middleware = []): void
    {
        self::add('GET', $pattern, $handler, $middleware);
        self::add('POST', $pattern, $handler, $middleware);
    }

    /**
     * Dispatch the current HTTP request.
     */
    public static function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = self::normalizeUri($uri);

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method && $route['method'] !== 'ANY') {
                continue;
            }
            $params = self::match($route['pattern'], $uri);
            if ($params === null) {
                continue;
            }

            self::$current = $route + ['params' => $params];

            // CSRF protection on all POST routes (skip explicitly marked)
            if ($method === 'POST' && !in_array('no_csrf', $route['middleware'], true)) {
                if (!CSRF::verify()) {
                    self::error(419, 'CSRF token mismatch.');
                    return;
                }
            }

            // Run middleware
            foreach ($route['middleware'] as $mw) {
                if ($mw === 'no_csrf') {
                    continue;
                }
                $result = self::runMiddleware($mw);
                if ($result !== true) {
                    return; // middleware produced a response (redirect/error)
                }
            }

            self::execute($route['handler'], $params);
            return;
        }

        self::error(404);
    }

    private static function normalizeUri(string $uri): string
    {
        // Strip the public entry prefix when the app lives in a subfolder
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        if ($scriptDir !== '/' && str_starts_with($uri, $scriptDir . '/')) {
            $uri = substr($uri, strlen($scriptDir));
        }
        return trim($uri, '/');
    }

    private static function match(string $pattern, string $uri): ?array
    {
        $pattern = trim($pattern, '/');
        if ($pattern === '') {
            return $uri === '' ? [] : null;
        }

        $pParts = explode('/', $pattern);
        $uParts = explode('/', $uri);
        if (count($pParts) !== count($uParts)) {
            return null;
        }

        $params = [];
        foreach ($pParts as $i => $seg) {
            if (str_starts_with($seg, '{') && str_ends_with($seg, '}')) {
                $name = substr($seg, 1, -1);
                if ($uParts[$i] === '') {
                    return null;
                }
                $params[$name] = rawurldecode($uParts[$i]);
            } elseif ($seg !== $uParts[$i]) {
                return null;
            }
        }
        return $params;
    }

    private static function execute(array|callable $handler, array $params): void
    {
        if (is_callable($handler)) {
            echo call_user_func_array($handler, $params);
            return;
        }
        [$class, $method] = $handler;
        if (!class_exists($class)) {
            throw new \RuntimeException("Controller not found: $class");
        }
        $controller = new $class();
        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Controller method not found: $class::$method");
        }
        $result = call_user_func_array([$controller, $method], $params);
        if (is_string($result)) {
            echo $result;
        }
    }

    private static function runMiddleware(string $name): bool
    {
        $map = [
            'auth'        => \App\Middleware\AuthMiddleware::class,
            'guest'       => \App\Middleware\GuestMiddleware::class,
            'super_admin' => \App\Middleware\SuperAdminMiddleware::class,
            'tenant'      => \App\Middleware\TenantMiddleware::class,
            'staff'       => \App\Middleware\StaffMiddleware::class,
            'customer'    => \App\Middleware\CustomerMiddleware::class,
            'subscription' => \App\Middleware\SubscriptionMiddleware::class,
        ];
        if (!isset($map[$name])) {
            throw new \RuntimeException("Unknown middleware: $name");
        }
        $instance = new $map[$name]();
        $result = $instance->handle();
        if ($result === true) {
            return true;
        }
        if (is_string($result)) {
            echo $result;
        }
        return false;
    }

    public static function error(int $code, string $message = ''): void
    {
        http_response_code($code);
        $view = match ($code) {
            403 => 'errors/403',
            404 => 'errors/404',
            419 => 'errors/419',
            500 => 'errors/500',
            default => 'errors/generic',
        };
        echo View::render($view, ['code' => $code, 'message' => $message], false);
    }

    public static function current(): array
    {
        return self::$current;
    }

    public static function url(string $path): string
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        return $base . '/' . ltrim($path, '/');
    }

    public static function asset(string $path): string
    {
        return self::url('assets/' . ltrim($path, '/'));
    }
}
