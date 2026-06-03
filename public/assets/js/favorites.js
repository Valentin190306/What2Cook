document.addEventListener('DOMContentLoaded', () => {
    // Initialize favorited state on page load
    const favoriteButtons = document.querySelectorAll('[data-fav-toggle]');
    favoriteButtons.forEach(btn => {
        const isFavorited = btn.dataset.favorited === 'true';
        if (isFavorited) {
            btn.classList.add('favorited');
        }
    });
    
    // Use event delegation for dynamically added buttons
    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-fav-toggle]');
        if (!btn) return;

        // Detener la propagación para no activar clics en contenedores/tarjetas
        event.stopPropagation();

        if (btn.disabled) return;

        // Immediate visual feedback on click
        btn.classList.add('clicking');
        setTimeout(() => btn.classList.remove('clicking'), 200);

        const spoonacularId = parseInt(btn.dataset.spoonacularId, 10);
        const title = btn.dataset.title || '';
        const image = btn.dataset.image || null;

        btn.disabled = true;
        
        try {
            const response = await fetch('/api/favorites', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    spoonacular_id: spoonacularId,
                    title: title,
                    image: image
                })
            });
            
            if (response.status === 401) {
                window.location = '/login';
                return;
            }
            
            if (!response.ok) {
                console.error('Error al modificar favorito:', response.statusText);
                return;
            }
            
            const data = await response.json();
            const favorited = !!data.favorited;
            
            // Actualizar el estado del botón
            btn.dataset.favorited = favorited ? 'true' : 'false';
            // Only set textContent if the button doesn't use background image (like recipe page)
            if (!btn.classList.contains('ca-card__like')) {
                btn.textContent = favorited ? '♥' : '♡';
            }
            btn.setAttribute('aria-label', favorited ? 'Quitar de favoritos' : 'Agregar a favoritos');
            
            // Aplicar estilo visual de favorito
            if (favorited) {
                btn.classList.add('favorited');
            } else {
                btn.classList.remove('favorited');
            }
            
            // Si estamos en la página de favoritos y desmarcar, removemos la tarjeta
            if (btn.hasAttribute('data-fav-remove-card') && !favorited) {
                const card = btn.closest('article');
                if (card) {
                    card.remove();
                }
                
                // Si no quedan tarjetas, recargar para mostrar el estado vacío
                const remainingCards = document.querySelectorAll('.recipe-grid article');
                if (remainingCards.length === 0) {
                    window.location.reload();
                }
            }
        } catch (error) {
            console.error('Error de red al alternar favorito:', error);
        } finally {
            btn.disabled = false;
        }
    });
    
    // Event delegation for meal prep like buttons
    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-mp-fav-toggle]');
        if (!btn) return;

        event.stopPropagation();

        if (btn.disabled) return;

        // Immediate visual feedback on click
        btn.classList.add('clicking');
        setTimeout(() => btn.classList.remove('clicking'), 200);

        const mealPrepData = JSON.parse(btn.dataset.mealPrepData || '{}');
        btn.disabled = true;
        
        try {
            const response = await fetch('/api/meal-prep-favorites', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(mealPrepData)
            });
            
            if (response.status === 401) {
                window.location = '/login';
                return;
            }
            
            if (!response.ok) {
                console.error('Error al modificar favorito de meal prep:', response.statusText);
                return;
            }
            
            const data = await response.json();
            const favorited = !!data.favorited;
            
            btn.dataset.favorited = favorited ? 'true' : 'false';
            btn.textContent = favorited ? '♥' : '♡';
            btn.setAttribute('aria-label', favorited ? 'Quitar de favoritos' : 'Agregar a favoritos');
            
            if (favorited) {
                btn.classList.add('favorited');
            } else {
                btn.classList.remove('favorited');
            }
        } catch (error) {
            console.error('Error de red al alternar favorito de meal prep:', error);
        } finally {
            btn.disabled = false;
        }
    });
});
