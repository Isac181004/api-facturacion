<?php

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('las empresas nuevas no reciben credenciales GRE compartidas de demostración', function () {
    $company = Company::factory()->create(['modo_produccion' => false]);

    expect($company->getGreClientId())->toBeNull()
        ->and($company->getGreClientSecret())->toBeNull()
        ->and($company->hasGreCredentials())->toBeFalse();
});

test('las credenciales GRE beta pertenecen exclusivamente a su empresa', function () {
    $company = Company::factory()->create(['modo_produccion' => false]);
    $otherCompany = Company::factory()->create(['modo_produccion' => false]);

    $company->setGreCredentials([
        'client_id' => 'beta-client-for-company-one',
        'client_secret' => 'beta-secret-for-company-one',
        'ruc_proveedor' => '20123456789',
        'usuario_sol' => 'BETAUSER',
        'clave_sol' => 'BetaPassword123',
    ], 'beta');

    $company->refresh();
    $otherCompany->refresh();

    expect($company->getGreClientId())->toBe('beta-client-for-company-one')
        ->and($company->getGreClientSecret())->toBe('beta-secret-for-company-one')
        ->and($company->hasGreCredentials())->toBeTrue()
        ->and($otherCompany->getGreClientId())->toBeNull()
        ->and($otherCompany->getGreClientSecret())->toBeNull()
        ->and($otherCompany->hasGreCredentials())->toBeFalse();
});

test('las credenciales GRE se resuelven según el ambiente de cada empresa', function () {
    $company = Company::factory()->create(['modo_produccion' => false]);

    $company->setGreCredentials([
        'client_id' => 'beta-client',
        'client_secret' => 'beta-secret',
    ], 'beta');
    $company->setGreCredentials([
        'client_id' => 'live-client',
        'client_secret' => 'live-secret',
    ], 'produccion');

    $company->refresh();
    expect($company->getGreClientId())->toBe('beta-client');
    expect($company->getGreCredentials()['environment'])->toBe('beta');

    $company->modo_produccion = true;
    $company->save();
    $company->refresh();

    expect($company->getGreClientId())->toBe('live-client')
        ->and($company->getGreClientSecret())->toBe('live-secret')
        ->and($company->getGreCredentials()['environment'])->toBe('produccion');
});

test('se pueden copiar y limpiar credenciales GRE de un ambiente concreto', function () {
    $company = Company::factory()->create(['modo_produccion' => false]);
    $company->setGreCredentials([
        'client_id' => 'beta-client',
        'client_secret' => 'beta-secret',
    ], 'beta');

    expect($company->copyGreCredentials('beta', 'produccion'))->toBeTrue();
    $company->refresh();
    expect($company->gre_client_id_produccion)->toBe('beta-client')
        ->and($company->gre_client_secret_produccion)->toBe('beta-secret');

    $company->clearGreCredentials('produccion');
    $company->refresh();
    expect($company->gre_client_id_produccion)->toBeNull()
        ->and($company->gre_client_secret_produccion)->toBeNull()
        ->and($company->gre_client_id_beta)->toBe('beta-client');
});
