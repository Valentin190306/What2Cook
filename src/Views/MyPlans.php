<?php
$title = 'Mis Planes - What2Cook';
$styles = ['perfil','carousel'];
$scripts = ['api','plans','carousel'];
?>

<section class="profile-section">
    <h2>Mis Planes</h2>
    <p>Historial de tus planificaciones de alimentación.</p>

    <?php if (empty($plans)): ?>
        <article class="empty-state">
            <p>Aún no tenés planes creados.</p>
            <a href="/asistente-dieta" class="btn-link">Crear un plan</a>
        </article>
    <?php else: ?>
        <div class="grid-container">
            <?php foreach ($plans as $plan): ?>
                <article class="stat-card plan-card">
                    <?php if (!empty($plan['active'])): ?>
                        <p><strong>[Activo]</strong></p>
                    <?php endif; ?>
                    
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

                    <div class="plan-carousel" data-plan-id="<?= (int) $plan['id'] ?>">
                        <button class="carousel-prev" aria-label="Anterior">‹</button>
                        <div class="carousel-slides">
                            <?php foreach ($plan['days'] as $day): ?>
                                <?php $week = (int) ceil($day['day_index'] / 7); $dayNames = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo']; $dayName = ucfirst($dayNames[($day['day_index'] - 1) % 7]); ?>
                                <div class="carousel-slide" data-day-index="<?= (int) $day['day_index'] ?>">
                                    <header><strong><?= htmlspecialchars($dayName) ?> - Semana <?= $week ?></strong></header>
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

                    <p><small>Creado el: <?= date('d/m/Y', strtotime($plan['created_at'])) ?></small></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
