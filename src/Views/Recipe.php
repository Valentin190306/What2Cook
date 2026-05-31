<?php

$title  = isset($recipe['title']) ? "What2Cook - {$recipe['title']}" : "What2Cook - Receta";
$styles = ['receta'];
$scripts = ['favorites'];

// Helpers
$readyIn  = $recipe['readyInMinutes'] ?? null;
$servings = $recipe['servings']       ?? null;
$image    = $recipe['image']          ?? null;
$summary  = $recipe['summary']        ?? '';
// Limpiar HTML del summary de Spoonacular
$summary  = strip_tags($summary);

$diets        = $recipe['diets']        ?? [];
$dishTypes    = $recipe['dishTypes']    ?? [];
$tags         = array_merge($dishTypes, $diets);

$ingredients  = $recipe['extendedIngredients'] ?? [];
$steps        = $recipe['analyzedInstructions'][0]['steps'] ?? [];

// Nutrición
$nutrients    = $recipe['nutrition']['nutrients'] ?? [];
$nutriMap     = [];
foreach ($nutrients as $n) {
    $nutriMap[$n['name']] = $n;
}
?>

<?php if ($recipe === null): ?>
    <div style="text-align:center; padding:4rem;">
        <h1>Receta no encontrada</h1>
        <p>No se pudo cargar la información de esta receta.</p>
        <a href="/asistente-cocina">Volver al asistente</a>
    </div>
<?php else: ?>

<article class="receta-detalle">

    <!-- ── Imagen + título ── -->
    <div class="receta-hero">
        <?php if ($image): ?>
            <img class="receta-img" src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>">
        <?php else: ?>
            <img class="receta-img" src="/assets/img/placeholder.jpg" alt="Sin imagen">
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
        <h1><?= htmlspecialchars($recipe['title']) ?></h1>

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
        <h2>Ingredientes</h2>
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
                <span class="cantidad" data-amount="<?= $ing['amount'] ?>">
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
// Ajuste de porciones
(function () {
    const baseServings = parseInt(document.getElementById('ingredients-list').dataset.baseServings, 10);
    let current = baseServings;

    const display = document.getElementById('servings-display');
    const items   = document.querySelectorAll('#ingredients-list .cantidad');

    function update() {
        display.textContent = current;
        const ratio = current / baseServings;
        items.forEach(item => {
            const base = parseFloat(item.dataset.amount);
            const unit = item.textContent.trim().replace(/^[\d.]+\s*/, '');
            item.textContent = (Math.round(base * ratio * 100) / 100) + ' ' + unit;
        });
    }

    document.getElementById('btn-menos').addEventListener('click', () => {
        if (current > 1) { current--; update(); }
    });
    document.getElementById('btn-mas').addEventListener('click', () => {
        current++;
        update();
    });
})();
</script>
<?php endif; ?>