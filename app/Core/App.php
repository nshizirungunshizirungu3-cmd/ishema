<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Application bootstrap: constants, locale, autoload.
 */
class App
{
    public static function bootstrap(): void
    {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
        ini_set('display_errors', APP_DEBUG ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', STORAGE_PATH . 'logs' . DIRECTORY_SEPARATOR . 'php_errors.log');
        date_default_timezone_set(APP_TIMEZONE);

        Session::start();

        // Locale resolution: user preference > session > default
        $locale = 'en';
        if (Session::has('locale')) {
            $locale = Session::get('locale');
        } elseif (isset($_COOKIE['ishema_lang'])) {
            $locale = $_COOKIE['ishema_lang'];
        }
        Translator::setLocale((string)$locale);
    }
}
