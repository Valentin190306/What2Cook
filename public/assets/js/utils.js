/**
 * utils.js — Utilidades generales
 */

function el(id) { return document.getElementById(id); }
function show(elem) { elem.hidden = false; }
function hide(elem) { elem.hidden = true; }
function setText(elem, text) { elem.textContent = text; }

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function showToast(message, type, duration) {
    type = type || 'success';
    duration = duration || 3000;
    var existing = document.querySelector('.w2c-toast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.className = 'w2c-toast w2c-toast--' + type;
    toast.textContent = message;
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    document.body.appendChild(toast);

    toast.offsetHeight;
    toast.classList.add('w2c-toast--visible');

    setTimeout(function () {
        toast.classList.remove('w2c-toast--visible');
        setTimeout(function () { toast.remove(); }, 300);
    }, duration);
}

/**
 * Aplica conversión de unidades a todos los elementos con data-amount + data-unit.
 * También actualiza etiquetas con data-unit-label.
 * Se llama en DOMContentLoaded y ante cada cambio de sistema de unidades.
 */
function applyUnitConversionToPage() {
    if (typeof UnitConversion === 'undefined' || typeof UnitPreferences === 'undefined') return;

    var system = UnitPreferences.getPreferredSystem();

    // 1. Conversión de cantidades/ingredientes: [data-amount][data-unit]
    document.querySelectorAll('[data-amount][data-unit]').forEach(function (el) {
        var rawAmount = parseFloat(el.dataset.amount);
        var rawUnit   = el.dataset.unit;
        if (isNaN(rawAmount)) return;

        if (!rawUnit || rawUnit === '') {
            // No unit — just display the number as-is
            el.textContent = rawAmount;
            return;
        }

        if (system === 'metric') {
            var formatted = (Number.isInteger(rawAmount) ? rawAmount : Math.round(rawAmount * 100) / 100);
            el.textContent = formatted + ' ' + rawUnit;
        } else {
            var conv = UnitConversion.convertAmount(rawAmount, rawUnit, system);
            el.textContent = UnitConversion.roundValue(conv.amount) + ' ' + conv.unit;
        }
    });

    // 2. Conversión de macros de nutrición: [data-nutri-amount][data-nutri-unit]
    document.querySelectorAll('[data-nutri-amount][data-nutri-unit]').forEach(function (el) {
        var rawAmount = parseFloat(el.dataset.nutriAmount);
        var rawUnit   = el.dataset.nutriUnit || 'g';
        if (isNaN(rawAmount)) return;

        if (system === 'metric') {
            el.textContent = Math.round(rawAmount) + rawUnit;
        } else {
            var conv = UnitConversion.convertAmount(rawAmount, rawUnit, system);
            el.textContent = UnitConversion.roundValue(conv.amount) + ' ' + conv.unit;
        }
    });

    // 3. Etiquetas de unidad en formularios: [data-unit-label]
    //    El atributo contiene la unidad base (ej. "g"). Se reemplaza según el sistema.
    document.querySelectorAll('[data-unit-label]').forEach(function (labelEl) {
        var baseUnit = labelEl.dataset.unitLabel || 'g';
        if (system === 'metric') {
            labelEl.textContent = '(' + baseUnit + ')';
        } else if (system === 'imperial') {
            labelEl.textContent = '(oz)';
        } else if (system === 'us') {
            labelEl.textContent = '(oz)';
        } else {
            labelEl.textContent = '(' + baseUnit + ')';
        }
    });
}
