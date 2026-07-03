/*
 * Service Worker para What2Cook
 * Habilita el funcionamiento offline de las recetas favoritas.
 * 
 * LIMITACIONES ACEPTADAS:
 * 1. El logout offline no purga la caché (el POST no llega a red y el SW no lo detecta).
 * 2. Sin namespacing por usuario (si comparten navegador sin logout, se ven recetas del anterior).
 * 3. ?v=time() en Layout.php fuerza la re-descarga de assets en cada navegación (mitigado por ignoreSearch).
 */

const STATIC_CACHE = 'static-v2';
const RECIPES_CACHE = 'recipes-v1';
const IMAGES_CACHE = 'images-v1';
const PLANS_CACHE = 'plans-v1';
const MEALPREPS_CACHE = 'mealpreps-v1';
const SHOPPING_CACHE = 'shopping-v1';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(['/offline.html']))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    const allowedCaches = [STATIC_CACHE, RECIPES_CACHE, IMAGES_CACHE, PLANS_CACHE, MEALPREPS_CACHE, SHOPPING_CACHE];
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (!allowedCaches.includes(cacheName)) {
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Regla 1: Métodos no GET (excepción POST a /logout)
    if (request.method !== 'GET') {
        if (request.method === 'POST' && url.pathname === '/logout') {
            event.respondWith(
                fetch(request).then(async (response) => {
                    try {
                        await Promise.all([
                            caches.delete(RECIPES_CACHE),
                            caches.delete(IMAGES_CACHE),
                            caches.delete(PLANS_CACHE),
                            caches.delete(MEALPREPS_CACHE),
                            caches.delete(SHOPPING_CACHE)
                        ]);
                        const cache = await caches.open(STATIC_CACHE);
                        await Promise.all([
                            cache.put('/offline-favorites.json', new Response(JSON.stringify([]), {
                                headers: { 'Content-Type': 'application/json' }
                            })),
                            cache.put('/offline-plans.json', new Response(JSON.stringify([]), {
                                headers: { 'Content-Type': 'application/json' }
                            })),
                            cache.put('/offline-mealpreps.json', new Response(JSON.stringify([]), {
                                headers: { 'Content-Type': 'application/json' }
                            })),
                            cache.put('/offline-shopping.json', new Response(JSON.stringify([]), {
                                headers: { 'Content-Type': 'application/json' }
                            }))
                        ]);
                    } catch (err) {
                        console.error('[SW] Error en purga tras logout:', err);
                    }
                    return response;
                }).catch((err) => {
                    caches.delete(RECIPES_CACHE);
                    caches.delete(IMAGES_CACHE);
                    caches.delete(PLANS_CACHE);
                    caches.delete(MEALPREPS_CACHE);
                    caches.delete(SHOPPING_CACHE);
                    throw err;
                })
            );
            return;
        }
        return; // Dejar pasar a red por defecto (network-only)
    }

    // Regla 2: GET a /offline-favorites.json
    if (url.origin === self.location.origin && url.pathname === '/offline-favorites.json') {
        event.respondWith(
            caches.open(STATIC_CACHE).then(async (cache) => {
                const matched = await cache.match('/offline-favorites.json');
                return matched || new Response(JSON.stringify([]), {
                    headers: { 'Content-Type': 'application/json' }
                });
            })
        );
        return;
    }

    // Regla 3: GET same-origin /assets/*
    if (url.origin === self.location.origin && url.pathname.startsWith('/assets/')) {
        event.respondWith(staleWhileRevalidate(request, STATIC_CACHE));
        return;
    }

    // Regla 4: GET same-origin /receta/*
    if (url.origin === self.location.origin && url.pathname.match(/^\/receta\/\d+/)) {
        event.respondWith(
            staleWhileRevalidate(request, RECIPES_CACHE)
                .catch(async () => {
                    const fallback = await caches.match('/offline.html', { ignoreSearch: true });
                    return fallback || Response.error();
                })
        );
        return;
    }

    // Regla 5: GET imágenes Spoonacular (cross-origin)
    if (url.hostname.includes('spoonacular.com')) {
        event.respondWith(cacheFirstImage(request));
        return;
    }

    // Regla 6: GET a /api/diet-helper/plan/{id}
    if (url.origin === self.location.origin && url.pathname.match(/^\/api\/diet-helper\/plan\/\d+$/)) {
        event.respondWith(
            staleWhileRevalidate(request, PLANS_CACHE)
                .catch(async () => {
                    const fallback = await caches.match('/offline.html', { ignoreSearch: true });
                    return fallback || Response.error();
                })
        );
        return;
    }

    // Regla 7: GET a /api/meal-prep-favorites/{id}
    if (url.origin === self.location.origin && url.pathname.match(/^\/api\/meal-prep-favorites\/\d+$/)) {
        event.respondWith(
            staleWhileRevalidate(request, MEALPREPS_CACHE)
                .catch(async () => {
                    const fallback = await caches.match('/offline.html', { ignoreSearch: true });
                    return fallback || Response.error();
                })
        );
        return;
    }

    // Regla 8: GET a /api/shopping-lists
    if (url.origin === self.location.origin && url.pathname === '/api/shopping-lists') {
        event.respondWith(
            staleWhileRevalidate(request, SHOPPING_CACHE)
                .catch(async () => {
                    const fallback = await caches.match('/offline.html', { ignoreSearch: true });
                    return fallback || Response.error();
                })
        );
        return;
    }

    // Regla 9: GET a /api/diet-helper/shopping-list/{id}
    if (url.origin === self.location.origin && url.pathname.match(/^\/api\/diet-helper\/shopping-list\/\d+$/)) {
        event.respondWith(
            staleWhileRevalidate(request, SHOPPING_CACHE)
                .catch(async () => {
                    const fallback = await caches.match('/offline.html', { ignoreSearch: true });
                    return fallback || Response.error();
                })
        );
        return;
    }

    // Regla 10: GET a otros /api/*
    if (url.pathname.startsWith('/api/')) {
        return; // network-only
    }

    // Regla 11: GET navegación a otras rutas
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .catch(async () => {
                    const cached = await caches.match(request, { ignoreSearch: true });
                    if (cached) return cached;
                    const fallback = await caches.match('/offline.html', { ignoreSearch: true });
                    return fallback || Response.error();
                })
        );
        return;
    }
});

