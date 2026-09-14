<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\GreenterService;
use Illuminate\Http\Request;

class SunatCredentialController extends Controller
{
    public function update(Request $request, Company $company)
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $data = $request->validate([
            'usuario_sol' => 'required|string|max:100',
            'clave_sol' => 'required|string|max:255',
            'certificado' => 'required|file|max:5120|mimes:pem,txt',
            'modo_produccion' => 'required|boolean',
        ]);

        $pem = file_get_contents($data['certificado']->getRealPath());
        if (!$pem || !str_contains($pem, 'BEGIN CERTIFICATE') || !str_contains($pem, 'PRIVATE KEY')) {
            return response()->json(['success' => false, 'message' => 'El PEM debe contener el certificado y su clave privada.'], 422);
        }

        $company->update([
            'usuario_sol' => $data['usuario_sol'],
            'clave_sol' => $data['clave_sol'],
            'certificado_pem' => $pem,
            'modo_produccion' => $data['modo_produccion'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Credenciales SUNAT guardadas de forma cifrada.',
            'data' => [
                'company_id' => $company->id,
                'ruc' => $company->ruc,
                'modo' => $company->modo_produccion ? 'produccion' : 'beta',
                'certificado_configurado' => true,
            ],
        ]);
    }

    public function validateConfiguration(Request $request, Company $company)
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        return response()->json([
            'success' => true,
            'data' => (new GreenterService($company))->diagnose(),
        ]);
    }
}
