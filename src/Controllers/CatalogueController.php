<?php

namespace App\Controllers;

use App\Core\Controller;

class CatalogueController extends Controller
{
    public function index()
    {
        $this->view('Catalogue', [
            'title' => 'Catálogo de Recetas - What2Cook',
            'styles' => ['catalogoRecetas']
        ]);
    }
}
