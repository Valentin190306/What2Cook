<?php
$title = 'Mi Perfil - What2Cook';
$styles = ['perfil','carousel'];
$scripts = ['api', 'perfil', 'carousel'];
?>
<?php /* Hidden success message for now
<?php if (!empty($success)): ?>
    <p class="form-success" role="status"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>
*/ ?>

<section class="profile-hero">
    <div class="profile-hero-content">
        <div class="avatar-container">
            <img src="<?= htmlspecialchars($avatarUrl ?? '/assets/img/avatar_placeholder.jpg') ?>" alt="Avatar de Usuario" class="avatar" id="profile-avatar">
            <div class="avatar-overlay" onclick="triggerAvatarUpload()">
                <svg class="camera-icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                </svg>
                <span>Editar</span>
            </div>
            <input type="file" id="avatar-file-input" accept="image/*" style="display: none;" onchange="handleAvatarUpload(this)">
        </div>
        <div class="profile-hero-text">
            <p>Bienvenido</p>
            <h1><?= htmlspecialchars($userName ?? 'Usuario') ?></h1>
            <a href="/perfil/editar" class="btn-edit">Editar Perfil</a>
        </div>
    </div>
    <aside class="dietary-panel">
        <h2>Preferencias Alimentarias</h2>
        <div class="dietary-info">
            <div class="dietary-item">
                <h3>Dieta:</h3>
                <p><?= htmlspecialchars($userDietLabel ? ucwords(strtolower($userDietLabel)) : 'Sin dieta') ?></p>
            </div>
            <?php if (!empty($userAllergyLabels)): ?>
                <div class="dietary-item">
                    <h3>Intolerancias:</h3>
                    <div class="allergy-tags">
                        <?php foreach ($userAllergyLabels as $allergy): ?>
                            <span class="allergy-tag"><?= htmlspecialchars($allergy) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="dietary-item">
                    <h3>Intolerancias:</h3>
                    <p>Ninguna</p>
                </div>
            <?php endif; ?>
        </div>
    </aside>
</section>

<section class="profile-layout">
    <div class="profile-left-panel">
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
                        <a href="/receta/<?= (int) $fav['spoonacular_id'] ?>" class="action-card recipe-card">
                            <?php if (!empty($fav['image'])): ?>
                                <img src="<?= htmlspecialchars($fav['image']) ?>" alt="<?= htmlspecialchars($fav['title']) ?>" class="recipe-thumb">
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($fav['title']) ?></h3>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section id="recent-meal-preps" class="profile-section">
            <h2>Mealpreps Recientes</h2>
            <p>Tus últimos meal preps guardados</p>
            <?php if (empty($recentMealPreps)): ?>
                <article class="empty-state">
                    <p>Aún no tenés meal preps guardados</p>
                    <a href="/asistente-cocina" class="btn-link">Crear meal prep</a>
                </article>
            <?php else: ?>
                <div class="grid-container">
                    <?php foreach ($recentMealPreps as $mp): ?>
                        <article class="action-card mealprep-card">
                            <h3>Meal Prep</h3>
                            <p><?= count(json_decode($mp['ingredients'], true) ?? []) ?> ingredientes</p>
                            <p class="date"><?= date('d/m/Y', strtotime($mp['created_at'])) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section id="recent-shopping-lists" class="profile-section">
            <h2>Listas Recientes</h2>
            <p>Tus últimas listas de compras</p>
            <?php if (empty($recentShoppingLists)): ?>
                <article class="empty-state">
                    <p>Aún no tenés listas de compras</p>
                    <a href="/lista-compras" class="btn-link">Ver todas</a>
                </article>
            <?php else: ?>
                <div class="grid-container">
                    <?php foreach ($recentShoppingLists as $list): ?>
                        <a href="/lista-compras" class="action-card list-card">
                            <h3><?= htmlspecialchars(!empty($list['name']) ? $list['name'] : ($list['source_type'] === 'recipe' ? 'Lista de receta' : ($list['source_type'] === 'meal_prep' ? 'Lista de meal prep' : 'Lista de plan de dieta'))) ?></h3>
                            <p><?= count($list['items'] ?? []) ?> ingredientes</p>
                            <p class="date"><?= date('d/m/Y', strtotime($list['created_at'])) ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section id="recent-diet-plans" class="profile-section">
            <h2>Planes Recientes</h2>
            <p>Tus últimos planes de dieta</p>
            <?php if (empty($recentDietPlans)): ?>
                <article class="empty-state">
                    <p>Aún no tenés planes de dieta</p>
                    <a href="/asistente-dieta" class="btn-link">Crear plan</a>
                </article>
            <?php else: ?>
                <div class="flex-column">
                    <?php foreach ($recentDietPlans as $plan): ?>
                <article class="action-card plan-card">
                    <div class="plan-card-inner">
                        <div class="plan-card-info">
                            <h3>Plan de <?= (int) $plan['duration_days'] ?> días</h3>
                            <p><strong>Tipo de dieta:</strong> <?= $plan['diet_type'] ? htmlspecialchars($plan['diet_type']) : 'Sin dieta específica' ?></p>
                            <p><strong>Objetivos nutricionales:</strong></p>
                            <ul>
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
                            <p><strong>Creado el: <?= date('d/m/Y', strtotime($plan['created_at'])) ?></strong></p>
                        </div>
                        <div class="plan-carousel-panel">
                            <div class="plan-carousel" data-plan-id="<?= (int) $plan['id'] ?>">
                                <button class="carousel-prev" aria-label="Anterior">‹</button>
                                <div class="carousel-slides">
                                    <?php foreach ($plan['days'] as $day): ?>
                                        <?php $week = (int) ceil($day['day_index'] / 7); $dayNames = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo']; $dayName = ucfirst($dayNames[($day['day_index'] - 1) % 7]); ?>
                                        <div class="carousel-slide" data-day-index="<?= (int) $day['day_index'] ?>">
                                            <header class="carousel-header">
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
                    </div>
                </article>
                    <?php endforeach; ?>
                    <div class="ver-todos-wrapper">
                        <a href="/mis-planes" class="btn-link">Ver todos</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>        
    </div>
</section>
