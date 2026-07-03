const UnitPreferences = (function () {
    const STORAGE_KEY = 'preferredUnitSystem';
    const VALID_SYSTEMS = ['metric', 'imperial', 'us'];
    const DEFAULT_SYSTEM = 'metric';
    const EVENT_NAME = 'unit-system-changed';

    function getPreferredSystem() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (stored && VALID_SYSTEMS.includes(stored)) {
                return stored;
            }
        } catch (e) {}
        return DEFAULT_SYSTEM;
    }

    function setPreferredSystem(system) {
        if (!VALID_SYSTEMS.includes(system)) {
            return Promise.reject(new Error('Invalid unit system: ' + system));
        }

        try {
            localStorage.setItem(STORAGE_KEY, system);
        } catch (e) {}

        const event = new CustomEvent(EVENT_NAME, { detail: { unitSystem: system } });
        document.dispatchEvent(event);

        const isAuth = document.body.hasAttribute('data-user-authed');
        if (isAuth) {
            return fetch('/api/preferences/unit-system', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ unit_system: system })
            }).then(function (res) {
                if (!res.ok) {
                    console.error('Error saving unit preference to server');
                }
                return res;
            }).catch(function (err) {
                console.error('Error saving unit preference:', err);
            });
        }

        return Promise.resolve();
    }

    function syncFromServer() {
        var isAuth = document.body.hasAttribute('data-user-authed');
        if (!isAuth) return Promise.resolve();

        return fetch('/api/preferences/unit-system')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.unitSystem && VALID_SYSTEMS.includes(data.unitSystem)) {
                    try {
                        localStorage.setItem(STORAGE_KEY, data.unitSystem);
                    } catch (e) {}
                    var event = new CustomEvent(EVENT_NAME, {
                        detail: { unitSystem: data.unitSystem }
                    });
                    document.dispatchEvent(event);
                }
            })
            .catch(function (err) {
                console.error('Error syncing unit preference from server:', err);
            });
    }

    function onSystemChange(callback) {
        document.addEventListener(EVENT_NAME, function (e) {
            callback(e.detail.unitSystem);
        });
    }

    return {
        getPreferredSystem: getPreferredSystem,
        setPreferredSystem: setPreferredSystem,
        syncFromServer: syncFromServer,
        onSystemChange: onSystemChange
    };
})();
