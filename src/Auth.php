<?php
declare(strict_types=1);

namespace Havoc;

/** Session-basierter Admin-Login. Zugangsdaten kommen aus config/config.php. */
final class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['admin_user']);
    }

    public static function attempt(string $username, string $password): bool
    {
        $validUser = hash_equals((string) config('admin.username', ''), $username);
        $validPassword = password_verify($password, (string) config('admin.password_hash', ''));

        if (!$validUser || !$validPassword) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin_user'] = $username;
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('admin/login.php');
        }
    }
}
