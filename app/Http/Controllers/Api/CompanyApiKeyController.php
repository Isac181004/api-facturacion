<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Services\CompanyCertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CompanyApiKeyController extends Controller
{
    public function index(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company, false);

        return response()->json([
            'data' => $company->apiKeys()
                ->latest()
                ->get(['id', 'name', 'key_prefix', 'environment', 'active', 'requests_count', 'last_used_at', 'expires_at', 'revoked_at', 'created_at']),
        ]);
    }

    public function store(Request $request, Company $company, CompanyCertificateService $certificates): JsonResponse
    {
        $this->authorizeCompany($request, $company, true);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'environment' => ['required', Rule::in(['test', 'live'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $environmentIsLive = $validated['environment'] === 'live';
        if ($environmentIsLive && !$request->user()->hasRole('super_admin')) {
            return response()->json(['message' => 'Solo un administrador puede emitir una API Key live.'], 403);
        }

        if ($environmentIsLive !== (bool) $company->modo_produccion) {
            return response()->json([
                'message' => 'La empresa debe estar autorizada en el mismo ambiente antes de emitir esta clave.',
            ], 422);
        }

        if ($environmentIsLive && (!$company->usuario_sol || !$company->clave_sol || !$certificates->hasValidPrivateCertificate($company))) {
            return response()->json([
                'message' => 'Para emitir una clave live se requieren credenciales SOL y un certificado PEM válidos.',
            ], 422);
        }

        $prefix = 'mc_' . $validated['environment'] . '_' . Str::lower(Str::random(12));
        $plainTextKey = $prefix . '_' . Str::random(48);

        $key = $company->apiKeys()->create([
            'created_by_user_id' => $request->user()->id,
            'name' => $validated['name'],
            'key_prefix' => $prefix,
            'token_hash' => hash('sha256', $plainTextKey),
            'environment' => $validated['environment'],
            'active' => true,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        // This is the only time the complete secret is returned. It is never persisted or logged.
        return response()->json([
            'message' => 'API Key creada. Copia el secreto ahora; no volverá a mostrarse.',
            'data' => [
                'id' => $key->id,
                'name' => $key->name,
                'key_prefix' => $key->key_prefix,
                'environment' => $key->environment,
                'created_at' => $key->created_at,
            ],
            'api_key' => $plainTextKey,
        ], 201);
    }

    public function destroy(Request $request, Company $company, CompanyApiKey $apiKey): JsonResponse
    {
        $this->authorizeCompany($request, $company, true);

        if ((int) $apiKey->company_id !== (int) $company->id) {
            abort(404);
        }

        $apiKey->forceFill([
            'active' => false,
            'revoked_at' => now(),
        ])->save();

        return response()->json(['message' => 'API Key revocada.']);
    }

    private function authorizeCompany(Request $request, Company $company, bool $manage): void
    {
        $user = $request->user();
        $isSuperAdmin = $user?->hasRole('super_admin') ?? false;
        $isCompanyAdmin = $user &&
            (int) $user->company_id === (int) $company->id &&
            $user->hasRole('company_admin');

        abort_unless($isSuperAdmin || $isCompanyAdmin, 403);
        abort_unless($company->activo, 403, 'La empresa está inactiva.');

        if ($manage) {
            abort_unless($isSuperAdmin || $isCompanyAdmin, 403);
        }
    }
}
