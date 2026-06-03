<?php
$title = 'Mis Listas de Compra - What2Cook';
$styles = ['lista-compras'];
?>
<section class="lista-compras-hero">
    <h1>Mis Listas de Compra</h1>
    <p>Tus listas de compras guardadas</p>
</section>

<section class="lista-compras-container">
    <?php if (empty($lists)): ?>
    <div class="empty-state">
        <p>No tenés listas de compras guardadas aún.</p>
        <a href="/recetas" class="btn-primary">Explorar recetas</a>
    </div>
    <?php else: ?>
    <div class="lists-grid">
        <?php foreach ($lists as $list): ?>
        <article class="shopping-list-card">
            <div class="shopping-list-card__header">
                <h3><?= htmlspecialchars($list['source_type'] === 'recipe' ? 'Lista de receta' : ($list['source_type'] === 'meal_prep' ? 'Lista de meal prep' : 'Lista de plan de dieta')) ?></h3>
                <span class="shopping-list-card__date"><?= date('d/m/Y', strtotime($list['created_at'])) ?></span>
            </div>
            <div class="shopping-list-card__items">
                <ul>
                    <?php foreach ($list['items'] as $item): ?>
                    <li>
                        <span class="item-name"><?= htmlspecialchars($item['ingredient_name']) ?></span>
                        <span class="item-amount"><?= round($item['amount'], 2) ?> <?= htmlspecialchars($item['unit'] ?? '') ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="shopping-list-card__actions">
                <button type="button" class="btn-delete" data-delete-list="<?= (int) $list['id'] ?>">Eliminar</button>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
