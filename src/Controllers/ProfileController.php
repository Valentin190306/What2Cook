<?php

namespace App\Controllers;

use App\Core\Controller;

class ProfileController extends Controller
{
    public function index()
    {
        $this->view('Profile', [
            'title' => 'Mi Perfil - What2Cook',
            'styles' => ['perfil'],
            'userName' => 'Usuario Gastronómico'
        ]);
    }
}
