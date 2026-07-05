<?php
$title = 'Mis Listas de Compra - What2Cook';
$styles = ['lista-compras'];
$scripts = ['shopping-lists', 'print'];
$noindex = true;
?>
<section class="lista-compras-hero">
    <h1>Mis Listas de Compra</h1>
    <p>Tus listas de compras guardadas</p>
    <button type="button" class="btn-rename no-print" onclick="window.print()" style="margin: 1rem auto 0; display: inline-block;">
        Imprimir listas
    </button>
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
                <h3 class="list-title" data-list-id="<?= (int) $list['id'] ?>"><?= htmlspecialchars(!empty($list['name']) ? $list['name'] : ($list['source_type'] === 'recipe' ? 'Lista de receta' : ($list['source_type'] === 'meal_prep' ? 'Lista de meal prep' : 'Lista de plan de dieta'))) ?></h3>
                <div class="shopping-list-card__header-right">
                    <span class="shopping-list-card__date"><?= date('d/m/Y', strtotime($list['created_at'])) ?></span>
                    <svg class="expand-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </div>
            <div class="shopping-list-card__items">
                <ul>
                    <?php foreach ($list['items'] as $item): ?>
                    <li>
                        <div class="item-left-group">
                            <span class="checkbox-square"></span>
                            <span class="item-name"><?= htmlspecialchars($item['ingredient_name']) ?></span>
                        </div>
                        <span class="item-amount" data-amount="<?= htmlspecialchars((string) round($item['amount'], 2)) ?>" data-unit="<?= htmlspecialchars($item['unit'] ?? '') ?>"><?= round($item['amount'], 2) ?> <?= htmlspecialchars($item['unit'] ?? '') ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="shopping-list-card__actions">
                <button type="button" class="btn-print no-print" onclick="printList(this.closest('.shopping-list-card'))">Imprimir</button>
                <button type="button" class="btn-rename" data-rename-list="<?= (int) $list['id'] ?>">Renombrar</button>
                <button type="button" class="btn-delete" data-delete-list="<?= (int) $list['id'] ?>">Eliminar</button>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- Modal de Renombrar -->
<div id="rename-modal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card">
        <header class="modal-card__header">
            <h2>Renombrar Lista</h2>
            <button type="button" class="btn-close-modal" id="close-rename-modal" aria-label="Cerrar modal">&times;</button>
        </header>
        <div class="modal-card__body">
            <label for="new-list-name-input">Nuevo nombre de la lista:</label>
            <input type="text" id="new-list-name-input" placeholder="Ej: Compras del finde" maxlength="255">
        </div>
        <footer class="modal-card__footer">
            <button type="button" class="btn-modal-cancel" id="cancel-rename-modal">Cancelar</button>
            <button type="button" class="btn-modal-save" id="save-rename-modal">Guardar</button>
        </footer>
    </div>
</div>

<!-- Modal de Confirmación de Eliminación -->
<div id="delete-modal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card">
        <header class="modal-card__header modal-card__header--danger">
            <h2>Eliminar Lista</h2>
            <button type="button" class="btn-close-modal" id="close-delete-modal" aria-label="Cerrar modal">&times;</button>
        </header>
        <div class="modal-card__body">
            <p style="margin: 0; font-family: var(--font-body); font-size: 1rem; color: var(--color-black);">¿Estás seguro de que querés eliminar esta lista de compras?</p>
        </div>
        <footer class="modal-card__footer">
            <button type="button" class="btn-modal-cancel" id="cancel-delete-modal">Cancelar</button>
            <button type="button" class="btn-modal-delete" id="confirm-delete-modal-btn">Eliminar</button>
        </footer>
    </div>
</div>
