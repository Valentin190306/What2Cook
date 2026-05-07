let planData = null;

const dictMeals = {
    'breakfast': 'Desayuno',
    'lunch': 'Almuerzo',
    'snack': 'Merienda',
    'dinner': 'Cena'
};

const diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('diet-form');
    const status = document.getElementById('plan-status');
    const panel = document.getElementById('plan-panel');
    const grid = document.getElementById('comidas-grid');
    const selectSemana = document.getElementById('semana-select');
    const tabsContainer = document.getElementById('tabs-dias');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnGenerar = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        status.textContent = 'Generando plan, por favor espera...';
        btnGenerar.disabled = true;
        panel.hidden = true;

        const data = {
            duration_days: parseInt(document.getElementById('duracion').value),
            target_calories: parseInt(document.getElementById('calorias').value) || 0,
            target_protein: parseInt(document.getElementById('proteinas').value) || 0,
            target_carbs: parseInt(document.getElementById('carbohidratos').value) || 0,
            target_fat: parseInt(document.getElementById('grasas').value) || 0,
            diet_type: document.getElementById('dieta').value || ''
        };

        try {
            const response = await fetch('/api/diet-helper/generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || 'Error al generar el plan');
            }

            planData = await response.json();
            status.textContent = '';
            renderPlan(planData);
        } catch (err) {
            status.textContent = `Error: ${err.message}`;
            console.error(err);
        } finally {
            btnGenerar.disabled = false;
        }
    });

    btnGuardar.addEventListener('click', async () => {
        if (!planData) return;
        
        btnGuardar.disabled = true;
        status.textContent = 'Guardando plan...';
        
        try {
            const response = await fetch('/api/diet-helper/plan', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(planData)
            });

            if (response.status === 401) {
                status.textContent = 'Iniciá sesión para guardar el plan.';
                return;
            }

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || 'Ocurrió un error al guardar el plan.');
            }

            status.textContent = 'Plan guardado correctamente.';
        } catch (err) {
            status.textContent = `Error: ${err.message}`;
            console.error(err);
        } finally {
            btnGuardar.disabled = false;
        }
    });

    selectSemana.addEventListener('change', () => {
        // Reset tabs to monday
        const firstTab = document.getElementById('lunes');
        if (firstTab) firstTab.checked = true;
        renderActiveDay();
    });

    tabsContainer.addEventListener('change', (e) => {
        if (e.target.name === 'dia') {
            renderActiveDay();
        }
    });
});

function renderPlan(data) {
    const panel = document.getElementById('plan-panel');
    const selectSemana = document.getElementById('semana-select');
    
    // Configure week select
    selectSemana.innerHTML = '';
    const weeks = Math.ceil(data.meta.duration_days / 7);
    for (let i = 1; i <= weeks; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = `Semana ${i}`;
        selectSemana.appendChild(option);
    }

    panel.hidden = false;
    
    // Render first day
    const firstTab = document.getElementById('lunes');
    if (firstTab) firstTab.checked = true;
    
    renderActiveDay();
}

function renderActiveDay() {
    if (!planData) return;

    const selectSemana = document.getElementById('semana-select');
    const week = parseInt(selectSemana.value) || 1;
    
    const activeTab = document.querySelector('input[name="dia"]:checked');
    const dayName = activeTab ? activeTab.value : 'lunes';
    
    const dayOffset = diasSemana.indexOf(dayName);
    const dayIndex = (week - 1) * 7 + dayOffset + 1;
    
    renderDay(dayIndex);
}

function renderDay(dayIndex) {
    const grid = document.getElementById('comidas-grid');
    const divTotales = document.getElementById('dia-totales');
    grid.innerHTML = '';
    divTotales.innerHTML = '';

    const day = planData.days.find(d => d.day_index === dayIndex);
    
    if (!day) {
        grid.innerHTML = '<p>No hay datos para este día.</p>';
        return;
    }

    day.meals.forEach(meal => {
        const article = document.createElement('article');
        article.className = 'comida';
        
        const headerName = dictMeals[meal.meal_type] || meal.meal_type;
        
        article.innerHTML = `
            <header class="comida-header"><h3>${escapeHtml(headerName)}</h3></header>
            <div class="comida-card">
                <a href="/receta/${meal.spoonacular_id}" class="recipe-link" aria-label="Ver receta"></a>
                <img src="${escapeHtml(meal.image || '/assets/img/placeholder_RecetaSinFoto.png')}" alt="Foto de la receta" onerror="this.onerror=null;this.src='/assets/img/placeholder_RecetaSinFoto.png';">
                <h4>${escapeHtml(meal.title)}</h4>
                <div class="recipe-meta">
                    <span class="recipe-time">⏱ ${meal.ready_in_minutes || 0} min</span>
                    <span class="recipe-badge">Porciones: ${meal.servings || 1}</span>
                </div>
                <table class="ca-nutrition-table">
                    <thead>
                        <tr><th>Kcal</th><th>Proteína</th><th>Carbs</th><th>Grasa</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>${Math.round(meal.calories)}</td>
                            <td>${Math.round(meal.protein)}g</td>
                            <td>${Math.round(meal.carbs)}g</td>
                            <td>${Math.round(meal.fat)}g</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
        
        grid.appendChild(article);
    });

    divTotales.innerHTML = `
        <div class="totales-dia-wrapper">
            <h3>Totales del Día ${dayIndex}</h3>
            <table class="totales-table">
                <thead>
                    <tr><th>Kcal</th><th>Proteína</th><th>Carbs</th><th>Grasa</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>${Math.round(day.total_calories)}</td>
                        <td>${Math.round(day.total_protein)}g</td>
                        <td>${Math.round(day.total_carbs)}g</td>
                        <td>${Math.round(day.total_fat)}g</td>
                    </tr>
                </tbody>
            </table>
        </div>
    `;
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
