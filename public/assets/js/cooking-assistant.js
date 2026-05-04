/**
 * cooking-assistant.js — Asistente de Cocina · What2Cook
 * Vanilla JS, sin dependencias externas.
 */

/* ============================================================
   Estado en memoria
   ============================================================ */
let ingredients = [];          // string[]
let activeSort  = null;        // null | "healthiness" | "time"
let activeMode  = 'single';    // "single" | "meal-prep"

/* ============================================================
   Utilidades DOM
   ============================================================ */
function el(id)         { return document.getElementById(id); }
function show(elem)     { elem.hidden = false; }
function hide(elem)     { elem.hidden = true;  }
function setText(elem, text) { elem.textContent = text; }

/* ============================================================
   a) Gestión de ingredientes
   ============================================================ */
function addIngredient() {
    const input = el('ingredient-input');
    const raw   = input.value.trim();

    if (!raw) return;

    const normalized = raw.toLowerCase();
    const duplicate  = ingredients.some(i => i.toLowerCase() === normalized);

    if (duplicate) {
        flashInput(input);
        input.value = '';
        return;
    }

    ingredients.push(raw);
    input.value = '';
    input.focus();
    renderIngredientList();
}

function removeIngredient(name) {
    ingredients = ingredients.filter(i => i !== name);
    renderIngredientList();
}

function renderIngredientList() {
    const list = el('ingredient-list');
    list.innerHTML = '';

    ingredients.forEach(name => {
        const li  = document.createElement('li');
        li.classList.add('ca-ingredient-tag');

        const text = document.createElement('span');
        text.textContent = name;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.classList.add('ca-tag-remove');
        btn.setAttribute('aria-label', `Quitar ${name}`);
        btn.innerHTML = '&times;';
        btn.addEventListener('click', () => removeIngredient(name));

        li.appendChild(text);
        li.appendChild(btn);
        list.appendChild(li);
    });
}

/** Efecto visual cuando se intenta agregar un duplicado */
function flashInput(input) {
    input.style.borderColor = '#e05454';
    input.style.boxShadow   = '0 0 0 3px rgba(224,84,84,0.2)';
    setTimeout(() => {
        input.style.borderColor = '';
        input.style.boxShadow   = '';
    }, 800);
}

/* ============================================================
   c) Gestión de modo y filtros
   ============================================================ */
function onModeChange(e) {
    activeMode = e.target.value;

    const singleControls   = el('controls-single');
    const mealPrepControls = el('controls-meal-prep');

    if (activeMode === 'single') {
        show(singleControls);
        hide(mealPrepControls);
    } else {
        hide(singleControls);
        show(mealPrepControls);
        // Resetear filtro de orden al cambiar de modo
        activeSort = null;
        updateFilterButtons(null);
    }
}

function onFilterClick(e) {
    const btn  = e.currentTarget;
    const sort = btn.dataset.sort;

    // Toggle: si ya está activo, lo desactiva
    if (activeSort === sort) {
        activeSort = null;
    } else {
        activeSort = sort;
    }

    updateFilterButtons(activeSort);
}

