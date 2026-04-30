<?php

namespace App\Controllers;

use App\Core\Controller;

class AuthController extends Controller
{
    public function loginForm()
    {
        $this->view('Login', [
            'title' => 'Iniciar Sesión - What2Cook',
            'styles' => ['components'] // Usamos estilos de componentes para el formulario
        ]);
    }

    public function registerForm()
    {
        $this->view('Register', [
            'title' => 'Registrarse - What2Cook',
            'styles' => ['components']
        ]);
    }

    public function login()
    {
        // Lógica de login (POST)
    }
}
