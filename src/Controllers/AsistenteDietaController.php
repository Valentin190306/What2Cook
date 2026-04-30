<?php

namespace App\Controllers;

use App\Core\Controller;

class AsistenteDietaController extends Controller
{
    public function index()
    {
        $this->view('AsistenteDieta', [
            'title' => 'Asistente de Dietas - What2Cook',
            'styles' => ['asistenteDieta']
        ]);
    }
}
