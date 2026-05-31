<?php
$title = 'Mis Planes - What2Cook';
$styles = ['perfil'];
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
                <article class="stat-card">
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
                    
                    <p><small>Creado el: <?= date('d/m/Y', strtotime($plan['created_at'])) ?></small></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
