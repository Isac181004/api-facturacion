<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\IntegrationClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IntegrationClientController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403, 'Solo el administrador de la plataforma puede gestionar integraciones.');
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        return response()->json([
            'success' => true,
            'data' => IntegrationClient::with('company:id,ruc,razon_social')
                ->latest()->paginate($request->integer('per_page', 20)),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:120',
            'abilities' => 'nullable|array',
            'abilities.*' => 'in:documents.read,invoices.create,invoices.send,boletas.create,boletas.send,documents.lookup',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
            'rate_limit_per_minute' => 'nullable|integer|min:1|max:1000',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $plainKey = 'sunat_'.Str::lower(Str::random(48));
        $client = IntegrationClient::create([
            ...$data,
            'key_prefix' => substr($plainKey, 0, 14),
            'key_hash' => hash('sha256', $plainKey),
            'abilities' => $data['abilities'] ?? ['documents.read', 'invoices.create', 'invoices.send', 'boletas.create', 'boletas.send', 'documents.lookup'],
            'rate_limit_per_minute' => $data['rate_limit_per_minute'] ?? 60,
            'active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Integración creada. Guarda la clave: no volverá a mostrarse.',
            'api_key' => $plainKey,
            'data' => $client->load('company:id,ruc,razon_social'),
        ], 201);
    }

    public function update(Request $request, IntegrationClient $integrationClient)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'abilities' => 'sometimes|array',
            'abilities.*' => 'in:documents.read,invoices.create,invoices.send,boletas.create,boletas.send,documents.lookup',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
            'rate_limit_per_minute' => 'sometimes|integer|min:1|max:1000',
            'expires_at' => 'nullable|date',
            'active' => 'sometimes|boolean',
        ]);
        $integrationClient->update($data);

        return response()->json(['success' => true, 'data' => $integrationClient->fresh()]);
    }

    public function rotate(Request $request, IntegrationClient $integrationClient)
    {
        $this->authorizeAdmin($request);
        $plainKey = 'sunat_'.Str::lower(Str::random(48));
        $integrationClient->update([
            'key_prefix' => substr($plainKey, 0, 14),
            'key_hash' => hash('sha256', $plainKey),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clave rotada. La anterior dejó de funcionar.',
            'api_key' => $plainKey,
        ]);
    }

    public function destroy(Request $request, IntegrationClient $integrationClient)
    {
        $this->authorizeAdmin($request);
        $integrationClient->update(['active' => false]);

        return response()->json(['success' => true, 'message' => 'Integración revocada.']);
    }
}
