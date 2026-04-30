<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $this->view('Home', [
            'title' => 'Inicio - What2Cook',
            'styles' => ['index']
        ]);
    }
}
