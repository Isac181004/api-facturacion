@php
    $numeroComprobante = $document->numero_completo
        ?? (($document->serie ?? '') . '-' . str_pad((string)($document->correlativo ?? ''), 6, '0', STR_PAD_LEFT));

    // En SUNAT BETA se conserva el RUC técnico de pruebas para no romper el envío.
    // Al pasar la empresa a producción, el encabezado usa automáticamente el RUC real del salón.
    $rucImpreso = ($company->modo_produccion ?? false)
        ? config('salon.ruc_real')
        : ($company->ruc ?? config('salon.ruc_real'));
@endphp

<div class="header">
    <div class="logo-section-ticket">
        @include('pdf.components.logo-maju-gimena', ['class' => 'logo-img-ticket'])
    </div>

    <div class="company-details">
        <strong>{{ config('salon.tagline') }}</strong><br>
        {{ config('salon.direccion') }}<br>
        CEL / WHATSAPP: {{ config('salon.telefono') }}
    </div>

    <div class="company-ruc">RUC: {{ $rucImpreso }}</div>
    <div class="document-title">{{ $tipo_documento_nombre ?? 'COMPROBANTE ELECTRÓNICO' }}</div>
    <div class="document-number">{{ $numeroComprobante }}</div>
</div>
