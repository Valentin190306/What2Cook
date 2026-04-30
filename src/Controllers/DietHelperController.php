<?php

namespace App\Controllers;

use App\Core\Controller;

class DietHelperController extends Controller
{
    public function index()
    {
        $this->view('DietHelper', [
            'title' => 'Asistente de Dietas - What2Cook',
            'styles' => ['asistenteDieta']
        ]);
    }
}
