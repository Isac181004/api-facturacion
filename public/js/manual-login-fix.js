(function () {
    'use strict';

    function setLoginStatus(message, ok) {
        const box = document.getElementById('loginEstado');
        if (!box) return;
        box.textContent = message || '';
        box.className = 'estado ' + (ok ? 'ok' : 'error');
    }

    async function readJson(response) {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (_) {
            return { message: text || 'Respuesta inválida del servidor.' };
        }
    }

    function errorMessage(data) {
        if (data && data.errors) {
            return Object.values(data.errors).flat().join('\n');
        }
        return (data && (data.message || data.error)) || 'No se pudo iniciar sesión.';
    }

    async function robustLogin(event) {
        if (event) event.preventDefault();

        const emailInput = document.getElementById('loginEmail');
        const passwordInput = document.getElementById('loginPassword');
        const button = document.getElementById('manualLoginButton');

        if (!emailInput || !passwordInput) {
            setLoginStatus('No se encontraron los campos de acceso.', false);
            return false;
        }

        const email = emailInput.value.trim();
        const password = passwordInput.value;

        if (!email || !password) {
            setLoginStatus('Ingresa correo y contraseña.', false);
            return false;
        }

        try {
            if (button) button.disabled = true;
            setLoginStatus('Ingresando...', true);

            const response = await fetch(window.location.origin + '/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const data = await readJson(response);

            if (!response.ok || !data.access_token) {
                throw new Error(errorMessage(data));
            }

            localStorage.setItem('sunat_token', data.access_token);

            // El Blade principal mantiene el token en una variable global léxica.
            // Actualizarla aquí evita recargar la página y perder el flujo ?preload=.
            try {
                if (typeof token !== 'undefined') {
                    token = data.access_token;
                }
            } catch (_) {
                // Si el navegador no expone el binding léxico, el resto del flujo
                // todavía puede usar el token persistido en localStorage.
            }

            setLoginStatus('Acceso correcto. Cargando facturación...', true);

            // NO recargar. En el video se veía que el login era correcto y la
            // recarga regresaba inmediatamente al formulario. Mostramos la app en
            // la misma página y cargamos la precarga con el token recién emitido.
            if (typeof mostrarApp === 'function') {
                mostrarApp();
            } else {
                const loginPanel = document.getElementById('loginPanel');
                const appPanel = document.getElementById('appPanel');
                if (loginPanel) loginPanel.classList.add('hidden');
                if (appPanel) appPanel.classList.remove('hidden');
            }

            if (typeof cargarPreload === 'function') {
                await cargarPreload();
            }

            return false;
        } catch (error) {
            setLoginStatus(error && error.message ? error.message : 'No se pudo iniciar sesión.', false);
            if (button) button.disabled = false;
            return false;
        }
    }

    window.login = robustLogin;

    document.addEventListener('DOMContentLoaded', function () {
        const loginPanel = document.getElementById('loginPanel');
        if (!loginPanel) return;

        const oldButton = loginPanel.querySelector('button.btn.primary');
        if (oldButton) {
            oldButton.id = 'manualLoginButton';
            oldButton.type = 'button';
            oldButton.onclick = robustLogin;
        }

        const passwordInput = document.getElementById('loginPassword');
        if (passwordInput) {
            passwordInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') robustLogin(event);
            });
        }
    });
})();
