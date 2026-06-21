<?php

$title = 'Mis Favoritos - What2Cook';
$styles = ['catalogoRecetas', 'receta', 'perfil', 'favoritos'];
$scripts = ['favorites', 'meal-prep-modal'];

?>

<section class="catalogo-header">
    <h1>Mis Favoritos</h1>
    <p>Tus recetas y meal preps guardados.</p>
</section>

<?php if (empty($favorites) && empty($mealPrepFavorites)): ?>
    <article class="empty-state">
        <p>Aún no tenés favoritos guardados.</p>
        <a href="/asistente-cocina" class="btn-link">Ir al Asistente</a>
    </article>
<?php else: ?>
    <?php if (!empty($favorites)): ?>
    <h2 class="section-title">Recetas</h2>
    <div class="recipe-grid">
        <?php foreach ($favorites as $recipe): 
            $id = (int) ($recipe['id'] ?? 0);
            $image = $recipe['image'] ?? '/assets/img/placeholder.jpg';
            $readyIn = $recipe['readyInMinutes'] ?? null;
            $servings = $recipe['servings'] ?? null;
            $diets = $recipe['diets'] ?? [];
            $dishTypes = $recipe['dishTypes'] ?? [];
            $tags = array_slice(array_merge($dishTypes, $diets), 0, 3);
            $nutrition = $recipe['nutrition']['nutrients'] ?? [];
            $nutritionMap = [];
            foreach ($nutrition as $nutrient) {
                if (!empty($nutrient['name'])) {
                    $nutritionMap[$nutrient['name']] = $nutrient;
                }
            }
        ?>
            <article onclick="if (!event.target.closest('button')) window.location='/receta/<?= $id ?>'" role="link" tabindex="0">
                <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($recipe['title'] ?? 'Receta') ?>">
                
                <button type="button" class="btn-favorito"
                        aria-label="Quitar de favoritos"
                        data-fav-toggle data-fav-remove-card
                        data-spoonacular-id="<?= $id ?>"
                        data-title="<?= htmlspecialchars($recipe['title'] ?? '') ?>"
                        data-image="<?= htmlspecialchars($image) ?>"
                        data-favorited="true">♥</button>
                
                <h2><?= htmlspecialchars($recipe['title'] ?? 'Sin título') ?></h2>
                
                <div class="recipe-meta">
                    <?php if ($readyIn !== null): ?><span class="recipe-time">Tiempo: <?= htmlspecialchars((string) $readyIn) ?> min</span><?php endif; ?>
                    <?php if ($servings !== null): ?><span>Porciones: <?= htmlspecialchars((string) $servings) ?></span><?php endif; ?>
                </div>

                <div class="recipe-tags">
                    <?php foreach ($tags as $tag): ?>
                        <span><?= htmlspecialchars(ucfirst($tag)) ?></span>
                    <?php endforeach; ?>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Kcal</th>
                            <th>Proteína</th>
                            <th>Carbs</th>
                            <th>Grasa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= round($nutritionMap['Calories']['amount'] ?? 0) ?></td>
                            <td><?= round($nutritionMap['Protein']['amount'] ?? 0) ?>g</td>
                            <td><?= round($nutritionMap['Carbohydrates']['amount'] ?? 0) ?>g</td>
                            <td><?= round($nutritionMap['Fat']['amount'] ?? 0) ?>g</td>
                        </tr>
                    </tbody>
                </table>
                
                <a class="recipe-link" href="/receta/<?= $id ?>" aria-hidden="true" tabindex="-1"></a>
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
            <article class="mealprep-card" data-mp-id="<?= (int) $mp['id'] ?>" onclick="if (!event.target.closest('button')) openMealPrepModal(<?= (int) $mp['id'] ?>)" role="link" tabindex="0">
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
                    <span class="mealprep-card__link-text">Ver recetas →</span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Modal de detalle de Meal Prep -->
<div class="modal-overlay" id="mp-modal" aria-hidden="true" role="dialog" aria-label="Detalle del Meal Prep">
  <div class="modal-card modal-card--wide">
    <div class="modal-card__header">
      <h2 id="mp-modal-title">Meal Prep</h2>
      <button type="button" class="btn-close-modal" id="mp-modal-close" aria-label="Cerrar">&times;</button>
    </div>
    <div class="modal-card__body" id="mp-modal-body">
      <div class="mp-ingredients" id="mp-ingredients"></div>
      <div class="recipe-grid" id="mp-recipes"></div>
    </div>
  </div>
</div>
