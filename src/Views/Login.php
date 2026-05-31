<?php
$title = 'Iniciar Sesión - What2Cook';
$styles = ['components'];
?>
<section class="form-panel">
    <h1>Iniciar sesión</h1>
    <p>Ingresá tus credenciales para acceder a tu cuenta.</p>

    <?php if (!empty($error)): ?>
        <p class="form-error" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="/login" method="POST">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
        <div class="form-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-field">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Entrar</button>
        </div>
    </form>
    <p class="form-link">¿No tenés cuenta? <a href="/register">Registrate aquí</a></p>
</section>
