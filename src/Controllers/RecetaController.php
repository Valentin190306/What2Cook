<?php

namespace App\Controllers;

use App\Core\Controller;

class RecetaController extends Controller
{
    public function show($params)
    {
        $id = $params['id'] ?? 0;
        
        $this->view('Receta', [
            'title' => "Receta #$id - What2Cook",
            'styles' => ['receta'],
            'recipeName' => "Receta Especial #$id"
        ]);
    }
}
