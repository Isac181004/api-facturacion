@if(isset($qr_code) && !empty($qr_code))
    <div class="qr-section">
        <div class="qr-code">
            <img src="{{ $qr_code }}" alt="Código QR">
        </div>
    </div>
@endif

<div class="footer-text">
    Representación impresa del
    {{ strtoupper($tipo_documento_nombre ?? 'COMPROBANTE DE PAGO ELECTRÓNICO') }}.
</div>

<div class="footer-text">
    Autorizado según normativa SUNAT.
</div>

@if(isset($hash) && !empty($hash))
    <div class="footer-auth">
        HASH:<br>{{ $hash }}
    </div>
@endif

<div class="footer-url">
    Consulte su comprobante en:<br>
    {{ config('app.url') }}
</div>
