<?php

namespace App\Controllers;

use App\Core\Controller;

class AuthController extends Controller
{
    public function loginForm()
    {
        \App\Core\View::render('Login');
    }

    public function registerForm()
    {
        \App\Core\View::render('Register');
    }

    public function login()
    {
        // Lógica de login (POST)
    }
}
