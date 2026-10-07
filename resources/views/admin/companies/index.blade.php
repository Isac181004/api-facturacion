@extends('layouts.portal')

@section('title', 'Empresas')

@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">Administración del sistema</span>
        <h1 class="page-title">Empresas</h1>
        <p class="subtitle">Revisa el ambiente autorizado y la conexión SUNAT de cada cuenta.</p>
    </div>
    <span class="status-pill pill-slate">{{ $companies->count() }} {{ $companies->count() === 1 ? 'empresa' : 'empresas' }}</span>
</div>

@if($companies->isEmpty())
    <section class="card empty">Todavía no hay empresas registradas.</section>
@else
    <div class="admin-list">
        @foreach($companies as $company)
            <section class="company-row card">
                <div>
                    <span class="eyebrow">RUC {{ $company->ruc }}</span>
                    <h3>{{ $company->razon_social }}</h3>
                    <p>{{ $company->nombre_comercial ?: $company->email ?: 'Cuenta empresarial' }}</p>
                    <div class="row-metrics">
                        <span class="metric-chip">{{ $company->modo_produccion ? 'Producción · live' : 'Beta · test' }}</span>
                        <span class="metric-chip">{{ $company->branches_count }} sucursales</span>
                        <span class="metric-chip">{{ $company->api_keys_count }} API Keys</span>
                        <span class="metric-chip">{{ $company->activo ? 'Activa' : 'Inactiva' }}</span>
                    </div>
                </div>
                <a class="btn btn-primary" href="{{ route('portal.admin.company.settings', $company) }}">Abrir configuración <span aria-hidden="true">→</span></a>
            </section>
        @endforeach
    </div>
@endif
@endsection
