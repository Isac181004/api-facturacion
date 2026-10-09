<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CompanyCertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function editOwn(Request $request, CompanyCertificateService $certificates): View
    {
        $user = $request->user();
        abort_unless($user && $user->company_id, 403);
        abort_unless($user->hasAnyRole(['company_admin', 'company_user']), 403);

        $company = $user->company;
        abort_unless($company, 404);

        return $this->renderSettings($company, $user->hasRole('company_admin'), false, $certificates);
    }

    public function updateOwn(Request $request, CompanyCertificateService $certificates): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->company_id && $user->hasRole('company_admin'), 403);

        return $this->save($request, $user->company, false, $certificates);
    }

    public function editAdmin(Company $company, Request $request, CompanyCertificateService $certificates): View
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        return $this->renderSettings($company, true, true, $certificates);
    }

    public function updateAdmin(Company $company, Request $request, CompanyCertificateService $certificates): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        return $this->save($request, $company, true, $certificates);
    }

    private function renderSettings(Company $company, bool $canManage, bool $adminView, CompanyCertificateService $certificates): View
    {
        $apiKeys = $canManage
            ? $company->apiKeys()->latest()->get()
            : collect();

        return view('company.settings', [
            'company' => $company,
            'canManage' => $canManage,
            'adminView' => $adminView,
            'hasCertificate' => $certificates->hasValidPrivateCertificate($company),
            'apiKeys' => $apiKeys,
            'updateUrl' => $adminView
                ? route('portal.admin.company.settings.update', $company)
                : route('portal.company.settings.update'),
            'apiKeysUrl' => route('portal.company.api-keys', $company),
        ]);
    }

    private function save(Request $request, Company $company, bool $adminView, CompanyCertificateService $certificates): RedirectResponse
    {
        $user = $request->user();
        $previousProductionMode = (bool) $company->modo_produccion;
        $rules = [
            'usuario_sol' => ['required', 'string', 'max:50'],
            'clave_sol' => ['nullable', 'string', 'max:100'],
            'certificado_password' => ['nullable', 'string', 'max:100'],
            'certificado_pem' => ['nullable', 'file', 'max:2048'],
            'gre_client_id_beta' => ['nullable', 'string', 'max:255'],
            'gre_client_secret_beta' => ['nullable', 'string', 'max:255'],
            'gre_ruc_proveedor' => ['nullable', 'digits:11'],
            'gre_usuario_sol' => ['nullable', 'string', 'max:50'],
            'gre_clave_sol' => ['nullable', 'string', 'max:100'],
        ];

        if ($adminView) {
            $rules['modo_produccion'] = ['required', 'in:beta,live'];
            $rules['gre_client_id_produccion'] = ['nullable', 'string', 'max:255'];
            $rules['gre_client_secret_produccion'] = ['nullable', 'string', 'max:255'];
        }

        $secretInputNames = [
            'clave_sol', 'certificado_password', 'gre_client_secret_beta', 'gre_clave_sol',
            'gre_client_secret_produccion',
        ];
        try {
            $validated = $request->validate($rules);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())
                ->withInput($request->except($secretInputNames));
        }

        if ($request->hasFile('certificado_pem')) {
            try {
                $certificates->store($company, $request->file('certificado_pem'));
            } catch (ValidationException $exception) {
                return back()->withErrors($exception->errors())
                    ->withInput($request->except($secretInputNames));
            }
        }

        $company->usuario_sol = $validated['usuario_sol'];
        if (!empty($validated['clave_sol'])) {
            $company->clave_sol = $validated['clave_sol'];
        }
        if (!empty($validated['certificado_password'])) {
            $company->certificado_password = $validated['certificado_password'];
        }

        foreach ([
            'gre_client_id_beta',
            'gre_client_secret_beta',
            'gre_ruc_proveedor',
            'gre_usuario_sol',
            'gre_clave_sol',
        ] as $field) {
            if (!empty($validated[$field])) {
                $company->{$field} = $validated[$field];
            }
        }

        if ($adminView) {
            foreach (['gre_client_id_produccion', 'gre_client_secret_produccion'] as $field) {
                if (!empty($validated[$field])) {
                    $company->{$field} = $validated[$field];
                }
            }

            $enableProduction = $validated['modo_produccion'] === 'live';
            if ($enableProduction && (!$company->usuario_sol || !$company->clave_sol || !$certificates->hasValidPrivateCertificate($company))) {
                return back()->withErrors([
                    'modo_produccion' => 'No se puede autorizar producción sin credenciales SOL y un certificado PEM válido.',
                ])->withInput($request->except($secretInputNames));
            }
            $company->modo_produccion = $enableProduction;
        }

        $company->save();
        if ($adminView && $previousProductionMode !== (bool) $company->modo_produccion) {
            $company->revokeApiKeysForEnvironment($previousProductionMode ? 'live' : 'test');
        }

        return redirect($adminView
            ? route('portal.admin.company.settings', $company)
            : route('portal.company.settings'))
            ->with('status', 'La configuración SUNAT se guardó correctamente.');
    }
}
