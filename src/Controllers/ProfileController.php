<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class ProfileController extends Controller
{
    public function index()
    {
        $userId = $this->requireAuthWeb();
        $user = (new User())->find($userId);
        $userName = $user ? $user['name'] : 'Usuario Gastronómico';

        \App\Core\View::render('Profile', [
            'userName' => $userName
        ]);
    }
}
