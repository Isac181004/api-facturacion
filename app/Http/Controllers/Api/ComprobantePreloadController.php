<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ComprobantePreloadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo_comprobante' => 'nullable|string|in:boleta,factura',
            'origen' => 'nullable|string|max:100',
            'venta_id' => 'nullable|string|max:100',
            'moneda' => 'nullable|string|in:PEN,USD',
            'forma_pago_tipo' => 'nullable|string|in:Contado,Credito',
            'client' => 'required|array',
            'client.tipo_documento' => 'nullable|string|in:1,6',
            'client.numero_documento' => 'nullable|string|max:15',
            'client.razon_social' => 'nullable|string|max:255',
            'client.direccion' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.codigo' => 'required|string|max:50',
            'items.*.descripcion' => 'required|string|max:500',
            'items.*.unidad' => 'required|string|in:ZZ,NIU',
            'items.*.cantidad' => 'required|numeric|min:0.001',
            'items.*.precio_final' => 'required|numeric|min:0.01',
        ]);

        if (($validated['tipo_comprobante'] ?? 'boleta') === 'factura') {
            $tipoDocumento = (string) data_get($validated, 'client.tipo_documento', '');
            $numero = preg_replace('/\D/', '', (string) data_get($validated, 'client.numero_documento', ''));

            if ($tipoDocumento !== '6' || !preg_match('/^\d{11}$/', $numero)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Para preparar una factura debe enviarse un RUC válido de 11 dígitos.'
                ], 422);
            }
        }

        $token = Str::random(48);
        Cache::put('comprobante_preload:' . $token, $validated, now()->addMinutes(15));

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'expires_in_minutes' => 15,
                'url' => url('/comprobantes/manual?preload=' . $token),
            ],
            'message' => 'Datos de venta preparados para facturación.'
        ], 201);
    }

    public function show(string $token): JsonResponse
    {
        if (!preg_match('/^[A-Za-z0-9]{48}$/', $token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token de precarga inválido.'
            ], 422);
        }

        $data = Cache::get('comprobante_preload:' . $token);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Los datos de la venta expiraron o ya no están disponibles.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
