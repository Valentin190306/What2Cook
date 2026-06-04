document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirm');
    const form = document.querySelector('form');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

    if (!passwordInput || !confirmInput) return;

    // Create error message element
    const errorMsg = document.createElement('div');
    errorMsg.style.color = 'var(--color-carrot, #e65c00)';
    errorMsg.style.fontSize = '0.8rem';
    errorMsg.style.fontWeight = '700';
    errorMsg.style.marginTop = '0.3rem';
    errorMsg.style.fontFamily = 'var(--font-title, sans-serif)';
    errorMsg.style.textTransform = 'uppercase';
    errorMsg.style.display = 'none';
    errorMsg.textContent = 'Las contraseñas no coinciden';
    
    // Insert after confirmation input
    confirmInput.parentNode.appendChild(errorMsg);

    const validatePasswords = () => {
        const pass = passwordInput.value;
        const confirmPass = confirmInput.value;

        // If either is empty, reset styles
        if (pass === '' || confirmPass === '') {
            confirmInput.style.borderColor = '';
            confirmInput.style.backgroundColor = '';
            errorMsg.style.display = 'none';
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '';
                submitBtn.style.cursor = '';
            }
            return;
        }

        if (pass !== confirmPass) {
            confirmInput.style.borderColor = 'var(--color-carrot, #e65c00)';
            confirmInput.style.borderWidth = '2px';
            confirmInput.style.backgroundColor = '#fff5f2';
            errorMsg.style.display = 'block';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                submitBtn.style.cursor = 'not-allowed';
            }
        } else {
            // They match!
            confirmInput.style.borderColor = 'var(--color-spinach, #2c5e43)';
            confirmInput.style.borderWidth = '2px';
            confirmInput.style.backgroundColor = '#f2faf5';
            errorMsg.style.display = 'none';
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '';
                submitBtn.style.cursor = '';
            }
        }
    };

    passwordInput.addEventListener('input', validatePasswords);
    confirmInput.addEventListener('input', validatePasswords);

    if (form) {
        form.addEventListener('submit', (e) => {
            if (passwordInput.value !== confirmInput.value) {
                e.preventDefault();
                validatePasswords();
            }
        });
    }
});
