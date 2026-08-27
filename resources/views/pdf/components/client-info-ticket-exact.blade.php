@php
    $tipoDoc = (string)($client['tipo_documento'] ?? '1');
    $etiquetaDoc = $tipoDoc === '6' ? 'RUC' : ($tipoDoc === '1' ? 'DNI' : 'DOC');
    $direccion = trim((string)($client['direccion'] ?? ''));
@endphp

<div class="client-section">
    <div class="client-name">CLIENTE: {{ strtoupper($client['razon_social'] ?? 'CLIENTE') }}</div>
    <div class="client-details"><strong>{{ $etiquetaDoc }}:</strong> {{ $client['numero_documento'] ?? '' }}</div>

    @if($direccion !== '')
        <div class="client-details"><strong>DIRECCIÓN:</strong> {{ strtoupper($direccion) }}</div>
    @endif

    <div class="client-details"><strong>FECHA:</strong> {{ $fecha_emision ?? now()->format('d/m/Y') }} &nbsp; <strong>HORA:</strong> {{ now()->format('H:i') }}</div>
</div>
