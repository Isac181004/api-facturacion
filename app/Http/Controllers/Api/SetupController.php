<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Services\CompanyCertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SetupController extends Controller
{
    /**
     * Setup completo del sistema
     */
    public function setup(Request $request, CompanyCertificateService $certificates)
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);
        // Verificar que las migraciones estén ejecutadas
        if ($this->checkMigrationsPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Debe ejecutar las migraciones primero',
                'required_action' => 'POST /setup/migrate',
                'current_step' => 'migrations_required'
            ], 400);
        }

        // Verificar que haya datos de ubigeos (seeders ejecutados)
        if (!\DB::table('ubi_regiones')->exists() || \DB::table('ubi_regiones')->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Debe ejecutar los seeders primero',
                'required_action' => 'POST /setup/seed',
                'current_step' => 'seeders_required'
            ], 400);
        }

        $request->validate([
            'environment' => 'required|in:beta,produccion',
            'company' => 'required|array',
            'company.ruc' => 'required|string|size:11',
            'company.razon_social' => 'required|string|max:255',
            'company.nombre_comercial' => 'nullable|string|max:255',
            'company.direccion' => 'required|string|max:255',
            'company.ubigeo' => 'required|string|size:6',
            'company.distrito' => 'required|string|max:255',
            'company.provincia' => 'required|string|max:255',
            'company.departamento' => 'required|string|max:255',
            'company.telefono' => 'nullable|string|max:255',
            'company.email' => 'nullable|email|max:255',
            'company.web' => 'nullable|url|max:255',
            'company.usuario_sol' => 'required|string|max:255',
            'company.clave_sol' => 'required|string|max:255',
            'certificado_pem' => 'nullable|file|extensions:pem|max:2048',
            'certificado_password' => 'nullable|string|max:255',
            'logo_path' => 'nullable|file|mimes:jpeg,jpg,png,gif|max:1024',
            'modo_produccion' => 'nullable|in:true,false,1,0',
            'activo' => 'nullable|in:true,false,1,0',
        ]);

        /* return response()->json(['message' => $request->all()], 202); */

        try {
            DB::beginTransaction();

            // Preparar datos de la empresa
            $companyData = $this->prepareCompanyData($request);

            $existingCompany = Company::where('ruc', $companyData['ruc'])->first();
            $previousProductionMode = $existingCompany?->modo_produccion;
            $previousActiveState = $existingCompany?->activo;
            $company = Company::updateOrCreate(
                ['ruc' => $companyData['ruc']],
                $companyData
            );
            if ($previousProductionMode !== null &&
                (bool) $previousProductionMode !== (bool) $company->modo_produccion) {
                $company->revokeApiKeysForEnvironment($previousProductionMode ? 'live' : 'test');
            }
            if ($previousActiveState !== null && (bool) $previousActiveState !== (bool) $company->activo) {
                $company->revokeApiKeysForEnvironment('test');
                $company->revokeApiKeysForEnvironment('live');
            }
            if ($request->hasFile('certificado_pem')) {
                $certificates->store($company, $request->file('certificado_pem'));
            }

            if ($company->modo_produccion &&
                (!$company->usuario_sol || !$company->clave_sol || !$certificates->hasValidPrivateCertificate($company))) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Para habilitar producción se requieren credenciales SOL y un certificado PEM válido.',
                ], 422);
            }

            // Crear sucursal principal
            $branch = $this->createMainBranch($company);

            // Configurar empresa para SUNAT
            $this->setupCompanyForSunat($company, $request->environment);

            // Asignar empresa al usuario actual si corresponde
            $this->assignCompanyToUser($request->user(), $company);

            DB::commit();

            return response()->json([
                'message' => 'Setup completado exitosamente',
                'company' => [
                    'id' => $company->id,
                    'ruc' => $company->ruc,
                    'razon_social' => $company->razon_social,
                    'environment' => $request->environment,
                    'has_certificate' => !empty($company->certificado_pem),
                    'branch_count' => $company->branches()->count()
                ],
                'branch' => [
                    'id' => $branch->id,
                    'codigo' => $branch->codigo,
                    'nombre' => $branch->nombre
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Errores de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error en setup: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Migrar base de datos
     */
    public function migrate(Request $request)
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            return response()->json([
                'message' => 'Migraciones ejecutadas exitosamente',
                'output' => $output
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al ejecutar migraciones: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Ejecutar seeders
     */
    public function seed(Request $request)
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $request->validate([
            'class' => 'nullable|string'
        ]);

        try {
            $seederClass = $request->input('class', 'DatabaseSeeder');
            
            Artisan::call('db:seed', [
                '--class' => $seederClass,
                '--force' => true
            ]);
            $output = Artisan::output();

            return response()->json([
                'message' => "Seeder '{$seederClass}' ejecutado exitosamente",
                'output' => $output,
                'seeder_class' => $seederClass
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al ejecutar seeder: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Estado del sistema
     */
    public function status()
    {
        try {
            $company = Company::first();
            $migracionesPendientes = $this->checkMigrationsPending();
            $ubigeosCargados = DB::table('ubi_regiones')->exists() && DB::table('ubi_regiones')->count() > 0;
            
            $status = [
                'database_connected' => $this->checkDatabaseConnection(),
                'migrations_pending' => $migracionesPendientes,
                'seeders_executed' => $ubigeosCargados,
                'companies_count' => Company::count(),
                'users_count' => User::count(),
                'storage_writable' => is_writable(storage_path()),
                'certificates_directory' => $this->checkCertificatesDirectory(),
                'app_environment' => config('app.env'),
                'company_environment' => $company ? ($company->modo_produccion ? 'produccion' : 'beta') : null,
                'debug' => config('app.debug'),
                'app_key_set' => !empty(config('app.key')),
                'certificate_exists' => $company ? !empty($company->certificado_pem) : false,
                'logo_exists' => $company ? !empty($company->logo_path) : false,
                
                // Estado de configuración paso a paso
                'setup_progress' => [
                    'step_1_migrations' => !$migracionesPendientes,
                    'step_2_seeders' => $ubigeosCargados,
                    'step_3_company' => Company::count() > 0,
                    'step_4_ready' => !$migracionesPendientes && $ubigeosCargados && Company::count() > 0
                ]
            ];

            $status['ready_for_use'] = $status['database_connected'] && 
                                      !$status['migrations_pending'] &&
                                      $status['seeders_executed'] &&
                                      $status['companies_count'] > 0 &&
                                      $status['app_key_set'];

            return response()->json([
                'system_status' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener estado: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Configuración del entorno SUNAT
     */
    public function configureSunat(Request $request, CompanyCertificateService $certificates)
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'environment' => 'required|in:beta,produccion',
            'certificate_file' => 'nullable|file|extensions:pem|max:2048',
            'certificate_password' => 'nullable|string',
            'force_update' => 'boolean'
        ]);

        try {
            $company = Company::findOrFail($request->company_id);

            // Verificar permisos
            if (!$request->user()->hasRole('super_admin') && 
                (int) $request->user()->company_id !== (int) $company->id) {
                return response()->json([
                    'message' => 'No tienes permisos para configurar esta empresa',
                    'status' => 'error'
                ], 403);
            }

            if ($request->environment === 'produccion' && !$request->user()->hasRole('super_admin')) {
                return response()->json(['message' => 'La habilitación de producción requiere autorización administrativa.'], 403);
            }

            // Each company owns its private PEM; no shared public certificate path is used.
            if ($request->hasFile('certificate_file')) {
                $certificates->store($company, $request->file('certificate_file'));
                if ($request->filled('certificate_password')) {
                    $company->update(['certificado_password' => $request->certificate_password]);
                }
            }

            if ($request->environment === 'produccion' &&
                (!$company->usuario_sol || !$company->clave_sol || !$certificates->hasValidPrivateCertificate($company))) {
                return response()->json(['message' => 'Para producción se requieren credenciales SOL y un certificado PEM válido.'], 422);
            }

            // Configurar para SUNAT
            $previousProductionMode = (bool) $company->modo_produccion;
            $newProductionMode = $request->environment === 'produccion';
            $company->update(['modo_produccion' => $newProductionMode]);
            if ($previousProductionMode !== $newProductionMode) {
                $company->revokeApiKeysForEnvironment($previousProductionMode ? 'live' : 'test');
            }
            $this->setupCompanyForSunat($company, $request->environment);

            return response()->json([
                'message' => 'Configuración SUNAT actualizada exitosamente',
                'company' => [
                    'id' => $company->id,
                    'ruc' => $company->ruc,
                    'environment' => $request->environment,
                    'has_certificate' => !empty($company->certificado_pem)
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Errores de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al configurar SUNAT: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Configurar empresa para SUNAT
     */
    private function setupCompanyForSunat(Company $company, string $environment)
    {
        $config = [
            'environment' => $environment,
            'services' => [
                'facturacion' => [
                    'beta' => $company->endpoint_beta ?? 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService',
                    'produccion' => $company->endpoint_produccion ?? 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService'
                ],
                'guias' => [
                    'beta' => 'https://e-beta.sunat.gob.pe/ol-ti-itemision-guia-gem-beta/billService',
                    'produccion' => 'https://e-guiaremision.sunat.gob.pe/ol-ti-itemision-guia-gem/billService'
                ],
                'consultas' => [
                    'beta' => 'https://e-beta.sunat.gob.pe/ol-it-wsconscpegem-beta/billConsultService',
                    'produccion' => 'https://e-factura.sunat.gob.pe/ol-it-wsconscpegem/billConsultService'
                ]
            ],
            'configuraciones_avanzadas' => [
                'timeout_conexion' => 30,
                'reintentos_automaticos' => 3,
                'validar_ssl' => $environment === 'produccion',
                'formato_fecha' => 'Y-m-d',
                'zona_horaria' => 'America/Lima'
            ]
        ];

        // Si la empresa no tiene el campo configuraciones, podemos agregarlo si existe en el modelo
        if (method_exists($company, 'update')) {
            try {
                $company->update(['configuraciones' => $config]);
            } catch (\Exception $e) {
                // Si no existe el campo configuraciones, continuar sin error
                Log::info('No se pudieron guardar las configuraciones: ' . $e->getMessage());
            }
        }
    }

    /**
     * Preparar datos de la empresa con valores por defecto
     */
    private function prepareCompanyData(Request $request): array
    {
        $companyData = $request->company;
        
        // Configurar endpoints por defecto
        $companyData['endpoint_beta'] = 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService';
        $companyData['endpoint_produccion'] = 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService';
        
        // The selected SUNAT environment is authoritative; ignore any client-supplied mode flag.
        $companyData['modo_produccion'] = $request->environment === 'produccion';
            
        $activo = $request->input('activo');
        $companyData['activo'] = $activo !== null 
            ? filter_var($activo, FILTER_VALIDATE_BOOLEAN) 
            : true;
        
        // The uploaded PEM is stored after company creation by CompanyCertificateService.
        unset($companyData['certificado_pem']);
        if ($request->hasFile('certificado_pem')) {
            $companyData['certificado_password'] = $request->certificado_password;
        }
        
        // Procesar logo si se subió un archivo
        if ($request->hasFile('logo_path')) {
            $logoFile = $request->file('logo_path');
            $fileName = 'logo.' . $logoFile->getClientOriginalExtension();
            $logoPath = $logoFile->storeAs('logo', $fileName, 'public');
            $companyData['logo_path'] = $logoPath;
        }
        
        // Agregar campos GRE si están presentes en la solicitud
        $greFields = [
            'gre_client_id_beta',
            'gre_client_secret_beta',
            'gre_client_id_produccion',
            'gre_client_secret_produccion',
            'gre_ruc_proveedor',
            'gre_usuario_sol',
            'gre_clave_sol'
        ];
        
        foreach ($greFields as $field) {
            if ($request->has($field)) {
                $companyData[$field] = $request->input($field);
            }
        }
        
        return $companyData;
    }

    /**
     * Crear sucursal principal
     */
    private function createMainBranch(Company $company): Branch
    {
        return Branch::updateOrCreate(
            [
                'company_id' => $company->id,
                'codigo' => '0000'
            ],
            [
                'nombre' => 'Sucursal Principal',
                'direccion' => $company->direccion,
                'ubigeo' => $company->ubigeo,
                'distrito' => $company->distrito,
                'provincia' => $company->provincia,
                'departamento' => $company->departamento,
                'activo' => true,
            ]
        );
    }

    /**
     * Asignar empresa al usuario si corresponde
     */
    private function assignCompanyToUser($user, Company $company): void
    {
        if ($user && !$user->company_id && $user->role && $user->role->name !== 'super_admin') {
            $user->update(['company_id' => $company->id]);
        }
    }

    /**
     * Verificar conexión a base de datos
     */
    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verificar migraciones pendientes
     */
    private function checkMigrationsPending(): bool
    {
        try {
            Artisan::call('migrate:status');
            $output = Artisan::output();
            return str_contains($output, 'Pending');
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Verificar directorio de certificados
     */
    private function checkCertificatesDirectory(): array
    {
        // PEM certificates are private; only company logos use the public disk.
        $privateCertificatesDirectory = 'sunat/certificates/companies';
        $privateDirectoryExists = Storage::disk('local')->exists($privateCertificatesDirectory);
        $logoExists = Storage::disk('public')->exists('logo');

        if (!$logoExists) {
            Storage::disk('public')->makeDirectory('logo');
        }

        return [
            'private_certificates_directory' => $privateDirectoryExists,
            'logo_directory' => $logoExists,
            'legacy_public_certificate_exists' => Storage::disk('public')->exists('certificado/certificado.pem'),
            'storage_link_exists' => is_link(public_path('storage')),
        ];
    }
}