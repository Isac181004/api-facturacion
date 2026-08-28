<div class="items-header items-header-description">
    <div>DETALLE DE VENTA</div>
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
            $descripcion = trim((string)($detalle['descripcion'] ?? ''));
        @endphp

        <div class="item-ticket-block">
            <div class="item-descripcion">{{ strtoupper($descripcion) }}</div>
            <div class="item-tax">{{ $igvTexto }}</div>

            <div class="item item-values">
                <div class="item-cant">
                    <span class="item-label">CANT.</span>
                    <strong>{{ number_format($cantidad, $cantidad == floor($cantidad) ? 0 : 3) }}</strong>
                </div>
                <div class="item-um">
                    <span class="item-label">U.M.</span>
                    <strong>{{ $unidadVisible }}</strong>
                </div>
                <div class="item-precio">
                    <span class="item-label">P.UNIT</span>
                    <strong>{{ number_format($precioFinalUnitario, 2) }}</strong>
                </div>
                <div class="item-total">
                    <span class="item-label">TOTAL</span>
                    <strong>{{ number_format($totalFinalLinea, 2) }}</strong>
                </div>
            </div>
        </div>
    @empty
        <div class="item-empty">Sin items</div>
    @endforelse
</div>
