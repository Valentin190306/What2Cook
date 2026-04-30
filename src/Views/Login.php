<?php
$title = 'Iniciar Sesión - What2Cook';
$styles = ['components'];
?>
<section class="form-panel">
    <h1>Iniciar sesión</h1>
    <p>Ingresá tus credenciales para acceder a tu cuenta.</p>

    <form action="/login" method="POST">
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
    <p>¿No tenés cuenta? <a href="/register">Registrate aquí</a></p>
</section>
