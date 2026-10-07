@extends('layouts.portal')

@section('title', 'Iniciar sesión')

@section('content')
<div class="login-shell card">
    <section class="login-story">
        <span class="eyebrow" style="color:#a8e1d8">Un espacio para tu empresa</span>
        <h1>Tu facturación, bajo tu control.</h1>
        <p>Administra la conexión SUNAT, tu certificado digital y las API Keys de forma segura desde un solo lugar.</p>
        <div class="feature-list">
            <div class="feature"><span class="feature-mark">✓</span> Certificado privado e independiente por empresa</div>
            <div class="feature"><span class="feature-mark">✓</span> Credenciales y ambiente controlados</div>
            <div class="feature"><span class="feature-mark">✓</span> Acceso API aislado por empresa</div>
        </div>
    </section>
    <section class="login-form">
        <span class="eyebrow">Portal de acceso</span>
        <h2>Bienvenido</h2>
        <p class="lead">Ingresa con la cuenta habilitada para tu empresa.</p>
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="field">
                <label for="email">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="tu@empresa.pe">
            </div>
            <div class="field">
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="••••••••••••">
            </div>
            <button class="btn btn-primary" type="submit">Entrar al portal <span aria-hidden="true">→</span></button>
        </form>
        <p class="login-note">¿Necesitas acceso? Solicítalo al administrador de tu empresa.</p>
    </section>
</div>
@endsection
