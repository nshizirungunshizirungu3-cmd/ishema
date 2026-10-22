<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Base controller with common helpers.
 */
abstract class Controller
{
    protected function view(string $view, array $data = []): string
    {
        $data['_currentRoute'] = Router::current();
        return View::render($view, $data);
    }

    protected function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $path, array $flash = []): void
    {
        foreach ($flash as $k => $v) {
            Session::flash($k, $v);
        }
        header('Location: ' . Router::url($path), true, 302);
        exit;
    }

    protected function back(array $flash = []): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? Router::url('/');
        foreach ($flash as $k => $v) {
            Session::flash($k, $v);
        }
        header('Location: ' . $ref, true, 302);
        exit;
    }

    protected function t(string $key, array $params = []): string
    {
        return Translator::t($key, $params);
    }

    /** Require the current user to hold a permission in the active business. */
    protected function requirePermission(string $permission): bool
    {
        return PermissionService::can(Auth::id(), Tenant::id(), $permission);
    }

    /** Validate a permission gate for AJAX requests; emits JSON error. */
    protected function gate(string $permission, string $message = ''): void
    {
        if (!$this->requirePermission($permission)) {
            $this->json([
                'ok' => false,
                'error' => $message !== '' ? $message : Translator::t('common.no_permission'),
            ], 403);
        }
    }
}
