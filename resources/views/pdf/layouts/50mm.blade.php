@extends('pdf.layouts.base')

@section('format-styles')
<style>
    @page { margin: 0; }

    * { box-sizing: border-box; }

    html,
    body {
        margin: 0;
        padding: 0;
        width: 50mm;
        max-width: 50mm;
        color: #000;
        background: #fff;
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 7.1px;
        line-height: 1.17;
        overflow-x: hidden;
    }

    .container {
        width: 47mm;
        max-width: 47mm;
        margin: 0 auto;
        padding: 1.3mm .9mm 2mm .9mm;
        overflow: hidden;
    }

    .header {
        width: 100%;
        max-width: 100%;
        text-align: center;
        padding: 0 0 1.5mm 0;
        margin: 0 0 1.2mm 0;
        border-bottom: .25mm dashed #000;
        overflow: hidden;
    }

    .logo-section-ticket {
        width: 100%;
        text-align: center;
        margin: 0 0 .5mm 0;
    }

    .logo-img-ticket {
        display: block;
        width: 20mm;
        max-width: 20mm;
        max-height: 14mm;
        object-fit: contain;
        margin: 0 auto;
    }

    .company-ruc {
        font-size: 7.7px;
        font-weight: bold;
        margin: .45mm 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .company-details {
        width: 100%;
        max-width: 100%;
        font-size: 6.4px;
        line-height: 1.18;
        margin: .35mm 0 .8mm 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .document-title {
        width: 100%;
        max-width: 100%;
        font-size: 7.6px;
        font-weight: bold;
        line-height: 1.15;
        text-transform: uppercase;
        padding: .8mm .5mm .6mm .5mm;
        margin: .7mm 0 0 0;
        border-top: .25mm solid #000;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .document-number {
        font-size: 8.5px;
        font-weight: bold;
        padding: .4mm 0 0 0;
        overflow-wrap: anywhere;
    }

    .client-section {
        width: 100%;
        max-width: 100%;
        padding: 0 0 1.2mm 0;
        margin: 0 0 1.2mm 0;
        border-bottom: .25mm dashed #000;
        text-align: left;
        overflow: hidden;
    }

    .client-name {
        width: 100%;
        max-width: 100%;
        font-size: 6.8px;
        font-weight: bold;
        line-height: 1.18;
        text-align: left;
        margin-bottom: .5mm;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .client-separator { display: none; }

    .client-details {
        width: 100%;
        max-width: 100%;
        font-size: 6.5px;
        line-height: 1.18;
        text-align: left;
        margin-bottom: .4mm;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .items-header {
        display: table;
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        padding: .6mm 0;
        margin: 0;
        border-top: .25mm solid #000;
        border-bottom: .25mm solid #000;
        font-size: 5.8px;
        line-height: 1.08;
        font-weight: bold;
        overflow: hidden;
    }

    .items-header > div,
    .item > div {
        display: table-cell;
        vertical-align: top;
        text-align: center;
        padding: .15mm .1mm;
        overflow: hidden;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .header-cant, .item-cant { width: 12%; }
    .header-um, .item-um { width: 14%; }
    .header-cod, .item-cod { width: 18%; }
    .header-precio, .item-precio { width: 27%; text-align: right; white-space: nowrap; }
    .header-total, .item-total { width: 29%; text-align: right; white-space: nowrap; }

    .items-section {
        width: 100%;
        max-width: 100%;
        margin: 0 0 1.2mm 0;
        padding: 0 0 .8mm 0;
        border-bottom: .25mm solid #000;
        overflow: hidden;
    }

    .item {
        display: table;
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        font-size: 6.1px;
        line-height: 1.12;
        margin: .7mm 0 .25mm 0;
        overflow: hidden;
    }

    .item-descripcion {
        width: 100%;
        max-width: 100%;
        font-size: 6.6px;
        font-weight: bold;
        line-height: 1.18;
        text-align: left;
        margin: 0;
        padding: 0 .2mm;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .item-tax {
        width: 100%;
        max-width: 100%;
        font-size: 5.9px;
        line-height: 1.12;
        text-align: left;
        margin: .25mm 0 .8mm .2mm;
        font-weight: normal;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .totals-section {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        font-size: 6.6px;
        overflow: hidden;
    }

    .total-line {
        display: table;
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        font-size: 6.6px;
        line-height: 1.18;
        margin: 0 0 .45mm 0;
        overflow: hidden;
    }

    .total-text {
        display: table-cell;
        width: 60%;
        text-align: left;
        font-weight: normal;
        padding-right: .5mm;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .total-dots { display: none; }

    .total-value {
        display: table-cell;
        width: 40%;
        text-align: right;
        font-weight: bold;
        white-space: nowrap;
        padding-right: .2mm;
    }

    .total-final {
        border-top: .25mm solid #000;
        border-bottom: .25mm solid #000;
        padding: .7mm 0;
        margin-top: .7mm;
    }

    .total-final .total-text,
    .total-final .total-value {
        font-size: 7.8px;
        font-weight: bold;
    }

    .total-letras {
        width: 100%;
        max-width: 100%;
        font-size: 6.4px;
        font-weight: bold;
        line-height: 1.18;
        text-align: left;
        margin: 1mm 0;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .payment-info {
        width: 100%;
        max-width: 100%;
        font-size: 6.3px;
        line-height: 1.18;
        text-align: left;
        padding: .8mm 0;
        margin: .8mm 0;
        border-top: .25mm dashed #000;
        border-bottom: .25mm dashed #000;
        overflow: hidden;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .payment-info div { margin-bottom: .4mm; }

    .qr-section {
        width: 100%;
        max-width: 100%;
        text-align: center;
        margin: 1.2mm 0 .8mm 0;
        padding: 0;
    }

    .qr-code img {
        display: block;
        width: 19mm;
        height: 19mm;
        max-width: 19mm;
        margin: 0 auto;
    }

    .footer-text {
        width: 100%;
        max-width: 100%;
        font-size: 5.8px;
        line-height: 1.18;
        text-align: center;
        margin: .7mm 0;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .footer-url {
        width: 100%;
        max-width: 100%;
        font-size: 5.4px;
        line-height: 1.15;
        text-align: center;
        margin: .6mm 0;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-all;
    }

    .footer-auth {
        width: 100%;
        max-width: 100%;
        font-size: 5.2px;
        line-height: 1.08;
        text-align: center;
        margin: .5mm 0;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-all;
    }

    .powered-by { display: none; }

    .actions,
    .btn,
    .no-print { display: none !important; }
</style>
@endsection

@section('body-content')
<div class="container">
    @yield('content')
</div>
@endsection
