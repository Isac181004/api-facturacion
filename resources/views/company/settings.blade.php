@extends('layouts.portal')

@section('title', 'Configuración SUNAT')

@section('content')
<div class="page-heading">
    <div>
        @if($adminView)
            <a class="back-link" href="{{ route('portal.admin.companies') }}">← Todas las empresas</a>
        @else
            <span class="eyebrow">Área de empresa</span>
        @endif
        <h1 class="page-title">Configuración SUNAT</h1>
        <p class="subtitle">Administra tus credenciales y certificado digital. Los secretos nunca se vuelven a mostrar una vez guardados.</p>
    </div>
    <span class="status-pill {{ $company->activo ? 'pill-green' : 'pill-red' }}">{{ $company->activo ? 'Empresa activa' : 'Empresa inactiva' }}</span>
</div>

<section class="company-hero card">
    <div class="company-name">
        <span class="eyebrow">{{ $company->ruc }}</span>
        <h2>{{ $company->razon_social }}</h2>
        <div class="company-meta">{{ $company->nombre_comercial ?: 'Configuración fiscal y conexión de servicios' }}</div>
    </div>
    <div class="hero-status">
        @if($company->modo_produccion)
            <span class="status-pill pill-amber">Producción autorizada</span>
        @else
            <span class="status-pill pill-green">Beta · Pruebas</span>
        @endif
        <span class="status-pill {{ $hasCertificate ? 'pill-green' : 'pill-red' }}">{{ $hasCertificate ? 'Certificado PEM listo' : 'Falta certificado PEM' }}</span>
    </div>
</section>

@if(!$canManage)
    <div class="hint-banner"><strong>Acceso de solo lectura.</strong> Un administrador de empresa puede actualizar credenciales, cargar el certificado y administrar API Keys.</div>
@endif
@if(!$company->modo_produccion)
    <div class="hint-banner"><strong>Modo Beta activo.</strong> Las solicitudes se enrutan al ambiente de prueba. La activación de producción requiere autorización administrativa.</div>
@else
    <div class="hint-banner"><strong>Modo Producción activo.</strong> Las claves live operan sobre el ambiente autorizado de esta empresa.</div>
@endif

