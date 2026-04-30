<?php

namespace App\Controllers;

use App\Core\Controller;

class PerfilController extends Controller
{
    public function index()
    {
        $this->view('Perfil', [
            'title' => 'Mi Perfil - What2Cook',
            'styles' => ['perfil'],
            'userName' => 'Usuario Gastronómico'
        ]);
    }
}
