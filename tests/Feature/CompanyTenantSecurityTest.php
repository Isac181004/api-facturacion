<?php

use App\Models\Branch;
use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\DispatchGuide;
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

test('company API key scopes credit notes debit notes and dispatch guides to its tenant', function () {
    $firstCompany = Company::factory()->create(['modo_produccion' => false]);
    $secondCompany = Company::factory()->create(['modo_produccion' => false]);
    $firstBranch = Branch::factory()->create(['company_id' => $firstCompany->id]);
    $secondBranch = Branch::factory()->create(['company_id' => $secondCompany->id]);
    $firstClient = Client::factory()->create(['company_id' => $firstCompany->id]);
    $secondClient = Client::factory()->create(['company_id' => $secondCompany->id]);

    $otherCreditNote = CreditNote::factory()->create([
        'company_id' => $secondCompany->id,
        'branch_id' => $secondBranch->id,
        'client_id' => $secondClient->id,
    ]);
    CreditNote::factory()->create([
        'company_id' => $firstCompany->id,
        'branch_id' => $firstBranch->id,
        'client_id' => $firstClient->id,
    ]);
    DebitNote::factory()->create([
        'company_id' => $firstCompany->id,
        'branch_id' => $firstBranch->id,
        'client_id' => $firstClient->id,
    ]);
    DispatchGuide::factory()->create([
        'company_id' => $firstCompany->id,
        'branch_id' => $firstBranch->id,
        'client_id' => $firstClient->id,
    ]);

    $key = issueTestKeyFor($firstCompany);

    $this->withHeader('Authorization', 'Bearer ' . $key)
        ->getJson('/api/v1/external/credit-notes')
        ->assertOk()
        ->assertJsonPath('data.total', 1);

    $this->withHeader('Authorization', 'Bearer ' . $key)
        ->getJson('/api/v1/external/debit-notes')
        ->assertOk()
        ->assertJsonPath('data.total', 1);

    $this->withHeader('Authorization', 'Bearer ' . $key)
        ->getJson('/api/v1/external/dispatch-guides')
        ->assertOk()
        ->assertJsonPath('data.total', 1);

    $this->withHeader('Authorization', 'Bearer ' . $key)
        ->getJson('/api/v1/external/credit-notes/' . $otherCreditNote->id)
        ->assertNotFound();
});

test('document API routes require a company API key', function () {
    $this->postJson('/api/v1/external/credit-notes', [])->assertUnauthorized();
    $this->postJson('/api/v1/external/debit-notes', [])->assertUnauthorized();
    $this->postJson('/api/v1/external/dispatch-guides', [])->assertUnauthorized();
});

test('company API key rejects a different company ID on document creation', function () {
    $company = Company::factory()->create(['modo_produccion' => false]);
    $otherCompany = Company::factory()->create(['modo_produccion' => false]);
    $key = issueTestKeyFor($company);

    $this->withHeader('Authorization', 'Bearer ' . $key)
        ->postJson('/api/v1/external/credit-notes', ['company_id' => $otherCompany->id])
        ->assertForbidden();
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

test('companies retain the established Beta endpoint and their configured invoice endpoint', function () {
    $company = Company::factory()->create(['modo_produccion' => false]);

    expect($company->getInvoiceEndpoint())
        ->toBe('https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService')
        ->and($company->getGuideEndpoint())
        ->toBe('https://gre-test.nubefact.com/v1');

    $customCompany = Company::factory()->create([
        'modo_produccion' => false,
        'endpoint_beta' => 'https://beta.example.test/billService',
    ]);
    expect($customCompany->getInvoiceEndpoint())->toBe('https://beta.example.test/billService');
});

test('legacy public PEM remains readable for a single-company installation', function () {
    Storage::fake('public');
    $pem = "legacy-certificate-bytes";
    Storage::disk('public')->put('certificado/certificado.pem', $pem);
    $company = Company::factory()->create(['certificado_pem' => 'certificado/certificado.pem']);

    expect(app(CompanyCertificateService::class)->contents($company))->toBe($pem);
});

test('legacy public PEM is blocked as soon as a second company exists', function () {
    Storage::fake('public');
    Storage::disk('public')->put('certificado/certificado.pem', 'legacy-certificate-bytes');
    $legacyCompany = Company::factory()->create(['certificado_pem' => 'certificado/certificado.pem']);
    Company::factory()->create(['certificado_pem' => null]);

    expect(fn () => app(CompanyCertificateService::class)->contents($legacyCompany))
        ->toThrow(\RuntimeException::class);
});

test('shared legacy note and guide files are privatized separately by company', function () {
    Storage::fake('local');
    Storage::fake('public');

    $firstCompany = Company::factory()->create();
    $secondCompany = Company::factory()->create();
    $firstBranch = Branch::factory()->create(['company_id' => $firstCompany->id]);
    $secondBranch = Branch::factory()->create(['company_id' => $secondCompany->id]);
    $firstClient = Client::factory()->create(['company_id' => $firstCompany->id]);
    $secondClient = Client::factory()->create(['company_id' => $secondCompany->id]);

    $sharedXml = 'notas-credito/shared.xml';
    Storage::disk('public')->put($sharedXml, '<credit-note/>');
    $firstNote = CreditNote::factory()->create([
        'company_id' => $firstCompany->id,
        'branch_id' => $firstBranch->id,
        'client_id' => $firstClient->id,
        'xml_path' => $sharedXml,
    ]);
    $secondNote = CreditNote::factory()->create([
        'company_id' => $secondCompany->id,
        'branch_id' => $secondBranch->id,
        'client_id' => $secondClient->id,
        'xml_path' => $sharedXml,
    ]);

    $sharedPdf = 'guias-remision/shared.pdf';
    Storage::disk('public')->put($sharedPdf, '%PDF-test');
    $firstGuide = DispatchGuide::factory()->create([
        'company_id' => $firstCompany->id,
        'branch_id' => $firstBranch->id,
        'client_id' => $firstClient->id,
        'pdf_path' => $sharedPdf,
    ]);
    $secondGuide = DispatchGuide::factory()->create([
        'company_id' => $secondCompany->id,
        'branch_id' => $secondBranch->id,
        'client_id' => $secondClient->id,
        'pdf_path' => $sharedPdf,
    ]);

    $this->artisan('sunat:privatize-document-files')->assertExitCode(0);

    $firstNote->refresh();
    $secondNote->refresh();
    $firstGuide->refresh();
    $secondGuide->refresh();

    expect($firstNote->xml_path)->toStartWith('companies/' . $firstCompany->id . '/')
        ->and($secondNote->xml_path)->toStartWith('companies/' . $secondCompany->id . '/')
        ->and($firstNote->xml_path)->not->toBe($secondNote->xml_path)
        ->and($firstGuide->pdf_path)->toStartWith('companies/' . $firstCompany->id . '/')
        ->and($secondGuide->pdf_path)->toStartWith('companies/' . $secondCompany->id . '/')
        ->and($firstGuide->pdf_path)->not->toBe($secondGuide->pdf_path);

    Storage::disk('local')->assertExists($firstNote->xml_path);
    Storage::disk('local')->assertExists($secondNote->xml_path);
    Storage::disk('local')->assertExists($firstGuide->pdf_path);
    Storage::disk('local')->assertExists($secondGuide->pdf_path);
    Storage::disk('public')->assertMissing($sharedXml);
    Storage::disk('public')->assertMissing($sharedPdf);
});