// Comunicación vía postMessage
self.addEventListener('message', (event) => {
    const data = event.data;
    if (!data) return;

    if (data.type === 'CACHE_RECIPE') {
        const { spoonacularId, title, imageUrl } = data;
        if (spoonacularId) {
            event.waitUntil(cacheRecipe(spoonacularId, title, imageUrl));
        }
    } else if (data.type === 'UNCACHE_RECIPE') {
        const { spoonacularId, imageUrl } = data;
        if (spoonacularId) {
            event.waitUntil(uncacheRecipe(spoonacularId, imageUrl));
        }
    } else if (data.type === 'RECONCILE') {
        const { favorites } = data;
        if (Array.isArray(favorites)) {
            event.waitUntil(reconcileFavorites(favorites));
        }
    } else if (data.type === 'CACHE_PLAN') {
        const { planId, title } = data;
        if (planId) {
            event.waitUntil(cachePlan(planId, title));
        }
    } else if (data.type === 'UNCACHE_PLAN') {
        const { planId } = data;
        if (planId) {
            event.waitUntil(uncachePlan(planId));
        }
    } else if (data.type === 'RECONCILE_PLANS') {
        const { plans } = data;
        if (Array.isArray(plans)) {
            event.waitUntil(reconcilePlans(plans));
        }
    } else if (data.type === 'CACHE_MEALPREP') {
        const { mealPrepId, title } = data;
        if (mealPrepId) {
            event.waitUntil(cacheMealPrep(mealPrepId, title));
        }
    } else if (data.type === 'UNCACHE_MEALPREP') {
        const { mealPrepId } = data;
        if (mealPrepId) {
            event.waitUntil(uncacheMealPrep(mealPrepId));
        }
    } else if (data.type === 'RECONCILE_MEALPREPS') {
        const { mealpreps } = data;
        if (Array.isArray(mealpreps)) {
            event.waitUntil(reconcileMealPreps(mealpreps));
        }
    } else if (data.type === 'CACHE_SHOPPING_LIST') {
        const { listId, name } = data;
        if (listId) {
            event.waitUntil(cacheShoppingList(listId, name));
        }
    } else if (data.type === 'UNCACHE_SHOPPING_LIST') {
        const { listId } = data;
        if (listId) {
            event.waitUntil(uncacheShoppingList(listId));
        }
    } else if (data.type === 'RECONCILE_SHOPPING') {
        const { lists } = data;
        if (Array.isArray(lists)) {
            event.waitUntil(reconcileShopping(lists));
        }
    }
});

