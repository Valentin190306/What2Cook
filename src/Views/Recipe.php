<?php

$title  = isset($recipe['title']) ? "What2Cook - {$recipe['title']}" : "What2Cook - Receta";
$styles = ['receta'];
$scripts = ['favorites', 'shopping-lists', 'share'];

// Helpers
$readyIn  = $recipe['readyInMinutes'] ?? null;
$servings = $recipe['servings']       ?? null;
$image    = $recipe['image']          ?? null;
$summary  = $recipe['summary']        ?? '';
// Limpiar HTML del summary de Spoonacular
$summary  = strip_tags($summary);

$diets        = $recipe['diets']        ?? [];
$dishTypes    = $recipe['dishTypes']    ?? [];
$cuisines     = $recipe['cuisines']      ?? [];
$tags         = array_merge($dishTypes, $diets);

$ingredients  = $recipe['extendedIngredients'] ?? [];
$steps        = $recipe['analyzedInstructions'][0]['steps'] ?? [];

// Nutrición
$nutrients    = $recipe['nutrition']['nutrients'] ?? [];
$nutriMap     = [];
foreach ($nutrients as $n) {
    $nutriMap[$n['name']] = $n;
}

if (!empty($recipe)) {
    $metaDescription = mb_substr($summary, 0, 155) . '...';
    $ogImage = $image;
}

$scheme = (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '8080') ? 'http' : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
$host   = $_SERVER['HTTP_HOST'] ?? 'what2cook.app';
$baseUrl = "{$scheme}://{$host}";
?>

<?php if ($recipe === null): ?>
    <div style="text-align:center; padding:4rem;">
        <h1>Receta no encontrada</h1>
        <p>No se pudo cargar la información de esta receta.</p>
        <a href="/asistente-cocina">Volver al asistente</a>
    </div>
<?php else: ?>

