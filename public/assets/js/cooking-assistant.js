/**
 * cooking-assistant.js — Asistente de Cocina · What2Cook
 * Vanilla JS, sin dependencias externas.
 */

/* ============================================================
   Estado en memoria
   ============================================================ */
let ingredients = [];          // string[]
let activeSort = null;        // null | "healthiness" | "time"
let activeMode = 'single';    // "single" | "meal-prep"

/* ============================================================
   Utilidades DOM
   ============================================================ */
function el(id) { return document.getElementById(id); }
function show(elem) { elem.hidden = false; }
function hide(elem) { elem.hidden = true; }
function setText(elem, text) { elem.textContent = text; }

/* ============================================================
   a) Gestión de ingredientes
   ============================================================ */
function addIngredient() {
    const input = el('ingredient-input');
    const raw = input.value.trim();

    if (!raw) return;

    const normalized = raw.toLowerCase();
    const duplicate = ingredients.some(i => i.toLowerCase() === normalized);

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
        const li = document.createElement('li');
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

function flashInput(input) {
    input.style.borderColor = '#e05454';
    input.style.boxShadow = '0 0 0 3px rgba(224,84,84,0.2)';
    setTimeout(() => {
        input.style.borderColor = '';
        input.style.boxShadow = '';
    }, 800);
}

/* ============================================================
   c) Gestión de modo y filtros
   ============================================================ */
function onModeChange(e) {
    activeMode = e.target.value;

    const singleControls = el('controls-single');
    const mealPrepControls = el('controls-meal-prep');

    if (activeMode === 'single') {
        show(singleControls);
        hide(mealPrepControls);
    } else {
        hide(singleControls);
        show(mealPrepControls);
        activeSort = null;
        updateFilterButtons(null);
    }
}

function onFilterClick(e) {
    const btn = e.currentTarget;
    const sort = btn.dataset.sort;

    if (activeSort === sort) {
        activeSort = null;
    } else {
        activeSort = sort;
    }

    updateFilterButtons(activeSort);

    if (ingredients.length > 0) {
        search();
    }
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
        setStatus('Agregá al menos un ingrediente para buscar.', false);
        return;
    }

    setLoading(true);

    try {
        let url, body;

        if (activeMode === 'single') {
            url = '/api/kitchen-helper/single';
            body = { ingredients, sort: activeSort };
        } else {
            const count = parseInt(el('meal-prep-count').value, 10);
            url = '/api/kitchen-helper/meal-prep';
            body = { ingredients, count };
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
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
    if (activeMode === 'meal-prep') {
        renderMealPrep(recipes);
    } else {
        renderSingle(recipes);
    }
}

function renderSingle(recipes) {
    const grid = el('results-grid');
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

function renderMealPrep(recipes) {
    const grid = el('results-grid');
    grid.innerHTML = '';

    if (!recipes || recipes.length === 0) {
        setStatus('Sin resultados. Probá con otros ingredientes.', false);
        return;
    }

    hide(el('results-status'));

    // Totales del conjunto
    const totals = recipes.reduce((acc, r) => {
        const n = r.nutrition || {};
        acc.calories += n.calories || 0;
        acc.protein += n.protein || 0;
        acc.carbs += n.carbs || 0;
        acc.fat += n.fat || 0;
        return acc;
    }, { calories: 0, protein: 0, carbs: 0, fat: 0 });

    // Banner de resumen
    const banner = document.createElement('div');
    banner.classList.add('mp-summary');
    banner.innerHTML = `
        <h2 class="mp-summary__title">Tu Meal Prep — ${recipes.length} recetas</h2>
        <div class="mp-summary__macros">
            <span><strong>${Math.round(totals.calories)}</strong> Kcal totales</span>
            <span><strong>${Math.round(totals.protein)}g</strong> Proteína</span>
            <span><strong>${Math.round(totals.carbs)}g</strong> Carbs</span>
            <span><strong>${Math.round(totals.fat)}g</strong> Grasa</span>
        </div>
    `;
    grid.appendChild(banner);

    // Cards con número de receta y porciones
    recipes.forEach((recipe, i) => {
        const wrapper = document.createElement('div');
        wrapper.classList.add('mp-recipe');

        const label = document.createElement('p');
        label.classList.add('mp-recipe__label');
        label.textContent = `Receta ${i + 1}${recipe.servings ? ' · ' + recipe.servings + ' porciones' : ''}`;

        const card = buildCard(recipe);

        wrapper.appendChild(label);
        wrapper.appendChild(card);
        grid.appendChild(wrapper);
    });
}

function buildCard(recipe) {
    const card = document.createElement('article');
    card.classList.add('ca-card');
    card.dataset.recipeId = recipe.id;

    const link = document.createElement('a');
    link.classList.add('recipe-link');
    link.href = `/receta/${recipe.id}`;
    link.setAttribute('aria-label', `Ver detalle de ${recipe.title}`);

    const img = document.createElement('img');
    img.classList.add('ca-card__img');
    img.src = recipe.image || '';
    img.alt = recipe.title;
    img.loading = 'lazy';

    const title = document.createElement('h2');
    title.classList.add('ca-card__title');
    title.textContent = recipe.title;

    const meta = document.createElement('div');
    meta.classList.add('recipe-meta', 'ca-card__meta');

    if (recipe.readyInMinutes) {
        const time = document.createElement('span');
        time.classList.add('recipe-time', 'ca-card__time');
        time.innerHTML = `⏱ ${recipe.readyInMinutes} min`;
        meta.appendChild(time);
    }

    if (recipe.usedIngredientCount !== undefined) {
        const used = document.createElement('span');
        used.classList.add('recipe-badge', 'recipe-badge--used', 'ca-badge', 'ca-badge--used');
        used.textContent = `✓ ${recipe.usedIngredientCount} usados`;
        meta.appendChild(used);
    }

    if (recipe.missedIngredientCount !== undefined) {
        const missed = document.createElement('span');
        missed.classList.add('recipe-badge', 'recipe-badge--missed', 'ca-badge', 'ca-badge--missed');
        missed.textContent = `✗ ${recipe.missedIngredientCount} faltan`;
        meta.appendChild(missed);
    }

    card.appendChild(link);
    card.appendChild(img);
    card.appendChild(title);
    card.appendChild(meta);

    // Tabla de macros
    if (recipe.nutrition) {
        const n = recipe.nutrition;
        const table = document.createElement('table');
        table.classList.add('ca-nutrition-table');
        table.innerHTML = `
            <thead><tr><th>Kcal</th><th>Proteína</th><th>Carbs</th><th>Grasa</th></tr></thead>
            <tbody><tr>
                <td>${Math.round(n.calories)}</td>
                <td>${Math.round(n.protein)}g</td>
                <td>${Math.round(n.carbs)}g</td>
                <td>${Math.round(n.fat)}g</td>
            </tr></tbody>
        `;
        card.appendChild(table);
    }

    // Listeners — navegación por teclado
    card.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            link.click();
        }
    });

    return card;
}

