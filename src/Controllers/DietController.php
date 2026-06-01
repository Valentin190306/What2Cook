<?php

namespace App\Controllers;

use App\Core\Controller;

class DietController extends Controller
{
    public function index(): void
    {
        $this->log('info', 'Viendo página de Dietas');
        \App\Core\View::render('Diets');
    }
}