function updateFilterButtons(sort) {
    document.querySelectorAll('.ca-filter-btn').forEach(btn => {
        const isActive = btn.dataset.sort === sort;
        btn.classList.toggle('active', isActive);
        btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
}

/* ============================================================
   d) Búsqueda (AJAX con fetch)
   ============================================================ */
async function search() {
    if (ingredients.length === 0) {
        setStatus('⚠️ Agregá al menos un ingrediente para buscar.', false);
        return;
    }

    setLoading(true);

    try {
        let url, body;

        if (activeMode === 'single') {
            url  = '/api/cooking-assistant/single';
            body = { ingredients, sort: activeSort };
        } else {
            const count = parseInt(el('meal-prep-count').value, 10);
            url  = '/api/cooking-assistant/meal-prep';
            body = { ingredients, count };
        }

        const response = await fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(body),
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const json = await response.json();

        if (json.success === true) {
            renderResults(json.data);
        } else {
            setStatus(json.message || 'Ocurrió un error inesperado.', true);
        }
    } catch (err) {
        console.error('[CookingAssistant] search error:', err);
        setStatus('Error al buscar recetas. Verificá tu conexión e intentá nuevamente.', true);
    } finally {
        setLoading(false);
    }
}

/* ============================================================
   e) Renderizado de resultados
   ============================================================ */
function renderResults(recipes) {
    const grid   = el('results-grid');
    const status = el('results-status');

    grid.innerHTML = '';

    if (!recipes || recipes.length === 0) {
        setStatus('Sin resultados. Probá con otros ingredientes.', false);
        return;
    }

    hide(status);

    recipes.forEach(recipe => {
        const card = buildCard(recipe);
        grid.appendChild(card);
    });
}

function buildCard(recipe) {
    const card = document.createElement('article');
    card.classList.add('ca-card');
    card.dataset.recipeId = recipe.id;
    card.tabIndex = 0;
    card.setAttribute('role', 'button');
    card.setAttribute('aria-label', `Ver detalle de ${recipe.title}`);

    const img = document.createElement('img');
    img.classList.add('ca-card__img');
    img.src   = recipe.image || '';
    img.alt   = recipe.title;
    img.loading = 'lazy';

    const body  = document.createElement('div');
    body.classList.add('ca-card__body');

    const title = document.createElement('h2');
    title.classList.add('ca-card__title');
    title.textContent = recipe.title;

    const meta = document.createElement('div');
    meta.classList.add('ca-card__meta');

    if (recipe.readyInMinutes) {
        const time = document.createElement('span');
        time.classList.add('ca-card__time');
        time.innerHTML = `⏱ ${recipe.readyInMinutes} min`;
        meta.appendChild(time);
    }

    if (recipe.usedIngredientCount !== undefined) {
        const used = document.createElement('span');
        used.classList.add('ca-badge', 'ca-badge--used');
        used.textContent = `✓ ${recipe.usedIngredientCount} usados`;
        meta.appendChild(used);
    }

    if (recipe.missedIngredientCount !== undefined) {
        const missed = document.createElement('span');
        missed.classList.add('ca-badge', 'ca-badge--missed');
        missed.textContent = `✗ ${recipe.missedIngredientCount} faltan`;
        meta.appendChild(missed);
    }

    body.appendChild(title);
    body.appendChild(meta);
    card.appendChild(img);
    card.appendChild(body);

    // Listeners para abrir el modal
    card.addEventListener('click',  () => openRecipeDetail(recipe.id));
    card.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openRecipeDetail(recipe.id);
        }
    });

    return card;
}

/* ============================================================
   f) Detalle de receta (modal)
   ============================================================ */
async function openRecipeDetail(id) {
    const modal = el('recipe-modal');
    const body  = el('modal-body');

    body.innerHTML = `<p class="ca-status"><span class="ca-spinner"></span> Cargando detalle…</p>`;
    show(modal);
    document.body.style.overflow = 'hidden';

    try {
        const response = await fetch(`/api/cooking-assistant/recipe/${id}`);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const json = await response.json();

        if (json.success === true) {
            renderModalContent(json.data);
        } else {
            body.innerHTML = `<p class="ca-status ca-status--error">${escapeHtml(json.message || 'No se pudo cargar la receta.')}</p>`;
        }
    } catch (err) {
        console.error('[CookingAssistant] recipe detail error:', err);
        body.innerHTML = `<p class="ca-status ca-status--error">Error al cargar el detalle de la receta.</p>`;
    }
}