/* ---------- Funciones Auxiliares ---------- */

async function staleWhileRevalidate(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request, { ignoreSearch: true });

    const fetchPromise = fetch(request).then(async (networkResponse) => {
        if (networkResponse && networkResponse.status === 200) {
            await cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    });

    return cachedResponse || fetchPromise;
}

async function cacheFirstImage(request) {
    const cache = await caches.open(IMAGES_CACHE);
    const cachedResponse = await cache.match(request, { ignoreSearch: true });

    if (cachedResponse) {
        return cachedResponse;
    }

    const networkResponse = await fetch(request);
    await cache.put(request, networkResponse.clone());
    await limitCacheItems(IMAGES_CACHE, 60);
    return networkResponse;
}

async function limitCacheItems(cacheName, maxItems) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    if (keys.length > maxItems) {
        const excess = keys.length - maxItems;
        for (let i = 0; i < excess; i++) {
            await cache.delete(keys[i]);
        }
    }
}

async function getOfflineIndex() {
    const cache = await caches.open(STATIC_CACHE);
    const matched = await cache.match('/offline-favorites.json');
    if (matched) {
        try {
            return await matched.json();
        } catch (_) {}
    }
    return [];
}

async function updateOfflineIndex(list) {
    const cache = await caches.open(STATIC_CACHE);
    await cache.put('/offline-favorites.json', new Response(JSON.stringify(list), {
        headers: { 'Content-Type': 'application/json' }
    }));
}

async function cacheRecipe(spoonacularId, title, imageUrl) {
    const recipeUrl = `/receta/${spoonacularId}`;
    try {
        const recipeCache = await caches.open(RECIPES_CACHE);
        const response = await fetch(recipeUrl);
        if (response.status === 200) {
            await recipeCache.put(recipeUrl, response);
        }

        if (imageUrl) {
            const imageCache = await caches.open(IMAGES_CACHE);
            const imgResponse = await fetch(imageUrl);
            await imageCache.put(imageUrl, imgResponse);
            await limitCacheItems(IMAGES_CACHE, 60);
        }

        // Actualizar índice JSON
        const list = await getOfflineIndex();
        if (!list.some((item) => item.spoonacular_id === parseInt(spoonacularId, 10))) {
            list.push({
                spoonacular_id: parseInt(spoonacularId, 10),
                title: title,
                image: imageUrl
            });
            await updateOfflineIndex(list);
        }
    } catch (err) {
        console.error('[SW] Error al cachear receta:', spoonacularId, err);
    }
}

