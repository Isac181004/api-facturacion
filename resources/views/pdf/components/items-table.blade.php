{{-- PDF Items Table Component --}}
{{-- Props: $detalles, $format --}}
@php
    $maxFilas = in_array($format, ['a5', 'A5']) ? 8 : 15;
    $contador = count($detalles);
@endphp

@if(in_array($format, ['a4', 'A4', 'a5', 'A5']))
    <table class="items-table">
        <thead>
            <tr>
                <th>Nº</th>
                <th>CÓDIGO</th>
                <th>DESCRIPCIÓN</th>
                <th>UNIDAD</th>
                <th>CANT.</th>
                <th>IGV</th>
                <th>P. UNIT.</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detalles as $index => $detalle)
                @php
                    $unidadCodigo = strtoupper((string)($detalle['unidad'] ?? ''));
                    $unidadVisible = match($unidadCodigo) {
                        'ZZ' => 'SERVICIO',
                        'NIU' => 'UNIDAD',
                        default => $unidadCodigo ?: 'UNIDAD'
                    };
                    $tipAfe = (string)($detalle['tip_afe_igv'] ?? '10');
                    $igvTexto = match($tipAfe) {
                        '10' => '18%',
                        '20' => 'EXON.',
                        '30' => 'INAF.',
                        default => number_format((float)($detalle['porcentaje_igv'] ?? 0), 0) . '%'
                    };
                    $valorVenta = (float)($detalle['mto_valor_venta'] ?? 0);
                    $impuestos = (float)($detalle['total_impuestos'] ?? ($detalle['igv'] ?? 0));
                    $precioFinalUnitario = (float)($detalle['mto_precio_unitario'] ?? ($detalle['mto_valor_unitario'] ?? 0));
                    $totalFinalLinea = $valorVenta + $impuestos;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detalle['codigo'] ?? '' }}</td>
                    <td>{{ $detalle['descripcion'] ?? '' }}</td>
                    <td>{{ $unidadVisible }}</td>
                    <td>{{ number_format($detalle['cantidad'] ?? 0, 2) }}</td>
                    <td>{{ $igvTexto }}</td>
                    <td>{{ number_format($precioFinalUnitario, 2) }}</td>
                    <td>{{ number_format($totalFinalLinea, 2) }}</td>
                </tr>
            @endforeach

            @for($i = $contador; $i < $maxFilas; $i++)
                <tr>
                    <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            @endfor
        </tbody>
    </table>
@else
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-codigo">Cód.</th>
                <th class="col-descripcion">Descripción</th>
                <th class="col-cantidad">Cant.</th>
                <th class="col-precio">P. Unit.</th>
                <th class="col-total">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detalles as $detalle)
                @php
                    $valorVenta = (float)($detalle['mto_valor_venta'] ?? 0);
                    $impuestos = (float)($detalle['total_impuestos'] ?? ($detalle['igv'] ?? 0));
                    $precioFinalUnitario = (float)($detalle['mto_precio_unitario'] ?? ($detalle['mto_valor_unitario'] ?? 0));
                @endphp
                <tr>
                    <td class="text-center">{{ $detalle['codigo'] ?? '-' }}</td>
                    <td class="text-left">{{ Str::limit($detalle['descripcion'] ?? '', 20) }}</td>
                    <td class="text-center">{{ number_format($detalle['cantidad'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($precioFinalUnitario, 2) }}</td>
                    <td class="text-right">{{ number_format($valorVenta + $impuestos, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
