<?php
$title = 'Registrarse - What2Cook';
$styles = ['components'];
?>
<section class="form-panel">
    <h1>Registrarse</h1>
    <p>Creá tu cuenta gratis y empezá a planificar tus comidas.</p>

    <form action="/register" method="POST">
        <div class="form-field">
            <label for="name">Nombre</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-field">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Crear cuenta</button>
        </div>
    </form>
    <p>¿Ya tenés cuenta? <a href="/login">Iniciá sesión aquí</a></p>
</section>
