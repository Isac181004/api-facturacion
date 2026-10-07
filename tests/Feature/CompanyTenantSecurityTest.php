<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Services\CompanyCertificateService;
use App\Services\FileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function issueTestKeyFor(Company $company, string $environment = 'test'): string
{
    $prefix = 'mc_' . $environment . '_' . Str::lower(Str::random(12));
    $secret = $prefix . '_' . Str::random(48);

    $company->apiKeys()->create([
        'name' => 'Feature test key',
        'key_prefix' => $prefix,
        'token_hash' => hash('sha256', $secret),
        'environment' => $environment,
        'active' => true,
    ]);

    return $secret;
}

test('company API key scopes client reads to its authenticated company', function () {
    $firstCompany = Company::factory()->create(['modo_produccion' => false]);
    $secondCompany = Company::factory()->create(['modo_produccion' => false]);
    $firstClient = Client::factory()->create(['company_id' => $firstCompany->id]);
    Client::factory()->create(['company_id' => $secondCompany->id]);
    $key = issueTestKeyFor($firstCompany);

    $response = $this->withHeader('Authorization', 'Bearer ' . $key)
        ->getJson('/api/v1/external/clients');

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $firstClient->id);
});

test('company API key rejects a client-supplied company ID for another tenant', function () {
    $firstCompany = Company::factory()->create(['modo_produccion' => false]);
    $secondCompany = Company::factory()->create(['modo_produccion' => false]);
    $key = issueTestKeyFor($firstCompany);

    $this->withHeader('Authorization', 'Bearer ' . $key)
        ->getJson('/api/v1/external/clients?company_id=' . $secondCompany->id)
        ->assertForbidden();
});

test('company API key cannot retrieve a client belonging to another company', function () {
    $firstCompany = Company::factory()->create(['modo_produccion' => false]);
    $secondCompany = Company::factory()->create(['modo_produccion' => false]);
    $otherClient = Client::factory()->create(['company_id' => $secondCompany->id]);
    $key = issueTestKeyFor($firstCompany);

    $this->withHeader('Authorization', 'Bearer ' . $key)
        ->getJson('/api/v1/external/clients/' . $otherClient->id)
        ->assertNotFound();
});

test('API key environment must match the company-authorized SUNAT mode', function () {
    $company = Company::factory()->create(['modo_produccion' => false]);
    $key = issueTestKeyFor($company, 'live');

    $this->withHeader('Authorization', 'Bearer ' . $key)
        ->getJson('/api/v1/external/clients')
        ->assertForbidden();
});

test('generated document files are private and bound to their company', function () {
    Storage::fake('local');
    Storage::fake('public');

    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $document = (object) [
        'company_id' => $company->id,
        'fecha_emision' => '2026-10-07',
        'numero_completo' => 'F001-000001',
        'tipo_documento' => '01',
        'xml_path' => null,
    ];

    $files = app(FileService::class);
    $path = $files->saveXml($document, '<invoice/>');

    expect($path)->toStartWith('companies/' . $company->id . '/');
    Storage::disk('local')->assertExists($path);
    Storage::disk('public')->assertMissing($path);

    $document->xml_path = $path;
    $otherTenantDocument = clone $document;
    $otherTenantDocument->company_id = $otherCompany->id;
    expect($files->downloadXml($otherTenantDocument))->toBeNull();
});

test('company PEM files are written only to private storage under that company', function () {
    Storage::fake('local');
    Storage::fake('public');

    $company = Company::factory()->create(['certificado_pem' => null]);
    $pem = "-----BEGIN CERTIFICATE-----\nZmFrZS1jZXJ0aWZpY2F0ZQ==\n-----END CERTIFICATE-----\n" .
        "-----BEGIN PRIVATE KEY-----\nZmFrZS1wcml2YXRlLWtleQ==\n-----END PRIVATE KEY-----\n";
    $file = UploadedFile::fake()->createWithContent('company.pem', $pem);

    $service = app(CompanyCertificateService::class);
    $path = $service->store($company, $file);

    expect($path)->toBe('sunat/certificates/companies/' . $company->id . '/certificate.pem');
    Storage::disk('local')->assertExists($path);
    Storage::disk('public')->assertMissing($path);
    expect($service->contents($company->fresh()))->toBe($pem);
});
