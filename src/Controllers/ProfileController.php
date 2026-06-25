<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;
use App\Models\Plan;
use App\Models\ShoppingList;
use App\Models\Favorite;
use App\Models\MealPrepFavorite;
use App\Models\SavedShoppingList;
use App\Services\UserPreferenceService;
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
        $listsCount = (new SavedShoppingList())->countByUser($userId);

        // Fetch recent favorites (up to 4) for the dashboard
        $allFavorites = $favoriteModel->findAllByUser($userId);
        $recentFavorites = array_slice($allFavorites, 0, 4);
        
        // Fetch recent meal prep favorites (up to 4)
        $recentMealPreps = (new MealPrepFavorite())->findRecentByUser($userId, 4);
        
        // Fetch recent shopping lists (up to 3)
        $savedListModel = new SavedShoppingList();
        $recentShoppingLists = $savedListModel->findRecentByUser($userId, 3);
        foreach ($recentShoppingLists as &$list) {
            $listWithItems = $savedListModel->findWithItems((int) $list['id']);
            $list['items'] = $listWithItems['items'] ?? [];
        }
        unset($list);
        
        // Fetch recent diet plans (up to 3) and load full days/meals for each
        $planModel = new Plan();
        $recentSummaries = $planModel->findRecentByUser($userId, 3);
        $recentDietPlans = [];
        foreach ($recentSummaries as $p) {
            $full = $planModel->findWithDays((int) $p['id']);
            if ($full !== null) {
                $recentDietPlans[] = $full;
            }
        }

        $prefs = (new UserPreferenceService())->getPreferences($userId);
        $userDiet = $prefs['diet'];
        $userAllergies = $prefs['intolerances'];

        $dietLabels = [
            '' => 'Sin dieta',
            'vegetarian' => 'Vegetariana',
            'vegan' => 'Vegana',
            'ketogenic' => 'Cetogénica',
            'paleo' => 'Paleo',
            'primal' => 'Primal',
            'whole30' => 'Whole30',
            'gluten free' => 'Libre de Gluten',
            'pescetarian' => 'Pescetariana',
            'lacto-vegetarian' => 'Lacto-vegetariana',
            'ovo-vegetarian' => 'Ovo-vegetariana'
        ];

        $allergyLabels = [
            'dairy' => 'Lácteos',
            'egg' => 'Huevo',
            'gluten' => 'Gluten',
            'grain' => 'Granos',
            'peanut' => 'Maní',
            'seafood' => 'Pescado',
            'sesame' => 'Sésamo',
            'shellfish' => 'Mariscos',
            'soy' => 'Soya',
            'sulfite' => 'Sulfito',
            'tree nut' => 'Frutos secos',
            'wheat' => 'Trigo'
        ];

        $avatarUrl = '/assets/img/avatar_placeholder.jpg';
        $extensions = ['jpg', 'jpeg', 'png', 'webp'];
        foreach ($extensions as $ext) {
            $path = __DIR__ . '/../../public/uploads/avatars/user_' . $userId . '.' . $ext;
            if (file_exists($path)) {
                $avatarUrl = '/uploads/avatars/user_' . $userId . '.' . $ext . '?t=' . filemtime($path);
                break;
            }
        }

        View::render('Profile', [
            'userName' => $user ? $user['name'] : 'Usuario',
            'userEmail' => $user ? $user['email'] : '',
            'avatarUrl' => $avatarUrl,
            'userDiet' => $userDiet,
            'userDietLabel' => $dietLabels[$userDiet] ?? 'Sin dieta',
            'userAllergies' => $userAllergies,
            'userAllergyLabels' => array_map(fn($a) => $allergyLabels[$a] ?? $a, $userAllergies),
            'favoritesCount' => $favoritesCount,
            'plansCount' => $plansCount,
            'listsCount' => $listsCount,
            'recentFavorites' => $recentFavorites,
            'recentMealPreps' => $recentMealPreps,
            'recentShoppingLists' => $recentShoppingLists,
            'recentDietPlans' => $recentDietPlans,
            'success' => Session::getFlash('success'),
        ]);
    }

    public function uploadAvatar(): void
    {
        $userId = $this->requireAuthWeb();
        
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No se subió ningún archivo o hubo un error.'], 400);
            return;
        }
        
        $file = $_FILES['avatar'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($ext, $allowed, true)) {
            $this->json(['error' => 'Formato no permitido (solo JPG, PNG y WEBP).'], 400);
            return;
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            $this->json(['error' => 'La imagen no debe superar los 5MB.'], 400);
            return;
        }
        
        $uploadDir = __DIR__ . '/../../public/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($allowed as $allowedExt) {
            $oldFile = $uploadDir . '/user_' . $userId . '.' . $allowedExt;
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }
        
        $destPath = $uploadDir . '/user_' . $userId . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $this->json([
                'success' => true,
                'avatar_url' => '/uploads/avatars/user_' . $userId . '.' . $ext . '?t=' . time()
            ]);
        } else {
            $this->json(['error' => 'No se pudo guardar el archivo.'], 500);
        }
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

        if (!Session::validateCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Sesión expirada, reintentá.');
            $this->redirect('/perfil/editar');
        }

        $name = Validator::string($_POST['name'] ?? null, 1, 100);
        $email = Validator::email($_POST['email'] ?? null);

        if ($name === null) {
            Session::flash('error', 'El nombre es obligatorio.');
            $this->redirect('/perfil/editar');
        }

        if ($email === null) {
            Session::flash('error', 'El formato del email es inválido.');
            $this->redirect('/perfil/editar');
        }

        $validDiets = ['vegetarian', 'vegan', 'ketogenic', 'paleo', 'primal', 'whole30', 'gluten free', 'pescetarian', 'lacto-vegetarian', 'ovo-vegetarian'];
        $diet = Validator::inList($_POST['diet'] ?? null, $validDiets);

        $validAllergies = ['dairy', 'egg', 'gluten', 'grain', 'peanut', 'seafood', 'sesame', 'shellfish', 'soy', 'sulfite', 'tree nut', 'wheat'];
        $filteredIntolerances = Validator::stringArray($_POST['intolerances'] ?? [], $validAllergies);

        $userModel = new User();
        $existing = $userModel->findByEmail($email);
        if ($existing !== null && (int) $existing['id'] !== $userId) {
            Session::flash('error', 'Ese email ya está en uso.');
            $this->redirect('/perfil/editar');
        }

        $preferences = $diet;
        $allergies = json_encode(array_values($filteredIntolerances));

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $newPasswordVal = null;

        if ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '') {
            $user = $userModel->find($userId);
            if ($user === null) {
                Session::flash('error', 'Usuario no encontrado.');
                $this->redirect('/perfil/editar');
            }

            if (!password_verify((string) $currentPassword, $user['password'])) {
                Session::flash('error', 'La contraseña actual es incorrecta.');
                $this->redirect('/perfil/editar');
            }

            $newPassword = Validator::password($newPassword);
            if ($newPassword === null) {
                Session::flash('error', 'La nueva contraseña debe tener entre 8 y 255 caracteres.');
                $this->redirect('/perfil/editar');
            }

            if ($newPassword !== (is_string($confirmPassword) ? $confirmPassword : '')) {
                Session::flash('error', 'Las contraseñas no coinciden.');
                $this->redirect('/perfil/editar');
            }

            $newPasswordVal = $newPassword;
        }

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

        $this->log('info', 'Perfil actualizado', ['user_id' => $userId]);
        Session::flash('success', 'Perfil actualizado.');
        $this->redirect('/perfil');
    }
}
