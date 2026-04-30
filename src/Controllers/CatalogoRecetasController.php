<?php

namespace App\Controllers;

use App\Core\Controller;

class CatalogoRecetasController extends Controller
{
    public function index()
    {
        $this->view('CatalogoRecetas', [
            'title' => 'Catálogo de Recetas - What2Cook',
            'styles' => ['catalogoRecetas']
        ]);
    }
}
