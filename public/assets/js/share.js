function shareOrCopy(options) {
    options = options || {};
    var title = options.title || document.title;
    var text = options.text || '';
    var shareText = text || window.location.href;

    if (navigator.share) {
        navigator.share({
            title: title,
            text: shareText
        }).catch(function (err) {
            if (err && err.name !== 'AbortError') {
                console.error('Error al compartir:', err);
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var shareBtn = document.getElementById('btn-share-search');
    if (shareBtn) {
        shareBtn.addEventListener('click', function () {
            shareOrCopy({
                title: 'Catálogo de Recetas - What2Cook',
                text: this.dataset.shareText || '¡Mira esta sección del catálogo de What2Cook!\n' + window.location.href
            });
        });
    }

    var recipeShareBtn = document.getElementById('btn-share-recipe');
    if (recipeShareBtn) {
        recipeShareBtn.addEventListener('click', function () {
            shareOrCopy({
                title: this.dataset.shareTitle || document.title,
                text: this.dataset.shareText || ''
            });
        });
    }

    var copySearchBtn = document.getElementById('btn-copy-search');
    if (copySearchBtn) {
        copySearchBtn.addEventListener('click', function () {
            var textToCopy = this.dataset.copyText || '';
            copyTextToClipboard(textToCopy);
        });
    }

    var copyRecipeBtn = document.getElementById('btn-copy-recipe');
    if (copyRecipeBtn) {
        copyRecipeBtn.addEventListener('click', function () {
            var textToCopy = this.dataset.copyText || '';
            copyTextToClipboard(textToCopy);
        });
    }
});

function copyTextToClipboard(textToCopy) {
    if (!textToCopy) {
        return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(textToCopy).then(function () {
            showToast('¡Enlace copiado al portapapeles!', 'success');
        }).catch(function () {
            fallbackManualCopy(textToCopy);
        });
    } else {
        fallbackManualCopy(textToCopy);
    }
}

function fallbackManualCopy(textToCopy) {
    try {
        var ta = document.createElement('textarea');
        ta.value = textToCopy;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
        showToast('¡Enlace copiado al portapapeles!', 'success');
    } catch (e) {
        console.error('Error al copiar manualmente:', e);
        showToast('No se pudo copiar el enlace. Intenta manualmente.', 'error');
    }
}

