<?php
$title = 'Mis Planes - What2Cook';
$styles = ['perfil','carousel','receta'];
$scripts = ['api','plans','carousel','print'];
$noindex = true;

// Auxiliar para recuperar detalles de recetas para la impresión (con fallback y caché)
if (!function_exists('getRecipeDetailsForPrint')) {
    function getRecipeDetailsForPrint(int $recipeId): ?array {
        $storage = new \App\Services\RecipeStorageService();
        $recipe = $storage->findLocal($recipeId);
        if ($recipe === null) {
            try {
                $service = new \App\Services\SpoonacularService(null);
                $recipeData  = $service->getRecipeInfo($recipeId, true, false);
                (new \App\Models\RecipeTranslation())->saveRaw($recipeId, $recipeData);
                $recipe = $storage->findLocal($recipeId);
            } catch (\Throwable $e) {
                $recipe = null;
            }
        }
        return $recipe;
    }
}
?>

<style>
    .print-cover,
    .print-only-recipes {
        display: none;
    }
</style>

<section class="profile-section">
    <h2>Mis Planes</h2>
    <p>Historial de tus planificaciones de alimentación.</p>

    <?php if (empty($plans)): ?>
        <article class="empty-state">
            <p>Aún no tenés planes creados.</p>
            <a href="/asistente-dieta" class="btn-link">Crear un plan</a>
        </article>
    <?php else: ?>
        <div class="flex-column">
            <?php foreach ($plans as $plan): ?>
                <article class="action-card plan-card">
                    <div class="plan-card-inner">
                        <!-- Carátula para la impresión en PDF (oculta en pantalla, visible en print) -->
                        <div class="print-cover">
                            <img src="/assets/img/LogoW2C_1.png" alt="Logo W2C" class="print-cover-logo">
                            <h1 class="print-cover-title">Plan de Alimentación</h1>
                            <h2 class="print-cover-subtitle">Plan Personalizado de <?= (int) $plan['duration_days'] ?> días</h2>
                            <div class="print-cover-divider"></div>
                            <div class="print-cover-meta">
                                <p><strong>Tipo de dieta:</strong> <?= $plan['diet_type'] ? htmlspecialchars($plan['diet_type']) : 'Sin dieta específica' ?></p>
                                <?php if ($plan['target_calories'] !== null || $plan['target_protein'] !== null || $plan['target_carbs'] !== null || $plan['target_fat'] !== null): ?>
                                    <p style="margin-top: 1rem; border-top: 1px dashed #000000; padding-top: 0.5rem; font-weight: bold;">Objetivos nutricionales diarios:</p>
                                    <ul style="margin: 0; padding-left: 1.5rem;">
                                        <?php if ($plan['target_calories'] !== null): ?>
                                            <li>Calorías: <?= (int) $plan['target_calories'] ?> kcal</li>
                                        <?php endif; ?>
                                        <?php if ($plan['target_protein'] !== null): ?>
                                            <li>Proteínas: <?= (int) $plan['target_protein'] ?>g</li>
                                        <?php endif; ?>
                                        <?php if ($plan['target_carbs'] !== null): ?>
                                            <li>Carbohidratos: <?= (int) $plan['target_carbs'] ?>g</li>
                                        <?php endif; ?>
                                        <?php if ($plan['target_fat'] !== null): ?>
                                            <li>Grasas: <?= (int) $plan['target_fat'] ?>g</li>
                                        <?php endif; ?>
                                    </ul>
                                <?php endif; ?>
                                <p style="margin-top: 1rem; font-size: 0.95rem; color: #555555; border-top: 1px solid #000000; padding-top: 0.5rem;">Creado el: <?= date('d/m/Y', strtotime($plan['created_at'])) ?></p>
                            </div>
                        </div>

                        <div class="plan-card-info">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
                                <h3 style="margin: 0;">Plan de <?= (int) $plan['duration_days'] ?> días</h3>
                                <button type="button" class="btn-rename no-print" onclick="printPlan(this.closest('.plan-card'))" style="margin: 0; padding: 0.3rem 0.6rem; font-size: 0.75rem;">
                                    Imprimir
                                </button>
                            </div>
                            <p style="margin-top: 0.5rem;"><strong>Tipo de dieta:</strong> <?= $plan['diet_type'] ? htmlspecialchars($plan['diet_type']) : 'Sin dieta específica' ?></p>
                            <p><strong>Objetivos nutricionales:</strong></p>
                            <ul>
                                <?php if ($plan['target_calories'] !== null): ?>
                                    <li>Calorías: <?= (int) $plan['target_calories'] ?> kcal</li>
                                <?php endif; ?>
                                <?php if ($plan['target_protein'] !== null): ?>
                                    <li>Proteínas: <span data-nutri-amount="<?= htmlspecialchars((string) $plan['target_protein']) ?>" data-nutri-unit="g"><?= (int) $plan['target_protein'] ?>g</span></li>
                                <?php endif; ?>
                                <?php if ($plan['target_carbs'] !== null): ?>
                                    <li>Carbohidratos: <span data-nutri-amount="<?= htmlspecialchars((string) $plan['target_carbs']) ?>" data-nutri-unit="g"><?= (int) $plan['target_carbs'] ?>g</span></li>
                                <?php endif; ?>
                                <?php if ($plan['target_fat'] !== null): ?>
                                    <li>Grasas: <span data-nutri-amount="<?= htmlspecialchars((string) $plan['target_fat']) ?>" data-nutri-unit="g"><?= (int) $plan['target_fat'] ?>g</span></li>
                                <?php endif; ?>
                            </ul>
                            <p><strong>Creado el: <?= date('d/m/Y', strtotime($plan['created_at'])) ?></strong></p>
                        </div>
                        <div class="plan-carousel-panel">
                            <div class="plan-carousel" data-plan-id="<?= (int) $plan['id'] ?>">
                                <button class="carousel-prev" aria-label="Anterior">‹</button>
                                <div class="carousel-slides">
                                    <?php foreach ($plan['days'] as $day): ?>
                                        <?php $week = (int) ceil($day['day_index'] / 7); $dayNames = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo']; $dayName = ucfirst($dayNames[($day['day_index'] - 1) % 7]); ?>
                                        <div class="carousel-slide" data-day-index="<?= (int) $day['day_index'] ?>">
                                            <header>
                                                <h2><?= htmlspecialchars($dayName) ?> - Semana <?= $week ?></h2>
                                            </header>
                                            <div class="carousel-meals">
                                                <?php foreach ($day['meals'] as $meal): ?>
                                                    <a class="meal-thumb" href="/receta/<?= (int) $meal['spoonacular_id'] ?>">
                                                        <?php if (!empty($meal['image'])): ?>
                                                            <img src="<?= htmlspecialchars($meal['image']) ?>" alt="<?= htmlspecialchars($meal['title']) ?>">
                                                        <?php else: ?>
                                                            <img src="/assets/img/placeholder_RecetaSinFoto.png" alt="Sin imagen">
                                                        <?php endif; ?>
                                                        <span class="meal-title"><?= htmlspecialchars($meal['title']) ?></span>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-next" aria-label="Siguiente">›</button>
                            </div>
                        </div>

                        <!-- Vista exclusiva de impresión: recetas una abajo de la otra como recetas separadas -->
                        <div class="print-only-recipes">
                            <?php foreach ($plan['days'] as $day): ?>
                                <?php 
                                $week = (int) ceil($day['day_index'] / 7);
                                $dayNames = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
                                $dayName = ucfirst($dayNames[($day['day_index'] - 1) % 7]);
                                ?>
                                <?php foreach ($day['meals'] as $meal): ?>
                                    <?php 
                                    $fullRecipe = getRecipeDetailsForPrint((int)$meal['spoonacular_id']);
                                    if ($fullRecipe !== null): 
                                        $readyIn  = $fullRecipe['readyInMinutes'] ?? null;
                                        $servings = $fullRecipe['servings']       ?? null;
                                        $image    = $fullRecipe['image']          ?? null;
                                        $summary  = strip_tags($fullRecipe['summary'] ?? '');
                                        
                                        $diets     = $fullRecipe['diets']        ?? [];
                                        $dishTypes = $fullRecipe['dishTypes']    ?? [];
                                        $tags      = array_merge($dishTypes, $diets);
                                        
                                        $ingredients = $fullRecipe['extendedIngredients'] ?? [];
                                        
                                        $prefService = new \App\Services\UnitPreferenceService();
                                        $convService = new \App\Services\UnitConversionService();
                                        $unitSystem = $prefService->getPreferredSystem(\App\Core\Session::userId());
                                        foreach ($ingredients as &$ing) {
                                            $converted = $convService->convertUsingSpoonacularMeasures($ing, $unitSystem);
                                            $ing['amount'] = $converted['amount'];
                                            $ing['unit'] = $converted['unit'];
                                        }
                                        unset($ing);
                                        
                                        $nutrients = $fullRecipe['nutrition']['nutrients'] ?? [];
                                        $nutriMap = [];
                                        foreach ($nutrients as $n) {
                                            $nutriMap[$n['name']] = $n;
                                        }
                                        foreach (['Protein', 'Carbohydrates', 'Fat'] as $nk) {
                                            if (isset($nutriMap[$nk])) {
                                                $convN = $convService->convertToSystem((float) $nutriMap[$nk]['amount'], 'g', $unitSystem);
                                                $nutriMap[$nk]['amount'] = $convN['amount'];
                                                $nutriMap[$nk]['unit'] = $convN['unit'];
                                            }
                                        }
                                    ?>
                                        <article class="receta-detalle print-recipe-block">
                                            <!-- Título especial que indica el día y la comida -->
                                            <h2 class="print-meal-header">
                                                <?= htmlspecialchars(mb_strtoupper($dayName)) ?> - 
                                                SEMANA <?= $week ?> - 
                                                <?= htmlspecialchars(mb_strtoupper([
                                                    'breakfast' => 'Desayuno',
                                                    'lunch'     => 'Almuerzo',
                                                    'snack'     => 'Merienda',
                                                    'dinner'    => 'Cena'
                                                ][$meal['meal_type']] ?? $meal['meal_type'])) ?>
                                            </h2>

                                            <!-- Imagen -->
                                            <div class="receta-hero">
                                                <?php if ($image): ?>
                                                    <img class="receta-img" src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($fullRecipe['title']) ?>">
                                                <?php else: ?>
                                                    <img class="receta-img" src="/assets/img/placeholder_RecetaSinFoto.png" alt="Sin imagen">
                                                <?php endif; ?>
                                            </div>

                                            <div class="receta-content">
                                                <h1><?= htmlspecialchars($fullRecipe['title']) ?></h1>

                                                <!-- Meta -->
                                                <dl class="receta-meta">
                                                    <?php if ($readyIn): ?>
                                                        <dt>Tiempo</dt>
                                                        <dd><?= $readyIn ?> min</dd>
                                                    <?php endif; ?>

                                                    <?php if ($servings): ?>
                                                        <dt>Porciones</dt>
                                                        <dd><?= $servings ?></dd>
                                                    <?php endif; ?>
                                                </dl>

                                                <!-- Tags -->
                                                <?php if (!empty($tags)): ?>
                                                    <ul class="receta-tags">
                                                        <?php foreach (array_unique($tags) as $tag): ?>
                                                            <li><?= htmlspecialchars(ucfirst($tag)) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>

                                                <!-- Descripción -->
                                                <?php if ($summary): ?>
                                                    <section class="descripcion">
                                                        <h2>Descripción</h2>
                                                        <p><?= htmlspecialchars(mb_substr($summary, 0, 400)) ?>...</p>
                                                    </section>
                                                <?php endif; ?>

                                                <!-- Macros -->
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
                                                                    <td><?= round($nutriMap['Protein']['amount']      ?? 0) ?><?= htmlspecialchars($nutriMap['Protein']['unit'] ?? 'g') ?></td>
                                                                    <td><?= round($nutriMap['Carbohydrates']['amount']?? 0) ?><?= htmlspecialchars($nutriMap['Carbohydrates']['unit'] ?? 'g') ?></td>
                                                                    <td><?= round($nutriMap['Fat']['amount']          ?? 0) ?><?= htmlspecialchars($nutriMap['Fat']['unit'] ?? 'g') ?></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </section>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Ingredientes -->
                                            <?php if (!empty($ingredients)): ?>
                                                <section class="ingredientes">
                                                    <h2>Ingredientes</h2>
                                                    <ul>
                                                        <?php foreach ($ingredients as $ing): ?>
                                                            <li>
                                                                <?php if (!empty($ing['image'])): ?>
                                                                    <img src="https://img.spoonacular.com/ingredients_100x100/<?= htmlspecialchars($ing['image']) ?>" alt="<?= htmlspecialchars($ing['name']) ?>" width="40">
                                                                <?php endif; ?>
                                                                <span class="nombre"><?= htmlspecialchars(ucfirst($ing['name'])) ?></span>
                                                                <span class="cantidad">
                                                                    <?= round($ing['amount'], 2) ?> <?= htmlspecialchars($ing['unit']) ?>
                                                                </span>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </section>
                                            <?php endif; ?>
                                        </article>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