<article class="receta-detalle">

    <!-- Schema: BreadcrumbList -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "Inicio", "item": "<?= htmlspecialchars($baseUrl) ?>/"},
            {"@type": "ListItem", "position": 2, "name": "Catálogo", "item": "<?= htmlspecialchars($baseUrl) ?>/recetas"},
            {"@type": "ListItem", "position": 3, "name": <?= json_encode($recipe['title'] ?? 'Receta', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>, "item": "<?= htmlspecialchars($baseUrl) ?>/receta/<?= (int) ($recipe['id'] ?? 0) ?>"}
        ]
    }
    </script>

    <!-- Schema: Recipe -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Recipe",
        "name": <?= json_encode($recipe['title'] ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        "image": <?= json_encode($image, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        "description": <?= json_encode(mb_substr($summary, 0, 400), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        <?php if ($readyIn): ?>"totalTime": "PT<?= (int) $readyIn ?>M",<?php endif; ?>
        <?php if ($servings): ?>"recipeYield": "<?= (int) $servings ?>",<?php endif; ?>
        <?php if (!empty($cuisines)): ?>"recipeCuisine": <?= json_encode($cuisines, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,<?php endif; ?>
        "recipeCategory": <?= json_encode($dishTypes, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        "nutrition": {
            "@type": "NutritionInformation",
            "calories": "<?= round($nutriMap['Calories']['amount'] ?? 0) ?>",
            "proteinContent": "<?= round($nutriMap['Protein']['amount'] ?? 0) ?> g",
            "carbohydrateContent": "<?= round($nutriMap['Carbohydrates']['amount'] ?? 0) ?> g",
            "fatContent": "<?= round($nutriMap['Fat']['amount'] ?? 0) ?> g"
        },
        "recipeIngredient": <?= json_encode(array_map(fn($i) => $i['original'] ?? $i['name'], $ingredients), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        "recipeInstructions": <?= json_encode(array_map(fn($s, $i) => [
            '@type' => 'HowToStep',
            'position' => $i + 1,
            'text' => $s['step']
        ], $steps, array_keys($steps)), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
    }
    </script>

    <!-- ── Imagen + título ── -->
    <div class="receta-hero">
        <?php if ($image): ?>
            <img class="receta-img" src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>">
        <?php else: ?>
            <img class="receta-img" src="/assets/img/placeholder_RecetaSinFoto.png" alt="Sin imagen">
        <?php endif; ?>

        <button type="button" class="btn-favorito"
                aria-label="<?= !empty($isFavorite) ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>"
                data-fav-toggle
                data-spoonacular-id="<?= (int) $id ?>"
                data-title="<?= htmlspecialchars($recipe['title'] ?? '') ?>"
                data-image="<?= htmlspecialchars($image ?? '') ?>"
                data-favorited="<?= !empty($isFavorite) ? 'true' : 'false' ?>">
            <?= !empty($isFavorite) ? '♥' : '♡' ?>
        </button>
    </div>

    <div class="receta-content">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h1 style="margin: 0;"><?= htmlspecialchars($recipe['title']) ?></h1>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn-save-list no-print btn-share-recipe" id="btn-share-recipe"
                        aria-label="Compartir receta"
                        data-share-title="<?= htmlspecialchars($recipe['title'] ?? '') ?>"
                        data-share-text="<?= htmlspecialchars('¡Echa un vistazo a esta receta! "' . ($recipe['title'] ?? 'Receta') . '"' . "\n" . $baseUrl . '/receta/' . (int) ($recipe['id'] ?? 0)) ?>">
                    <svg class="share-svg" aria-hidden="true" viewBox="0 0 24 24" width="18" height="18">
                        <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/>
                    </svg>
                    Compartir
                </button>
                <button type="button" class="btn-save-list no-print btn-copy-share" id="btn-copy-recipe" aria-label="Copiar receta al portapapeles"
                        data-copy-text="<?= htmlspecialchars($baseUrl . '/receta/' . (int) ($recipe['id'] ?? 0)) ?>">
                    <svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18">
                        <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                    </svg>
                </button>
                <button type="button" class="btn-save-list no-print" onclick="window.print()" style="margin: 0;">
                    Imprimir receta
                </button>
            </div>
        </div>

        <!-- ── Meta ── -->
        <dl class="receta-meta">
            <?php if ($readyIn): ?>
            <dt>
                <svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
                Tiempo
            </dt>
            <dd><?= $readyIn ?> min</dd>
            <?php endif; ?>

            <?php if ($servings): ?>
            <dt>
                <svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                Porciones
            </dt>
            <dd id="servings-count"><?= $servings ?></dd>
            <?php endif; ?>

            <dt class="likes-meta-dt">
                <svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20" style="fill: var(--color-carrot);"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                Me gusta
            </dt>
            <dd id="likes-count-val"><?= (int) ($likesCount ?? 0) ?></dd>
        </dl>

        <!-- ── Tags ── -->
        <?php if (!empty($tags)): ?>
        <ul class="receta-tags">
            <?php foreach (array_unique($tags) as $tag): ?>
                <li><?= htmlspecialchars(ucfirst($tag)) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <!-- ── Descripción ── -->
        <?php if ($summary): ?>
        <section class="descripcion">
            <h2>Descripción</h2>
            <p><?= htmlspecialchars(mb_substr($summary, 0, 400)) ?>...</p>
        </section>
        <?php endif; ?>

        <!-- ── Macros ── -->
        <?php if (!empty($nutriMap)): ?>
        <section class="receta-macros">
            <h2>Información nutricional</h2>
            <table class="macros-table">
                <thead>
                    <tr><th>Kcal</th><th>Proteína</th><th>Carbs</th><th>Grasa</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= round($nutriMap['Calories']['amount']     ?? 0) ?></td>
                        <td><?= round($nutriMap['Protein']['amount']      ?? 0) ?>g</td>
                        <td><?= round($nutriMap['Carbohydrates']['amount']?? 0) ?>g</td>
                        <td><?= round($nutriMap['Fat']['amount']          ?? 0) ?>g</td>
                    </tr>
                </tbody>
            </table>
        </section>
        <?php endif; ?>
    </div>

    <!-- ── Ingredientes ── -->
    <?php if (!empty($ingredients)): ?>
    <section class="ingredientes">
        <div class="ingredientes-header">
            <h2>Ingredientes</h2>
            <button type="button" class="btn-save-list" id="btn-save-list" 
                    data-sl-save 
                    data-source-type="recipe" 
                    data-source-id="<?= (int) $id ?>">
                <svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14c-2.33 0-7-1.17-7-3.5V19h14v-2.5c0 2.33-4.67 3.5-7 3.5z"/>
                </svg>
                Guardar lista
            </button>
        </div>
        <fieldset class="porciones">
            <legend>Porciones:</legend>
            <button type="button" id="btn-menos" aria-label="Reducir porciones">−</button>
            <output id="servings-display"><?= $servings ?? 1 ?></output>
            <button type="button" id="btn-mas" aria-label="Aumentar porciones">+</button>
        </fieldset>
        <ul id="ingredients-list" data-base-servings="<?= $servings ?? 1 ?>">
            <?php foreach ($ingredients as $ing): ?>
            <li>
                <?php if (!empty($ing['image'])): ?>
                    <img src="https://img.spoonacular.com/ingredients_100x100/<?= htmlspecialchars($ing['image']) ?>" alt="<?= htmlspecialchars($ing['name']) ?>" width="40">
                <?php endif; ?>
                <span class="nombre"><?= htmlspecialchars(ucfirst($ing['name'])) ?></span>
                <span class="cantidad" data-amount="<?= $ing['amount'] ?>" data-unit="<?= htmlspecialchars($ing['unit']) ?>">
                    <?= round($ing['amount'], 2) ?> <?= htmlspecialchars($ing['unit']) ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- ── Preparación ── -->
    <?php if (!empty($steps)): ?>
    <section class="preparacion">
        <h2>Preparación</h2>
        <ol>
            <?php foreach ($steps as $step): ?>
                <li><?= htmlspecialchars($step['step']) ?></li>
            <?php endforeach; ?>
        </ol>
    </section>
    <?php endif; ?>

</article>

<script>
document.addEventListener('DOMContentLoaded', function () {

// Ajuste de porciones y conversión de unidades
(function () {
    const baseServings = parseInt(document.getElementById('ingredients-list').dataset.baseServings, 10);
    let current = baseServings;
    var currentUnitSystem = UnitPreferences.getPreferredSystem();

    const display = document.getElementById('servings-display');
    const items = document.querySelectorAll('#ingredients-list .cantidad');

    function update() {
        display.textContent = current;
        const ratio = current / baseServings;

        items.forEach(item => {
            const base = parseFloat(item.dataset.amount);
            const originalUnit = item.dataset.unit || '';
            const isVolume = UnitConversion.isVolumeUnit(originalUnit);
            
            const scaledAmount = base * ratio;
            
            const baseAmount = UnitConversion.convertToBase(scaledAmount, originalUnit);
            const converted = UnitConversion.getBestUnit(baseAmount, currentUnitSystem, isVolume);
            
            item.textContent = UnitConversion.roundValue(converted.amount) + ' ' + converted.unit;
        });
    }

    document.getElementById('btn-menos').addEventListener('click', () => {
        if (current > 1) { current--; update(); }
    });

    document.getElementById('btn-mas').addEventListener('click', () => {
        current++;
        update();
    });

    UnitPreferences.onSystemChange(function (system) {
        currentUnitSystem = system;
        update();
    });

    update();
})();

// Conversión de la tabla de macros (Información nutricional)
(function () {
    var system = UnitPreferences.getPreferredSystem();
    var table = document.querySelector('.macros-table tbody tr');
    if (!table) return;

    var cells = table.querySelectorAll('td');
    if (cells.length < 4) return;

    var kcal = parseInt(cells[0].textContent) || 0;
    var protein = parseFloat(cells[1].textContent) || 0;
    var carbs = parseFloat(cells[2].textContent) || 0;
    var fat = parseFloat(cells[3].textContent) || 0;

    function updateNutrition(sys) {
        if (sys === 'metric') {
            cells[1].textContent = Math.round(protein) + 'g';
            cells[2].textContent = Math.round(carbs) + 'g';
            cells[3].textContent = Math.round(fat) + 'g';
        } else {
            var p = UnitConversion.convertAmount(protein, 'g', sys);
            var c = UnitConversion.convertAmount(carbs, 'g', sys);
            var f = UnitConversion.convertAmount(fat, 'g', sys);
            cells[1].textContent = UnitConversion.roundValue(p.amount) + ' ' + p.unit;
            cells[2].textContent = UnitConversion.roundValue(c.amount) + ' ' + c.unit;
            cells[3].textContent = UnitConversion.roundValue(f.amount) + ' ' + f.unit;
        }
    }

    updateNutrition(system);

    UnitPreferences.onSystemChange(function (sys) {
        updateNutrition(sys);
    });
})();

});

</script>
<?php endif; ?>