function renderModalContent(recipe) {
    const body = el('modal-body');

    // Imagen
    let imgHtml = '';
    if (recipe.image) {
        imgHtml = `<img class="ca-modal-img" src="${escapeHtml(recipe.image)}" alt="${escapeHtml(recipe.title)}">`;
    }

    // Tiempo
    const timeHtml = recipe.readyInMinutes
        ? `<p class="ca-modal-meta">⏱ <strong>${recipe.readyInMinutes} minutos</strong> de preparación</p>`
        : '';

    // Ingredientes
    let ingredientsHtml = '';
    if (recipe.extendedIngredients && recipe.extendedIngredients.length > 0) {
        const items = recipe.extendedIngredients
            .map(ing => `<li>${escapeHtml(ing.original || ing.name)}</li>`)
            .join('');
        ingredientsHtml = `
            <h3 class="ca-modal-section-title">Ingredientes</h3>
            <ul class="ca-modal-ingredients">${items}</ul>
        `;
    }

    // Instrucciones (viene como HTML del backend)
    let instructionsHtml = '';
    if (recipe.instructions) {
        instructionsHtml = `
            <h3 class="ca-modal-section-title">Instrucciones</h3>
            <div class="ca-modal-instructions">${recipe.instructions}</div>
        `;
    }

    // Nutrición
    let nutritionHtml = '';
    if (recipe.nutrition && recipe.nutrition.nutrients) {
        const targets = ['Calories', 'Protein', 'Fat', 'Carbohydrates'];
        const labels  = {
            'Calories':      'Calorías',
            'Protein':       'Proteínas',
            'Fat':           'Grasas',
            'Carbohydrates': 'Carbohidratos',
        };

        const rows = recipe.nutrition.nutrients
            .filter(n => targets.includes(n.name))
            .map(n => `
                <tr>
                    <td>${escapeHtml(labels[n.name] || n.name)}</td>
                    <td>${n.amount} ${escapeHtml(n.unit)}</td>
                </tr>
            `)
            .join('');

        if (rows) {
            nutritionHtml = `
                <h3 class="ca-modal-section-title">Información nutricional</h3>
                <table class="ca-nutrition-table">
                    <thead><tr><th>Nutriente</th><th>Cantidad</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            `;
        }
    }

    body.innerHTML = `
        ${imgHtml}
        <h2 id="modal-title" class="ca-modal-title">${escapeHtml(recipe.title)}</h2>
        ${timeHtml}
        ${ingredientsHtml}
        ${instructionsHtml}
        ${nutritionHtml}
    `;
}

function closeRecipeModal() {
    const modal = el('recipe-modal');
    hide(modal);
    el('modal-body').innerHTML = '';
    document.body.style.overflow = '';
}

/* ============================================================
   Utilidades
   ============================================================ */
function setStatus(message, isError) {
    const status = el('results-status');
    const grid   = el('results-grid');
    grid.innerHTML = '';
    status.textContent = message;
    status.classList.toggle('ca-status--error', !!isError);
    show(status);
}

function setLoading(loading) {
    const btn    = el('search-btn');
    const status = el('results-status');
    const grid   = el('results-grid');

    if (loading) {
        btn.disabled = true;
        grid.innerHTML = '';
        status.innerHTML = '<span class="ca-spinner"></span> Buscando recetas…';
        show(status);
    } else {
        btn.disabled = false;
    }
}

/** Escapa caracteres HTML para inserción segura como texto */
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/* ============================================================
   g) Inicialización
   ============================================================ */
function init() {
    /* Ingredientes */
    el('add-ingredient-btn').addEventListener('click', addIngredient);

    el('ingredient-input').addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addIngredient();
        }
    });

    /* Modo */
    document.querySelectorAll('input[name="mode"]').forEach(radio => {
        radio.addEventListener('change', onModeChange);
    });

    /* Filtros toggle */
    document.querySelectorAll('.ca-filter-btn').forEach(btn => {
        btn.addEventListener('click', onFilterClick);
    });

    /* Búsqueda */
    el('search-btn').addEventListener('click', search);

    /* Cerrar modal */
    el('modal-close-btn').addEventListener('click', closeRecipeModal);

    el('recipe-modal').addEventListener('click', e => {
        // Click en el backdrop (fuera del contenido)
        if (e.target === el('recipe-modal')) closeRecipeModal();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !el('recipe-modal').hidden) {
            closeRecipeModal();
        }
    });
}

document.addEventListener('DOMContentLoaded', init);
