document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][data-item-id]');
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', async () => {
            const id = checkbox.dataset.itemId;
            const purchased = checkbox.checked;
            
            checkbox.disabled = true;
            
            try {
                const response = await fetch('/api/diet-helper/shopping-list/item/' + id, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ purchased })
                });
                
                if (response.status === 401) {
                    window.location = '/login';
                    return;
                }
                
                if (!response.ok) {
                    checkbox.checked = !purchased;
                    console.error('Error actualizando ítem de compra');
                }
            } catch (error) {
                checkbox.checked = !purchased;
                console.error('Error de red al actualizar ítem de compra:', error);
            } finally {
                checkbox.disabled = false;
            }
        });
    });
});
