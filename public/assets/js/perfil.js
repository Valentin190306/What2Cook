function triggerAvatarUpload() {
    const fileInput = document.getElementById('avatar-file-input');
    if (fileInput) {
        fileInput.click();
    }
}

async function handleAvatarUpload(input) {
    if (!input.files || input.files.length === 0) return;
    
    const file = input.files[0];
    
    // Check file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
        alert('La imagen no debe superar los 5MB.');
        input.value = '';
        return;
    }
    
    const formData = new FormData();
    formData.append('avatar', file);
    
    const overlaySpan = document.querySelector('.avatar-overlay span');
    const originalText = overlaySpan ? overlaySpan.textContent : 'Editar';
    if (overlaySpan) {
        overlaySpan.textContent = 'Subiendo...';
    }
    
    try {
        const response = await fetch('/api/profile/avatar', {
            method: 'POST',
            body: formData
        });
        
        if (response.status === 401) {
            window.location = '/login';
            return;
        }
        
        const data = await response.json();
        
        if (!response.ok) {
            alert(data.error || 'Hubo un error al subir el avatar.');
            return;
        }
        
        if (data.success && data.avatar_url) {
            // Update the profile avatar image on screen
            const avatarImg = document.getElementById('profile-avatar');
            if (avatarImg) {
                avatarImg.src = data.avatar_url;
            }
        }
    } catch (error) {
        console.error('Error al subir el avatar:', error);
        alert('Error de red al subir el avatar.');
    } finally {
        if (overlaySpan) {
            overlaySpan.textContent = originalText;
        }
        input.value = '';
    }
}
