<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->log('info', 'Viendo página principal');
        \App\Core\View::render('Home');
    }
}