async function uncacheRecipe(spoonacularId, imageUrl) {
    const recipeUrl = `/receta/${spoonacularId}`;
    try {
        const recipeCache = await caches.open(RECIPES_CACHE);
        await recipeCache.delete(recipeUrl, { ignoreSearch: true });

        // Borrar imagen
        let resolvedImageUrl = imageUrl;
        if (!resolvedImageUrl) {
            const cachedRecipe = await recipeCache.match(recipeUrl, { ignoreSearch: true });
            if (cachedRecipe) {
                const html = await cachedRecipe.text();
                const match = html.match(/class="receta-img"\s+src="([^"]+)"/);
                if (match && match[1]) {
                    resolvedImageUrl = match[1];
                }
            }
        }

        if (resolvedImageUrl) {
            const imageCache = await caches.open(IMAGES_CACHE);
            await imageCache.delete(resolvedImageUrl, { ignoreSearch: true });
        }

        // Actualizar índice JSON
        const list = await getOfflineIndex();
        const updatedList = list.filter((item) => item.spoonacular_id !== parseInt(spoonacularId, 10));
        await updateOfflineIndex(updatedList);
    } catch (err) {
        console.error('[SW] Error al des-cachear receta:', spoonacularId, err);
    }
}

async function reconcileFavorites(favorites) {
    try {
        const recipeCache = await caches.open(RECIPES_CACHE);
        const imageCache = await caches.open(IMAGES_CACHE);

        const activeIds = new Set(favorites.map((f) => parseInt(f.spoonacular_id, 10)));
        const activeImages = new Set(favorites.map((f) => f.image).filter(Boolean));

        // 1. Evictar recetas huérfanas
        const recipeKeys = await recipeCache.keys();
        for (const req of recipeKeys) {
            const path = new URL(req.url).pathname;
            const match = path.match(/^\/receta\/(\d+)/);
            if (match) {
                const id = parseInt(match[1], 10);
                if (!activeIds.has(id)) {
                    await recipeCache.delete(req);
                }
            }
        }

        // 2. Evictar imágenes huérfanas
        const imageKeys = await imageCache.keys();
        for (const req of imageKeys) {
            const urlString = req.url;
            const isImageActive = Array.from(activeImages).some((activeUrl) => {
                try {
                    return new URL(activeUrl).pathname === new URL(urlString).pathname;
                } catch (_) {
                    return activeUrl === urlString;
                }
            });
            if (!isImageActive) {
                await imageCache.delete(req);
            }
        }

        // 3. Cachear faltantes
        for (const fav of favorites) {
            const sid = parseInt(fav.spoonacular_id, 10);
            if (!sid) continue;

            const cached = await recipeCache.match(`/receta/${sid}`, { ignoreSearch: true });
            if (!cached) {
                await cacheRecipe(sid, fav.title, fav.image);
            }
        }

        // 4. Escribir índice completo reconciliado
        const list = favorites.map((fav) => ({
            spoonacular_id: parseInt(fav.spoonacular_id, 10),
            title: fav.title,
            image: fav.image
        }));
        await updateOfflineIndex(list);
    } catch (err) {
        console.error('[SW] Error en reconciliación:', err);
    }
}

/* ---------- Funciones auxiliares para Plans ---------- */

async function getOfflinePlansIndex() {
    const cache = await caches.open(STATIC_CACHE);
    const matched = await cache.match('/offline-plans.json');
    if (matched) {
        try {
            return await matched.json();
        } catch (_) {}
    }
    return [];
}

async function updateOfflinePlansIndex(list) {
    const cache = await caches.open(STATIC_CACHE);
    await cache.put('/offline-plans.json', new Response(JSON.stringify(list), {
        headers: { 'Content-Type': 'application/json' }
    }));
}

async function cachePlan(planId, title) {
    const planUrl = `/api/diet-helper/plan/${planId}`;
    try {
        const planCache = await caches.open(PLANS_CACHE);
        const response = await fetch(planUrl);
        if (response.status === 200) {
            await planCache.put(planUrl, response);
        }

        // Actualizar índice JSON
        const list = await getOfflinePlansIndex();
        if (!list.some((item) => item.plan_id === parseInt(planId, 10))) {
            list.push({
                plan_id: parseInt(planId, 10),
                title: title
            });
            await updateOfflinePlansIndex(list);
        }
    } catch (err) {
        console.error('[SW] Error al cachear plan:', planId, err);
    }
}

