<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;
use App\Core\View;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Session::isAuthenticated()) {
            $this->redirect('/perfil');
        }

        View::render('Login', [
            'error' => Session::getFlash('error')
        ]);
    }

    public function registerForm(): void
    {
        if (Session::isAuthenticated()) {
            $this->redirect('/perfil');
        }

        View::render('Register', [
            'error' => Session::getFlash('error')
        ]);
    }

    public function login(): void
    {
        if (!Session::validateCsrf($_POST['_csrf'] ?? null)) {
            $this->log('warning', 'Login: CSRF inválido');
            Session::flash('error', 'Sesión expirada, reintentá.');
            $this->redirect('/login');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $db = \App\Core\Database::getInstance();

        // 1. Rate Limiting Check (max 5 intentos en 15 minutos)
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM login_attempts 
                WHERE ip_address = :ip 
                  AND attempted_at > NOW() - INTERVAL '15 minutes'
            ");
            $stmt->execute(['ip' => $ip]);
            $attempts = (int) $stmt->fetchColumn();

            if ($attempts >= 5) {
                $this->log('warning', 'Login bloqueado por exceso de intentos', ['ip' => $ip]);
                Session::flash('error', 'Demasiados intentos fallidos. Tu IP ha sido bloqueada temporalmente por 15 minutos.');
                $this->redirect('/login');
            }
        } catch (\Throwable $e) {
            $this->log('error', 'Error al verificar rate limiting', ['error' => $e->getMessage()]);
        }

        $email = Validator::email($_POST['email'] ?? null);
        $password = Validator::password($_POST['password'] ?? null, 1, 255);

        if ($email === null || $password === null) {
            // Registrar intento fallido
            try {
                $stmt = $db->prepare("INSERT INTO login_attempts (ip_address) VALUES (:ip)");
                $stmt->execute(['ip' => $ip]);
            } catch (\Throwable $e) {}

            $this->log('warning', 'Login: credenciales inválidas');
            Session::flash('error', 'Credenciales inválidas.');
            $this->redirect('/login');
        }

        $user = (new User())->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password'])) {
            // Registrar intento fallido
            try {
                $stmt = $db->prepare("INSERT INTO login_attempts (ip_address) VALUES (:ip)");
                $stmt->execute(['ip' => $ip]);
            } catch (\Throwable $e) {}

            $this->log('warning', 'Login: credenciales incorrectas', ['email' => $email]);
            Session::flash('error', 'Credenciales inválidas.');
            $this->redirect('/login');
        }

        // Limpiar intentos fallidos al iniciar sesión con éxito
        try {
            $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = :ip");
            $stmt->execute(['ip' => $ip]);
        } catch (\Throwable $e) {}

        // Iniciar sesión en session php
        Session::login((int) $user['id']);
        $this->log('info', 'Login exitoso', ['user_id' => (int) $user['id'], 'email' => $email]);

        // Manejar "Recordarme" (Remember Me)
        $remember = !empty($_POST['remember_me']);
        if ($remember) {
            try {
                $selector = bin2hex(random_bytes(12));
                $validator = bin2hex(random_bytes(32));
                $hashedValidator = hash('sha256', $validator);
                $expiresAt = (new \DateTime())->modify('+30 days')->format('Y-m-d H:i:s');

                $stmt = $db->prepare("
                    INSERT INTO user_remember_tokens (user_id, selector, hashed_validator, expires_at)
                    VALUES (:user_id, :selector, :hashed, :expires)
                ");
                $stmt->execute([
                    'user_id' => $user['id'],
                    'selector' => $selector,
                    'hashed' => $hashedValidator,
                    'expires' => $expiresAt
                ]);

                $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                setcookie('remember_me', "{$selector}:{$validator}", [
                    'expires' => time() + (30 * 24 * 60 * 60),
                    'path' => '/',
                    'domain' => '',
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            } catch (\Throwable $e) {
                $this->log('error', 'Error creando token Remember Me', ['error' => $e->getMessage()]);
            }
        }

        $this->redirect('/perfil');
    }

    public function register(): void
    {
        if (!Session::validateCsrf($_POST['_csrf'] ?? null)) {
            $this->log('warning', 'Register: CSRF inválido');
            Session::flash('error', 'Sesión expirada, reintentá.');
            $this->redirect('/register');
        }

        $name = Validator::string($_POST['name'] ?? null, 1, 100);
        $email = Validator::email($_POST['email'] ?? null);
        $password = Validator::password($_POST['password'] ?? null, 8, 255);
        $passwordConfirm = $_POST['password_confirm'] ?? null;

        if ($name === null) {
            $this->log('warning', 'Register: nombre inválido');
            Session::flash('error', 'El nombre es obligatorio.');
            $this->redirect('/register');
        }

        if ($email === null) {
            $this->log('warning', 'Register: email inválido');
            Session::flash('error', 'El formato del email es inválido.');
            $this->redirect('/register');
        }

        if ($password === null) {
            $this->log('warning', 'Register: contraseña inválida');
            Session::flash('error', 'La contraseña debe tener entre 8 y 255 caracteres.');
            $this->redirect('/register');
        }

        if ($password !== (is_string($passwordConfirm) ? $passwordConfirm : '')) {
            $this->log('warning', 'Register: contraseñas no coinciden', ['email' => $email]);
            Session::flash('error', 'Las contraseñas no coinciden.');
            $this->redirect('/register');
        }

        $userModel = new User();
        if ($userModel->findByEmail($email) !== null) {
            $this->log('warning', 'Register: email ya registrado', ['email' => $email]);
            Session::flash('error', 'Ese email ya está registrado.');
            $this->redirect('/register');
        }

        try {
            $userModel->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'preferences' => null,
                'allergies' => null
            ]);
        } catch (\PDOException $e) {
            Session::flash('error', 'Ese email ya está registrado.');
            $this->redirect('/register');
        }

        $user = $userModel->findByEmail($email);
        if ($user === null) {
            $this->log('error', 'Registro: usuario no encontrado tras crear', ['email' => $email]);
            Session::flash('error', 'Ocurrió un error inesperado al registrar el usuario.');
            $this->redirect('/register');
        }

        Session::login((int) $user['id']);
        $this->log('info', 'Registro exitoso', ['user_id' => (int) $user['id'], 'email' => $email]);
        $this->redirect('/perfil');
    }

    public function logout(): void
    {
        if (!Session::validateCsrf($_POST['_csrf'] ?? null)) {
            $this->redirect('/');
        }

        $userId = Session::userId();
        Session::logout();
        $this->log('info', 'Logout', ['user_id' => $userId]);
        $this->redirect('/');
    }

    public function checkEmail(): void
    {
        $this->requireJson();
        $body = $this->parseBody();
        
        $email = Validator::email($body['email'] ?? null);

        if ($email === null) {
            $this->json(['error' => 'Email inválido.'], 422);
            return;
        }

        $user = (new User())->findByEmail($email);
        $this->json(['exists' => $user !== null]);
    }
}
