<?php
$title = 'Iniciar Sesión - What2Cook';
$styles = ['components'];
$scripts = ['auth'];
$noindex = true;
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
        <div class="form-field remember-me-field">
            <label class="checkbox-label">
                <input type="checkbox" name="remember_me" id="remember_me">
                <span>Recordarme</span>
            </label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Entrar</button>
        </div>
    </form>
    <p class="form-link">¿No tenés cuenta? <a href="/register">Registrate aquí</a></p>
</section>
