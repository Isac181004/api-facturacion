<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\Client;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCompanyApiKey
{
    public function __construct(private TenantContext $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenantContext->clear();
        $plainTextKey = (string) $request->bearerToken();

        if (!preg_match('/^(mc_(test|live)_[a-zA-Z0-9]{12})_([a-zA-Z0-9]{48})$/', $plainTextKey, $matches)) {
            return $this->unauthorized();
        }

        $apiKey = CompanyApiKey::query()
            ->where('key_prefix', $matches[1])
            ->where('active', true)
            ->whereNull('revoked_at')
            ->first();

        if (!$apiKey || ($apiKey->expires_at && $apiKey->expires_at->isPast()) ||
            !hash_equals($apiKey->token_hash, hash('sha256', $plainTextKey))) {
            return $this->unauthorized();
        }

        $company = Company::withoutGlobalScopes()->find($apiKey->company_id);
        if (!$company || !$company->activo) {
            return response()->json(['message' => 'La empresa asociada a esta clave está inactiva.'], 403);
        }

        $productionKey = $apiKey->environment === 'live';
        if ($productionKey !== (bool) $company->modo_produccion) {
            return response()->json([
                'message' => 'El ambiente de la API Key no coincide con el ambiente autorizado de la empresa.'
            ], 403);
        }

        $providedCompanyId = $request->input('company_id');
        if ($providedCompanyId !== null && (int) $providedCompanyId !== (int) $company->id) {
            return response()->json(['message' => 'La API Key no puede operar sobre otra empresa.'], 403);
        }

        $this->tenantContext->setCompany((int) $company->id, $apiKey->environment);

        try {
            $request->attributes->set('tenant_company', $company);
            $request->attributes->set('company_api_key', $apiKey);

            foreach (($request->route()?->parameters() ?? []) as $parameter) {
                $resourceCompanyId = null;
                if ($parameter instanceof Company) {
                    $resourceCompanyId = (int) $parameter->id;
                } elseif (is_object($parameter) && isset($parameter->company_id)) {
                    $resourceCompanyId = (int) $parameter->company_id;
                }
                if ($resourceCompanyId !== null && $resourceCompanyId !== (int) $company->id) {
                    return response()->json(['message' => 'El recurso no pertenece a la empresa de esta API Key.'], 404);
                }
            }

            $request->merge(['company_id' => $company->id]);

            $branchId = $request->input('branch_id');
            if ($branchId !== null && !Branch::query()->whereKey($branchId)->exists()) {
                return response()->json(['message' => 'La sucursal no pertenece a la empresa de esta API Key.'], 404);
            }

            $clientId = $request->input('client_id');
            if ($clientId !== null && !Client::query()->whereKey($clientId)->exists()) {
                return response()->json(['message' => 'El cliente no pertenece a la empresa de esta API Key.'], 404);
            }

            $apiKey->forceFill(['last_used_at' => now()])->save();
            $apiKey->increment('requests_count');

            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'message' => 'API Key ausente, inválida, revocada o vencida.'
        ], 401, ['WWW-Authenticate' => 'Bearer']);
    }
}
