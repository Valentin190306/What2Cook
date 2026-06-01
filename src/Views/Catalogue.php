<?php
$title = 'Catálogo de Recetas - What2Cook';
$styles = ['catalogoRecetas'];
$scripts = ['favorites'];
?>
<section class="catalogo-header">
    <h1>Catálogo de Recetas</h1>
    <p>Explorá nuestra colección de recetas deliciosas y saludables.</p>
</section>

<form class="search-bar" action="/recetas" method="GET" autocomplete="off">
    <input type="search" name="query" placeholder="Buscar..." value="<?= htmlspecialchars($query ?? '') ?>">
    <button type="submit" aria-label="Buscar recetas"></button>
</form>

<section class="filtros-panel">
    <fieldset class="filtros">
        <legend>Categoría</legend>
        <div class="filtros-opciones">
            <?php
            $types = [
                '' => 'Todas',
                'breakfast' => 'Breakfast',
                'lunch' => 'Lunch',
                'dinner' => 'Dinner',
                'dessert' => 'Dessert',
                'snack' => 'Snack',
                'soup' => 'Soup',
                'salad' => 'Salad',
                'main course' => 'Main course',
                'appetizer' => 'Appetizer',
            ];
            foreach ($types as $value => $label): ?>
                <label>
                    <input type="radio" name="type" value="<?= htmlspecialchars($value) ?>" <?= ($type ?? '') === $value ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <fieldset class="filtros">
        <legend>Cocina</legend>
        <div class="filtros-opciones">
            <?php
            $cuisines = [
                '' => 'Todas',
                'italian' => 'Italian',
                'mexican' => 'Mexican',
                'asian' => 'Asian',
                'american' => 'American',
                'mediterranean' => 'Mediterranean',
                'french' => 'French',
                'thai' => 'Thai',
                'spanish' => 'Spanish',
            ];
            foreach ($cuisines as $value => $label): ?>
                <label>
                    <input type="radio" name="cuisine" value="<?= htmlspecialchars($value) ?>" <?= ($cuisine ?? '') === $value ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <fieldset class="filtros">
        <legend>Dieta</legend>
        <div class="filtros-opciones">
            <?php
            $diets = [
                '' => 'Todas',
                'keto' => 'Keto',
                'vegan' => 'Vegan',
                'vegetarian' => 'Vegetarian',
                'gluten-free' => 'Gluten-free',
                'paleo' => 'Paleo',
                'primal' => 'Primal',
                'whole30' => 'Whole30',
                'lacto-vegetarian' => 'Lacto-vegetarian',
                'ovo-vegetarian' => 'Ovo-vegetarian',
                'pescatarian' => 'Pescatarian',
            ];
            foreach ($diets as $value => $label): ?>
                <label>
                    <input type="radio" name="diet" value="<?= htmlspecialchars($value) ?>" <?= ($diet ?? '') === $value ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <div class="filtros-actions">
        <button type="submit" class="btn-apply">Aplicar filtros</button>
        <a href="/recetas" class="btn-clear">Limpiar filtros</a>
    </div>
</section>

<?php if (!empty($errorMessage)): ?>
    <div class="results-message results-error">
        <p>No se pudo obtener recetas de Spoonacular: <?= htmlspecialchars($errorMessage) ?></p>
    </div>
<?php endif; ?>

<?php if (empty($recipes)): ?>
    <div class="results-message">
        <p>No encontramos recetas para estos filtros. Probá otra combinación o quitá algún filtro.</p>
    </div>
<?php else: ?>
    <div class="results-summary">
        <p><?= number_format(count($recipes), 0, ',', '.') ?> recetas mostrando página <?= $page ?> de <?= $totalPages ?> (<?= number_format($totalResults, 0, ',', '.') ?> resultados totales)</p>
    </div>

    <div class="recipe-grid">
        <?php foreach ($recipes as $recipe):
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
            $isFavorite = in_array($id, $favoriteIds, true);
        ?>
        <article onclick="window.location='/receta/<?= $id ?>'" role="link" tabindex="0">
            <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($recipe['title'] ?? 'Receta') ?>">
            <button type="button" class="btn-favorito" data-fav-toggle data-spoonacular-id="<?= $id ?>" data-title="<?= htmlspecialchars($recipe['title'] ?? '') ?>" data-image="<?= htmlspecialchars($image) ?>" data-favorited="<?= $isFavorite ? 'true' : 'false' ?>" aria-label="<?= $isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>">
                <?= $isFavorite ? '♥' : '♡' ?>
            </button>
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

    <nav class="pagination-controls" aria-label="Paginación de recetas">
        <?php
        $baseParams = array_filter([
            'query' => $query ?? '',
            'cuisine' => $cuisine ?? '',
            'type' => $type ?? '',
            'diet' => $diet ?? '',
        ], static fn($value) => $value !== '');
        $prevUrl = '/recetas?' . http_build_query(array_merge($baseParams, ['page' => max(1, $page - 1)]));
        $nextUrl = '/recetas?' . http_build_query(array_merge($baseParams, ['page' => min($totalPages, $page + 1)]));
        ?>

        <a href="<?= $prevUrl ?>" class="pagination-button<?= $page <= 1 ? ' disabled' : '' ?>">← Anterior</a>
        <span class="page-info">Página <?= $page ?> de <?= $totalPages ?></span>
        <a href="<?= $nextUrl ?>" class="pagination-button<?= $page >= $totalPages ? ' disabled' : '' ?>">Siguiente →</a>
    </nav>
<?php endif; ?>
