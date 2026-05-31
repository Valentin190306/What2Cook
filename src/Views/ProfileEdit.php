<?php
$title = 'Editar Perfil - What2Cook';
$styles = ['components'];

$diets = [
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

$allergiesList = [
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

$userAllergies = [];
if (!empty($user['allergies'])) {
    $decoded = json_decode($user['allergies'], true);
    if (is_array($decoded)) {
        $userAllergies = $decoded;
    }
}
?>

<section class="form-panel">
    <h1>Editar Perfil</h1>
    <p>Actualizá tus datos personales y preferencias de alimentación.</p>

    <?php if (!empty($error)): ?>
        <p class="form-error" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="/perfil/editar" method="POST">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">

        <div class="form-field">
            <label for="name">Nombre</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="diet">Dieta de preferencia</label>
            <select id="diet" name="diet">
                <?php foreach ($diets as $val => $label): ?>
                    <option value="<?= htmlspecialchars($val) ?>" <?= ($user['preferences'] ?? '') === $val ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label>Intolerancias / Alergias</label>
            <?php foreach ($allergiesList as $val => $label): ?>
                <label>
                    <input type="checkbox" name="intolerances[]" value="<?= htmlspecialchars($val) ?>" <?= in_array($val, $userAllergies, true) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <hr style="border: 0; border-top: 1px dashed var(--color-border); margin: 1.5rem 0;">

        <h2>Cambiar Contraseña</h2>
        <p style="text-align: left; font-size: 0.9rem; margin-bottom: 1rem;">
            Dejá estos campos vacíos si no deseás cambiar tu contraseña actual.
        </p>

        <div class="form-field">
            <label for="current_password">Contraseña Actual</label>
            <input type="password" id="current_password" name="current_password" placeholder="Tu contraseña actual">
        </div>

        <div class="form-field">
            <label for="new_password">Nueva Contraseña</label>
            <input type="password" id="new_password" name="new_password" placeholder="Mínimo 8 caracteres">
        </div>

        <div class="form-field">
            <label for="confirm_password">Confirmar Nueva Contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repetí la nueva contraseña">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Guardar cambios</button>
        </div>
    </form>
    
    <div class="form-link">
        <a href="/perfil">Volver al perfil</a>
    </div>
</section>
