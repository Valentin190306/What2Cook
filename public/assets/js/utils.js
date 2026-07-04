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
