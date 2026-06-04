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

    activeSort = sort;

    updateFilterButtons(null);

    if (ingredients.length === 0) {
        setStatus('Ingresá al menos 1 ingrediente', false);
        return;
    }
    search();
}

function updateFilterButtons(sort) {
    document.querySelectorAll('.ca-filter-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.setAttribute('aria-pressed', 'false');
    });
}

/* ============================================================
   d) Búsqueda (AJAX con fetch)
   ============================================================ */
async function search() {
    if (ingredients.length === 0) {
        setStatus('Ingresá al menos 1 ingrediente', false);
        return;
    }

    setLoading(true);

    try {
        const count = activeMode === 'meal-prep' ? parseInt(el('meal-prep-count').value, 10) : undefined;
        const json = await BackendAPI.searchRecipes(ingredients, activeMode, activeSort, count);

        if (json.success === true) {
            renderResults(json.data);
        } else {
            setStatus(json.message || 'Ocurrió un error inesperado.', true);
        }
    } catch (err) {
        console.error('[CookingAssistant] search error:', err);
        setStatus(`Error al buscar recetas: ${err.message}`, true);
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

async function renderSingle(recipes) {
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

    // Initialize favorited state for dynamically created buttons
    await initializeFavoritedState();
}

async function renderMealPrep(recipes) {
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
    
    // Create content container
    const content = document.createElement('div');
    content.classList.add('mp-summary__content');
    content.innerHTML = `
        <h2 class="mp-summary__title">Tu Meal Prep — ${recipes.length} recetas</h2>
        <div class="mp-summary__macros">
            <span><strong>${Math.round(totals.calories)}</strong> Kcal totales</span>
            <span><strong>${Math.round(totals.protein)}g</strong> Proteína</span>
            <span><strong>${Math.round(totals.carbs)}g</strong> Carbs</span>
            <span><strong>${Math.round(totals.fat)}g</strong> Grasa</span>
        </div>
    `;
    
    // Like button for meal prep
    const likeBtn = document.createElement('button');
    likeBtn.type = 'button';
    likeBtn.classList.add('mp-summary__like');
    likeBtn.setAttribute('aria-label', 'Guardar meal prep');
    likeBtn.setAttribute('data-mp-fav-toggle', '');
    likeBtn.textContent = '♡';
    
    // Shopping list button for meal prep
    const shoppingListBtn = document.createElement('button');
    shoppingListBtn.type = 'button';
    shoppingListBtn.classList.add('mp-summary__shopping-list');
    shoppingListBtn.setAttribute('aria-label', 'Guardar lista de compras');
    shoppingListBtn.setAttribute('data-sl-save', '');
    shoppingListBtn.innerHTML = `
        <svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
            <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
        </svg>
        Guardar lista
    `;
    
    // Store meal prep data for like and shopping list functionality
    const mealPrepData = {
        ingredients: ingredients,
        recipe_ids: recipes.map(r => r.id),
        servings: recipes.map(r => r.servings || 1)
    };
    likeBtn.dataset.mealPrepData = JSON.stringify(mealPrepData);
    shoppingListBtn.dataset.sourceType = 'meal_prep';
    shoppingListBtn.dataset.sourceId = '0'; // Will be set dynamically
    shoppingListBtn.dataset.mealPrepData = JSON.stringify(mealPrepData);
    
    // Button container
    const buttonContainer = document.createElement('div');
    buttonContainer.classList.add('mp-summary__buttons');
    buttonContainer.appendChild(likeBtn);
    buttonContainer.appendChild(shoppingListBtn);
    
    banner.appendChild(content);
    banner.appendChild(buttonContainer);
    grid.appendChild(banner);
    
    // Initialize meal prep like button state
    await initializeMealPrepLikeState(likeBtn, mealPrepData);

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

    // Initialize favorited state for dynamically created buttons
    await initializeFavoritedState();
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
    img.src = recipe.image || '/assets/img/placeholder_RecetaSinFoto.png';
    img.alt = recipe.title;
    img.loading = 'lazy';
    img.onerror = function() {
        this.onerror = null;
        this.src = '/assets/img/placeholder_RecetaSinFoto.png';
    };

    // Like button
    const likeBtn = document.createElement('button');
    likeBtn.type = 'button';
    likeBtn.classList.add('ca-card__like');
    likeBtn.setAttribute('aria-label', 'Agregar a favoritos');
    likeBtn.setAttribute('data-fav-toggle', '');
    likeBtn.setAttribute('data-spoonacular-id', recipe.id);
    likeBtn.setAttribute('data-title', recipe.title);
    likeBtn.setAttribute('data-image', recipe.image || '');

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
    card.appendChild(likeBtn);
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
        const json = await BackendAPI.getRecipeDetail(id);

        if (json.success === true) {
            renderModalContent(json.data);
        } else {
            body.innerHTML = `<p class="ca-status ca-status--error">${escapeHtml(json.message || 'No se pudo cargar la receta.')}</p>`;
        }
    } catch (err) {
        console.error('[CookingAssistant] recipe detail error:', err);
        body.innerHTML = `<p class="ca-status ca-status--error">${escapeHtml(err.message || 'Error al cargar el detalle de la receta.')}</p>`;
    }
}

function renderModalContent(recipe) {
    const body = el('modal-body');

    let imgHtml = '';
    if (recipe.image) {
        imgHtml = `<img class="ca-modal-img" src="${escapeHtml(recipe.image)}" alt="${escapeHtml(recipe.title)}" onerror="this.onerror=null;this.src='/assets/img/placeholder_RecetaSinFoto.png';">`;
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
async function initializeFavoritedState() {
    try {
        const response = await fetch('/api/favorites');
        if (!response.ok) return;

        const data = await response.json();
        const favorites = data.favorites || [];

        const buttons = document.querySelectorAll('[data-fav-toggle]');
        buttons.forEach(btn => {
            const spoonacularId = parseInt(btn.dataset.spoonacularId, 10);
            const isFavorited = favorites.some(f => f.spoonacular_id === spoonacularId);
            if (isFavorited) {
                btn.dataset.favorited = 'true';
                btn.classList.add('favorited');
            } else {
                btn.dataset.favorited = 'false';
                btn.classList.remove('favorited');
            }
        });
    } catch (error) {
        console.error('Error al cargar favoritos:', error);
    }
}

async function initializeMealPrepLikeState(btn, mealPrepData) {
    try {
        const response = await fetch('/api/meal-prep-favorites');
        if (!response.ok) return;

        const data = await response.json();
        const favorites = data.favorites || [];
        
        const ingredientsHash = md5(mealPrepData.ingredients.join(','));
        const isFavorited = favorites.some(f => f.ingredients_hash === ingredientsHash);
        
        if (isFavorited) {
            btn.dataset.favorited = 'true';
            btn.classList.add('favorited');
            btn.textContent = '♥';
        } else {
            btn.dataset.favorited = 'false';
            btn.classList.remove('favorited');
            btn.textContent = '♡';
        }
    } catch (error) {
        console.error('Error al cargar favoritos de meal prep:', error);
    }
}

// Simple MD5 implementation for meal prep hashing
function md5(string) {
    function md5cycle(x, k) {
        var a = x[0], b = x[1], c = x[2], d = x[3];
        a = ff(a, b, c, d, k[0], 7, -680876936);
        d = ff(d, a, b, c, k[1], 12, -389564586);
        c = ff(c, d, a, b, k[2], 17, 606105819);
        b = ff(b, c, d, a, k[3], 22, -1044525330);
        a = ff(a, b, c, d, k[4], 7, -176418897);
        d = ff(d, a, b, c, k[5], 12, 1200080426);
        c = ff(c, d, a, b, k[6], 17, -1473231341);
        b = ff(b, c, d, a, k[7], 22, -45705983);
        a = ff(a, b, c, d, k[8], 7, 1770035416);
        d = ff(d, a, b, c, k[9], 12, -1958414417);
        c = ff(c, d, a, b, k[10], 17, -42063);
        b = ff(b, c, d, a, k[11], 22, -1990404162);
        a = ff(a, b, c, d, k[12], 7, 1804112514);
        d = ff(d, a, b, c, k[13], 12, -40341101);
        c = ff(c, d, a, b, k[14], 17, -1502002290);
        b = ff(b, c, d, a, k[15], 22, 1236535329);
        a = gg(a, b, c, d, k[1], 5, -165796510);
        d = gg(d, a, b, c, k[6], 9, -1069501632);
        c = gg(c, d, a, b, k[11], 14, 643717713);
        b = gg(b, c, d, a, k[0], 20, -373897302);
        a = gg(a, b, c, d, k[5], 5, -701558691);
        d = gg(d, a, b, c, k[10], 9, 38016083);
        c = gg(c, d, a, b, k[15], 14, -660478335);
        b = gg(b, c, d, a, k[4], 20, -405537848);
        a = gg(a, b, c, d, k[9], 5, 568446438);
        d = gg(d, a, b, c, k[14], 9, -1019803690);
        c = gg(c, d, a, b, k[3], 14, -187363961);
        b = gg(b, c, d, a, k[8], 20, 1163531501);
        a = gg(a, b, c, d, k[13], 5, -1444681467);
        d = gg(d, a, b, c, k[2], 9, -51403784);
        c = gg(c, d, a, b, k[7], 14, 1735328473);
        b = gg(b, c, d, a, k[12], 20, -1926607734);
        a = hh(a, b, c, d, k[5], 4, -378558);
        d = hh(d, a, b, c, k[8], 11, -2022574463);
        c = hh(c, d, a, b, k[11], 16, 1839030562);
        b = hh(b, c, d, a, k[14], 23, -35309556);
        a = hh(a, b, c, d, k[1], 4, -1530992060);
        d = hh(d, a, b, c, k[4], 11, 1272893353);
        c = hh(c, d, a, b, k[7], 16, -155497632);
        b = hh(b, c, d, a, k[10], 23, -1094730640);
        a = hh(a, b, c, d, k[13], 4, 681279174);
        d = hh(d, a, b, c, k[0], 11, -358537222);
        c = hh(c, d, a, b, k[3], 16, -722521979);
        b = hh(b, c, d, a, k[6], 23, 76029189);
        a = hh(a, b, c, d, k[9], 4, -640364487);
        d = hh(d, a, b, c, k[12], 11, -421815835);
        c = hh(c, d, a, b, k[15], 16, 530742520);
        b = hh(b, c, d, a, k[2], 23, -995338651);
        a = ii(a, b, c, d, k[0], 6, -198630844);
        d = ii(d, a, b, c, k[7], 10, 1126891415);
        c = ii(c, d, a, b, k[14], 15, -1416354905);
        b = ii(b, c, d, a, k[5], 21, -57434055);
        a = ii(a, b, c, d, k[12], 6, 1700485571);
        d = ii(d, a, b, c, k[3], 10, -1894986606);
        c = ii(c, d, a, b, k[10], 15, -1051523);
        b = ii(b, c, d, a, k[1], 21, -2054922799);
        a = ii(a, b, c, d, k[8], 6, 1873313359);
        d = ii(d, a, b, c, k[15], 10, -30611744);
        c = ii(c, d, a, b, k[6], 15, -1560198380);
        b = ii(b, c, d, a, k[13], 21, 1309151649);
        a = ii(a, b, c, d, k[4], 6, -145523070);
        d = ii(d, a, b, c, k[11], 10, -1120210379);
        c = ii(c, d, a, b, k[2], 15, 718787259);
        b = ii(b, c, d, a, k[9], 21, -343485551);
        x[0] = add32(a, x[0]);
        x[1] = add32(b, x[1]);
        x[2] = add32(c, x[2]);
        x[3] = add32(d, x[3]);
    }
    function cmn(q, a, b, x, s, t) {
        a = add32(add32(a, q), add32(x, t));
        return add32((a << s) | (a >>> (32 - s)), b);
    }
    function ff(a, b, c, d, x, s, t) {
        return cmn((b & c) | ((~b) & d), a, b, x, s, t);
    }
    function gg(a, b, c, d, x, s, t) {
        return cmn((b & d) | (c & (~d)), a, b, x, s, t);
    }
    function hh(a, b, c, d, x, s, t) {
        return cmn(b ^ c ^ d, a, b, x, s, t);
    }
    function ii(a, b, c, d, x, s, t) {
        return cmn(c ^ (b | (~d)), a, b, x, s, t);
    }
    function md51(s) {
        var n = s.length, state = [1732584193, -271733879, -1732584194, 271733878], i;
        for (i = 64; i <= s.length; i += 64) {
            md5cycle(state, md5blk(s.substring(i - 64, i)));
        }
        s = s.substring(i - 64);
        var tail = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        for (i = 0; i < s.length; i++)
            tail[i >> 2] |= s.charCodeAt(i) << ((i % 4) << 3);
        tail[i >> 2] |= 0x80 << ((i % 4) << 3);
        if (i > 55) {
            md5cycle(state, tail);
            for (i = 0; i < 16; i++) tail[i] = 0;
        }
        tail[14] = n * 8;
        md5cycle(state, tail);
        return state;
    }
    function md5blk(s) {
        var md5blks = [], i;
        for (i = 0; i < 64; i += 4) {
            md5blks[i >> 2] = s.charCodeAt(i) + (s.charCodeAt(i + 1) << 8) + (s.charCodeAt(i + 2) << 16) + (s.charCodeAt(i + 3) << 24);
        }
        return md5blks;
    }
    var hex_chr = '0123456789abcdef'.split('');
    function rhex(n) {
        var s = '', j = 0;
        for (; j < 4; j++)
            s += hex_chr[(n >> (j * 8 + 4)) & 0x0F] + hex_chr[(n >> (j * 8)) & 0x0F];
        return s;
    }
    function hex(x) {
        for (var i = 0; i < x.length; i++)
            x[i] = rhex(x[i]);
        return x.join('');
    }
    function add32(a, b) {
        return (a + b) & 0xFFFFFFFF;
    }
    return hex(md51(string));
}

function setStatus(message, isError) {
    const status = el('results-status');
    const grid = el('results-grid');
    grid.innerHTML = '';
    status.textContent = message;
    
    // Usamos las nuevas clases CSS en lugar de estilos en línea
    status.className = 'ca-status-box' + (isError ? ' ca-status-box--error' : '');

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

function showLoaderThenNavigate(url) {
    const status = el('results-status');
    const grid = el('results-grid');

    grid.innerHTML = '';
    status.innerHTML = `
        <div class="ca-loading-newspaper">
            <span class="ca-loading-spinner"></span>
            <span class="ca-loading-text">Cargando receta...</span>
        </div>
    `;
    show(status);

    setTimeout(() => {
        window.location.href = url;
    }, 100);
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

    el('results-grid').addEventListener('click', e => {
        const link = e.target.closest('.recipe-link');
        if (!link) return;
        e.preventDefault();
        showLoaderThenNavigate(link.getAttribute('href'));
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