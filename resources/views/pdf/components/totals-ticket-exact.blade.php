@php
    $simbolo = ($document->moneda ?? 'PEN') === 'USD' ? '$' : 'S/';
    $gravado = (float)($document->mto_oper_gravadas ?? 0);
    $exonerado = (float)($document->mto_oper_exoneradas ?? 0);
    $inafecto = (float)($document->mto_oper_inafectas ?? 0);
    $igv = (float)($document->mto_igv ?? 0);
    $total = (float)($document->mto_imp_venta ?? 0);
@endphp

<div class="totals-section">
    <div class="total-line">
        <span class="total-text">OP. GRAVADAS</span>
        <span class="total-value">{{ $simbolo }} {{ number_format($gravado, 2) }}</span>
    </div>

    @if($exonerado > 0)
        <div class="total-line">
            <span class="total-text">OP. EXONERADAS</span>
            <span class="total-value">{{ $simbolo }} {{ number_format($exonerado, 2) }}</span>
        </div>
    @endif

    @if($inafecto > 0)
        <div class="total-line">
            <span class="total-text">OP. INAFECTAS</span>
            <span class="total-value">{{ $simbolo }} {{ number_format($inafecto, 2) }}</span>
        </div>
    @endif

    <div class="total-line">
        <span class="total-text">IGV</span>
        <span class="total-value">{{ $simbolo }} {{ number_format($igv, 2) }}</span>
    </div>

    <div class="total-line total-final">
        <span class="total-text">TOTAL</span>
        <span class="total-value">{{ $simbolo }} {{ number_format($total, 2) }}</span>
    </div>
</div>

<div class="total-letras">
    SON: {{ strtoupper($total_en_letras ?? '') }}
</div>

<div class="payment-info">
    <div><strong>FORMA DE PAGO:</strong> {{ $document->forma_pago_tipo ?? 'CONTADO' }}</div>
    <div><strong>COND. VENTA:</strong> {{ $document->condicion_venta ?? 'CONTADO' }}</div>
</div>

@if(!empty($document->observaciones))
    <div class="payment-info">
        <div><strong>OBSERVACIONES:</strong></div>
        <div>{{ $document->observaciones }}</div>
    </div>
@endif
