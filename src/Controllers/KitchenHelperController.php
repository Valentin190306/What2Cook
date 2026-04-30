<?php

namespace App\Controllers;

use App\Core\Controller;

class KitchenHelperController extends Controller
{
    public function index()
    {
        $this->view('KitchenHelper', [
            'title' => 'Asistente de Cocina - What2Cook',
            'styles' => ['asistenteCocina']
        ]);
    }
}
