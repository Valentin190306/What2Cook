document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('mp-modal');
    const closeBtn = document.getElementById('mp-modal-close');
    const body = document.getElementById('mp-modal-body');

    function closeModal() {
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
            closeModal();
        }
    });
});

async function openMealPrepModal(mpId) {
    const modal = document.getElementById('mp-modal');
    const titleEl = document.getElementById('mp-modal-title');
    const ingredientsEl = document.getElementById('mp-ingredients');
    const recipesEl = document.getElementById('mp-recipes');

    recipesEl.innerHTML = '<p style="text-align:center;padding:2rem;">Cargando...</p>';
    ingredientsEl.innerHTML = '';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    try {
        const response = await fetch(`/api/meal-prep-favorites/${mpId}`);
        if (!response.ok) throw new Error('Error al cargar');
        const json = await response.json();
        if (!json.success) throw new Error(json.error || 'Error');

        const data = json;
        const count = data.recipes ? data.recipes.length : 0;
        titleEl.textContent = `Meal Prep — ${count} ${count === 1 ? 'receta' : 'recetas'}`;

        if (data.ingredients && data.ingredients.length > 0) {
            ingredientsEl.innerHTML = `
                <details class="mp-ingredients-details">
                    <summary class="mp-ingredients-summary">Ingredientes (${data.ingredients.length})</summary>
                    <div class="mp-ingredients-chips">
                        ${data.ingredients.map(ing => `<span class="mp-ingredient-chip">${escapeHtml(ing)}</span>`).join('')}
                    </div>
                </details>
            `;
        }

        if (data.recipes && data.recipes.length > 0) {
            var mpModalSys = UnitPreferences.getPreferredSystem();
            function mpModalMacro(val) {
                if (mpModalSys === 'metric') return Math.round(val) + 'g';
                var conv = UnitConversion.convertAmount(val || 0, 'g', mpModalSys);
                return UnitConversion.roundValue(conv.amount) + ' ' + conv.unit;
            }
            recipesEl.innerHTML = data.recipes.map(recipe => {
                const id = recipe.id || 0;
                const image = recipe.image || '/assets/img/placeholder_RecetaSinFoto.png';
                const title = recipe.title || 'Sin título';
                const readyIn = recipe.readyInMinutes || null;
                const servings = recipe.servings || null;
                const diets = recipe.diets || [];
                const dishTypes = recipe.dishTypes || [];
                const tags = [...new Set([...dishTypes, ...diets])].slice(0, 3);
                const nutrients = recipe.nutrition && recipe.nutrition.nutrients ? recipe.nutrition.nutrients : [];
                const nutrientMap = {};
                nutrients.forEach(n => { if (n.name) nutrientMap[n.name] = n; });

                return `
                    <article onclick="window.location='/receta/${id}'" role="link" tabindex="0">
                        <img src="${escapeHtml(image)}" alt="${escapeHtml(title)}" loading="lazy">
                        <h2>${escapeHtml(title)}</h2>
                        <div class="recipe-meta">
                            ${readyIn ? `<span class="recipe-time">Tiempo: ${readyIn} min</span>` : ''}
                            ${servings ? `<span>Porciones: ${servings}</span>` : ''}
                        </div>
                        ${tags.length > 0 ? `<div class="recipe-tags">${tags.map(t => `<span>${escapeHtml(t.charAt(0).toUpperCase() + t.slice(1))}</span>`).join('')}</div>` : ''}
                        <table>
                            <thead><tr><th>Kcal</th><th>Proteína</th><th>Carbs</th><th>Grasa</th></tr></thead>
                            <tbody><tr>
                                <td>${Math.round(nutrientMap['Calories'] ? nutrientMap['Calories'].amount : 0)}</td>
                                <td data-nutri-amount="${escapeHtml((String)(nutrientMap['Protein'] ? nutrientMap['Protein'].amount : 0))}" data-nutri-unit="g">${mpModalMacro(nutrientMap['Protein'] ? nutrientMap['Protein'].amount : 0)}</td>
                                <td data-nutri-amount="${escapeHtml((String)(nutrientMap['Carbohydrates'] ? nutrientMap['Carbohydrates'].amount : 0))}" data-nutri-unit="g">${mpModalMacro(nutrientMap['Carbohydrates'] ? nutrientMap['Carbohydrates'].amount : 0)}</td>
                                <td data-nutri-amount="${escapeHtml((String)(nutrientMap['Fat'] ? nutrientMap['Fat'].amount : 0))}" data-nutri-unit="g">${mpModalMacro(nutrientMap['Fat'] ? nutrientMap['Fat'].amount : 0)}</td>
                            </tr></tbody>
                        </table>
                        <a class="recipe-link" href="/receta/${id}" aria-hidden="true" tabindex="-1"></a>
                    </article>
                `;
            }).join('');
            if (typeof applyUnitConversionToPage === 'function') {
                applyUnitConversionToPage();
            }
        } else {
            recipesEl.innerHTML = '<p style="text-align:center;padding:2rem;">No hay recetas en este meal prep.</p>';
        }
    } catch (err) {
        console.error('[MealPrepModal] Error:', err);
        recipesEl.innerHTML = `<p style="text-align:center;padding:2rem;color:var(--color-carrot);">Error al cargar: ${err.message}</p>`;
    }
}

function escapeHtml(str) {
    if (typeof str !== 'string') return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
