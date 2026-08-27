(function () {
    'use strict';

    let preloadCargado = false;

    function setLoginStatus(message, ok) {
        const box = document.getElementById('loginEstado');
        if (!box) return;
        box.textContent = message || '';
        box.className = 'estado ' + (ok ? 'ok' : 'error');
    }

    function setGeneralStatus(message, ok) {
        if (typeof mostrarEstadoGeneral === 'function') {
            mostrarEstadoGeneral(message, ok);
            return;
        }
        const box = document.getElementById('estadoGeneral');
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
        return (data && (data.message || data.error)) || 'No se pudo completar la operación.';
    }

    function obtenerPreloadToken() {
        const queryToken = new URLSearchParams(window.location.search).get('preload');
        if (/^[A-Za-z0-9]{48}$/.test(queryToken || '')) {
            return queryToken;
        }

        const match = window.location.pathname.match(/\/comprobantes\/manual\/([A-Za-z0-9]{48})\/?$/);
        return match ? match[1] : '';
    }

    function mostrarAplicacion() {
        if (typeof mostrarApp === 'function') {
            mostrarApp();
            return;
        }
        const loginPanel = document.getElementById('loginPanel');
        const appPanel = document.getElementById('appPanel');
        if (loginPanel) loginPanel.classList.add('hidden');
        if (appPanel) appPanel.classList.remove('hidden');
    }

    function agregarBotonVolver() {
        if (document.getElementById('btnVolverVentasSunat')) return;

        const topbar = document.querySelector('.topbar');
        if (!topbar) return;

        const button = document.createElement('button');
        button.id = 'btnVolverVentasSunat';
        button.type = 'button';
        button.textContent = '← Volver a ventas';
        button.style.cssText = 'border:0;border-radius:8px;padding:9px 14px;background:#dc2626;color:#fff;font-weight:700;cursor:pointer;margin-left:12px;';

        button.addEventListener('click', function () {
            try {
                if (document.referrer) {
                    const ref = new URL(document.referrer);
                    if (ref.origin !== window.location.origin || /ventas_(servicios|productos)\.php/i.test(ref.pathname)) {
                        window.location.href = document.referrer;
                        return;
                    }
                }
            } catch (_) {}

            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.close();
            }
        });

        topbar.appendChild(button);
    }

    async function cargarVentaReal(authToken) {
        if (preloadCargado) return true;

        const preloadToken = obtenerPreloadToken();
        if (!preloadToken) return false;
        if (!authToken) return false;

        try {
            setGeneralStatus('Cargando datos de la venta...', true);

            const response = await fetch(
                window.location.origin + '/api/v1/comprobantes/preload/' + encodeURIComponent(preloadToken),
                {
                    headers: {
                        'Authorization': 'Bearer ' + authToken,
                        'Accept': 'application/json'
                    }
                }
            );

            const data = await readJson(response);
            if (!response.ok || data.success === false) {
                throw new Error(errorMessage(data));
            }

            if (typeof aplicarPreload !== 'function') {
                throw new Error('No se encontró la función que carga el detalle de la venta.');
            }

            aplicarPreload(data.data || {});
            preloadCargado = true;
            mostrarAplicacion();
            setGeneralStatus('Venta cargada correctamente. Revisa los datos y emite el comprobante.', true);
            agregarBotonVolver();
            return true;
        } catch (error) {
            setGeneralStatus(error && error.message ? error.message : 'No se pudo cargar la venta.', false);
            return false;
        }
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

            try {
                if (typeof token !== 'undefined') {
                    token = data.access_token;
                }
            } catch (_) {}

            setLoginStatus('Acceso correcto. Cargando venta...', true);
            mostrarAplicacion();
            agregarBotonVolver();

            const cargada = await cargarVentaReal(data.access_token);
            if (!cargada && typeof cargarPreload === 'function') {
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
        agregarBotonVolver();

        const loginPanel = document.getElementById('loginPanel');
        const oldButton = loginPanel ? loginPanel.querySelector('button.btn.primary') : null;
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

        const savedToken = localStorage.getItem('sunat_token') || '';
        if (savedToken && obtenerPreloadToken()) {
            // Se ejecuta además del bootstrap original para garantizar que el
            // detalle real se cargue incluso en navegadores embebidos.
            window.setTimeout(function () {
                mostrarAplicacion();
                cargarVentaReal(savedToken);
            }, 120);
        }
    });
})();
