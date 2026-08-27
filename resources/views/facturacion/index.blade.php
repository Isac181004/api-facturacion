<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación electrónica</title>
    <style>
        :root {
            --navy: #0b1739;
            --navy-soft: #142451;
            --gold: #d4af37;
            --gold-soft: #fff6d6;
            --surface: #ffffff;
            --background: #f2f5fa;
            --line: #dfe5ee;
            --muted: #64748b;
            --text: #172033;
            --success: #087f5b;
            --danger: #b42318;
            --warning: #b54708;
            --radius: 16px;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--text);
            background:
                radial-gradient(circle at 10% 0%, rgba(212, 175, 55, .13), transparent 28%),
                linear-gradient(180deg, #e9eef7 0, var(--background) 270px);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        button, input, select { font: inherit; }
        button { cursor: pointer; }
        .hidden { display: none !important; }

        .topbar {
            min-height: 78px;
            padding: 14px clamp(18px, 4vw, 56px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            color: white;
            background: linear-gradient(120deg, var(--navy), var(--navy-soft));
            border-bottom: 3px solid var(--gold);
            box-shadow: 0 12px 30px rgba(11, 23, 57, .18);
        }
        .brand { display: flex; align-items: center; gap: 13px; }
        .brand-mark {
            width: 44px; height: 44px; display: grid; place-items: center;
            color: var(--navy); background: var(--gold); border-radius: 13px;
            font-size: 20px; font-weight: 900;
        }
        .brand h1 { margin: 0; font-size: clamp(18px, 2vw, 24px); }
        .brand p { margin: 2px 0 0; color: #bdc8df; font-size: 12px; }
        .top-actions { display: flex; align-items: center; gap: 10px; }
        .environment {
            padding: 7px 11px; border-radius: 999px; font-size: 11px; font-weight: 800;
            letter-spacing: .08em; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
        }
        .environment.production { color: #fff; background: #b42318; border-color: #d92d20; }

        .shell { width: min(1500px, 100%); margin: 0 auto; padding: 28px clamp(14px, 3vw, 38px) 50px; }
        .login-wrap { width: min(470px, calc(100% - 28px)); margin: 8vh auto; }
        .login-logo { text-align: center; margin-bottom: 18px; }
        .login-logo .brand-mark { margin: 0 auto 12px; width: 60px; height: 60px; font-size: 25px; }
        .login-logo h2 { margin: 0; color: var(--navy); }

        .layout { display: grid; grid-template-columns: minmax(0, 1fr) 350px; gap: 22px; align-items: start; }
        .card {
            background: rgba(255,255,255,.97); border: 1px solid rgba(215,223,235,.9);
            border-radius: var(--radius); box-shadow: 0 10px 28px rgba(34, 51, 84, .08);
            margin-bottom: 20px; overflow: hidden;
        }
        .card-head {
            display: flex; align-items: center; justify-content: space-between; gap: 15px;
            padding: 18px 20px; border-bottom: 1px solid var(--line);
        }
        .card-head h2 { margin: 0; color: var(--navy); font-size: 16px; }
        .step {
            width: 28px; height: 28px; display: inline-grid; place-items: center; margin-right: 8px;
            color: var(--navy); background: var(--gold-soft); border: 1px solid #ecd575; border-radius: 9px;
            font-size: 12px;
        }
        .card-body { padding: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 14px; }
        .field { grid-column: span 6; min-width: 0; }
        .field.third { grid-column: span 4; }
        .field.full { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 6px; color: #3b4861; font-size: 12px; font-weight: 750; }
        input, select {
            width: 100%; min-height: 42px; padding: 9px 11px; color: var(--text); background: white;
            border: 1px solid #cbd5e1; border-radius: 10px; outline: none; transition: .15s ease;
        }
        input:focus, select:focus { border-color: #4166ad; box-shadow: 0 0 0 3px rgba(65,102,173,.12); }
        input[readonly] { background: #f5f7fb; color: #46546e; }
        .input-action { display: grid; grid-template-columns: 1fr auto; gap: 8px; }
        .hint { margin: 6px 0 0; color: var(--muted); font-size: 11px; }

        .btn {
            min-height: 41px; padding: 9px 15px; border: 1px solid transparent; border-radius: 10px;
            font-weight: 750; transition: transform .12s ease, opacity .12s ease, background .12s ease;
        }
        .btn:hover:not(:disabled) { transform: translateY(-1px); }
        .btn:disabled { cursor: not-allowed; opacity: .46; }
        .btn-primary { color: white; background: var(--navy); }
        .btn-gold { color: #1e2a45; background: var(--gold); }
        .btn-success { color: white; background: var(--success); }
        .btn-light { color: var(--navy); background: #f4f6fa; border-color: #d5dce8; }
        .btn-danger { color: var(--danger); background: #fff0ee; border-color: #ffd0ca; }
        .btn-sm { min-height: 34px; padding: 6px 10px; font-size: 12px; }

        .lookup-state { display: none; margin-top: 12px; padding: 10px 12px; border-radius: 10px; font-size: 12px; }
        .lookup-state.show { display: block; }
        .lookup-state.ok { color: #05603f; background: #eafaf4; border: 1px solid #a6e7d2; }
        .lookup-state.error { color: #9f1b12; background: #fff0ee; border: 1px solid #ffc9c2; }
        .lookup-state.loading { color: #694100; background: #fff8e5; border: 1px solid #f2d77e; }

        .table-wrap { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 10px; color: #536079; background: #f3f6fa; font-size: 11px; text-align: left; white-space: nowrap; }
        td { padding: 9px 7px; border-top: 1px solid #e7ebf1; vertical-align: middle; }
        td input, td select { min-width: 82px; min-height: 38px; padding: 7px 8px; font-size: 12px; }
        .item-description { min-width: 230px; }
        .money { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }

        .sticky { position: sticky; top: 18px; }
        .summary-company { padding: 14px; margin-bottom: 17px; color: #e7edf9; background: var(--navy); border-radius: 12px; }
        .summary-company strong { display: block; color: white; font-size: 14px; }
        .summary-company span { font-size: 11px; color: #b8c5dc; }
        .total-line { display: flex; justify-content: space-between; gap: 20px; padding: 10px 0; border-bottom: 1px dashed #d9e0ea; }
        .total-line span { color: var(--muted); }
        .total-line strong { font-variant-numeric: tabular-nums; }
        .total-final { padding: 17px 0 8px; border: 0; font-size: 24px; color: var(--navy); }
        .action-stack { display: grid; gap: 9px; margin-top: 18px; }
        .download-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7px; margin-top: 9px; }
        .status-box { display: none; padding: 13px; margin-top: 14px; border-radius: 11px; font-size: 12px; white-space: pre-line; }
        .status-box.show { display: block; }
        .status-box.ok { color: #05603f; background: #eafaf4; border: 1px solid #a6e7d2; }
        .status-box.error { color: #9f1b12; background: #fff0ee; border: 1px solid #ffc9c2; }
        .status-box.info { color: #194185; background: #eff4ff; border: 1px solid #b2ccff; }

        .recent-empty { padding: 28px; text-align: center; color: var(--muted); }
        .status-tag { display: inline-block; padding: 5px 8px; border-radius: 999px; font-size: 10px; font-weight: 800; }
        .status-tag.accepted { color: #05603f; background: #dff8ee; }
        .status-tag.pending { color: #7a4b00; background: #fff3cd; }
        .status-tag.rejected { color: #9f1b12; background: #ffe4e0; }

        @media (max-width: 1050px) {
            .layout { grid-template-columns: 1fr; }
            .sticky { position: static; }
        }
        @media (max-width: 700px) {
            .topbar { align-items: flex-start; }
            .top-actions { flex-direction: column; align-items: flex-end; }
            .field, .field.third { grid-column: 1 / -1; }
            .card-body { padding: 16px; }
            .download-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">FE</div>
            <div>
                <h1>Facturación electrónica</h1>
                <p>Facturas y boletas conectadas con SUNAT</p>
            </div>
        </div>
        <div class="top-actions">
            <span id="environmentBadge" class="environment">SIN CONEXIÓN</span>
            <button id="logoutButton" class="btn btn-light btn-sm hidden" type="button">Cerrar sesión</button>
        </div>
    </header>

    <main>
        <section id="loginPanel" class="login-wrap">
            <div class="login-logo">
                <div class="brand-mark">FE</div>
                <h2>Acceso al sistema</h2>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="form-grid">
                        <div class="field full">
                            <label for="loginEmail">Correo electrónico</label>
                            <input id="loginEmail" type="email" value="admin@local.test" autocomplete="username">
                        </div>
                        <div class="field full">
                            <label for="loginPassword">Contraseña</label>
                            <input id="loginPassword" type="password" autocomplete="current-password">
                        </div>
                    </div>
                    <button id="loginButton" class="btn btn-gold" style="width:100%;margin-top:17px" type="button">Ingresar</button>
                    <div id="loginStatus" class="status-box"></div>
                </div>
            </div>
        </section>

        <section id="appPanel" class="shell hidden">
            <div class="layout">
                <div>
                    <article class="card">
                        <div class="card-head"><h2><span class="step">1</span>Comprobante y emisor</h2></div>
                        <div class="card-body">
                            <div class="form-grid">
                                <div class="field third">
                                    <label for="documentType">Tipo de comprobante</label>
                                    <select id="documentType">
                                        <option value="invoice">Factura electrónica</option>
                                        <option value="boleta">Boleta de venta</option>
                                    </select>
                                </div>
                                <div class="field third">
                                    <label for="companyId">Empresa emisora</label>
                                    <select id="companyId"></select>
                                </div>
                                <div class="field third">
                                    <label for="branchId">Sucursal</label>
                                    <select id="branchId"></select>
                                </div>
                                <div class="field third">
                                    <label for="series">Serie</label>
                                    <input id="series" value="F001" maxlength="4">
                                </div>
                                <div class="field third">
                                    <label for="issueDate">Fecha de emisión</label>
                                    <input id="issueDate" type="date">
                                </div>
                                <div class="field third">
                                    <label for="currency">Moneda</label>
                                    <select id="currency"><option value="PEN">Soles (PEN)</option><option value="USD">Dólares (USD)</option></select>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="card">
                        <div class="card-head"><h2><span class="step">2</span>Cliente</h2></div>
                        <div class="card-body">
                            <div class="form-grid">
                                <div class="field third">
                                    <label for="clientDocumentType">Tipo de documento</label>
                                    <select id="clientDocumentType"><option value="6">RUC</option><option value="1">DNI</option></select>
                                </div>
                                <div class="field" style="grid-column:span 8">
                                    <label for="clientDocumentNumber">Número de documento</label>
                                    <div class="input-action">
                                        <input id="clientDocumentNumber" inputmode="numeric" autocomplete="off" placeholder="Ingrese 8 u 11 dígitos">
                                        <button id="lookupButton" class="btn btn-gold" type="button">Consultar</button>
                                    </div>
                                    <p class="hint">Primero busca clientes guardados; si es nuevo, realiza la consulta automática.</p>
                                </div>

                                <div id="personFields" class="field full hidden">
                                    <div class="form-grid">
                                        <div class="field third"><label for="clientNames">Nombres</label><input id="clientNames"></div>
                                        <div class="field third"><label for="clientLastName">Apellido paterno</label><input id="clientLastName"></div>
                                        <div class="field third"><label for="clientSecondLastName">Apellido materno</label><input id="clientSecondLastName"></div>
                                    </div>
                                </div>
                                <div class="field full">
                                    <label for="clientBusinessName">Nombre completo / Razón social</label>
                                    <input id="clientBusinessName" placeholder="Se completará automáticamente o puede escribirlo manualmente">
                                </div>
                                <div class="field full">
                                    <label for="clientAddress">Dirección</label>
                                    <input id="clientAddress" placeholder="Domicilio fiscal o dirección del cliente">
                                </div>
                                <div class="field third"><label for="clientDistrict">Distrito</label><input id="clientDistrict"></div>
                                <div class="field third"><label for="clientProvince">Provincia</label><input id="clientProvince"></div>
                                <div class="field third"><label for="clientDepartment">Departamento</label><input id="clientDepartment"></div>
                            </div>
                            <div id="lookupStatus" class="lookup-state"></div>
                        </div>
                    </article>

                    <article class="card">
                        <div class="card-head">
                            <h2><span class="step">3</span>Productos o servicios</h2>
                            <button id="addItemButton" class="btn btn-light btn-sm" type="button">+ Agregar línea</button>
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead><tr><th>Código</th><th>Descripción</th><th>Unidad</th><th>Cantidad</th><th>Precio final</th><th>IGV</th><th>Importe</th><th></th></tr></thead>
                                <tbody id="itemsBody"></tbody>
                            </table>
                        </div>
                    </article>

                    <article class="card">
                        <div class="card-head"><h2>Comprobantes recientes</h2><button id="refreshButton" class="btn btn-light btn-sm" type="button">Actualizar</button></div>
                        <div class="table-wrap">
                            <table>
                                <thead><tr><th>Número</th><th>Fecha</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Acción</th></tr></thead>
                                <tbody id="recentBody"><tr><td colspan="6" class="recent-empty">Inicia sesión para cargar los comprobantes.</td></tr></tbody>
                            </table>
                        </div>
                    </article>
                </div>

                <aside class="sticky">
                    <article class="card">
                        <div class="card-head"><h2>Resumen</h2></div>
                        <div class="card-body">
                            <div id="companySummary" class="summary-company"><strong>Selecciona una empresa</strong><span>Emisor del comprobante</span></div>
                            <div class="total-line"><span>Valor de venta</span><strong id="netTotal">S/ 0.00</strong></div>
                            <div class="total-line"><span>IGV</span><strong id="taxTotal">S/ 0.00</strong></div>
                            <div class="total-line total-final"><span>Total</span><strong id="grandTotal">S/ 0.00</strong></div>
                            <div class="action-stack">
                                <button id="createButton" class="btn btn-primary" type="button">Crear comprobante</button>
                                <button id="sendButton" class="btn btn-success" type="button" disabled>Enviar a SUNAT</button>
                            </div>
                            <div class="download-grid">
                                <button id="pdfButton" class="btn btn-light btn-sm" type="button" disabled>PDF</button>
                                <button id="xmlButton" class="btn btn-light btn-sm" type="button" disabled>XML</button>
                                <button id="cdrButton" class="btn btn-light btn-sm" type="button" disabled>CDR</button>
                            </div>
                            <button id="newButton" class="btn btn-light" style="width:100%;margin-top:9px" type="button">Nuevo comprobante</button>
                            <div id="generalStatus" class="status-box"></div>
                        </div>
                    </article>
                </aside>
            </div>
        </section>
    </main>

    <script>
        const API = window.location.origin;
        const state = { token: localStorage.getItem('sunat_token') || '', companies: [], current: null, lookupTimer: null };
        const $ = id => document.getElementById(id);
        const money = value => new Intl.NumberFormat('es-PE', { style: 'currency', currency: $('currency').value || 'PEN' }).format(Number(value || 0));

        function setStatus(element, message = '', type = 'info') {
            element.textContent = message;
            element.className = message ? `status-box show ${type}` : 'status-box';
        }

        function setLookupStatus(message = '', type = 'loading') {
            $('lookupStatus').textContent = message;
            $('lookupStatus').className = message ? `lookup-state show ${type}` : 'lookup-state';
        }

        async function request(path, options = {}) {
            const headers = { Accept: 'application/json', ...(options.headers || {}) };
            if (state.token) headers.Authorization = `Bearer ${state.token}`;
            if (options.body && !(options.body instanceof FormData)) headers['Content-Type'] = 'application/json';
            const response = await fetch(API + path, { ...options, headers });
            if (response.status === 401 && !path.includes('/auth/login')) logout(false);
            return response;
        }

        async function readJson(response) {
            const text = await response.text();
            try { return text ? JSON.parse(text) : {}; } catch { return { message: text || 'Respuesta inválida del servidor.' }; }
        }

        function errorMessage(data) {
            if (data?.errors) return Object.values(data.errors).flat().join('\n');
            return data?.message || data?.error || 'No se pudo completar la operación.';
        }

        async function login() {
            setStatus($('loginStatus'), 'Verificando credenciales...', 'info');
            $('loginButton').disabled = true;
            try {
                const response = await request('/api/auth/login', { method: 'POST', body: JSON.stringify({ email: $('loginEmail').value.trim(), password: $('loginPassword').value }) });
                const data = await readJson(response);
                if (!response.ok) throw new Error(errorMessage(data));
                state.token = data.access_token;
                localStorage.setItem('sunat_token', state.token);
                await openApp();
            } catch (error) {
                setStatus($('loginStatus'), error.message, 'error');
            } finally {
                $('loginButton').disabled = false;
            }
        }

        async function restoreSession() {
            if (!state.token) return;
            try {
                const response = await request('/api/v1/auth/me');
                if (response.ok) await openApp();
            } catch {}
        }

        async function openApp() {
            $('loginPanel').classList.add('hidden');
            $('appPanel').classList.remove('hidden');
            $('logoutButton').classList.remove('hidden');
            await loadCompanies();
        }

        async function logout(callApi = true) {
            if (callApi && state.token) {
                try { await request('/api/v1/auth/logout', { method: 'POST' }); } catch {}
            }
            state.token = '';
            localStorage.removeItem('sunat_token');
            location.reload();
        }

        async function loadCompanies() {
            try {
                const response = await request('/api/v1/companies');
                const data = await readJson(response);
                if (!response.ok) throw new Error(errorMessage(data));
                state.companies = data.data || [];
                $('companyId').innerHTML = state.companies.map(company => `<option value="${company.id}">${escapeHtml(company.razon_social)} · ${company.ruc}</option>`).join('');
                if (!state.companies.length) throw new Error('No hay empresas activas. Completa primero la configuración inicial.');
                await companyChanged();
            } catch (error) {
                setStatus($('generalStatus'), error.message, 'error');
            }
        }

        async function companyChanged() {
            const company = state.companies.find(item => String(item.id) === $('companyId').value);
            if (!company) return;
            $('companySummary').innerHTML = `<strong>${escapeHtml(company.razon_social)}</strong><span>RUC ${company.ruc}</span>`;
            $('environmentBadge').textContent = company.modo_produccion ? 'PRODUCCIÓN' : 'SUNAT BETA';
            $('environmentBadge').className = company.modo_produccion ? 'environment production' : 'environment';
            const response = await request(`/api/v1/companies/${company.id}/branches`);
            const data = await readJson(response);
            const branches = response.ok ? (data.data || []).filter(branch => branch.activo !== false) : [];
            $('branchId').innerHTML = branches.map(branch => `<option value="${branch.id}">${escapeHtml(branch.nombre)}</option>`).join('');
            if (!branches.length) setStatus($('generalStatus'), 'La empresa seleccionada no tiene una sucursal activa.', 'error');
            await loadRecent();
        }

        function documentTypeChanged() {
            const invoice = $('documentType').value === 'invoice';
            $('series').value = invoice ? 'F001' : 'B001';
            $('clientDocumentType').value = invoice ? '6' : '1';
            $('clientDocumentType').disabled = invoice;
            clientTypeChanged();
            resetCurrent();
            loadRecent();
        }

        function clientTypeChanged() {
            const dni = $('clientDocumentType').value === '1';
            $('personFields').classList.toggle('hidden', !dni);
            $('clientDocumentNumber').maxLength = dni ? 8 : 11;
            $('clientDocumentNumber').placeholder = dni ? 'DNI de 8 dígitos' : 'RUC de 11 dígitos';
            clearClient(true);
        }

        function clearClient(clearNumber = true) {
            if (clearNumber) $('clientDocumentNumber').value = '';
            ['clientNames','clientLastName','clientSecondLastName','clientBusinessName','clientAddress','clientDistrict','clientProvince','clientDepartment'].forEach(id => $(id).value = '');
            setLookupStatus();
        }

        async function lookupDocument() {
            const type = $('clientDocumentType').value;
            const number = $('clientDocumentNumber').value.replace(/\D/g, '');
            const requiredLength = type === '1' ? 8 : 11;
            $('clientDocumentNumber').value = number;
            if (number.length !== requiredLength) {
                setLookupStatus(type === '1' ? 'El DNI debe tener 8 dígitos.' : 'El RUC debe tener 11 dígitos.', 'error');
                return;
            }

            $('lookupButton').disabled = true;
            setLookupStatus('Buscando datos del cliente...', 'loading');
            try {
                const localResponse = await request('/api/v1/clients/search-by-document', {
                    method: 'POST',
                    body: JSON.stringify({ company_id: Number($('companyId').value), tipo_documento: type, numero_documento: number })
                });
                if (localResponse.ok) {
                    const localData = await readJson(localResponse);
                    fillClient(localData.data || {});
                    setLookupStatus('Cliente recuperado de la base de datos.', 'ok');
                    return;
                }

                const response = await request(`/api/v1/documentos/consultar?tipo=${type}&numero=${number}`);
                const data = await readJson(response);
                if (!response.ok) throw new Error(errorMessage(data));
                fillClient(data.data || {});
                setLookupStatus(type === '1' ? 'Nombres y apellidos encontrados correctamente.' : 'Datos de la empresa encontrados correctamente.', 'ok');
            } catch (error) {
                setLookupStatus(`${error.message} Puedes completar los datos manualmente.`, 'error');
            } finally {
                $('lookupButton').disabled = false;
            }
        }

        function fillClient(data) {
            $('clientNames').value = data.nombres || '';
            $('clientLastName').value = data.apellido_paterno || '';
            $('clientSecondLastName').value = data.apellido_materno || '';
            $('clientBusinessName').value = data.razon_social || '';
            $('clientAddress').value = data.direccion || '';
            $('clientDistrict').value = data.distrito || '';
            $('clientProvince').value = data.provincia || '';
            $('clientDepartment').value = data.departamento || '';
        }

        function composePersonName() {
            if ($('clientDocumentType').value !== '1') return;
            $('clientBusinessName').value = [
                $('clientNames').value,
                $('clientLastName').value,
                $('clientSecondLastName').value
            ].map(value => value.trim()).filter(Boolean).join(' ').toUpperCase();
        }

        function addItem(values = {}) {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input class="item-code" value="${escapeHtml(values.codigo || '')}" placeholder="SERV-001"></td>
                <td><input class="item-description" value="${escapeHtml(values.descripcion || '')}" placeholder="Descripción"></td>
                <td><select class="item-unit"><option value="ZZ">Servicio</option><option value="NIU">Unidad</option></select></td>
                <td><input class="item-quantity money" type="number" min="0.001" step="0.001" value="${values.cantidad || 1}"></td>
                <td><input class="item-price money" type="number" min="0" step="0.01" value="${values.precio || ''}" placeholder="0.00"></td>
                <td><select class="item-tax"><option value="18">18%</option><option value="0">0%</option></select></td>
                <td class="item-total money">${money(0)}</td>
                <td><button class="btn btn-danger btn-sm item-remove" type="button">×</button></td>`;
            $('itemsBody').appendChild(row);
            row.querySelector('.item-unit').value = values.unidad || 'ZZ';
            row.querySelector('.item-tax').value = String(values.igv ?? 18);
            row.querySelectorAll('input,select').forEach(element => element.addEventListener('input', calculateTotals));
            row.querySelector('.item-remove').addEventListener('click', () => { row.remove(); if (!$('itemsBody').children.length) addItem(); calculateTotals(); });
            calculateTotals();
        }

        function calculateTotals() {
            let net = 0, tax = 0, total = 0;
            [...$('itemsBody').rows].forEach(row => {
                const quantity = Number(row.querySelector('.item-quantity').value || 0);
                const finalPrice = Number(row.querySelector('.item-price').value || 0);
                const rate = Number(row.querySelector('.item-tax').value || 0);
                const lineTotal = quantity * finalPrice;
                const lineNet = rate > 0 ? lineTotal / (1 + rate / 100) : lineTotal;
                total += lineTotal; net += lineNet; tax += lineTotal - lineNet;
                row.querySelector('.item-total').textContent = money(lineTotal);
            });
            $('netTotal').textContent = money(net);
            $('taxTotal').textContent = money(tax);
            $('grandTotal').textContent = money(total);
        }

        function collectClient() {
            const type = $('clientDocumentType').value;
            const number = $('clientDocumentNumber').value.trim();
            const businessName = $('clientBusinessName').value.trim();
            if (number.length !== (type === '1' ? 8 : 11) || !businessName) throw new Error('Completa y consulta correctamente el documento del cliente.');
            return {
                tipo_documento: type,
                numero_documento: number,
                razon_social: businessName,
                nombres: type === '1' ? ($('clientNames').value.trim() || null) : null,
                apellido_paterno: type === '1' ? ($('clientLastName').value.trim() || null) : null,
                apellido_materno: type === '1' ? ($('clientSecondLastName').value.trim() || null) : null,
                direccion: $('clientAddress').value.trim() || null,
                distrito: $('clientDistrict').value.trim() || null,
                provincia: $('clientProvince').value.trim() || null,
                departamento: $('clientDepartment').value.trim() || null
            };
        }

        function collectItems() {
            const items = [...$('itemsBody').rows].map(row => {
                const code = row.querySelector('.item-code').value.trim();
                const description = row.querySelector('.item-description').value.trim();
                const unit = row.querySelector('.item-unit').value;
                const quantity = Number(row.querySelector('.item-quantity').value || 0);
                const finalPrice = Number(row.querySelector('.item-price').value || 0);
                const tax = Number(row.querySelector('.item-tax').value || 0);
                if (!code || !description || quantity <= 0 || finalPrice <= 0) throw new Error('Completa el código, descripción, cantidad y precio de cada línea.');
                return {
                    codigo: code, descripcion: description, unidad: unit, cantidad: quantity,
                    mto_valor_unitario: Number((tax > 0 ? finalPrice / (1 + tax / 100) : finalPrice).toFixed(6)),
                    porcentaje_igv: tax, tip_afe_igv: tax > 0 ? '10' : '30'
                };
            });
            if (!items.length) throw new Error('Agrega por lo menos un producto o servicio.');
            return items;
        }

        async function createDocument() {
            try {
                if (!$('companyId').value || !$('branchId').value) throw new Error('Selecciona una empresa y una sucursal.');
                const type = $('documentType').value;
                const payload = {
                    company_id: Number($('companyId').value), branch_id: Number($('branchId').value),
                    serie: $('series').value.trim().toUpperCase(), fecha_emision: $('issueDate').value,
                    tipo_operacion: '0101', moneda: $('currency').value, forma_pago_tipo: 'Contado',
                    client: collectClient(), detalles: collectItems()
                };
                if (type === 'boleta') { payload.ubl_version = '2.1'; payload.metodo_envio = 'individual'; }
                $('createButton').disabled = true;
                setStatus($('generalStatus'), 'Creando el comprobante...', 'info');
                const response = await request(`/api/v1/${type === 'invoice' ? 'invoices' : 'boletas'}`, { method: 'POST', body: JSON.stringify(payload) });
                const data = await readJson(response);
                if (!response.ok) throw new Error(errorMessage(data));
                const document = data.data;
                state.current = { id: document.id, type, number: document.numero_completo, status: document.estado_sunat || 'PENDIENTE' };
                $('sendButton').disabled = false;
                setStatus($('generalStatus'), `${type === 'invoice' ? 'Factura' : 'Boleta'} ${state.current.number} creada correctamente.`, 'ok');
                await loadRecent();
            } catch (error) {
                setStatus($('generalStatus'), error.message, 'error');
            } finally {
                $('createButton').disabled = false;
            }
        }

        async function sendToSunat() {
            if (!state.current) return;
            $('sendButton').disabled = true;
            setStatus($('generalStatus'), `Enviando ${state.current.number} a SUNAT...`, 'info');
            try {
                const endpoint = state.current.type === 'invoice' ? 'invoices' : 'boletas';
                const response = await request(`/api/v1/${endpoint}/${state.current.id}/send-sunat`, { method: 'POST' });
                const data = await readJson(response);
                if (!response.ok) throw new Error(errorMessage(data));
                state.current.status = data.data?.estado_sunat || 'ACEPTADO';
                $('xmlButton').disabled = false;
                $('cdrButton').disabled = false;
                await generatePdf();
                setStatus($('generalStatus'), `${state.current.number}\nEstado SUNAT: ${state.current.status}`, 'ok');
                await loadRecent();
            } catch (error) {
                $('sendButton').disabled = false;
                setStatus($('generalStatus'), error.message, 'error');
            }
        }

        async function generatePdf() {
            if (!state.current) return;
            const endpoint = state.current.type === 'invoice' ? 'invoices' : 'boletas';
            const response = await request(`/api/v1/${endpoint}/${state.current.id}/generate-pdf?format=a4`, { method: 'POST' });
            if (response.ok) $('pdfButton').disabled = false;
        }

        async function download(kind) {
            if (!state.current) return;
            try {
                const endpoint = state.current.type === 'invoice' ? 'invoices' : 'boletas';
                const response = await request(`/api/v1/${endpoint}/${state.current.id}/download-${kind}?format=a4`);
                if (!response.ok) throw new Error(errorMessage(await readJson(response)));
                const blob = await response.blob();
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `${kind === 'cdr' ? 'R-' : ''}${state.current.number}.${kind === 'cdr' ? 'zip' : kind}`;
                document.body.appendChild(link); link.click(); link.remove(); URL.revokeObjectURL(url);
            } catch (error) { setStatus($('generalStatus'), error.message, 'error'); }
        }

        async function loadRecent() {
            if (!state.token || !$('companyId').value) return;
            try {
                const type = $('documentType').value;
                const endpoint = type === 'invoice' ? 'invoices' : 'boletas';
                const response = await request(`/api/v1/${endpoint}?company_id=${$('companyId').value}&per_page=8`);
                const data = await readJson(response);
                if (!response.ok) throw new Error(errorMessage(data));
                const documents = type === 'invoice' ? (data.data?.data || []) : (data.data || []);
                $('recentBody').innerHTML = documents.length ? documents.map(document => {
                    const status = document.estado_sunat || 'PENDIENTE';
                    const statusClass = status === 'ACEPTADO' ? 'accepted' : (status === 'RECHAZADO' ? 'rejected' : 'pending');
                    const client = document.client?.razon_social || 'Cliente';
                    return `<tr><td><strong>${escapeHtml(document.numero_completo)}</strong></td><td>${escapeHtml(document.fecha_emision)}</td><td>${escapeHtml(client)}</td><td class="money">${money(document.mto_imp_venta)}</td><td><span class="status-tag ${statusClass}">${escapeHtml(status)}</span></td><td><button class="btn btn-light btn-sm" data-document='${JSON.stringify({ id: document.id, type, number: document.numero_completo, status }).replace(/'/g, '&#39;')}'>Seleccionar</button></td></tr>`;
                }).join('') : '<tr><td colspan="6" class="recent-empty">Todavía no hay comprobantes de este tipo.</td></tr>';
                $('recentBody').querySelectorAll('[data-document]').forEach(button => button.addEventListener('click', () => selectRecent(JSON.parse(button.dataset.document))));
            } catch (error) {
                $('recentBody').innerHTML = `<tr><td colspan="6" class="recent-empty">${escapeHtml(error.message)}</td></tr>`;
            }
        }

        function selectRecent(document) {
            state.current = document;
            const accepted = document.status === 'ACEPTADO';
            $('sendButton').disabled = accepted;
            $('pdfButton').disabled = !accepted;
            $('xmlButton').disabled = !accepted;
            $('cdrButton').disabled = !accepted;
            setStatus($('generalStatus'), `${document.number} seleccionado.\nEstado: ${document.status}`, 'info');
        }

        function resetCurrent() {
            state.current = null;
            ['sendButton','pdfButton','xmlButton','cdrButton'].forEach(id => $(id).disabled = true);
            setStatus($('generalStatus'));
        }

        function newDocument() {
            resetCurrent(); clearClient();
            $('itemsBody').innerHTML = ''; addItem();
            $('issueDate').value = localDate();
        }

        function localDate() {
            const date = new Date();
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));
        }

        $('loginButton').addEventListener('click', login);
        $('loginPassword').addEventListener('keydown', event => { if (event.key === 'Enter') login(); });
        $('logoutButton').addEventListener('click', () => logout(true));
        $('companyId').addEventListener('change', companyChanged);
        $('documentType').addEventListener('change', documentTypeChanged);
        $('clientDocumentType').addEventListener('change', clientTypeChanged);
        ['clientNames','clientLastName','clientSecondLastName'].forEach(id => $(id).addEventListener('input', composePersonName));
        $('clientDocumentNumber').addEventListener('input', event => {
            event.target.value = event.target.value.replace(/\D/g, '');
            clearTimeout(state.lookupTimer);
            const required = $('clientDocumentType').value === '1' ? 8 : 11;
            if (event.target.value.length === required) state.lookupTimer = setTimeout(lookupDocument, 500);
        });
        $('lookupButton').addEventListener('click', lookupDocument);
        $('addItemButton').addEventListener('click', () => addItem());
        $('createButton').addEventListener('click', createDocument);
        $('sendButton').addEventListener('click', sendToSunat);
        $('pdfButton').addEventListener('click', () => download('pdf'));
        $('xmlButton').addEventListener('click', () => download('xml'));
        $('cdrButton').addEventListener('click', () => download('cdr'));
        $('newButton').addEventListener('click', newDocument);
        $('refreshButton').addEventListener('click', loadRecent);
        $('currency').addEventListener('change', calculateTotals);

        $('issueDate').value = localDate();
        addItem({ codigo: 'SERV-001', cantidad: 1, igv: 18 });
        documentTypeChanged();
        restoreSession();
    </script>
</body>
</html>
