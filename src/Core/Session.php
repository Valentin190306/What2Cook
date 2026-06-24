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

    public static function userId(): ?int
    {
        self::start();
        if (isset($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }

        // Intentar autologueo con cookie Remember Me
        $userId = self::attemptRememberMeLogin();
        if ($userId !== null) {
            $_SESSION['user_id'] = $userId;
            return $userId;
        }

        return null;
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

        // Eliminar token de "Remember Me" en base de datos y borrar cookie
        if (!empty($_COOKIE['remember_me'])) {
            $parts = explode(':', $_COOKIE['remember_me']);
            if (count($parts) === 2) {
                try {
                    $db = \App\Core\Database::getInstance();
                    $stmt = $db->prepare("DELETE FROM user_remember_tokens WHERE selector = :selector");
                    $stmt->execute(['selector' => $parts[0]]);
                } catch (\Throwable $e) {
                    error_log("Error deleting remember token on logout: " . $e->getMessage());
                }
            }
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            setcookie('remember_me', '', [
                'expires' => time() - 42000,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
    }

    /**
     * Intenta iniciar sesión usando la cookie remember_me (split token).
     */
    private static function attemptRememberMeLogin(): ?int
    {
        if (empty($_COOKIE['remember_me'])) {
            return null;
        }

        $parts = explode(':', $_COOKIE['remember_me']);
        if (count($parts) !== 2) {
            return null;
        }

        [$selector, $validator] = $parts;

        try {
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM user_remember_tokens 
                WHERE selector = :selector 
                  AND expires_at > NOW() 
                LIMIT 1
            ");
            $stmt->execute(['selector' => $selector]);
            $token = $stmt->fetch();

            if ($token && hash_equals($token['hashed_validator'], hash('sha256', $validator))) {
                return (int) $token['user_id'];
            }
        } catch (\Throwable $e) {
            error_log("Error in remember me login: " . $e->getMessage());
        }

        return null;
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
