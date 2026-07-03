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
    
    <div class="form-divider">
        <span>o</span>
    </div>
    
    <a href="/auth/google" class="btn-google">
        <svg viewBox="0 0 24 24" width="20" height="20">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Continuar con Google
    </a>
    
    <p class="form-link">¿No tenés cuenta? <a href="/register">Registrate aquí</a></p>
</section>
