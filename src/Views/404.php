<?php
$title = '404 - No encontrado';
$styles = [];
?>
<section class="error-page">
    <h1>Error 404</h1>
    <h2>Página no encontrada</h2>
    <p><?= $message ?? 'Lo sentimos, la página que estás buscando no existe.' ?></p>
    <a href="/" class="btn-primary">Volver al inicio</a>
</section>

<style>
.error-page {
    text-align: center;
    padding: 100px 20px;
}
.error-page h1 {
    font-size: 80px;
    margin-bottom: 0;
    color: var(--color-carrot);
}
.error-page h2 {
    font-size: 30px;
    margin-top: 0;
}
</style>
