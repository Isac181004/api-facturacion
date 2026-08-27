@php
    $logoPath = public_path('images/salon.png');
    $nombreEmpresa = $company->nombre_comercial ?: ($company->razon_social ?? 'MAJU GIMENA SALÓN & SPA');
    $numeroComprobante = $document->numero_completo
        ?? (($document->serie ?? '') . '-' . str_pad((string)($document->correlativo ?? ''), 6, '0', STR_PAD_LEFT));
@endphp

<div class="header">
    @if(file_exists($logoPath))
        <div class="logo-section-ticket">
            <img src="{{ $logoPath }}" alt="Logo" class="logo-img-ticket">
        </div>
    @endif

    <div class="company-name">{{ $nombreEmpresa }}</div>
    <div class="company-details">
        <strong>Belleza • Estética • Bienestar</strong>
        @if(!empty($company->direccion))<br>{{ $company->direccion }}@endif
        @if(!empty($company->distrito) || !empty($company->provincia))
            <br>{{ trim(($company->distrito ?? '') . (!empty($company->distrito) && !empty($company->provincia) ? ', ' : '') . ($company->provincia ?? '')) }}
        @endif
        @if(!empty($company->telefono))<br>TEL / WHATSAPP: {{ $company->telefono }}@endif
        @if(!empty($company->email))<br>{{ $company->email }}@endif
    </div>

    <div class="company-ruc">RUC: {{ $company->ruc ?? '' }}</div>
    <div class="document-title">{{ $tipo_documento_nombre ?? 'COMPROBANTE ELECTRÓNICO' }}</div>
    <div class="document-number">{{ $numeroComprobante }}</div>
</div>
