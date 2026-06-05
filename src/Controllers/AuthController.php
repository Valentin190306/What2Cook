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

        $email = Validator::email($_POST['email'] ?? null);
        $password = Validator::password($_POST['password'] ?? null, 1, 255);

        if ($email === null || $password === null) {
            $this->log('warning', 'Login: credenciales inválidas');
            Session::flash('error', 'Credenciales inválidas.');
            $this->redirect('/login');
        }

        $user = (new User())->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password'])) {
            $this->log('warning', 'Login: credenciales incorrectas', ['email' => $email]);
            Session::flash('error', 'Credenciales inválidas.');
            $this->redirect('/login');
        }

        Session::login((int) $user['id']);
        $this->log('info', 'Login exitoso', ['user_id' => (int) $user['id'], 'email' => $email]);
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
}
