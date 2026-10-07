<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Models\Company;
use App\Services\CompanyCertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Exception;

class CompanyController extends Controller
{
    /**
     * Listar todas las empresas
     */
    public function index(): JsonResponse
    {
        try {
            $companies = Company::active()
                ->with(['branches'])
                ->select([
                    'id', 'ruc', 'razon_social', 'nombre_comercial', 
                    'direccion', 'distrito', 'provincia', 'departamento',
                    'email', 'telefono', 'modo_produccion', 'activo',
                    'created_at', 'updated_at'
                ])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $companies,
                'meta' => [
                    'total' => $companies->count(),
                    'active_count' => $companies->where('activo', true)->count(),
                    'production_count' => $companies->where('modo_produccion', true)->count()
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Error al listar empresas", ['error' => $e->getMessage()]);

            return $this->errorResponse('Error al obtener empresas', $e);
        }
    }

    /**
     * Crear nueva empresa
     */
    public function store(StoreCompanyRequest $request, CompanyCertificateService $certificates): JsonResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        try {
            $validatedData = $this->processRequestData($request);
            if (!empty($validatedData['modo_produccion'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Crea la empresa en Beta y habilita producción después de cargar credenciales y certificado.'
                ], 422);
            }
            $company = Company::create($validatedData);
            if ($request->hasFile('certificado_pem')) {
                $certificates->store($company, $request->file('certificado_pem'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Empresa creada exitosamente',
                'data' => $company
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return $this->errorResponse('Error al crear empresa', $e);
        }
    }

    /**
     * Obtener empresa específica
     */
    public function show(Company $company): JsonResponse
    {
        try {
            $company->load(['branches']);

            return response()->json([
                'success' => true,
                'data' => [
                    'company' => $company,
                    'stats' => $this->getCompanyStats($company)
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Error al obtener empresa", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Error al obtener empresa', $e);
        }
    }

    /**
     * Actualizar empresa
     */
    public function update(UpdateCompanyRequest $request, Company $company, CompanyCertificateService $certificates): JsonResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        try {
            $validatedData = $this->processRequestData($request, true);
            if (array_key_exists('modo_produccion', $validatedData) &&
                (bool) $validatedData['modo_produccion'] !== (bool) $company->modo_produccion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cambia el ambiente con la ruta de autorización de producción.'
                ], 422);
            }
            if ($request->hasFile('certificado_pem')) {
                $certificates->store($company, $request->file('certificado_pem'));
            }
            $wasActive = (bool) $company->activo;
            $company->update($validatedData);
            if ($wasActive !== (bool) $company->activo) {
                $company->revokeApiKeysForEnvironment('test');
                $company->revokeApiKeysForEnvironment('live');
            }

            Log::info("Empresa actualizada exitosamente", [
                'company_id' => $company->id,
                'ruc' => $company->ruc,
                'changed_fields' => array_values(array_diff(array_keys($company->getChanges()), [
                    'clave_sol', 'certificado_pem', 'certificado_password',
                    'gre_client_secret_beta', 'gre_client_secret_produccion', 'gre_clave_sol',
                ]))
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Empresa actualizada exitosamente',
                'data' => $company->fresh()
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error("Error al actualizar empresa", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Error al actualizar empresa', $e);
        }
    }

    /**
     * Eliminar empresa (soft delete)
     */
    public function destroy(Request $request, Company $company): JsonResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        try {
            if ($this->hasAssociatedDocuments($company)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la empresa porque tiene documentos asociados. Considere desactivarla en su lugar.'
                ], 400);
            }

            $company->update(['activo' => false]);
            $company->revokeApiKeysForEnvironment('test');
            $company->revokeApiKeysForEnvironment('live');

            Log::warning("Empresa desactivada", [
                'company_id' => $company->id,
                'ruc' => $company->ruc
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Empresa desactivada exitosamente'
            ]);

        } catch (Exception $e) {
            Log::error("Error al desactivar empresa", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Error al desactivar empresa', $e);
        }
    }

    /**
     * Activar empresa
     */
    public function activate(Request $request, Company $company): JsonResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        try {
            if (!$company->activo) {
                // Do not revive credentials that may have remained active before tenant safeguards were deployed.
                $company->revokeApiKeysForEnvironment('test');
                $company->revokeApiKeysForEnvironment('live');
                $company->update(['activo' => true]);
            }

            Log::info("Empresa activada", [
                'company_id' => $company->id,
                'ruc' => $company->ruc
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Empresa activada exitosamente',
                'data' => $company
            ]);

        } catch (Exception $e) {
            Log::error("Error al activar empresa", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Error al activar empresa', $e);
        }
    }

    /**
     * Cambiar modo de producción
     */
    public function toggleProductionMode(Request $request, Company $company, CompanyCertificateService $certificates): JsonResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        try {
            $validator = Validator::make($request->all(), [
                'modo_produccion' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $oldMode = $company->modo_produccion;
            $newMode = filter_var($request->modo_produccion, FILTER_VALIDATE_BOOLEAN);
            if ($newMode && (!$company->usuario_sol || !$company->clave_sol || !$certificates->hasValidPrivateCertificate($company))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Para autorizar producción, la empresa debe tener usuario SOL, clave SOL y certificado PEM válidos.'
                ], 422);
            }

            $company->update(['modo_produccion' => $newMode]);
            if ((bool) $oldMode !== (bool) $newMode) {
                $company->revokeApiKeysForEnvironment($oldMode ? 'live' : 'test');
            }

            Log::info("Modo de producción cambiado", [
                'company_id' => $company->id,
                'ruc' => $company->ruc,
                'old_mode' => $this->getModeName($oldMode),
                'new_mode' => $this->getModeName($newMode)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Modo de producción actualizado exitosamente',
                'data' => [
                    'company_id' => $company->id,
                    'modo_anterior' => $this->getModeName($oldMode),
                    'modo_actual' => $this->getModeName($newMode)
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Error al cambiar modo de producción", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Error al cambiar modo de producción', $e);
        }
    }

    /**
     * Procesar datos de la request
     */
    private function processRequestData(Request $request, bool $updating = false): array
    {
        $validatedData = $request->validated();

        // Defaults apply only when creating; omitted update fields must remain unchanged.
        if (array_key_exists('modo_produccion', $validatedData)) {
            $validatedData['modo_produccion'] = $this->processBoolean($validatedData['modo_produccion']);
        } elseif (!$updating) {
            $validatedData['modo_produccion'] = false;
        }

        if (array_key_exists('activo', $validatedData)) {
            $validatedData['activo'] = $this->processBoolean($validatedData['activo']);
        } elseif (!$updating) {
            $validatedData['activo'] = true;
        }

        // Certificate uploads are written by CompanyCertificateService to a private, company-specific path.
        unset($validatedData['certificado_pem']);

        if ($request->hasFile('logo_path')) {
            $fileName = 'logo_' . time() . '.' . $request->file('logo_path')->getClientOriginalExtension();
            $validatedData['logo_path'] = $this->storeFile($request->file('logo_path'), 'logos', $fileName);
        }

        return $validatedData;
    }

    /**
     * Procesar valor booleano
     */
    private function processBoolean($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Almacenar archivo
     */
    private function storeFile($file, string $directory, string $fileName): string
    {
        return $file->storeAs($directory, $fileName, 'public');
    }

    /**
     * Verificar documentos asociados
     */
    private function hasAssociatedDocuments(Company $company): bool
    {
        return $company->invoices()->exists() ||
               $company->boletas()->exists() ||
               $company->dispatchGuides()->exists();
    }

    /**
     * Obtener estadísticas de la empresa
     */
    private function getCompanyStats(Company $company): array
    {
        return [
            'branches_count' => $company->branches()->count(),
            'configurations_count' => $company->configurations()->active()->count(),
            'has_gre_credentials' => $company->hasGreCredentials(),
            'environment_mode' => $this->getModeName($company->modo_produccion)
        ];
    }

    /**
     * Obtener nombre del modo
     */
    private function getModeName(bool $mode): string
    {
        return $mode ? 'produccion' : 'beta';
    }

    /**
     * Respuesta de error estandarizada
     */
    private function errorResponse(string $message, Exception $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message . ': ' . $e->getMessage()
        ], 500);
    }
}