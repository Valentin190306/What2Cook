<?php

namespace App\Controllers;

use App\Core\Controller;

class ErrorController extends Controller
{
    public function notFound($message = '')
    {
        http_response_code(404);
        $this->view('404', [
            'title' => '404 - No encontrado',
            'message' => $message,
            'styles' => []
        ]);
        exit;
    }
}
