(function () {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });

            if (navigator.onLine) {
                let sw = registration.active;
                if (registration.installing) {
                    registration.installing.addEventListener('statechange', (e) => {
                        if (e.target.state === 'activated') {
                            reconcile(registration.active);
                        }
                    });
                } else if (registration.waiting) {
                    if (sw) reconcile(sw);
                } else if (sw) {
                    reconcile(sw);
                }
            }

            registration.addEventListener('updatefound', () => {
                const installingWorker = registration.installing;
                if (installingWorker) {
                    installingWorker.addEventListener('statechange', () => {
                        if (installingWorker.state === 'installed') {
                            if (navigator.serviceWorker.controller) {
                                console.log('[SW] Nueva versión disponible. Se activará en la próxima carga.');
                            } else {
                                console.log('[SW] Service Worker instalado correctamente.');
                            }
                        }
                    });
                }
            });

        } catch (err) {
            console.error('[SW] Registro fallido:', err);
        }
    });

    async function reconcile(sw) {
        if (!sw) return;
        try {
            const response = await fetch('/api/favorites');
            if (!response.ok) {
                return;
            }
            const data = await response.json();
            const favorites = data.favorites || [];

            sw.postMessage({
                type: 'RECONCILE',
                favorites: favorites
            });
        } catch (err) {
            console.error('[SW] Error en reconciliación de favoritos:', err);
        }
    }
})();
