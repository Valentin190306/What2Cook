<?php

namespace App\Controllers;

use App\Core\Controller;

class DietController extends Controller
{
    public function index()
    {
        $this->view('Diets', [
            'title' => 'Dietas y Nutrición - What2Cook',
            'styles' => ['informacionDietas']
        ]);
    }
}