<section class="card panel" style="margin-bottom:20px">
    <div class="section-title">
        <div><h2>Conexión SUNAT</h2><p>Credenciales SOL y certificado digital de esta empresa.</p></div>
        <span class="status-pill {{ $hasCertificate ? 'pill-green' : 'pill-red' }}">{{ $hasCertificate ? 'PEM privado configurado' : 'Pendiente de carga' }}</span>
    </div>

    @if($canManage)
        <form method="POST" action="{{ $updateUrl }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="field-grid">
                <div class="field">
                    <label for="usuario_sol">Usuario SOL</label>
                    <input id="usuario_sol" name="usuario_sol" value="{{ old('usuario_sol', $company->usuario_sol) }}" autocomplete="off" required>
                    <span class="field-hint">Se utiliza con el RUC de esta empresa.</span>
                </div>
                <div class="field">
                    <label for="clave_sol">Clave SOL</label>
                    <input id="clave_sol" name="clave_sol" type="password" autocomplete="new-password" placeholder="{{ $company->clave_sol ? 'Guardada · dejar vacío para conservarla' : 'Ingresa la clave SOL' }}">
                    <span class="field-hint">Por seguridad, el valor guardado no se muestra.</span>
                </div>
                <div class="field full">
                    <label for="certificado_pem">Certificado digital (.pem)</label>
                    <input id="certificado_pem" name="certificado_pem" type="file" accept=".pem,application/x-pem-file">
                    <span class="field-hint">Máximo 2 MB. El archivo se guarda en almacenamiento privado bajo la carpeta de esta empresa; no se publica ni se comparte.</span>
                </div>
                <div class="field">
                    <label for="certificado_password">Contraseña del certificado</label>
                    <input id="certificado_password" name="certificado_password" type="password" autocomplete="new-password" placeholder="{{ $company->certificado_password ? 'Guardada · dejar vacío para conservarla' : 'Opcional, si el PEM está protegido' }}">
                </div>

                @if($adminView)
                    <div class="field">
                        <label for="modo_produccion">Ambiente autorizado</label>
                        <select id="modo_produccion" name="modo_produccion" required>
                            <option value="beta" @selected(old('modo_produccion', $company->modo_produccion ? 'live' : 'beta') === 'beta')>Beta · test</option>
                            <option value="live" @selected(old('modo_produccion', $company->modo_produccion ? 'live' : 'beta') === 'live')>Producción · live</option>
                        </select>
                        <span class="field-hint">Sólo un superadministrador puede autorizar el ambiente live.</span>
                    </div>
                @else
                    <div class="field">
                        <label>Ambiente autorizado</label>
                        <input value="{{ $company->modo_produccion ? 'Producción · live' : 'Beta · test' }}" readonly>
                        <span class="field-hint">Solicita al administrador la habilitación de producción.</span>
                    </div>
                @endif
            </div>

            <details style="margin-top:24px;border-top:1px solid var(--line);padding-top:18px">
                <summary style="cursor:pointer;font-size:13px;font-weight:750;color:#354b57">Credenciales GRE opcionales · guías de remisión</summary>
                <p class="field-hint" style="margin:9px 0 16px">Se guardan para esta empresa y se usan según su ambiente autorizado. Deja los secretos vacíos para mantener los valores actuales.</p>
                <div class="field-grid">
                    <div class="field"><label for="gre_client_id_beta">GRE Client ID · Beta</label><input id="gre_client_id_beta" name="gre_client_id_beta" value="{{ old('gre_client_id_beta') }}" placeholder="{{ $company->gre_client_id_beta ? 'Guardado · opcional' : 'Client ID de prueba' }}"></div>
                    <div class="field"><label for="gre_client_secret_beta">GRE Client Secret · Beta</label><input id="gre_client_secret_beta" name="gre_client_secret_beta" type="password" autocomplete="new-password" placeholder="{{ $company->gre_client_secret_beta ? 'Guardado · dejar vacío para conservarlo' : 'Client Secret de prueba' }}"></div>
                    <div class="field"><label for="gre_ruc_proveedor">RUC proveedor GRE</label><input id="gre_ruc_proveedor" name="gre_ruc_proveedor" value="{{ old('gre_ruc_proveedor', $company->gre_ruc_proveedor) }}" placeholder="Por defecto, el RUC de la empresa"></div>
                    <div class="field"><label for="gre_usuario_sol">Usuario SOL para GRE</label><input id="gre_usuario_sol" name="gre_usuario_sol" value="{{ old('gre_usuario_sol', $company->gre_usuario_sol) }}" placeholder="Por defecto, el usuario SOL principal"></div>
                    <div class="field"><label for="gre_clave_sol">Clave SOL para GRE</label><input id="gre_clave_sol" name="gre_clave_sol" type="password" autocomplete="new-password" placeholder="{{ $company->gre_clave_sol ? 'Guardada · dejar vacío para conservarla' : 'Opcional · por defecto usa la clave SOL' }}"></div>
                    @if($adminView)
                        <div class="field"><label for="gre_client_id_produccion">GRE Client ID · Producción</label><input id="gre_client_id_produccion" name="gre_client_id_produccion" value="{{ old('gre_client_id_produccion') }}" placeholder="{{ $company->gre_client_id_produccion ? 'Guardado · opcional' : 'Client ID live' }}"></div>
                        <div class="field"><label for="gre_client_secret_produccion">GRE Client Secret · Producción</label><input id="gre_client_secret_produccion" name="gre_client_secret_produccion" type="password" autocomplete="new-password" placeholder="{{ $company->gre_client_secret_produccion ? 'Guardado · dejar vacío para conservarlo' : 'Client Secret live' }}"></div>
                    @endif
                </div>
            </details>

            <div class="form-actions">
                <span class="field-hint">{{ $adminView ? 'La autorización live cambia el ambiente activo y las claves admitidas.' : 'La configuración queda limitada a ' . $company->razon_social . '.' }}</span>
                <button class="btn btn-primary" type="submit">Guardar configuración <span aria-hidden="true">→</span></button>
            </div>
        </form>
    @else
        <div class="field-grid">
            <div class="field"><label>Usuario SOL</label><input value="{{ $company->usuario_sol }}" readonly></div>
            <div class="field"><label>Clave SOL</label><input value="{{ $company->clave_sol ? 'Configurada' : 'Pendiente' }}" readonly></div>
        </div>
    @endif
</section>

