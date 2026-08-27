<div class="items-header">
    <div class="header-cant">CANT</div>
    <div class="header-um">U.M.</div>
    <div class="header-cod">CÓD.</div>
    <div class="header-precio">P.UNIT</div>
    <div class="header-total">TOTAL</div>
</div>
<div class="items-header" style="border-top:none;border-bottom:none;font-weight:bold;padding:1px 0;">
    <div style="width:100%;text-align:left;padding-left:1px;">DESCRIPCIÓN / AFECTACIÓN</div>
</div>

<div class="items-section">
    @forelse($detalles as $detalle)
        @php
            $unidadCodigo = strtoupper((string)($detalle['unidad'] ?? ''));
            $unidadVisible = match($unidadCodigo) {
                'ZZ' => 'SERV.',
                'NIU' => 'UND.',
                default => $unidadCodigo ?: 'UND.'
            };

            $tipAfe = (string)($detalle['tip_afe_igv'] ?? '10');
            $igvTexto = match($tipAfe) {
                '10' => 'IGV: SÍ · 18% GRAVADO',
                '20' => 'IGV: NO · EXONERADO',
                '30' => 'IGV: NO · INAFECTO',
                default => 'IGV: ' . number_format((float)($detalle['porcentaje_igv'] ?? 0), 0) . '%'
            };

            $cantidad = (float)($detalle['cantidad'] ?? 1);
            $valorVenta = (float)($detalle['mto_valor_venta'] ?? 0);
            $impuestos = (float)($detalle['total_impuestos'] ?? ($detalle['igv'] ?? 0));
            $precioFinalUnitario = (float)($detalle['mto_precio_unitario'] ?? ($detalle['mto_valor_unitario'] ?? 0));
            $totalFinalLinea = $valorVenta + $impuestos;
        @endphp

        <div class="item">
            <div class="item-cant">{{ number_format($cantidad, 0) }}</div>
            <div class="item-um">{{ $unidadVisible }}</div>
            <div class="item-cod">{{ $detalle['codigo'] ?? '-' }}</div>
            <div class="item-precio">{{ number_format($precioFinalUnitario, 2) }}</div>
            <div class="item-total">{{ number_format($totalFinalLinea, 2) }}</div>
        </div>
        <div class="item-descripcion">{{ strtoupper($detalle['descripcion'] ?? '') }}</div>
        <div class="item-tax">{{ $igvTexto }}</div>
    @empty
        <div class="item">
            <div style="width:100%;text-align:center;">Sin items</div>
        </div>
    @endforelse
</div>
