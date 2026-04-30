<?php

namespace App\Controllers;

use App\Core\Controller;

class DietHelperController extends Controller
{
    public function index()
    {
        \App\Core\View::render('DietHelper');
    }
}
