<?php
$title  = 'Asistente de Cocina - What2Cook';
$styles = ['asistenteCocina'];
$scripts = ['cooking-assistant'];
?>

<!-- ── HERO ─────────────────────────────────────────────────────────────── -->
<section class="asistente-hero">
    <h1>Asistente de Cocina</h1>
    <p>Decime qué ingredientes tenés y te diré qué cocinar</p>

    <div class="tabs">
        <input type="radio" id="tab-single" name="mode" value="single" checked>
        <label for="tab-single">Comida Única</label>
        <input type="radio" id="tab-prep" name="mode" value="meal-prep">
        <label for="tab-prep">Meal Prep</label>
    </div>
</section>

<!-- ── FORMULARIO ───────────────────────────────────────────────────────── -->
<section class="form-panel">
    <h2>Encontrá la receta perfecta</h2>
    <p>Ingresá los ingredientes que tenés y te sugerimos recetas que puedas preparar.</p>

    <!-- Lista de ingredientes agregados (poblada por JS) -->
    <ul class="ingredientes-lista" id="ingredient-list"></ul>

    <!-- Input + botón agregar -->
    <div class="input-row">
        <input
            type="text"
            id="ingredient-input"
            placeholder="Ej: pollo, arroz, tomate..."
            autocomplete="off"
        >
        <button type="button" id="add-ingredient-btn" class="btn-add" aria-label="Agregar ingrediente">+</button>
    </div>

    <!-- Controles modo Comida Única -->
    <div id="controls-single">
        <div class="form-actions">
            <button type="button" id="search-btn" class="btn-primary">Buscar Recetas</button>
            <button type="button" class="btn-secondary ca-filter-btn" data-sort="time"        aria-pressed="false">Las más rápidas</button>
            <button type="button" class="btn-secondary ca-filter-btn" data-sort="healthiness" aria-pressed="false">Las más saludables</button>
        </div>
    </div>

    <!-- Controles modo Meal Prep (oculto por defecto) -->
    <div id="controls-meal-prep" hidden>
        <div class="meal-prep-quantity">
            <label for="meal-prep-count">Cantidad de recetas:</label>
            <div class="ca-stepper-row">
                <button type="button" class="ca-stepper-circle" id="mp-minus" aria-label="Disminuir cantidad">−</button>
                <div class="ca-stepper-field">
                    <input
                        type="number"
                        id="meal-prep-count"
                        min="2"
                        max="28"
                        value="3"
                        readonly
                    >
                </div>
                <button type="button" class="ca-stepper-circle" id="mp-plus" aria-label="Aumentar cantidad">+</button>
            </div>
        </div>
        <div class="form-actions">
            <button type="button" id="search-btn-prep" class="btn-primary">Generar Meal Prep</button>
            <button type="button" class="btn-secondary ca-filter-btn" data-sort="time" aria-pressed="false">Las más rápidas</button>
            <button type="button" class="btn-secondary ca-filter-btn" data-sort="healthiness" aria-pressed="false">Las más saludables</button>
        </div>
    </div>
</section>

<!-- ── ESTADO Y RESULTADOS ──────────────────────────────────────────────── -->
<p id="results-status" hidden></p>

<div id="results-grid" class="recipe-grid"></div>

<!-- ── MODAL DE DETALLE ─────────────────────────────────────────────────── -->
<div id="recipe-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title" hidden
     class="recipe-modal-overlay" style="position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,0.55);padding:1rem;">
    <div style="background:var(--color-cream);border:2px solid var(--color-black);box-shadow:8px 8px 0 var(--color-black);max-width:680px;width:100%;max-height:90vh;overflow-y:auto;position:relative;">
        <button id="modal-close-btn" type="button" aria-label="Cerrar"
                style="position:sticky;top:0;float:right;margin:0.75rem 0.75rem 0 0;width:36px;height:36px;border:2px solid var(--color-black);background:var(--color-white);font-size:1.3rem;cursor:pointer;line-height:1;z-index:1;">
            &times;
        </button>
        <div id="modal-body" style="padding:1.5rem 1.75rem 2rem; clear:both;"></div>
    </div>
</div>