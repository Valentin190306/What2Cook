<?php

namespace App\Controllers;

use App\Core\Controller;

class AboutController extends Controller
{
    public function index()
    {
        $this->view('About', [
            'title' => 'Sobre Nosotros - What2Cook',
            'styles' => ['nosotros']
        ]);
    }
}
