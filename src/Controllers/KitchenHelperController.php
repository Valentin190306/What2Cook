<?php

namespace App\Controllers;

use App\Core\Controller;

class KitchenHelperController extends Controller
{
    public function index()
    {
        \App\Core\View::render('KitchenHelper');
    }
}
