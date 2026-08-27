<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    config([
        'services.document_lookup.driver' => 'apiinti',
        'services.document_lookup.cache_hours' => 24,
        'services.document_lookup.apiinti.key' => 'test-token',
        'services.document_lookup.apiinti.base_url' => 'https://app.apiinti.dev/api/v1',
    ]);

    $this->user = User::factory()->create();
});

test('consulta un DNI y normaliza nombres y apellidos', function () {
    Http::fake([
        'https://app.apiinti.dev/api/v1/dni/12345678' => Http::response([
            'success' => true,
            'data' => [
                'nombres' => 'María Elena',
                'apellidoPaterno' => 'Vásquez',
                'apellidoMaterno' => 'Rojas',
            ],
        ]),
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/v1/documentos/dni/12345678')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.nombres', 'MARÍA ELENA')
        ->assertJsonPath('data.apellido_paterno', 'VÁSQUEZ')
        ->assertJsonPath('data.apellido_materno', 'ROJAS')
        ->assertJsonPath('data.razon_social', 'MARÍA ELENA VÁSQUEZ ROJAS');
});

test('consulta un RUC y normaliza los datos de la empresa', function () {
    Http::fake([
        'https://app.apiinti.dev/api/v1/ruc/20601234567' => Http::response([
            'success' => true,
            'data' => [
                'ruc' => '20601234567',
                'razonSocial' => 'Distribuidora Comercial del Pacífico S.A.C.',
                'nombreComercial' => 'Comercial Pacífico',
                'direccion' => 'Av. República de Panamá 3565',
                'distrito' => 'Surquillo',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'estado' => 'ACTIVO',
                'condicion' => 'HABIDO',
            ],
        ]),
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/v1/documentos/ruc/20601234567')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.razon_social', 'DISTRIBUIDORA COMERCIAL DEL PACÍFICO S.A.C.')
        ->assertJsonPath('data.nombre_comercial', 'COMERCIAL PACÍFICO')
        ->assertJsonPath('data.estado', 'ACTIVO')
        ->assertJsonPath('data.condicion', 'HABIDO');
});

test('rechaza documentos con longitud inválida sin consultar al proveedor', function () {
    Http::fake();

    $this->actingAs($this->user)
        ->getJson('/api/v1/documentos/consultar?tipo=1&numero=123')
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    Http::assertNothingSent();
});

test('informa claramente cuando falta la credencial del proveedor', function () {
    config(['services.document_lookup.apiinti.key' => null]);

    $this->actingAs($this->user)
        ->getJson('/api/v1/documentos/dni/12345678')
        ->assertStatus(503)
        ->assertJsonPath('success', false);
});

test('las consultas requieren autenticación', function () {
    $this->getJson('/api/v1/documentos/dni/12345678')->assertStatus(401);
    $this->getJson('/api/v1/documentos/ruc/20601234567')->assertStatus(401);
});
