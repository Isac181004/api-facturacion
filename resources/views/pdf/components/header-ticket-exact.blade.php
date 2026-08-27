@php
    $numeroComprobante = $document->numero_completo
        ?? (($document->serie ?? '') . '-' . str_pad((string)($document->correlativo ?? ''), 6, '0', STR_PAD_LEFT));
    $rucImpreso = config('salon.ruc_real');
@endphp

<div class="header">
    <div class="logo-section-ticket">
        @include('pdf.components.logo-maju-gimena', ['class' => 'logo-img-ticket'])
    </div>

    <div class="company-details">
        {{ config('salon.direccion') }}<br>
        CEL / WHATSAPP: {{ config('salon.telefono') }}
    </div>

    <div class="company-ruc">RUC: {{ $rucImpreso }}</div>
    <div class="document-title">{{ $tipo_documento_nombre ?? 'COMPROBANTE ELECTRÓNICO' }}</div>
    <div class="document-number">{{ $numeroComprobante }}</div>
</div>
