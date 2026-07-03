document.addEventListener('DOMContentLoaded', () => {
    const status = document.getElementById('catalogue-status');
    const form = document.querySelector('.search-bar');
    const applyBtn = document.querySelector('.btn-apply');
    const clearBtn = document.querySelector('.btn-clear');
    const shareBtn = document.getElementById('btn-share-search');
    const paginationAnchors = document.querySelectorAll('.pagination-button:not(.disabled)');
    const recipeLinks = document.querySelectorAll('.recipe-link');

    function showLoading(message) {
        if (!status) return;
        status.innerHTML = `
            <div class="ca-loading-newspaper">
                <span class="ca-loading-spinner"></span>
                <span class="ca-loading-text">${message}</span>
            </div>
        `;
    }

    function buildQueryString() {
        const params = new URLSearchParams();
        const qInput = form ? form.querySelector('input[name="query"]') : null;
        const q = qInput ? qInput.value.trim() : '';
        if (q) params.set('query', q);

        // append multiple selections as arrays (type[], cuisine[], diet[], intolerances[])
        document.querySelectorAll('.filtros-opciones input[name="type[]"]:checked').forEach((el) => params.append('type[]', el.value));
        document.querySelectorAll('.filtros-opciones input[name="cuisine[]"]:checked').forEach((el) => params.append('cuisine[]', el.value));
        document.querySelectorAll('.filtros-opciones input[name="diet[]"]:checked').forEach((el) => params.append('diet[]', el.value));
        document.querySelectorAll('.filtros-opciones input[name="intolerances[]"]:checked').forEach((el) => params.append('intolerances[]', el.value));

        return params.toString();
    }

    function populateFiltersFromUrl() {
        const params = new URLSearchParams(window.location.search);
        
        // Populate query input
        const qInput = form ? form.querySelector('input[name="query"]') : null;
        if (qInput) {
            qInput.value = params.get('query') || '';
        }

        // Populate checkboxes
        ['type', 'cuisine', 'diet', 'intolerances'].forEach(filterType => {
            const values = params.getAll(filterType + '[]');
            document.querySelectorAll(`.filtros-opciones input[name="${filterType}[]"]`).forEach(input => {
                input.checked = values.includes(input.value);
            });
        });

        updateLabelStates();
    }

    function updateLabelStates() {
        document.querySelectorAll('.filtros-opciones input').forEach((input) => {
            const lab = input.closest('label');
            if (!lab) return;
            if (input.checked) {
                lab.classList.add('selected');
                lab.setAttribute('aria-pressed', 'true');
            } else {
                lab.classList.remove('selected');
                lab.setAttribute('aria-pressed', 'false');
            }
        });
        updateSummaries();
    }

    function updateSummaries() {
        document.querySelectorAll('.filters-group').forEach((group) => {
            const summary = group.querySelector('summary');
            if (!summary) return;
            const chipsContainer = summary.querySelector('.summary-chips');
            const selected = Array.from(group.querySelectorAll('.filtros-opciones input:checked'));
            if (chipsContainer) chipsContainer.innerHTML = '';
            if (selected.length > 0) {
                selected.forEach((sel) => {
                    if (sel.value === '') return;
                    const labelText = sel.closest('label')?.querySelector('span')?.textContent || sel.value;
                    if (labelText && chipsContainer) {
                        const chip = document.createElement('span');
                        chip.className = 'summary-chip';
                        chip.textContent = labelText;
                        chipsContainer.appendChild(chip);
                    }
                });
            }
        });
    }

    // Store original summary title for restoration
    document.querySelectorAll('.filters-group summary').forEach((s) => {
        const title = s.querySelector('.summary-title');
        s.dataset.original = title ? title.textContent.trim() : s.textContent.trim();
    });

    // Wire up change handlers on inputs
    document.querySelectorAll('.filtros-opciones input').forEach((input) => {
        input.addEventListener('change', updateLabelStates);
    });

    // Populate filters from URL on page load
    populateFiltersFromUrl();

    if (shareBtn) {
        shareBtn.addEventListener('click', async () => {
            const currentUrl = window.location.href;
            try {
                await navigator.clipboard.writeText(currentUrl);
                const originalText = shareBtn.innerHTML;
                shareBtn.innerHTML = `
                    <svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                    ¡Copiado!
                `;
                shareBtn.classList.add('copied');
                setTimeout(() => {
                    shareBtn.innerHTML = originalText;
                    shareBtn.classList.remove('copied');
                }, 2000);
            } catch (err) {
                console.error('Error al copiar URL:', err);
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = currentUrl;
                textArea.style.position = 'fixed';
                textArea.style.left = '-9999px';
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    const originalText = shareBtn.innerHTML;
                    shareBtn.innerHTML = `
                        <svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                        ¡Copiado!
                    `;
                    shareBtn.classList.add('copied');
                    setTimeout(() => {
                        shareBtn.innerHTML = originalText;
                        shareBtn.classList.remove('copied');
                    }, 2000);
                } catch (e) {
                    console.error('Error en fallback de copia:', e);
                }
                document.body.removeChild(textArea);
            }
        });
    }

    if (applyBtn) {
        applyBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const qs = buildQueryString();
            const url = '/recetas' + (qs ? '?' + qs : '');
            showLoading('Aplicando filtros...');
            history.pushState({}, '', url);
            window.location.href = url;
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelectorAll('.filtros-opciones input').forEach((i) => { i.checked = false; });
            if (form) {
                const qInput = form.querySelector('input[name="query"]');
                if (qInput) qInput.value = '';
            }
            updateLabelStates();
            const url = '/recetas';
            history.pushState({}, '', url);
            window.location.href = url;
        });
    }

    paginationAnchors.forEach((a) => {
        a.addEventListener('click', (e) => {
            if (a.classList.contains('disabled')) return;
            e.preventDefault();
            showLoading('Cargando recetas...');
            history.pushState({}, '', a.getAttribute('href'));
            window.location.href = a.getAttribute('href');
        });
    });

    recipeLinks.forEach((a) => {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            showLoading('Cargando receta...');
            window.location.href = a.getAttribute('href');
        });
    });

    // Show loader on form submit
    if (form) form.addEventListener('submit', () => showLoading('Buscando recetas...'));

    // Handle browser back/forward navigation
    window.addEventListener('popstate', () => {
        populateFiltersFromUrl();
    });

    // Initialize visual state
    updateLabelStates();
});
