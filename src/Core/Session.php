<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Class Session
 *
 * Clase auxiliar estática para la gestión de sesiones y seguridad CSRF.
 */
class Session
{
    /**
     * Inicia la sesión de forma idempotente con configuraciones de seguridad.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (is_dir('/var/lib/php/sessions')) {
                ini_set('session.save_path', '/var/lib/php/sessions');
            }

            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => $secure,
            ]);

            session_start();
        }
    }

    /**
     * Obtiene el ID del usuario autenticado si existe.
     */
    public static function userId(): ?int
    {
        self::start();
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    /**
     * Verifica si el usuario está autenticado.
     */
    public static function isAuthenticated(): bool
    {
        return self::userId() !== null;
    }

    /**
     * Registra el inicio de sesión para un usuario.
     */
    public static function login(int $userId): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
    }

    /**
     * Finaliza la sesión y destruye las cookies asociadas.
     */
    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
    }

    /**
     * Establece un mensaje flash en la sesión.
     */
    public static function flash(string $key, string $message): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $message;
    }

    /**
     * Obtiene y consume un mensaje flash.
     */
    public static function getFlash(string $key): ?string
    {
        self::start();
        if (isset($_SESSION['_flash'][$key])) {
            $message = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $message;
        }
        return null;
    }

    /**
     * Genera u obtiene el token CSRF para la sesión.
     */
    public static function csrfToken(): string
    {
        self::start();
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /**
     * Valida un token CSRF provisto contra el token en sesión.
     */
    public static function validateCsrf(?string $token): bool
    {
        self::start();
        if ($token === null || !isset($_SESSION['_csrf'])) {
            return false;
        }
        return hash_equals($_SESSION['_csrf'], $token);
    }
}
