<?php

use App\Models\Company;
use App\Models\IntegrationClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function integrationCompany(): Company
{
    return Company::create([
        'ruc' => '20123456789',
        'razon_social' => 'EMPRESA INTEGRADA SAC',
        'direccion' => 'AV. PRUEBA 123',
        'ubigeo' => '060101',
        'distrito' => 'CAJAMARCA',
        'provincia' => 'CAJAMARCA',
        'departamento' => 'CAJAMARCA',
        'usuario_sol' => 'MODDATOS',
        'clave_sol' => 'secreto',
        'endpoint_beta' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService',
        'endpoint_produccion' => 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService',
        'modo_produccion' => false,
        'activo' => true,
    ]);
}

it('rejects requests without an integration key', function () {
    $this->getJson('/api/external/v1/documentos/consultar?tipo=1&numero=12345678')
        ->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('accepts a valid integration key and applies endpoint validation', function () {
    $company = integrationCompany();
    $plainKey = 'sunat_test_key';

    IntegrationClient::create([
        'company_id' => $company->id,
        'name' => 'Sistema de prueba',
        'key_prefix' => 'sunat_test',
        'key_hash' => hash('sha256', $plainKey),
        'abilities' => ['documents.lookup'],
        'rate_limit_per_minute' => 60,
        'active' => true,
    ]);

    $this->withHeader('X-API-Key', $plainKey)
        ->getJson('/api/external/v1/documentos/consultar?tipo=9&numero=123')
        ->assertStatus(422)
        ->assertJsonPath('message', 'Tipo de documento inválido.');
});

it('does not store SOL credentials as plaintext', function () {
    $company = integrationCompany();
    $raw = \DB::table('companies')->where('id', $company->id)->first();

    expect($raw->clave_sol)->not->toBe('secreto')
        ->and($company->fresh()->clave_sol)->toBe('secreto');
});
