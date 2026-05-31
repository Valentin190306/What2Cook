<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Models\Plan;
use App\Models\ShoppingList;
use App\Models\Favorite;
use App\Core\View;

class ProfileController extends Controller
{
    public function index(): void
    {
        $userId = $this->requireAuthWeb();
        $user = (new User())->find($userId);

        $favoriteModel = new Favorite();
        $favoritesCount = $favoriteModel->countByUser($userId);
        $plansCount = (new Plan())->countByUser($userId);
        $listsCount = (new ShoppingList())->countByUser($userId);

        // Fetch recent favorites (up to 3) for the dashboard
        $allFavorites = $favoriteModel->findAllByUser($userId);
        $recentFavorites = array_slice($allFavorites, 0, 3);

        View::render('Profile', [
            'userName' => $user ? $user['name'] : 'Usuario',
            'favoritesCount' => $favoritesCount,
            'plansCount' => $plansCount,
            'listsCount' => $listsCount,
            'recentFavorites' => $recentFavorites,
            'success' => Session::getFlash('success'),
        ]);
    }

    public function editForm(): void
    {
        $userId = $this->requireAuthWeb();
        $user = (new User())->find($userId);

        View::render('ProfileEdit', [
            'user' => $user,
            'error' => Session::getFlash('error')
        ]);
    }

    public function update(): void
    {
        $userId = $this->requireAuthWeb();

        // 1. Validar CSRF
        if (!Session::validateCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Sesión expirada, reintentá.');
            $this->redirect('/perfil/editar');
        }

        // 2. Leer y limpiar inputs
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $diet = trim($_POST['diet'] ?? '');
        
        $intolerancesRaw = $_POST['intolerances'] ?? [];
        $intolerances = is_array($intolerancesRaw) ? $intolerancesRaw : [];
        $validAllergies = ['dairy', 'egg', 'gluten', 'grain', 'peanut', 'seafood', 'sesame', 'shellfish', 'soy', 'sulfite', 'tree nut', 'wheat'];
        $filteredIntolerances = array_intersect($intolerances, $validAllergies);

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // 3. Validaciones
        if ($name === '') {
            Session::flash('error', 'El nombre es obligatorio.');
            $this->redirect('/perfil/editar');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'El formato del email es inválido.');
            $this->redirect('/perfil/editar');
        }

        $validDiets = ['vegetarian', 'vegan', 'ketogenic', 'paleo', 'primal', 'whole30', 'gluten free', 'pescetarian', 'lacto-vegetarian', 'ovo-vegetarian'];
        if ($diet !== '' && !in_array($diet, $validDiets, true)) {
            Session::flash('error', 'Dieta inválida.');
            $this->redirect('/perfil/editar');
        }

        // 4. Unicidad de email
        $userModel = new User();
        $existing = $userModel->findByEmail($email);
        if ($existing !== null && (int) $existing['id'] !== $userId) {
            Session::flash('error', 'Ese email ya está en uso.');
            $this->redirect('/perfil/editar');
        }

        // 5. Preparar datos
        $preferences = ($diet !== '') ? $diet : null;
        $allergies = json_encode(array_values($filteredIntolerances));

        // 6. Cambio de contraseña opcional
        $newPasswordVal = null;
        if ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '') {
            $user = $userModel->find($userId);
            if ($user === null) {
                Session::flash('error', 'Usuario no encontrado.');
                $this->redirect('/perfil/editar');
            }

            if (!password_verify($currentPassword, $user['password'])) {
                Session::flash('error', 'La contraseña actual es incorrecta.');
                $this->redirect('/perfil/editar');
            }

            if (strlen($newPassword) < 8) {
                Session::flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
                $this->redirect('/perfil/editar');
            }

            if ($newPassword !== $confirmPassword) {
                Session::flash('error', 'Las contraseñas no coinciden.');
                $this->redirect('/perfil/editar');
            }

            $newPasswordVal = $newPassword;
        }

        // 7. Persistir
        try {
            $userModel->updateProfile($userId, [
                'name' => $name,
                'email' => $email,
                'preferences' => $preferences,
                'allergies' => $allergies
            ], $newPasswordVal);
        } catch (\PDOException $e) {
            Session::flash('error', 'Ese email ya está en uso.');
            $this->redirect('/perfil/editar');
        }

        Session::flash('success', 'Perfil actualizado.');
        $this->redirect('/perfil');
    }
}
