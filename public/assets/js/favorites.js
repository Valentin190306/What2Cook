document.addEventListener('DOMContentLoaded', () => {
    const favoriteButtons = document.querySelectorAll('[data-fav-toggle]');
    
    favoriteButtons.forEach(btn => {
        btn.addEventListener('click', async (event) => {
            // Detener la propagación para no activar clics en contenedores/tarjetas
            event.stopPropagation();
            
            if (btn.disabled) return;
            
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
                btn.textContent = favorited ? '♥' : '♡';
                btn.setAttribute('aria-label', favorited ? 'Quitar de favoritos' : 'Agregar a favoritos');
                
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
    });
});
