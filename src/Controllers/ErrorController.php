<?php

namespace App\Controllers;

use App\Core\Controller;

class ErrorController extends Controller
{
    public function notFound(string $message = ''): void
    {
        $this->log('warning', 'Página no encontrada', ['message' => $message]);
        http_response_code(404);
        \App\Core\View::render('404', [
            'message' => $message
        ]);
        exit;
    }
}
