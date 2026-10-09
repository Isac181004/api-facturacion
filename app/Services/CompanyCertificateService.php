<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CompanyCertificateService
{
    private const PRIVATE_DIRECTORY = 'sunat/certificates/companies';
    private const LEGACY_PUBLIC_PATH = 'certificado/certificado.pem';

    /** Store only private-key/certificate PEM files below the company's private directory. */
    public function store(Company $company, UploadedFile $file): string
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'pem') {
            throw ValidationException::withMessages([
                'certificado_pem' => 'El certificado debe tener extensión .pem.',
            ]);
        }

        $contents = $file->get();
        if (!is_string($contents)) {
            throw ValidationException::withMessages([
                'certificado_pem' => 'No se pudo leer el archivo PEM cargado.',
            ]);
        }
        $this->assertPemContents($contents);

        $path = $this->pathFor((int) $company->id);
        if (!Storage::disk('local')->put($path, $contents)) {
            throw new RuntimeException('No se pudo guardar el certificado en el almacenamiento privado.');
        }

        $previousPath = $company->certificado_pem;
        $company->forceFill(['certificado_pem' => $path])->save();

        if ($previousPath && $previousPath !== $path) {
            if ($this->isPrivateCompanyPath($previousPath, (int) $company->id)) {
                Storage::disk('local')->delete($previousPath);
            } elseif ($previousPath === self::LEGACY_PUBLIC_PATH &&
                Company::withoutGlobalScopes()->where('certificado_pem', $previousPath)->doesntExist()) {
                Storage::disk('public')->delete($previousPath);
            }
        }

        return $path;
    }

    /** Return the certificate bytes for a single company's SUNAT request. */
    public function contents(Company $company): string
    {
        $certificate = $company->certificado_pem;

        if (!$certificate) {
            throw new RuntimeException("La empresa {$company->id} no tiene un certificado PEM configurado.");
        }

        // Support databases where the complete PEM was stored inline in the text column.
        if (str_contains($certificate, '-----BEGIN CERTIFICATE-----') &&
            preg_match('/-----BEGIN (?:RSA |EC |ENCRYPTED )?PRIVATE KEY-----/', $certificate)) {
            return $certificate;
        }

        if ($this->isPrivateCompanyPath($certificate, (int) $company->id)) {
            if (!Storage::disk('local')->exists($certificate)) {
                throw new RuntimeException("No se encontró el certificado privado de la empresa {$company->id}.");
            }

            $contents = Storage::disk('local')->get($certificate);
            if (!is_string($contents)) {
                throw new RuntimeException("No se pudo leer el certificado privado de la empresa {$company->id}.");
            }

            return $contents;
        }

        // Backwards-compatible read for a true single-company installation only. Once the
        // platform has multiple tenants, each one must upload its own private PEM—even if
        // only one row still points to the old shared public path.
        if ($certificate === self::LEGACY_PUBLIC_PATH) {
            $companyCount = Company::withoutGlobalScopes()->count();
            $references = Company::withoutGlobalScopes()
                ->where('certificado_pem', self::LEGACY_PUBLIC_PATH)
                ->count();

            if ($companyCount !== 1 || $references !== 1 || !Storage::disk('public')->exists(self::LEGACY_PUBLIC_PATH)) {
                throw new RuntimeException('El certificado histórico sólo puede usarse en una instalación de una empresa; cada empresa debe cargar su PEM privado.');
            }

            $contents = Storage::disk('public')->get(self::LEGACY_PUBLIC_PATH);
            if (!is_string($contents)) {
                throw new RuntimeException('No se pudo leer el certificado histórico.');
            }

            return $contents;
        }

        throw new RuntimeException('La ruta del certificado no pertenece al almacenamiento privado de esta empresa.');
    }

    public function hasCertificate(Company $company): bool
    {
        try {
            return $this->hasPemStructure($this->contents($company));
        } catch (\Throwable) {
            return false;
        }
    }

    public function hasPrivateCertificate(Company $company): bool
    {
        return $this->isPrivateCompanyPath((string) $company->certificado_pem, (int) $company->id)
            && $this->hasCertificate($company);
    }

    public function hasValidPrivateCertificate(Company $company): bool
    {
        if (!$this->hasPrivateCertificate($company)) {
            return false;
        }

        try {
            $contents = $this->contents($company);
            $certificate = @openssl_x509_read($contents);
            $privateKey = @openssl_pkey_get_private($contents, (string) $company->certificado_password);
            if (!$certificate || !$privateKey || !@openssl_x509_check_private_key($certificate, $privateKey)) {
                return false;
            }

            $details = @openssl_x509_parse($certificate);
            return is_array($details) &&
                ($details['validFrom_time_t'] ?? PHP_INT_MAX) <= time() &&
                ($details['validTo_time_t'] ?? 0) >= time();
        } catch (\Throwable) {
            return false;
        }
    }

    private function pathFor(int $companyId): string
    {
        return self::PRIVATE_DIRECTORY . '/' . $companyId . '/certificate.pem';
    }

    private function isPrivateCompanyPath(string $path, int $companyId): bool
    {
        return $path === $this->pathFor($companyId);
    }

    private function assertPemContents(string $contents): void
    {
        if (!$this->hasPemStructure($contents)) {
            throw ValidationException::withMessages([
                'certificado_pem' => 'El archivo debe contener un certificado X.509 y su clave privada en formato PEM.',
            ]);
        }
    }

    private function hasPemStructure(string $contents): bool
    {
        return str_contains($contents, '-----BEGIN CERTIFICATE-----') &&
            (bool) preg_match('/-----BEGIN (?:RSA |EC |ENCRYPTED )?PRIVATE KEY-----/', $contents);
    }
}
