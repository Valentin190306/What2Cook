<?php
$title = 'Registrarse - What2Cook';
$styles = ['components'];
$scripts = ['auth'];
$noindex = true;
?>
<section class="form-panel">
    <h1>Registrarse</h1>
    <p>Creá tu cuenta gratis y empezá a planificar tus comidas.</p>

    <?php if (!empty($error)): ?>
        <p class="form-error" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="/register" method="POST">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
        <div class="form-field">
            <label for="name">Nombre</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-field password-wrapper">
            <label for="password">Contraseña</label>
            <div class="input-toggle-wrapper">
                <input type="password" id="password" name="password" required>
                <button type="button" class="btn-toggle-password" aria-label="Mostrar contraseña" tabIndex="-1">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
        </div>
        <div class="form-field password-wrapper">
            <label for="password_confirm">Confirmar contraseña</label>
            <div class="input-toggle-wrapper">
                <input type="password" id="password_confirm" name="password_confirm" required>
                <button type="button" class="btn-toggle-password" aria-label="Mostrar contraseña" tabIndex="-1">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Crear cuenta</button>
        </div>
    </form>
    <p class="form-link">¿Ya tenés cuenta? <a href="/login">Iniciá sesión aquí</a></p>
</section>
