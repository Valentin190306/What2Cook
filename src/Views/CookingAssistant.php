<?php
$title = 'Asistente de Cocina - What2Cook';
$styles = ['asistenteCocina'];
?>

<section class="ca-hero">
    <h1>Asistente de Cocina</h1>
    <p>Ingresá los ingredientes que tenés y te decimos qué podés cocinar.</p>
</section>

<section class="ca-panel" aria-label="Configuración de búsqueda">

    <!-- a) Sección de ingredientes -->
    <div class="ca-block">
        <label class="ca-label" for="ingredient-input">Ingredientes</label>
        <div class="ca-input-row">
            <input
                type="text"
                id="ingredient-input"
                class="ca-text-input"
                placeholder="Ej: pollo, arroz, tomate…"
                autocomplete="off"
                aria-label="Agregar ingrediente"
            >
            <button type="button" id="add-ingredient-btn" class="ca-btn-add" aria-label="Agregar ingrediente">
                Agregar
            </button>
        </div>
        <ul id="ingredient-list" class="ca-ingredient-list" aria-label="Lista de ingredientes agregados">
            <!-- Las etiquetas de ingredientes se renderizan por JS -->
        </ul>
    </div>

    <!-- b) Selector de modo -->
    <div class="ca-block">
        <fieldset class="ca-mode-fieldset">
            <legend class="ca-label">Modo de búsqueda</legend>
            <label class="ca-radio-label">
                <input type="radio" name="mode" value="single" checked>
                <span>Comida única</span>
            </label>
            <label class="ca-radio-label">
                <input type="radio" name="mode" value="meal-prep">
                <span>Meal Prep</span>
            </label>
        </fieldset>
    </div>

    <!-- c) Controles condicionales -->
    <div id="controls-single" class="ca-block ca-mode-controls">
        <span class="ca-label">Ordenar por</span>
        <div class="ca-filter-group" role="group" aria-label="Filtros de ordenamiento">
            <button type="button" class="ca-filter-btn" data-sort="healthiness" id="filter-healthiness" aria-pressed="false">
                🥗 Más saludable
            </button>
            <button type="button" class="ca-filter-btn" data-sort="time" id="filter-time" aria-pressed="false">
                ⚡ Más rápido
            </button>
        </div>
    </div>

    <div id="controls-meal-prep" class="ca-block ca-mode-controls" hidden>
        <label class="ca-label" for="meal-prep-count">Cantidad de comidas</label>
        <input
            type="number"
            id="meal-prep-count"
            class="ca-number-input"
            min="2"
            max="5"
            value="3"
            aria-describedby="meal-prep-hint"
        >
        <small id="meal-prep-hint" class="ca-hint">Entre 2 y 5 recetas para tu semana.</small>
    </div>

    <!-- d) Botón principal -->
    <div class="ca-block">
        <button type="button" id="search-btn" class="ca-btn-primary">
            <span class="ca-btn-icon">🔍</span> Buscar recetas
        </button>
    </div>

</section>

<!-- e) Contenedor de resultados -->
<section id="results-container" class="ca-results" aria-live="polite" aria-label="Resultados de búsqueda">
    <p id="results-status" class="ca-status" hidden></p>
    <div id="results-grid" class="ca-results-grid"></div>
</section>

<!-- f) Modal de detalle -->
<div id="recipe-modal" class="ca-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modal-title" hidden>
    <div class="ca-modal-content">
        <button type="button" id="modal-close-btn" class="ca-modal-close" aria-label="Cerrar detalle de receta">
            &times;
        </button>

        <div id="modal-body" class="ca-modal-body">
            <!-- El contenido se inyecta por JS -->
        </div>
    </div>
</div>

<link rel="stylesheet" href="/assets/css/cooking-assistant.css">
<script src="/assets/js/cooking-assistant.js" defer></script>
