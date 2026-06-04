document.addEventListener('DOMContentLoaded', () => {
    const showNonBlockingError = (btn, message) => {
        console.error(message);
        let errEl = btn.parentNode.querySelector('.save-error-msg');
        if (!errEl) {
            errEl = document.createElement('p');
            errEl.className = 'save-error-msg';
            errEl.style.color = '#e53e3e';
            errEl.style.fontSize = '0.9rem';
            errEl.style.marginTop = '0.5rem';
            errEl.style.width = '100%';
            errEl.style.flexBasis = '100%';
            errEl.style.textAlign = 'center';
            btn.parentNode.appendChild(errEl);
        }
        errEl.textContent = message;
        setTimeout(() => {
            errEl.remove();
        }, 4000);
    };

    // Event delegation for shopping list save buttons
    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-sl-save]');
        if (!btn) return;

        event.stopPropagation();

        if (btn.disabled) return;

        const sourceType = btn.dataset.sourceType;
        let sourceId = parseInt(btn.dataset.sourceId, 10);
        
        let itemsToSend = [];
        
        if (sourceType === 'recipe') {
            // Get ingredients from recipe page
            const items = document.querySelectorAll('#ingredients-list li');
            items.forEach(li => {
                const nombre = li.querySelector('.nombre').textContent.trim();
                const amount = parseFloat(li.querySelector('.cantidad').dataset.amount);
                const unit = li.querySelector('.cantidad').textContent.trim()
                               .replace(/^[\d.]+\s*/, ''); // quitar el número del frente
                
                if (nombre && !isNaN(amount)) {
                    itemsToSend.push({ name: nombre, amount, unit });
                }
            });
        } else if (sourceType === 'meal_prep') {
            // Get meal prep data from button
            const mealPrepData = JSON.parse(btn.dataset.mealPrepData || '{}');
            itemsToSend = mealPrepData.ingredients || [];
        } else if (sourceType === 'diet_plan') {
            // Get diet plan shopping list
            let planId = sourceId;
            if (!planId && window.dietHelperPlanData?.meta?.plan_id) {
                planId = parseInt(window.dietHelperPlanData.meta.plan_id, 10);
                if (planId) {
                    btn.dataset.sourceId = planId;
                    sourceId = planId;
                }
            }

            if (!planId && typeof BackendAPI?.saveDietPlan === 'function' && window.dietHelperPlanData) {
                try {
                    const result = await BackendAPI.saveDietPlan(window.dietHelperPlanData);
                    planId = result.plan_id;
                    if (planId) {
                        btn.dataset.sourceId = planId;
                        sourceId = planId;
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
                        itemsToSend = (data.items || []).map(item => ({
                            name: item.ingredient_name || item.name,
                            amount: parseFloat(item.amount),
                            unit: item.unit || ''
                        }));
                    }
                } catch (error) {
                    console.error('Error al obtener lista de compras del plan:', error);
                }
            }

            // Fallback: aggregate directly from window.dietHelperPlanData in JS if still empty
            if (itemsToSend.length === 0 && window.dietHelperPlanData?.days) {
                const aggregated = {};
                window.dietHelperPlanData.days.forEach(day => {
                    if (!day.meals) return;
                    day.meals.forEach(meal => {
                        const ingredients = meal.ingredients || [];
                        ingredients.forEach(ing => {
                            const name = (ing.name || '').trim();
                            const unit = (ing.unit || '').trim();
                            const amount = parseFloat(ing.amount) || 0;
                            if (!name) return;

                            const key = name.toLowerCase() + '|' + unit.toLowerCase();
                            if (!aggregated[key]) {
                                aggregated[key] = { name, amount: 0, unit };
                            }
                            aggregated[key].amount += amount;
                        });
                    });
                });
                itemsToSend = Object.values(aggregated);
            }
        }
        
        if (itemsToSend.length === 0) {
            showNonBlockingError(btn, 'No hay ingredientes para guardar.');
            return;
        }
        
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.textContent = 'Guardando...';
        
        let success = false;
        try {
            const response = await fetch('/api/shopping-lists', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    source_type: sourceType,
                    source_id: sourceId,
                    items: itemsToSend
                })
            });
            
            if (response.status === 401) {
                window.location = '/login';
                return;
            }
            
            if (!response.ok) {
                let errorMsg = 'Error al guardar la lista de compras.';
                try {
                    const errData = await response.json();
                    if (errData && errData.error) {
                        errorMsg = errData.error;
                    }
                } catch (_) {}
                showNonBlockingError(btn, errorMsg);
                btn.innerHTML = originalText;
                return;
            }
            
            const data = await response.json();
            
            btn.innerHTML = '¡Guardado!';
            btn.classList.add('saved');
            success = true;
            
        } catch (error) {
            console.error('Error de red al guardar lista de compras:', error);
            showNonBlockingError(btn, 'Error al guardar la lista de compras.');
            btn.innerHTML = originalText;
        } finally {
            if (!success) {
                btn.disabled = false;
            }
        }
    });
    
    // Event delegation for delete buttons
    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-delete-list]');
        if (!btn) return;

        event.stopPropagation();

        if (btn.disabled) return;

        const listId = parseInt(btn.dataset.deleteList, 10);
        const card = btn.closest('.shopping-list-card');

        const modal = document.getElementById('delete-modal');
        const confirmBtn = document.getElementById('confirm-delete-modal-btn');
        const cancelBtn = document.getElementById('cancel-delete-modal');
        const closeBtn = document.getElementById('close-delete-modal');

        if (!modal || !confirmBtn || !cancelBtn || !closeBtn) return;

        modal.setAttribute('aria-hidden', 'false');

        const originalText = btn.textContent;

        const closeModal = () => {
            modal.setAttribute('aria-hidden', 'true');
            cleanup();
        };

        const handleDelete = async () => {
            btn.disabled = true;
            btn.textContent = 'Eliminando...';
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Eliminando...';

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

                if (card) {
                    card.remove();

                    // If no cards remain, reload to show empty state
                    const remainingCards = document.querySelectorAll('.shopping-list-card');
                    if (remainingCards.length === 0) {
                        window.location.reload();
                    }
                }
                closeModal();

            } catch (error) {
                console.error('Error de red al eliminar lista de compras:', error);
                alert('Error al eliminar la lista de compras.');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Eliminar';
            }
        };

        const handleKeydown = (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleDelete();
            } else if (e.key === 'Escape') {
                closeModal();
            }
        };

        const handleOverlayClick = (e) => {
            if (e.target === modal) {
                closeModal();
            }
        };

        const cleanup = () => {
            confirmBtn.removeEventListener('click', handleDelete);
            cancelBtn.removeEventListener('click', closeModal);
            closeBtn.removeEventListener('click', closeModal);
            document.removeEventListener('keydown', handleKeydown);
            modal.removeEventListener('click', handleOverlayClick);
        };

        confirmBtn.addEventListener('click', handleDelete);
        cancelBtn.addEventListener('click', closeModal);
        closeBtn.addEventListener('click', closeModal);
        document.addEventListener('keydown', handleKeydown);
        modal.addEventListener('click', handleOverlayClick);
    });

    // Event delegation for rename buttons
    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-rename-list]');
        if (!btn) return;

        event.stopPropagation();

        if (btn.disabled) return;

        const listId = parseInt(btn.dataset.renameList, 10);
        const card = btn.closest('.shopping-list-card');
        const titleEl = card ? card.querySelector('.list-title') : null;
        const currentName = titleEl ? titleEl.textContent.trim() : '';

        const modal = document.getElementById('rename-modal');
        const input = document.getElementById('new-list-name-input');
        const saveBtn = document.getElementById('save-rename-modal');
        const cancelBtn = document.getElementById('cancel-rename-modal');
        const closeBtn = document.getElementById('close-rename-modal');

        if (!modal || !input || !saveBtn || !cancelBtn || !closeBtn) return;

        input.value = currentName;
        modal.setAttribute('aria-hidden', 'false');
        input.focus();

        const originalText = btn.textContent;

        const closeModal = () => {
            modal.setAttribute('aria-hidden', 'true');
            cleanup();
        };

        const handleSave = async () => {
            const trimmedName = input.value.trim();
            if (trimmedName === '') {
                alert('El nombre de la lista no puede estar vacío.');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Guardando...';
            saveBtn.disabled = true;
            saveBtn.textContent = 'Guardando...';

            try {
                const response = await fetch('/api/shopping-lists/rename', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        list_id: listId,
                        name: trimmedName
                    })
                });

                if (response.status === 401) {
                    window.location = '/login';
                    return;
                }

                if (!response.ok) {
                    let errorMsg = 'Error al renombrar la lista.';
                    try {
                        const errData = await response.json();
                        if (errData && errData.error) {
                            errorMsg = errData.error;
                        }
                    } catch (_) {}
                    alert(errorMsg);
                    return;
                }

                if (titleEl) {
                    titleEl.textContent = trimmedName;
                }
                closeModal();

            } catch (error) {
                console.error('Error de red al renombrar la lista:', error);
                alert('Error de red al renombrar la lista de compras.');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
                saveBtn.disabled = false;
                saveBtn.textContent = 'Guardar';
            }
        };

        const handleKeydown = (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleSave();
            } else if (e.key === 'Escape') {
                closeModal();
            }
        };

        const handleOverlayClick = (e) => {
            if (e.target === modal) {
                closeModal();
            }
        };

        const cleanup = () => {
            saveBtn.removeEventListener('click', handleSave);
            cancelBtn.removeEventListener('click', closeModal);
            closeBtn.removeEventListener('click', closeModal);
            input.removeEventListener('keydown', handleKeydown);
            modal.removeEventListener('click', handleOverlayClick);
        };

        saveBtn.addEventListener('click', handleSave);
        cancelBtn.addEventListener('click', closeModal);
        closeBtn.addEventListener('click', closeModal);
        input.addEventListener('keydown', handleKeydown);
        modal.addEventListener('click', handleOverlayClick);
    });

    // Toggle card expansion in /lista-compras
    document.addEventListener('click', (event) => {
        const card = event.target.closest('.shopping-list-card');
        if (!card) return;

        // If the click is on interactive components, do nothing
        if (event.target.closest('button, input, select, textarea, a, .modal-card, .modal-overlay')) {
            return;
        }

        card.classList.toggle('is-expanded');
    });
});
