<?php

namespace App\Controllers;

use App\Core\Controller;

class DietController extends Controller
{
    public function index()
    {
        \App\Core\View::render('Diets');
    }
}
