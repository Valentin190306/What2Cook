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
            <?php 
                $ingredients = json_decode($mp['ingredients'], true) ?? [];
                $recipeIds = json_decode($mp['recipe_ids'], true) ?? [];
                $servings = json_decode($mp['servings'], true) ?? [];
                $recipeCount = count($recipeIds);
            ?>
            <article class="mealprep-card" onclick="window.location='/asistente-cocina?meal_prep=<?= (int) $mp['id'] ?>'" role="link" tabindex="0">
                <div class="mealprep-card__header">
                    <span class="mealprep-card__badge">Meal Prep</span>
                    <button type="button" class="btn-favorito"
                            aria-label="Quitar de favoritos"
                            data-mp-fav-toggle
                            data-fav-remove-card
                            data-meal-prep-data="<?= htmlspecialchars(json_encode([
                                'ingredients' => $ingredients,
                                'recipe_ids' => $recipeIds,
                                'servings' => $servings
                            ])) ?>"
                            data-favorited="true">♥</button>
                </div>
                
                <div class="mealprep-card__body">
                    <div class="mealprep-card__icon-wrapper">
                        <svg class="mealprep-card__icon" viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="mealprep-card__title"><?= $recipeCount ?> <?= $recipeCount === 1 ? 'Receta' : 'Recetas' ?></h3>
                    <p class="mealprep-card__date">Guardado el <?= date('d/m/Y', strtotime($mp['created_at'])) ?></p>
                    
                    <?php if (!empty($ingredients)): ?>
                        <div class="mealprep-card__ingredients">
                            <strong>Ingredientes:</strong>
                            <div class="mealprep-card__chips">
                                <?php foreach ($ingredients as $ing): ?>
                                    <span class="mealprep-card__chip"><?= htmlspecialchars($ing) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mealprep-card__footer">
                    <span class="mealprep-card__link-text">Ver Meal Prep →</span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
