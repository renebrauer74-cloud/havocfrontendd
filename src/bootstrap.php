<?php
declare(strict_types=1);

// Alle Fehler melden und vorerst anzeigen – nach dem Laden der Config entscheidet 'debug' über die Anzeige.
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');

define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');

spl_autoload_register(static function (string $class): void {
    $prefix = 'Havoc\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = BASE_PATH . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/src/helpers.php';

$configFile = BASE_PATH . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('config/config.php fehlt – bitte config/config.example.php kopieren und anpassen.');
}
$GLOBALS['havoc_config'] = require $configFile;

// Produktion: Fehler nur ins Server-Log, nicht in die Seite.
if (!config('debug', false)) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

date_default_timezone_set(config('timezone', 'Europe/Berlin'));

session_name('havoc_session');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();
