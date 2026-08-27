@php
    $rucImpreso = config('salon.ruc_real');
@endphp

<table style="width:100%;border-collapse:collapse;margin-bottom:18px;">
    <tr>
        <td style="width:30%;text-align:center;vertical-align:middle;padding:8px;">
            @include('pdf.components.logo-maju-gimena', [
                'style' => 'max-width:145px;max-height:105px;display:block;margin:0 auto;'
            ])
        </td>

        <td style="width:40%;vertical-align:middle;padding:10px 8px;">
            <div style="font-size:10px;line-height:1.55;">
                <strong>{{ config('salon.tagline') }}</strong><br>
                {{ config('salon.direccion') }}<br>
                CELULAR / WHATSAPP: {{ config('salon.telefono') }}
            </div>
        </td>

        <td style="width:30%;vertical-align:middle;padding:7px;">
            <div style="border:1.5px solid #111;border-radius:10px;text-align:center;padding:15px 7px;line-height:1.45;">
                <div style="font-size:12px;font-weight:bold;">RUC {{ $rucImpreso }}</div>
                <div style="font-size:14px;font-weight:bold;margin-top:5px;">{{ $tipo_documento_nombre }}</div>
                <div style="font-size:14px;font-weight:bold;margin-top:5px;">{{ $document->numero_completo }}</div>
            </div>
        </td>
    </tr>
</table>

<div style="border-bottom:1px solid #444;margin-bottom:18px;"></div>
