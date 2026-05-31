<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Core\View;

class AuthController extends Controller
{
    /**
     * Muestra el formulario de inicio de sesión.
     */
    public function loginForm(): void
    {
        if (Session::isAuthenticated()) {
            $this->redirect('/perfil');
        }

        View::render('Login', [
            'error' => Session::getFlash('error')
        ]);
    }

    /**
     * Muestra el formulario de registro.
     */
    public function registerForm(): void
    {
        if (Session::isAuthenticated()) {
            $this->redirect('/perfil');
        }

        View::render('Register', [
            'error' => Session::getFlash('error')
        ]);
    }

    /**
     * Procesa el inicio de sesión (POST).
     */
    public function login(): void
    {
        // 1. Validar CSRF
        if (!Session::validateCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Sesión expirada, reintentá.');
            $this->redirect('/login');
        }

        // 2. Leer y limpiar inputs
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        // 3. Validar presencia y formato
        if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Credenciales inválidas.');
            $this->redirect('/login');
        }

        // 4. Buscar usuario en base de datos
        $user = (new User())->findByEmail($email);

        // 5. Verificar contraseña
        if ($user === null || !password_verify($password, $user['password'])) {
            Session::flash('error', 'Credenciales inválidas.');
            $this->redirect('/login');
        }

        // 6. Login exitoso
        Session::login((int) $user['id']);
        $this->redirect('/perfil');
    }

    /**
     * Procesa el registro de usuario (POST).
     */
    public function register(): void
    {
        // 1. Validar CSRF
        if (!Session::validateCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Sesión expirada, reintentá.');
            $this->redirect('/register');
        }

        // 2. Leer y limpiar inputs
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        // 3. Validar
        if ($name === '') {
            Session::flash('error', 'El nombre es obligatorio.');
            $this->redirect('/register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'El formato del email es inválido.');
            $this->redirect('/register');
        }

        if (strlen($password) < 8) {
            Session::flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            $this->redirect('/register');
        }

        // 4. Verificar si el email ya existe
        $userModel = new User();
        if ($userModel->findByEmail($email) !== null) {
            Session::flash('error', 'Ese email ya está registrado.');
            $this->redirect('/register');
        }

        // 5. Crear usuario
        try {
            $userModel->create([
                'name' => $name,
                'email' => $email,
                'password' => $password, // User::create handles hashing
                'preferences' => null,
                'allergies' => null
            ]);
        } catch (\PDOException $e) {
            Session::flash('error', 'Ese email ya está registrado.');
            $this->redirect('/register');
        }

        // 6. Éxito: Auto-login
        $user = $userModel->findByEmail($email);
        if ($user === null) {
            Session::flash('error', 'Ocurrió un error inesperado al registrar el usuario.');
            $this->redirect('/register');
        }

        Session::login((int) $user['id']);
        $this->redirect('/perfil');
    }

    /**
     * Procesa el cierre de sesión (POST).
     */
    public function logout(): void
    {
        if (!Session::validateCsrf($_POST['_csrf'] ?? null)) {
            $this->redirect('/');
        }

        Session::logout();
        $this->redirect('/');
    }
}
