<?php

$title = 'Mis Favoritos - What2Cook';
$styles = ['catalogoRecetas', 'receta', 'favoritos'];
$scripts = ['favorites'];

?>

<section class="catalogo-header">
    <h1>Mis Favoritos</h1>
    <p>Tus recetas y meal preps guardados.</p>
</section>

<?php if (empty($favorites) && empty($mealPrepFavorites)): ?>
    <div class="form-panel">
        <h1>No tenés favoritos</h1>
        <p>Explorá recetas en el asistente de cocina para agregarlas a tu lista.</p>
        <a href="/asistente-cocina" class="btn btn-carrot">Ir al Asistente</a>
    </div>
<?php else: ?>
    <?php if (!empty($favorites)): ?>
    <h2 class="section-title">Recetas</h2>
    <div class="recipe-grid">
        <?php foreach ($favorites as $fav): ?>
            <article onclick="window.location='/receta/<?= (int) $fav['spoonacular_id'] ?>'" role="link" tabindex="0">
                <img src="<?= htmlspecialchars($fav['image'] ?: '/assets/img/placeholder.jpg') ?>" alt="<?= htmlspecialchars($fav['title']) ?>">
                
                <div>
                    <button type="button" class="btn-favorito"
                            aria-label="Quitar de favoritos"
                            data-fav-toggle data-fav-remove-card
                            data-spoonacular-id="<?= (int) $fav['spoonacular_id'] ?>"
                            data-title="<?= htmlspecialchars($fav['title']) ?>"
                            data-image="<?= htmlspecialchars($fav['image'] ?? '') ?>"
                            data-favorited="true">♥</button>
                </div>
                
                <h2><?= htmlspecialchars($fav['title']) ?></h2>
                
                <a class="recipe-link" href="/receta/<?= (int) $fav['spoonacular_id'] ?>" aria-hidden="true" tabindex="-1"></a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($mealPrepFavorites)): ?>
    <h2 class="section-title">Meal Preps</h2>
    <div class="recipe-grid">
        <?php foreach ($mealPrepFavorites as $mp): ?>
            <article class="mealprep-card">
                <div class="mealprep-info">
                    <h2>Meal Prep</h2>
                    <p><?= count(json_decode($mp['ingredients'], true) ?? []) ?> ingredientes</p>
                    <p class="date"><?= date('d/m/Y', strtotime($mp['created_at'])) ?></p>
                </div>
                <button type="button" class="btn-favorito"
                        aria-label="Quitar de favoritos"
                        data-mp-fav-toggle
                        data-meal-prep-data="<?= htmlspecialchars(json_encode([
                            'ingredients' => json_decode($mp['ingredients'], true) ?? [],
                            'recipe_ids' => json_decode($mp['recipe_ids'], true) ?? [],
                            'servings' => json_decode($mp['servings'], true) ?? []
                        ])) ?>"
                        data-favorited="true">♥</button>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
