<div class="items-header" style="border-bottom:.25mm solid #000;font-size:6.2px;padding:.7mm 0;">
    <div style="width:100%;text-align:left;padding-left:.2mm;">DETALLE DE VENTA</div>
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

        <div style="width:100%;padding:1.1mm .2mm 1mm .2mm;border-bottom:.18mm dashed #888;">
            <div class="item-descripcion" style="font-size:6.9px;line-height:1.2;margin:0 0 .45mm 0;padding:0;font-weight:bold;">
                {{ strtoupper($descripcion) }}
            </div>

            <div class="item-tax" style="font-size:5.7px;margin:0 0 .75mm 0;padding:0;">
                {{ $igvTexto }}
            </div>

            <div style="display:table;width:100%;table-layout:fixed;font-size:6.1px;line-height:1.12;">
                <div style="display:table-cell;width:17%;text-align:center;vertical-align:top;">
                    <div style="font-size:5.2px;font-weight:bold;">CANT.</div>
                    <div>{{ number_format($cantidad, $cantidad == floor($cantidad) ? 0 : 3) }}</div>
                </div>
                <div style="display:table-cell;width:19%;text-align:center;vertical-align:top;">
                    <div style="font-size:5.2px;font-weight:bold;">U.M.</div>
                    <div>{{ $unidadVisible }}</div>
                </div>
                <div style="display:table-cell;width:31%;text-align:right;vertical-align:top;padding-right:.6mm;">
                    <div style="font-size:5.2px;font-weight:bold;">P.UNIT</div>
                    <div>{{ number_format($precioFinalUnitario, 2) }}</div>
                </div>
                <div style="display:table-cell;width:33%;text-align:right;vertical-align:top;">
                    <div style="font-size:5.2px;font-weight:bold;">TOTAL</div>
                    <div style="font-weight:bold;">{{ number_format($totalFinalLinea, 2) }}</div>
                </div>
            </div>
        </div>
    @empty
        <div style="width:100%;text-align:center;padding:2mm 0;">Sin items</div>
    @endforelse
</div>