async function uncachePlan(planId) {
    const planUrl = `/api/diet-helper/plan/${planId}`;
    try {
        const planCache = await caches.open(PLANS_CACHE);
        await planCache.delete(planUrl, { ignoreSearch: true });

        // Actualizar índice JSON
        const list = await getOfflinePlansIndex();
        const updatedList = list.filter((item) => item.plan_id !== parseInt(planId, 10));
        await updateOfflinePlansIndex(updatedList);
    } catch (err) {
        console.error('[SW] Error al des-cachear plan:', planId, err);
    }
}

async function reconcilePlans(plans) {
    try {
        const planCache = await caches.open(PLANS_CACHE);
        const activeIds = new Set(plans.map((p) => parseInt(p.plan_id, 10)));

        // Evictar planes huérfanos
        const planKeys = await planCache.keys();
        for (const req of planKeys) {
            const path = new URL(req.url).pathname;
            const match = path.match(/^\/api\/diet-helper\/plan\/(\d+)$/);
            if (match) {
                const id = parseInt(match[1], 10);
                if (!activeIds.has(id)) {
                    await planCache.delete(req);
                }
            }
        }

        // Cachear faltantes
        for (const plan of plans) {
            const pid = parseInt(plan.plan_id, 10);
            if (!pid) continue;

            const cached = await planCache.match(`/api/diet-helper/plan/${pid}`, { ignoreSearch: true });
            if (!cached) {
                await cachePlan(pid, plan.title);
            }
        }

        // Escribir índice completo reconciliado
        const list = plans.map((p) => ({
            plan_id: parseInt(p.plan_id, 10),
            title: p.title
        }));
        await updateOfflinePlansIndex(list);
    } catch (err) {
        console.error('[SW] Error en reconciliación de planes:', err);
    }
}

/* ---------- Funciones auxiliares para Meal Preps ---------- */

async function getOfflineMealPrepsIndex() {
    const cache = await caches.open(STATIC_CACHE);
    const matched = await cache.match('/offline-mealpreps.json');
    if (matched) {
        try {
            return await matched.json();
        } catch (_) {}
    }
    return [];
}

async function updateOfflineMealPrepsIndex(list) {
    const cache = await caches.open(STATIC_CACHE);
    await cache.put('/offline-mealpreps.json', new Response(JSON.stringify(list), {
        headers: { 'Content-Type': 'application/json' }
    }));
}

async function cacheMealPrep(mealPrepId, title) {
    const mealPrepUrl = `/api/meal-prep-favorites/${mealPrepId}`;
    try {
        const mealPrepCache = await caches.open(MEALPREPS_CACHE);
        const response = await fetch(mealPrepUrl);
        if (response.status === 200) {
            await mealPrepCache.put(mealPrepUrl, response);
        }

        // Actualizar índice JSON
        const list = await getOfflineMealPrepsIndex();
        if (!list.some((item) => item.meal_prep_id === parseInt(mealPrepId, 10))) {
            list.push({
                meal_prep_id: parseInt(mealPrepId, 10),
                title: title
            });
            await updateOfflineMealPrepsIndex(list);
        }
    } catch (err) {
        console.error('[SW] Error al cachear meal prep:', mealPrepId, err);
    }
}

async function uncacheMealPrep(mealPrepId) {
    const mealPrepUrl = `/api/meal-prep-favorites/${mealPrepId}`;
    try {
        const mealPrepCache = await caches.open(MEALPREPS_CACHE);
        await mealPrepCache.delete(mealPrepUrl, { ignoreSearch: true });

        // Actualizar índice JSON
        const list = await getOfflineMealPrepsIndex();
        const updatedList = list.filter((item) => item.meal_prep_id !== parseInt(mealPrepId, 10));
        await updateOfflineMealPrepsIndex(updatedList);
    } catch (err) {
        console.error('[SW] Error al des-cachear meal prep:', mealPrepId, err);
    }
}

