<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use LogicException;
use RuntimeException;

class DocumentLookupService
{
    public function lookup(string $type, string $number): array
    {
        $driver = (string) config('services.document_lookup.driver', 'apiinti');
        $cacheHours = max(1, (int) config('services.document_lookup.cache_hours', 24));
        $cacheKey = "document_lookup:{$driver}:{$type}:{$number}";

        return Cache::remember(
            $cacheKey,
            now()->addHours($cacheHours),
            fn () => $this->lookupWithDriver($driver, $type, $number)
        );
    }

    private function lookupWithDriver(string $driver, string $type, string $number): array
    {
        return match ($driver) {
            'apiinti' => $this->lookupWithApiInti($type, $number),
            default => throw new LogicException('El proveedor de consulta de documentos no está configurado correctamente.'),
        };
    }

    private function lookupWithApiInti(string $type, string $number): array
    {
        $apiKey = (string) config('services.document_lookup.apiinti.key');
        $baseUrl = rtrim(
            (string) config('services.document_lookup.apiinti.base_url', 'https://app.apiinti.dev/api/v1'),
            '/'
        );

        if ($apiKey === '') {
            throw new LogicException('Configura APIINTI_KEY en el archivo .env para habilitar la consulta automática.');
        }

        $endpoint = $type === '1'
            ? "{$baseUrl}/dni/{$number}"
            : "{$baseUrl}/ruc/{$number}";

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->retry(2, 250, throw: false)
            ->get($endpoint);

        $data = $this->extractData($response);

        return $type === '1'
            ? $this->normalizeDni($number, $data)
            : $this->normalizeRuc($number, $data);
    }

    private function extractData(Response $response): array
    {
        if ($response->status() === 404) {
            throw new RuntimeException('No se encontraron datos para el documento consultado.');
        }

        if ($response->status() === 429) {
            throw new RuntimeException('Se alcanzó el límite temporal de consultas. Inténtalo nuevamente en unos minutos.');
        }

        if (!$response->successful()) {
            $message = $response->json('error.message')
                ?? $response->json('message')
                ?? 'El servicio de consulta de documentos no está disponible.';

            throw new RuntimeException((string) $message);
        }

        $json = $response->json();

        if (!is_array($json) || ($json['success'] ?? true) === false) {
            throw new RuntimeException(
                (string) ($json['error']['message'] ?? $json['message'] ?? 'El proveedor rechazó la consulta.')
            );
        }

        $data = $json['data'] ?? $json;

        if (!is_array($data) || $data === []) {
            throw new RuntimeException('No se encontraron datos para el documento consultado.');
        }

        return $data;
    }

    private function normalizeDni(string $number, array $data): array
    {
        $names = $this->upper($data['nombres'] ?? $data['names'] ?? '');
        $lastName = $this->upper(
            $data['apellidoPaterno'] ?? $data['apellido_paterno'] ?? $data['first_last_name'] ?? ''
        );
        $secondLastName = $this->upper(
            $data['apellidoMaterno'] ?? $data['apellido_materno'] ?? $data['second_last_name'] ?? ''
        );
        $fullName = $this->upper(
            $data['nombreCompleto']
                ?? $data['nombre_completo']
                ?? $data['full_name']
                ?? implode(' ', array_filter([$names, $lastName, $secondLastName]))
        );

        if ($fullName === '') {
            throw new RuntimeException('La consulta no devolvió los nombres del titular del DNI.');
        }

        return [
            'tipo' => '1',
            'numero_documento' => $number,
            'razon_social' => $fullName,
            'nombres' => $names,
            'apellido_paterno' => $lastName,
            'apellido_materno' => $secondLastName,
            'nombre_comercial' => null,
            'direccion' => '',
            'ubigeo' => null,
            'distrito' => '',
            'provincia' => '',
            'departamento' => '',
            'estado' => null,
            'condicion' => null,
            'fuente' => 'apiinti',
        ];
    }

    private function normalizeRuc(string $number, array $data): array
    {
        $businessName = $this->upper(
            $data['razonSocial'] ?? $data['razon_social'] ?? $data['nombre'] ?? $data['name'] ?? ''
        );

        if ($businessName === '') {
            throw new RuntimeException('La consulta no devolvió la razón social del RUC.');
        }

        return [
            'tipo' => '6',
            'numero_documento' => (string) ($data['ruc'] ?? $number),
            'razon_social' => $businessName,
            'nombres' => '',
            'apellido_paterno' => '',
            'apellido_materno' => '',
            'nombre_comercial' => $this->upper(
                $data['nombreComercial'] ?? $data['nombre_comercial'] ?? ''
            ) ?: null,
            'direccion' => $this->upper(
                $data['direccion'] ?? $data['domicilioFiscal'] ?? $data['domicilio_fiscal'] ?? ''
            ),
            'ubigeo' => $data['ubigeo'] ?? null,
            'distrito' => $this->upper($data['distrito'] ?? ''),
            'provincia' => $this->upper($data['provincia'] ?? ''),
            'departamento' => $this->upper($data['departamento'] ?? ''),
            'estado' => $data['estado'] ?? $data['estadoContribuyente'] ?? null,
            'condicion' => $data['condicion'] ?? $data['condicionDomicilio'] ?? null,
            'fuente' => 'apiinti',
        ];
    }

    private function upper(mixed $value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }
}
