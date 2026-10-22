<?php
declare(strict_types=1);

namespace App\Core;

/**
 * View renderer: plain PHP templates with layout support.
 */
class View
{
    /** Render a view file and return its output. */
    public static function render(string $view, array $data = [], bool $useLayout = true): string
    {
        extract($data, EXTR_SKIP);
        $content = self::fetch($view, $data);
        if (!$useLayout) {
            return $content;
        }
        return self::fetch('layouts.app', ['content' => $content, 'view' => $view] + $data);
    }

    private static function fetch(string $view, array $data): string
    {
        $file = VIEW_PATH . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $view");
        }
        ob_start();
        extract($data, EXTR_SKIP);
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }

    /** Escape helper for templates. */
    public static function e(mixed $value): string
    {
        return Security::e($value);
    }

    /** Translation helper for templates. */
    public static function t(string $key, array $params = []): string
    {
        return Translator::t($key, $params);
    }
}