async function reconcileMealPreps(mealpreps) {
    try {
        const mealPrepCache = await caches.open(MEALPREPS_CACHE);
        const activeIds = new Set(mealpreps.map((mp) => parseInt(mp.meal_prep_id, 10)));

        // Evictar meal preps huérfanos
        const mealPrepKeys = await mealPrepCache.keys();
        for (const req of mealPrepKeys) {
            const path = new URL(req.url).pathname;
            const match = path.match(/^\/api\/meal-prep-favorites\/(\d+)$/);
            if (match) {
                const id = parseInt(match[1], 10);
                if (!activeIds.has(id)) {
                    await mealPrepCache.delete(req);
                }
            }
        }

        // Cachear faltantes
        for (const mp of mealpreps) {
            const mpid = parseInt(mp.meal_prep_id, 10);
            if (!mpid) continue;

            const cached = await mealPrepCache.match(`/api/meal-prep-favorites/${mpid}`, { ignoreSearch: true });
            if (!cached) {
                await cacheMealPrep(mpid, mp.title);
            }
        }

        // Escribir índice completo reconciliado
        const list = mealpreps.map((mp) => ({
            meal_prep_id: parseInt(mp.meal_prep_id, 10),
            title: mp.title
        }));
        await updateOfflineMealPrepsIndex(list);
    } catch (err) {
        console.error('[SW] Error en reconciliación de meal preps:', err);
    }
}

/* ---------- Funciones auxiliares para Shopping Lists ---------- */

async function getOfflineShoppingIndex() {
    const cache = await caches.open(STATIC_CACHE);
    const matched = await cache.match('/offline-shopping.json');
    if (matched) {
        try {
            return await matched.json();
        } catch (_) {}
    }
    return [];
}

async function updateOfflineShoppingIndex(list) {
    const cache = await caches.open(STATIC_CACHE);
    await cache.put('/offline-shopping.json', new Response(JSON.stringify(list), {
        headers: { 'Content-Type': 'application/json' }
    }));
}

async function cacheShoppingList(listId, name) {
    const listUrl = `/api/shopping-lists`;
    try {
        const shoppingCache = await caches.open(SHOPPING_CACHE);
        const response = await fetch(listUrl);
        if (response.status === 200) {
            await shoppingCache.put(listUrl, response);
        }

        // Actualizar índice JSON
        const list = await getOfflineShoppingIndex();
        if (!list.some((item) => item.list_id === parseInt(listId, 10))) {
            list.push({
                list_id: parseInt(listId, 10),
                name: name
            });
            await updateOfflineShoppingIndex(list);
        }
    } catch (err) {
        console.error('[SW] Error al cachear lista de compras:', listId, err);
    }
}

async function uncacheShoppingList(listId) {
    try {
        // Para listas de compras, solo actualizamos el índice ya que la URL es compartida
        const list = await getOfflineShoppingIndex();
        const updatedList = list.filter((item) => item.list_id !== parseInt(listId, 10));
        await updateOfflineShoppingIndex(updatedList);
    } catch (err) {
        console.error('[SW] Error al des-cachear lista de compras:', listId, err);
    }
}

async function reconcileShopping(lists) {
    try {
        const shoppingCache = await caches.open(SHOPPING_CACHE);
        const activeIds = new Set(lists.map((l) => parseInt(l.list_id, 10)));

        // Escribir índice completo reconciliado
        const list = lists.map((l) => ({
            list_id: parseInt(l.list_id, 10),
            name: l.name
        }));
        await updateOfflineShoppingIndex(list);

        // Refrescar caché compartida
        const response = await fetch('/api/shopping-lists');
        if (response.status === 200) {
            await shoppingCache.put('/api/shopping-lists', response);
        }
    } catch (err) {
        console.error('[SW] Error en reconciliación de listas de compras:', err);
    }
}
