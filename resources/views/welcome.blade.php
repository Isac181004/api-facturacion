@extends('layouts.portal')

@section('title', 'Facturación electrónica')

@section('content')
<section class="company-hero card" style="padding:48px 42px;min-height:330px">
    <div class="company-name" style="max-width:620px">
        <span class="eyebrow">Facturación electrónica multiempresa</span>
        <h1 class="page-title" style="font-size:clamp(36px,6vw,58px);margin-top:14px">Una conexión clara con SUNAT.</h1>
        <p class="subtitle" style="font-size:16px;max-width:550px">Gestiona facturas, boletas y guías con configuración independiente, certificado privado y acceso API aislado para cada empresa.</p>
        <a class="btn btn-primary" style="margin-top:25px" href="{{ auth()->check() ? route('portal.home') : route('login') }}">{{ auth()->check() ? 'Ir al portal' : 'Ingresar al portal' }} <span aria-hidden="true">→</span></a>
    </div>
    <div aria-hidden="true" style="position:relative;z-index:1;width:180px;height:180px;flex:0 0 180px;border-radius:34px;background:linear-gradient(145deg,#0c8c80,#145265);box-shadow:0 25px 50px #087e7830;display:grid;place-items:center;color:#fff;font-size:56px;font-weight:800;letter-spacing:-.08em">FN</div>
</section>
<section class="stack" style="grid-template-columns:repeat(3,minmax(0,1fr));margin-top:18px">
    <article class="card panel"><span class="eyebrow">01 · Aislamiento</span><h2 style="font-size:17px;margin:8px 0">Cada empresa, sus datos</h2><p class="field-hint">Documentos, sucursales, clientes, configuración y credenciales con límites por empresa.</p></article>
    <article class="card panel"><span class="eyebrow">02 · Certificados</span><h2 style="font-size:17px;margin:8px 0">PEM en almacenamiento privado</h2><p class="field-hint">Carga el certificado desde el portal; no se expone por una URL pública ni se comparte con otras empresas.</p></article>
    <article class="card panel"><span class="eyebrow">03 · API segura</span><h2 style="font-size:17px;margin:8px 0">API Keys test y live</h2><p class="field-hint">La clave determina la empresa autenticada. El cliente no elige el tenant con un company_id.</p></article>
</section>
<style>@media(max-width:760px){section.stack[style*="grid-template-columns"]{grid-template-columns:1fr!important}.company-hero[style*="min-height"]{padding:29px 25px!important}.company-hero[style*="min-height"]>div[aria-hidden]{display:none!important}}</style>
@endsection
