<?php

namespace App\Controllers;

use App\Core\Controller;

class AboutController extends Controller
{
    public function index(): void
    {
        $this->log('info', 'Viendo página Acerca de');
        \App\Core\View::render('About');
    }
}
