<?php
$title = 'Lista de Compras - What2Cook';
$styles = ['perfil'];
$scripts = ['shopping-list'];
?>

<section class="profile-section">
    <h2>Lista de Compras</h2>
    
    <?php if ($plan === null): ?>
        <article class="empty-state">
            <p>No tenés un plan activo.</p>
            <a href="/asistente-dieta" class="btn-link">Crear un plan</a>
        </article>
    <?php elseif (empty($items)): ?>
        <article class="empty-state">
            <p>Tu lista de compras está vacía.</p>
        </article>
    <?php else: ?>
        <ul>
            <?php foreach ($items as $item): ?>
                <li>
                    <label>
                        <input type="checkbox" data-item-id="<?= (int) $item['id'] ?>" <?= !empty($item['purchased']) ? 'checked' : '' ?>>
                        <span class="ingrediente"><?= htmlspecialchars($item['ingredient_name']) ?></span>
                        <span class="cantidad"><?= rtrim(rtrim(number_format((float) $item['amount'], 2, '.', ''), '0'), '.') ?> <?= htmlspecialchars($item['unit'] ?? '') ?></span>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
