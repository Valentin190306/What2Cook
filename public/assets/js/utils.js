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
