<div class="items-header" style="display:table;width:100%;table-layout:fixed;border-top:.25mm solid #000;border-bottom:.25mm solid #000;font-size:5.9px;font-weight:bold;padding:.65mm 0;line-height:1.05;">
    <div style="display:table-cell;width:18%;text-align:left;padding-left:.2mm;">[ CANT. ]</div>
    <div style="display:table-cell;width:47%;text-align:left;">DESCRIPCIÓN</div>
    <div style="display:table-cell;width:16%;text-align:right;padding-right:.5mm;">P/U</div>
    <div style="display:table-cell;width:19%;text-align:right;">TOTAL</div>
</div>

<div class="items-section" style="border-bottom:.25mm solid #000;margin-bottom:1mm;padding-bottom:.4mm;">
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
            $cantidadVisible = number_format($cantidad, $cantidad == floor($cantidad) ? 0 : 3);
            $valorVenta = (float)($detalle['mto_valor_venta'] ?? 0);
            $impuestos = (float)($detalle['total_impuestos'] ?? ($detalle['igv'] ?? 0));
            $precioFinalUnitario = (float)($detalle['mto_precio_unitario'] ?? ($detalle['mto_valor_unitario'] ?? 0));
            $totalFinalLinea = $valorVenta + $impuestos;
            $descripcion = trim((string)($detalle['descripcion'] ?? ''));
        @endphp

        <div style="width:100%;padding:.85mm .15mm .7mm .15mm;{{ !$loop->last ? 'border-bottom:.18mm dashed #999;' : '' }}">
            <div style="display:table;width:100%;table-layout:fixed;font-size:6.25px;line-height:1.16;">
                <div style="display:table-cell;width:18%;text-align:left;vertical-align:top;font-weight:bold;white-space:nowrap;">
                    [{{ $cantidadVisible }}]
                </div>

                <div style="display:table-cell;width:47%;text-align:left;vertical-align:top;padding-right:.5mm;white-space:normal;overflow-wrap:break-word;word-break:normal;">
                    <span style="font-weight:bold;">{{ $unidadVisible }}</span>
                    {{ strtoupper($descripcion) }}
                </div>

                <div style="display:table-cell;width:16%;text-align:right;vertical-align:top;padding-right:.5mm;white-space:nowrap;">
                    {{ number_format($precioFinalUnitario, 2) }}
                </div>

                <div style="display:table-cell;width:19%;text-align:right;vertical-align:top;font-weight:bold;white-space:nowrap;">
                    {{ number_format($totalFinalLinea, 2) }}
                </div>
            </div>

            <div style="margin-left:18%;padding-top:.35mm;font-size:5.45px;line-height:1.08;text-align:left;font-weight:normal;">
                {{ $igvTexto }}
            </div>
        </div>
    @empty
        <div style="width:100%;text-align:center;padding:2mm 0;">Sin items</div>
    @endforelse
</div>
