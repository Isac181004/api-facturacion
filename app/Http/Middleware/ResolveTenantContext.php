<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function __construct(private TenantContext $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenantContext->clear();
        $user = $request->user();

        if (!$user || !$user->active || $user->isLocked()) {
            return response()->json(['message' => 'Autenticación requerida.'], 401);
        }

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        if (!$user->company_id) {
            return response()->json(['message' => 'El usuario no está asociado a una empresa.'], 403);
        }

        $companyId = (int) $user->company_id;
        $this->tenantContext->setCompany($companyId);

        try {
            $suppliedCompanyId = $request->input('company_id');
            if ($suppliedCompanyId !== null && (int) $suppliedCompanyId !== $companyId) {
                return response()->json(['message' => 'No tienes acceso a esa empresa.'], 403);
            }

            foreach (($request->route()?->parameters() ?? []) as $parameter) {
                $resourceCompanyId = null;

                if ($parameter instanceof Company) {
                    $resourceCompanyId = (int) $parameter->id;
                } elseif ($parameter instanceof CompanyApiKey || (is_object($parameter) && isset($parameter->company_id))) {
                    $resourceCompanyId = (int) $parameter->company_id;
                }

                if ($resourceCompanyId !== null && $resourceCompanyId !== $companyId) {
                    return response()->json(['message' => 'No tienes acceso a ese recurso.'], 404);
                }
            }

            $routeCompanyId = $request->route('company_id');
            if ($routeCompanyId !== null && (int) $routeCompanyId !== $companyId) {
                return response()->json(['message' => 'No tienes acceso a esa empresa.'], 404);
            }

            if ($suppliedCompanyId === null) {
                $request->merge(['company_id' => $companyId]);
            }

            $branchId = $request->input('branch_id');
            if ($branchId !== null && !Branch::query()->whereKey($branchId)->exists()) {
                return response()->json(['message' => 'La sucursal no pertenece a tu empresa.'], 404);
            }

            $clientId = $request->input('client_id');
            if ($clientId !== null && !Client::query()->whereKey($clientId)->exists()) {
                return response()->json(['message' => 'El cliente no pertenece a tu empresa.'], 404);
            }

            $company = Company::query()->find($companyId);
            if (!$company || !$company->activo) {
                return response()->json(['message' => 'La empresa no está activa.'], 403);
            }

            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }
}
