document.addEventListener('DOMContentLoaded', () => {
    // 1. Password Visibility Toggle
    const eyeOpenSvg = `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    const eyeClosedSvg = `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

    const toggleButtons = document.querySelectorAll('.btn-toggle-password');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentNode.querySelector('input');
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.innerHTML = eyeClosedSvg;
                    btn.setAttribute('aria-label', 'Ocultar contraseña');
                } else {
                    input.type = 'password';
                    btn.innerHTML = eyeOpenSvg;
                    btn.setAttribute('aria-label', 'Mostrar contraseña');
                }
            }
        });
    });

    // 2. Real-time Form Validation (Registration page only)
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirm');
    const emailInput = document.getElementById('email');
    const form = document.querySelector('form');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

    // Check if we are on the register page (requires confirmInput)
    if (!passwordInput || !confirmInput || !emailInput) return;

    let emailIsValid = false;
    let passwordIsValid = false;
    let passwordsMatch = false;

    // A. Email warning element
    const emailMsg = document.createElement('div');
    emailMsg.style.color = 'var(--color-carrot, #e65c00)';
    emailMsg.style.fontSize = '0.8rem';
    emailMsg.style.fontWeight = '700';
    emailMsg.style.marginTop = '0.3rem';
    emailMsg.style.fontFamily = 'var(--font-title, sans-serif)';
    emailMsg.style.textTransform = 'uppercase';
    emailMsg.style.display = 'none';
    emailInput.parentNode.appendChild(emailMsg);

    // B. Password strength warning element
    const strengthMsg = document.createElement('div');
    strengthMsg.style.color = 'var(--color-carrot, #e65c00)';
    strengthMsg.style.fontSize = '0.8rem';
    strengthMsg.style.fontWeight = '700';
    strengthMsg.style.marginTop = '0.3rem';
    strengthMsg.style.fontFamily = 'var(--font-title, sans-serif)';
    strengthMsg.style.textTransform = 'uppercase';
    strengthMsg.style.display = 'none';
    passwordInput.parentNode.appendChild(strengthMsg);

    // C. Password match warning element
    const matchMsg = document.createElement('div');
    matchMsg.style.color = 'var(--color-carrot, #e65c00)';
    matchMsg.style.fontSize = '0.8rem';
    matchMsg.style.fontWeight = '700';
    matchMsg.style.marginTop = '0.3rem';
    matchMsg.style.fontFamily = 'var(--font-title, sans-serif)';
    matchMsg.style.textTransform = 'uppercase';
    matchMsg.style.display = 'none';
    matchMsg.textContent = 'Las contraseñas no coinciden';
    confirmInput.parentNode.appendChild(matchMsg);

    const updateSubmitState = () => {
        if (submitBtn) {
            if (emailIsValid && passwordIsValid && passwordsMatch) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '';
                submitBtn.style.cursor = '';
            } else {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                submitBtn.style.cursor = 'not-allowed';
            }
        }
    };

    // Debounce timeout for email API check
    let emailTimeout = null;

    const checkEmailAvailability = () => {
        const email = emailInput.value.trim();
        emailMsg.style.display = 'none';
        emailInput.style.borderColor = '';
        emailInput.style.backgroundColor = '';

        if (email === '') {
            emailIsValid = false;
            updateSubmitState();
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            emailMsg.textContent = 'Formato de correo inválido';
            emailMsg.style.display = 'block';
            emailInput.style.borderColor = 'var(--color-carrot, #e65c00)';
            emailInput.style.borderWidth = '2px';
            emailInput.style.backgroundColor = '#fff5f2';
            emailIsValid = false;
            updateSubmitState();
            return;
        }

        clearTimeout(emailTimeout);
        emailTimeout = setTimeout(async () => {
            try {
                const response = await fetch('/api/check-email', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.exists) {
                        emailMsg.textContent = 'El email ya está registrado';
                        emailMsg.style.display = 'block';
                        emailInput.style.borderColor = 'var(--color-carrot, #e65c00)';
                        emailInput.style.borderWidth = '2px';
                        emailInput.style.backgroundColor = '#fff5f2';
                        emailIsValid = false;
                    } else {
                        emailInput.style.borderColor = 'var(--color-spinach, #2c5e43)';
                        emailInput.style.borderWidth = '2px';
                        emailInput.style.backgroundColor = '#f2faf5';
                        emailIsValid = true;
                    }
                } else {
                    emailIsValid = true; // Fallback
                }
            } catch (e) {
                console.error('Error verificando disponibilidad de email:', e);
                emailIsValid = true;
            }
            updateSubmitState();
        }, 400);
    };

    const validatePasswordStrength = () => {
        const pass = passwordInput.value;
        strengthMsg.style.display = 'none';
        passwordInput.style.borderColor = '';
        passwordInput.style.backgroundColor = '';

        if (pass === '') {
            passwordIsValid = false;
            updateSubmitState();
            return;
        }

        if (pass.length < 8) {
            strengthMsg.textContent = 'Mínimo 8 caracteres';
            strengthMsg.style.display = 'block';
            passwordInput.style.borderColor = 'var(--color-carrot, #e65c00)';
            passwordInput.style.borderWidth = '2px';
            passwordInput.style.backgroundColor = '#fff5f2';
            passwordIsValid = false;
        } else {
            const hasUpper = /[A-Z]/.test(pass);
            const hasLower = /[a-z]/.test(pass);
            const hasDigit = /[0-9]/.test(pass);

            if (!hasUpper || !hasLower || !hasDigit) {
                strengthMsg.textContent = 'Debe incluir mayúsculas, minúsculas y números';
                strengthMsg.style.display = 'block';
                passwordInput.style.borderColor = 'var(--color-carrot, #e65c00)';
                passwordInput.style.borderWidth = '2px';
                passwordInput.style.backgroundColor = '#fff5f2';
                passwordIsValid = false;
            } else {
                passwordInput.style.borderColor = 'var(--color-spinach, #2c5e43)';
                passwordInput.style.borderWidth = '2px';
                passwordInput.style.backgroundColor = '#f2faf5';
                passwordIsValid = true;
            }
        }
        validatePasswordsMatch();
    };

    const validatePasswordsMatch = () => {
        const pass = passwordInput.value;
        const confirmPass = confirmInput.value;

        if (pass === '' || confirmPass === '') {
            confirmInput.style.borderColor = '';
            confirmInput.style.backgroundColor = '';
            matchMsg.style.display = 'none';
            passwordsMatch = false;
            updateSubmitState();
            return;
        }

        if (pass !== confirmPass) {
            confirmInput.style.borderColor = 'var(--color-carrot, #e65c00)';
            confirmInput.style.borderWidth = '2px';
            confirmInput.style.backgroundColor = '#fff5f2';
            matchMsg.style.display = 'block';
            passwordsMatch = false;
        } else {
            confirmInput.style.borderColor = 'var(--color-spinach, #2c5e43)';
            confirmInput.style.borderWidth = '2px';
            confirmInput.style.backgroundColor = '#f2faf5';
            matchMsg.style.display = 'none';
            passwordsMatch = true;
        }
        updateSubmitState();
    };

    emailInput.addEventListener('input', checkEmailAvailability);
    passwordInput.addEventListener('input', validatePasswordStrength);
    confirmInput.addEventListener('input', validatePasswordsMatch);

    if (form) {
        form.addEventListener('submit', (e) => {
            if (!emailIsValid || !passwordIsValid || !passwordsMatch) {
                e.preventDefault();
            }
        });
    }
});
