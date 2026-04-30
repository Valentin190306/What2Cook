<?php

namespace App\Controllers;

use App\Core\Controller;

class AsistenteCocinaController extends Controller
{
    public function index()
    {
        $this->view('AsistenteCocina', [
            'title' => 'Asistente de Cocina - What2Cook',
            'styles' => ['asistenteCocina']
        ]);
    }
}
