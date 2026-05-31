<?php
$title = 'Mi Perfil - What2Cook';
$styles = ['perfil'];
?>
<?php if (!empty($success)): ?>
    <p class="form-success" role="status"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<section class="profile-hero">
    <img src="/assets/img/avatar_placeholder.jpg" alt="Avatar de Usuario" class="avatar">
    <p>Bienvenido</p>
    <h1><?= htmlspecialchars($userName ?? 'Usuario') ?></h1>
    <a href="/perfil/editar" class="btn-edit">Editar Perfil</a>
</section>

<section id="dashboard" class="profile-section">
    <h2>Dashboard</h2>
    <div class="grid-container">
        <article class="stat-card">
            <h3>Recetas Favoritas</h3>
            <span class="stat-number"><?= (int) ($favoritesCount ?? 0) ?></span>
        </article>
        <article class="stat-card">
            <h3>Planes Creados</h3>
            <span class="stat-number"><?= (int) ($plansCount ?? 0) ?></span>
        </article>
        <article class="stat-card">
            <h3>Listas de Compras</h3>
            <span class="stat-number"><?= (int) ($listsCount ?? 0) ?></span>
        </article>
    </div>
</section>

<section id="quick-actions" class="profile-section">
    <h2>Acciones Rápidas</h2>
    <div class="grid-container">
        <a href="/recetas" class="action-card">
            <h3>Explorar Recetas</h3>
            <p>Descubrí qué cocinar hoy con nuestro catálogo</p>
        </a>
        <a href="/asistente-dieta" class="action-card">
            <h3>Crear un Plan</h3>
            <p>Organizá tus comidas de la semana</p>
        </a>
        <a href="/asistente-cocina" class="action-card">
            <h3>Consultar al Asistente</h3>
            <p>Obtené sugerencias personalizadas de cocina y dieta</p>
        </a>
    </div>
</section>

<section id="recent-recipes" class="profile-section">
    <h2>Recetas Recientes</h2>
    <p>Tus últimas recetas favoritas</p>
    <?php if (empty($recentFavorites)): ?>
        <article class="empty-state">
            <p>Aún no tenés recetas favoritas</p>
            <a href="/recetas" class="btn-link">Explorar recetas</a>
        </article>
    <?php else: ?>
        <div class="grid-container">
            <?php foreach ($recentFavorites as $fav): ?>
                <a href="/receta/<?= (int) $fav['spoonacular_id'] ?>" class="action-card">
                    <h3><?= htmlspecialchars($fav['title']) ?></h3>
                    <p>Hacé clic para ver los detalles de la receta.</p>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section id="meal-plans" class="profile-section">
    <h2>Planes de Comidas</h2>
    <p>Tus planificaciones semanales</p>
    <article class="empty-state">
        <p>Aún no tenés planes creados</p>
        <a href="/asistente-dieta" class="btn-link">Crear plan</a>
    </article>
</section>

<section id="shopping-lists" class="profile-section">
    <h2>Listas de Compras</h2>
    <p>Tus listas de ingredientes a comprar</p>
    <article class="empty-state">
        <p>Aún no tenés listas de compras</p>
        <a href="/asistente-cocina" class="btn-link">Crear lista</a>
    </article>
</section>
