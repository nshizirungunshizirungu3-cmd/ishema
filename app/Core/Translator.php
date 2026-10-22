<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Multilingual translator.
 * Language files live in resources/languages/{en,rw,fr,de}/<group>.php
 * and return an associative array of "key" => "translation".
 */
class Translator
{
    public const LANGS = ['en' => 'English', 'rw' => 'Kinyarwanda', 'fr' => 'Français', 'de' => 'Deutsch'];

    private static string $locale = 'en';

    /** @var array<string, array<string,string>> loaded groups per language */
    private static array $cache = [];

    public static function setLocale(string $locale): void
    {
        $locale = strtolower(substr($locale, 0, 2));
        self::$locale = array_key_exists($locale, self::LANGS) ? $locale : 'en';
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function t(string $key, array $params = [], ?string $locale = null): string
    {
        $locale = $locale ?? self::$locale;
        $group = 'common';
        if (str_contains($key, '.')) {
            [$group, $rest] = explode('.', $key, 2);
            $key = $rest;
        }

        $dict = self::load($locale, $group);
        $text = $dict[$key] ?? null;

        if ($text === null && $locale !== 'en') {
            $dict = self::load('en', $group);
            $text = $dict[$key] ?? null;
        }

        if ($text === null) {
            return $key;
        }

        foreach ($params as $k => $v) {
            $text = str_replace(':' . $k, (string)$v, $text);
        }

        return $text;
    }

    private static function load(string $locale, string $group): array
    {
        if (isset(self::$cache[$locale][$group])) {
            return self::$cache[$locale][$group];
        }
        $file = LANGUAGE_PATH . $locale . DIRECTORY_SEPARATOR . $group . '.php';
        $data = is_file($file) ? (require $file) : [];
        self::$cache[$locale][$group] = $data;
        return $data;
    }

    public static function langName(string $code): string
    {
        return self::LANGS[$code] ?? $code;
    }
}
