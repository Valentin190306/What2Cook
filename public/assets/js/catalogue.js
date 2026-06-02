document.addEventListener('DOMContentLoaded', () => {
    const status = document.getElementById('catalogue-status');
    const form = document.querySelector('.search-bar');
    const applyBtn = document.querySelector('.btn-apply');
    const clearBtn = document.querySelector('.btn-clear');
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

        // append multiple selections as arrays (type[], cuisine[], diet[])
        document.querySelectorAll('.filtros-opciones input[name="type[]"]:checked').forEach((el) => params.append('type[]', el.value));
        document.querySelectorAll('.filtros-opciones input[name="cuisine[]"]:checked').forEach((el) => params.append('cuisine[]', el.value));
        document.querySelectorAll('.filtros-opciones input[name="diet[]"]:checked').forEach((el) => params.append('diet[]', el.value));

        return params.toString();
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

    if (applyBtn) {
        applyBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const qs = buildQueryString();
            const url = '/recetas' + (qs ? '?' + qs : '');
            showLoading('Aplicando filtros...');
            window.location.href = url;
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelectorAll('.filtros-opciones input').forEach((i) => { i.checked = false; });
            updateLabelStates();
        });
    }

    paginationAnchors.forEach((a) => {
        a.addEventListener('click', (e) => {
            if (a.classList.contains('disabled')) return;
            e.preventDefault();
            showLoading('Cargando recetas...');
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

    // Initialize visual state
    updateLabelStates();
});
