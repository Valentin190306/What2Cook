<?php

namespace App\Controllers;

use App\Core\Controller;

class CatalogueController extends Controller
{
    public function index()
    {
        \App\Core\View::render('Catalogue');
    }
}
