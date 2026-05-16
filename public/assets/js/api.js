/**
 * api.js — Cliente de API interno
 */

const BackendAPI = {
    async searchRecipes(ingredients, mode, sort, count) {
        // 0. Traducir ingredientes localmente usando el diccionario
        const translatedIngredients = ingredients.map(ing => {
            const lower = ing.toLowerCase().trim();
            if (window.INGREDIENT_TRANSLATIONS && window.INGREDIENT_TRANSLATIONS[lower]) {
                return window.INGREDIENT_TRANSLATIONS[lower];
            }
            return lower; // fallback
        });

        // 1. Create cache key
        const sortVal = sort || 'none';
        const cacheKey = `recipes_${mode}_${translatedIngredients.slice().sort().join(',')}_${sortVal}_${count || 'none'}`;
        
        // 2. Check cache
        const cached = sessionStorage.getItem(cacheKey);
        if (cached) {
            return JSON.parse(cached);
        }

        // 3. Prepare request
        let url, body;
        if (mode === 'single') {
            url = '/api/kitchen-helper/single';
            body = { ingredients: translatedIngredients, sort };
        } else {
            url = '/api/kitchen-helper/meal-prep';
            body = { ingredients: translatedIngredients, count, sort };
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const detail = errorData.message || errorData.error || '';
            throw new Error(`Error ${response.status}${detail ? ': ' + detail : ''}`);
        }

        const json = await response.json();
        
        // 4. Save to cache before returning
        if (json && json.success === true) {
            sessionStorage.setItem(cacheKey, JSON.stringify(json));
        }
        
        return json;
    },

    async getRecipeDetail(id) {
        const cacheKey = `recipe_detail_${id}`;
        
        const cached = sessionStorage.getItem(cacheKey);
        if (cached) {
            return JSON.parse(cached);
        }

        const response = await fetch(`/api/kitchen-helper/recipe/${id}`);

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const detail = errorData.message || errorData.error || '';
            throw new Error(`Error ${response.status}${detail ? ': ' + detail : ''}`);
        }

        const json = await response.json();
        
        if (json && json.success === true) {
            sessionStorage.setItem(cacheKey, JSON.stringify(json));
        }
        
        return json;
    },

    async generateDiet(data) {
        // Obtenemos una cadena única para este set de datos
        const cacheKey = `diet_plan_${JSON.stringify(data)}`;
        
        const cached = sessionStorage.getItem(cacheKey);
        if (cached) {
            return JSON.parse(cached);
        }

        const response = await fetch('/api/diet-helper/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const detail = errorData.error || errorData.message || '';
            throw new Error(`Error ${response.status}${detail ? ': ' + detail : ''}`);
        }

        const json = await response.json();
        
        // Asumiendo que diet-helper no usa wrapper "success" sino data directa
        sessionStorage.setItem(cacheKey, JSON.stringify(json));
        
        return json;
    }
};
