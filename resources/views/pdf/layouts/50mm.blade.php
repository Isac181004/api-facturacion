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
        color: #000;
        background: #fff;
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 7.4px;
        line-height: 1.18;
    }

    .container {
        width: 50mm;
        margin: 0;
        padding: 1.5mm 1.6mm 2mm 1.6mm;
    }

    .header {
        width: 100%;
        text-align: center;
        padding: 0 0 2mm 0;
        margin: 0 0 1.5mm 0;
        border-bottom: .25mm dashed #000;
    }

    .logo-section-ticket {
        text-align: center;
        margin: 0 0 .6mm 0;
    }

    .logo-img-ticket {
        display: block;
        width: 22mm;
        max-height: 16mm;
        object-fit: contain;
        margin: 0 auto;
    }

    .company-ruc {
        font-size: 8px;
        font-weight: bold;
        margin: .5mm 0;
    }

    .company-details {
        font-size: 6.8px;
        line-height: 1.2;
        margin: .4mm 0 .9mm 0;
        overflow-wrap: anywhere;
    }

    .document-title {
        font-size: 8.2px;
        font-weight: bold;
        line-height: 1.15;
        text-transform: uppercase;
        padding: 1mm 0 .7mm 0;
        margin: .8mm 0 0 0;
        border-top: .25mm solid #000;
    }

    .document-number {
        font-size: 9px;
        font-weight: bold;
        padding: .5mm 0 0 0;
    }

    .client-section {
        width: 100%;
        padding: 0 0 1.5mm 0;
        margin: 0 0 1.5mm 0;
        border-bottom: .25mm dashed #000;
        text-align: left;
    }

    .client-name {
        font-size: 7.4px;
        font-weight: bold;
        line-height: 1.2;
        text-align: left;
        margin-bottom: .6mm;
        overflow-wrap: anywhere;
    }

    .client-separator { display: none; }

    .client-details {
        font-size: 6.9px;
        line-height: 1.2;
        text-align: left;
        margin-bottom: .5mm;
        overflow-wrap: anywhere;
    }

    .items-header {
        display: table;
        width: 100%;
        table-layout: fixed;
        padding: .7mm 0;
        margin: 0;
        border-top: .25mm solid #000;
        border-bottom: .25mm solid #000;
        font-size: 6.3px;
        line-height: 1.1;
        font-weight: bold;
    }

    .items-header > div,
    .item > div {
        display: table-cell;
        vertical-align: top;
        text-align: center;
        padding: .2mm .15mm;
        overflow-wrap: anywhere;
    }

    .header-cant, .item-cant { width: 13%; }
    .header-um, .item-um { width: 14%; }
    .header-cod, .item-cod { width: 18%; }
    .header-precio, .item-precio { width: 27%; text-align: right; }
    .header-total, .item-total { width: 28%; text-align: right; }

    .items-section {
        width: 100%;
        margin: 0 0 1.5mm 0;
        padding: 0 0 1mm 0;
        border-bottom: .25mm solid #000;
    }

    .item {
        display: table;
        width: 100%;
        table-layout: fixed;
        font-size: 6.5px;
        line-height: 1.15;
        margin: .8mm 0 .3mm 0;
    }

    .item-descripcion {
        font-size: 7px;
        font-weight: bold;
        line-height: 1.2;
        text-align: left;
        margin: 0;
        padding-left: .3mm;
        overflow-wrap: anywhere;
    }

    .item-tax {
        font-size: 6.2px;
        line-height: 1.15;
        text-align: left;
        margin: .3mm 0 1mm .3mm;
        font-weight: normal;
    }

    .totals-section {
        width: 100%;
        margin: 0;
        padding: 0;
        font-size: 7px;
    }

    .total-line {
        display: table;
        width: 100%;
        table-layout: fixed;
        font-size: 7px;
        line-height: 1.2;
        margin: 0 0 .5mm 0;
    }

    .total-text {
        display: table-cell;
        width: 62%;
        text-align: left;
        font-weight: normal;
    }

    .total-dots { display: none; }

    .total-value {
        display: table-cell;
        width: 38%;
        text-align: right;
        font-weight: bold;
        white-space: nowrap;
    }

    .total-final {
        border-top: .25mm solid #000;
        border-bottom: .25mm solid #000;
        padding: .8mm 0;
        margin-top: .8mm;
    }

    .total-final .total-text,
    .total-final .total-value {
        font-size: 8.3px;
        font-weight: bold;
    }

    .total-letras {
        font-size: 6.8px;
        font-weight: bold;
        line-height: 1.2;
        text-align: left;
        margin: 1.2mm 0;
        overflow-wrap: anywhere;
    }

    .payment-info {
        font-size: 6.8px;
        line-height: 1.2;
        text-align: left;
        padding: 1mm 0;
        margin: 1mm 0;
        border-top: .25mm dashed #000;
        border-bottom: .25mm dashed #000;
    }

    .payment-info div { margin-bottom: .5mm; }

    .qr-section {
        text-align: center;
        margin: 1.5mm 0 1mm 0;
        padding: 0;
    }

    .qr-code img {
        display: block;
        width: 22mm;
        height: 22mm;
        margin: 0 auto;
    }

    .footer-text {
        font-size: 6.2px;
        line-height: 1.2;
        text-align: center;
        margin: 1mm 0;
        overflow-wrap: anywhere;
    }

    .footer-url {
        font-size: 6px;
        line-height: 1.15;
        text-align: center;
        margin: .7mm 0;
        overflow-wrap: anywhere;
    }

    .footer-auth {
        font-size: 5.5px;
        line-height: 1.1;
        text-align: center;
        margin: .6mm 0;
        overflow-wrap: anywhere;
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