@if($canManage)
<section class="card panel">
    <div class="section-title">
        <div><h2>API Keys de la empresa</h2><p>Claves individuales para conectar tu sistema al API de facturación.</p></div>
        <span class="status-pill pill-slate">{{ $apiKeys->count() }} {{ $apiKeys->count() === 1 ? 'clave' : 'claves' }}</span>
    </div>
    <div class="hint-banner"><strong>El secreto completo se muestra una sola vez.</strong> Guárdalo en un gestor de secretos y envíalo en el encabezado <code class="code">Authorization: Bearer …</code>. Nunca lo incluyas en el código del navegador.</div>

    @php($canIssueKey = !$company->modo_produccion || $adminView)
    @if($canIssueKey)
        <form class="key-create" data-api-key-form action="{{ route('portal.company.api-keys.store', $company) }}" method="POST">
            @csrf
            <div class="field"><label for="key_name">Nombre de la clave</label><input id="key_name" name="name" maxlength="100" required placeholder="Ej. ERP de ventas"></div>
            <div class="field"><label for="key_environment">Ambiente</label>
                <select id="key_environment" name="environment" required>
                    @if($company->modo_produccion)
                        <option value="live">Producción · live</option>
                    @else
                        <option value="test">Pruebas · test</option>
                    @endif
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Crear API Key</button>
        </form>
    @else
        <div class="hint-banner">La empresa está en producción. Sólo un superadministrador puede emitir claves live; un administrador de empresa puede revocar sus claves.</div>
    @endif

    <div id="api-key-result" class="secret-box" role="status" aria-live="polite"></div>

    @if($apiKeys->isEmpty())
        <div class="empty">Todavía no hay API Keys registradas para esta empresa.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Nombre</th><th>Prefijo</th><th>Ambiente</th><th>Uso</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @foreach($apiKeys as $key)
                        <tr>
                            <td><strong>{{ $key->name }}</strong><div class="secondary">Creada {{ $key->created_at?->format('d/m/Y') }}</div></td>
                            <td><code class="code">{{ $key->key_prefix }}…</code></td>
                            <td><span class="status-pill {{ $key->environment === 'live' ? 'pill-amber' : 'pill-green' }}">{{ $key->environment }}</span></td>
                            <td>{{ number_format($key->requests_count) }} <div class="secondary">{{ $key->last_used_at ? 'Último uso '.$key->last_used_at->diffForHumans() : 'Sin uso aún' }}</div></td>
                            <td>
                                @if($key->revoked_at || !$key->active)
                                    <span class="status-pill pill-red">Revocada</span>
                                @elseif($key->expires_at && $key->expires_at->isPast())
                                    <span class="status-pill pill-red">Vencida</span>
                                @else
                                    <span class="status-pill pill-green">Activa</span>
                                @endif
                            </td>
                            <td>
                                @if(!$key->revoked_at && $key->active)
                                    <button class="btn btn-danger btn-small" type="button" data-revoke-key="{{ route('portal.company.api-keys.destroy', [$company, $key]) }}">Revocar</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endif
@endsection

@push('scripts')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const result = document.getElementById('api-key-result');
    const showMessage = (text, isError = false) => {
        if (!result) return;
        result.classList.add('is-visible');
        result.style.background = isError ? '#fff0ef' : '#effaf7';
        result.style.borderColor = isError ? '#f2d0cc' : '#b7ddd7';
        result.innerHTML = '';
        const strong = document.createElement('strong');
        strong.textContent = isError ? 'No se pudo completar la operación' : 'Copia y guarda esta API Key ahora';
        result.appendChild(strong);
        const detail = document.createElement('div');
        detail.textContent = text;
        result.appendChild(detail);
    };

    document.querySelector('[data-api-key-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest'},
                body: new FormData(form),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Revisa los datos y vuelve a intentar.');
            const strong = document.createElement('strong');
            strong.textContent = 'API Key creada · copia y guarda este secreto ahora';
            const code = document.createElement('code');
            code.className = 'secret-value';
            code.textContent = data.api_key;
            const note = document.createElement('div');
            note.textContent = 'Este secreto no se volverá a mostrar.';
            result.classList.add('is-visible');
            result.innerHTML = '';
            result.append(strong, code, note);
            form.reset();
            setTimeout(() => window.location.reload(), 12000);
        } catch (error) {
            showMessage(error.message, true);
        } finally {
            button.disabled = false;
        }
    });

    document.querySelectorAll('[data-revoke-key]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (!window.confirm('¿Revocar esta API Key? Las solicitudes que usen la clave dejarán de estar autorizadas.')) return;
            try {
                const response = await fetch(button.dataset.revokeKey, {
                    method: 'DELETE',
                    headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest'},
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'No se pudo revocar la clave.');
                window.location.reload();
            } catch (error) {
                showMessage(error.message, true);
            }
        });
    });
})();
</script>
@endpush
