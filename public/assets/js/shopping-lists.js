document.addEventListener('DOMContentLoaded', () => {
    // Event delegation for shopping list save buttons
    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-sl-save]');
        if (!btn) return;

        event.stopPropagation();

        if (btn.disabled) return;

        const sourceType = btn.dataset.sourceType;
        const sourceId = parseInt(btn.dataset.sourceId, 10);
        
        let items = [];
        
        if (sourceType === 'recipe') {
            // Get ingredients from recipe page
            const ingredientItems = document.querySelectorAll('#ingredients-list li');
            ingredientItems.forEach(li => {
                const name = li.querySelector('.nombre')?.textContent || '';
                const cantidadSpan = li.querySelector('.cantidad');
                const cantidadText = cantidadSpan?.textContent || '';
                
                // Parse amount and unit from text like "2.5 cups"
                const match = cantidadText.match(/^([\d.]+)\s*(.*)$/);
                const amount = match ? parseFloat(match[1]) : 0;
                const unit = match ? match[2] : '';
                
                if (name && amount > 0) {
                    items.push({ name, amount, unit });
                }
            });
        } else if (sourceType === 'meal_prep') {
            // Get meal prep data from button
            const mealPrepData = JSON.parse(btn.dataset.mealPrepData || '{}');
            items = mealPrepData.ingredients || [];
        } else if (sourceType === 'diet_plan') {
            // Get diet plan shopping list
            let planId = sourceId;
            if (!planId && window.dietHelperPlanData?.meta?.plan_id) {
                planId = parseInt(window.dietHelperPlanData.meta.plan_id, 10);
                if (planId) {
                    btn.dataset.sourceId = planId;
                }
            }

            if (!planId && typeof BackendAPI?.saveDietPlan === 'function' && window.dietHelperPlanData) {
                try {
                    const result = await BackendAPI.saveDietPlan(window.dietHelperPlanData);
                    planId = result.plan_id;
                    if (planId) {
                        btn.dataset.sourceId = planId;
                    }
                } catch (error) {
                    console.error('Error al guardar plan antes de obtener la lista de compras:', error);
                }
            }

            if (planId) {
                try {
                    const response = await fetch(`/api/diet-helper/shopping-list/${planId}`);
                    if (response.ok) {
                        const data = await response.json();
                        items = data.items || [];
                    }
                } catch (error) {
                    console.error('Error al obtener lista de compras del plan:', error);
                }
            }
        }
        
        if (items.length === 0) {
            alert('No hay ingredientes para guardar.');
            return;
        }
        
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.textContent = 'Guardando...';
        
        try {
            const response = await fetch('/api/shopping-lists', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    source_type: sourceType,
                    source_id: sourceId,
                    items: items
                })
            });
            
            if (response.status === 401) {
                window.location = '/login';
                return;
            }
            
            if (!response.ok) {
                console.error('Error al guardar lista de compras:', response.statusText);
                alert('Error al guardar la lista de compras.');
                return;
            }
            
            const data = await response.json();
            
            btn.innerHTML = '¡Guardado!';
            btn.classList.add('saved');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.remove('saved');
            }, 2000);
            
        } catch (error) {
            console.error('Error de red al guardar lista de compras:', error);
            alert('Error al guardar la lista de compras.');
        } finally {
            btn.disabled = false;
        }
    });
    
    // Event delegation for delete buttons
    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-delete-list]');
        if (!btn) return;

        event.stopPropagation();

        if (btn.disabled) return;

        const listId = parseInt(btn.dataset.deleteList, 10);
        
        if (!confirm('¿Estás seguro de que querés eliminar esta lista de compras?')) {
            return;
        }
        
        btn.disabled = true;
        btn.textContent = 'Eliminando...';
        
        try {
            const response = await fetch('/api/shopping-lists', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    list_id: listId
                })
            });
            
            if (response.status === 401) {
                window.location = '/login';
                return;
            }
            
            if (!response.ok) {
                console.error('Error al eliminar lista de compras:', response.statusText);
                alert('Error al eliminar la lista de compras.');
                return;
            }
            
            const data = await response.json();
            
            // Remove the card from the DOM
            const card = btn.closest('.shopping-list-card');
            if (card) {
                card.remove();
                
                // If no cards remain, reload to show empty state
                const remainingCards = document.querySelectorAll('.shopping-list-card');
                if (remainingCards.length === 0) {
                    window.location.reload();
                }
            }
            
        } catch (error) {
            console.error('Error de red al eliminar lista de compras:', error);
            alert('Error al eliminar la lista de compras.');
        } finally {
            btn.disabled = false;
        }
    });
});