/* ============================================================
   f) Detalle de receta (modal)
   ============================================================ */
async function openRecipeDetail(id) {
    const modal = el('recipe-modal');
    const body = el('modal-body');

    body.innerHTML = `<p class="ca-status"><span class="ca-spinner"></span> Cargando detalle…</p>`;
    modal.style.display = "flex"; modal.style.alignItems = "center"; modal.style.justifyContent = "center"; modal.hidden = false;
    document.body.style.overflow = 'hidden';

    try {
        const response = await fetch(`/api/kitchen-helper/recipe/${id}`);

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

    let imgHtml = '';
    if (recipe.image) {
        imgHtml = `<img class="ca-modal-img" src="${escapeHtml(recipe.image)}" alt="${escapeHtml(recipe.title)}">`;
    }

    const timeHtml = recipe.readyInMinutes
        ? `<p class="ca-modal-meta">⏱ <strong>${recipe.readyInMinutes} minutos</strong> de preparación</p>`
        : '';

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

    let instructionsHtml = '';
    if (recipe.instructions) {
        instructionsHtml = `
            <h3 class="ca-modal-section-title">Instrucciones</h3>
            <div class="ca-modal-instructions">${recipe.instructions}</div>
        `;
    }

    let nutritionHtml = '';
    if (recipe.nutrition && recipe.nutrition.nutrients) {
        const targets = ['Calories', 'Protein', 'Fat', 'Carbohydrates'];
        const labels = {
            'Calories': 'Calorías',
            'Protein': 'Proteínas',
            'Fat': 'Grasas',
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
    modal.hidden = true; modal.style.display = "";
    el('modal-body').innerHTML = '';
    document.body.style.overflow = '';
}

/* ============================================================
   Utilidades
   ============================================================ */
function setStatus(message, isError) {
    const status = el('results-status');
    const grid = el('results-grid');
    grid.innerHTML = '';
    status.textContent = message;
    status.classList.toggle('ca-status--error', !!isError);
    show(status);
}

function setLoading(loading) {
    const btn = el('search-btn');
    const btnPrep = el('search-btn-prep');
    const status = el('results-status');
    const grid = el('results-grid');

    if (loading) {
        if (btn) btn.disabled = true;
        if (btnPrep) btnPrep.disabled = true;
        grid.innerHTML = '';
        status.innerHTML = `
            <div class="ca-loading-newspaper">
                <span class="ca-loading-spinner"></span>
                <span class="ca-loading-text">Buscando recetas...</span>
            </div>
        `;
        show(status);
    } else {
        if (btn) btn.disabled = false;
        if (btnPrep) btnPrep.disabled = false;
    }
}

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
    el('add-ingredient-btn').addEventListener('click', addIngredient);

    el('ingredient-input').addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addIngredient();
        }
    });

    document.querySelectorAll('input[name="mode"]').forEach(radio => {
        radio.addEventListener('change', onModeChange);
    });

    document.querySelectorAll('.ca-filter-btn').forEach(btn => {
        btn.addEventListener('click', onFilterClick);
    });

    el('search-btn').addEventListener('click', search);
    el('search-btn-prep').addEventListener('click', search);

    // Stepper Meal Prep
    const mpCount = el('meal-prep-count');
    el('mp-minus').addEventListener('click', () => {
        const val = parseInt(mpCount.value, 10);
        if (val > parseInt(mpCount.min, 10)) {
            mpCount.value = val - 1;
        }
    });
    el('mp-plus').addEventListener('click', () => {
        const val = parseInt(mpCount.value, 10);
        if (val < parseInt(mpCount.max, 10)) {
            mpCount.value = val + 1;
        }
    });

    el('modal-close-btn').addEventListener('click', closeRecipeModal);

    el('recipe-modal').addEventListener('click', e => {
        if (e.target === el('recipe-modal')) closeRecipeModal();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !el('recipe-modal').hidden) {
            closeRecipeModal();
        }
    });
}

document.addEventListener('DOMContentLoaded', init);