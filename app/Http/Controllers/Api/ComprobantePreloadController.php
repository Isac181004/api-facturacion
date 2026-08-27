<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ComprobantePreloadController extends Controller
{
    /**
     * Precarga protegida por Sanctum para consumidores autenticados.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        if ($error = $this->facturaRucError($validated)) {
            return $error;
        }

        return $this->cachePayload($validated);
    }

    /**
     * Puente para el sistema PHP local de ventas.
     * No expone credenciales ni tokens: únicamente acepta solicitudes loopback
     * (127.0.0.1 / ::1) enviadas desde el mismo equipo donde corre Laravel.
     *
     * Para FACTURA se permite precargar sin RUC porque la pantalla manual obliga
     * al usuario a ingresar/consultar un RUC válido antes de crear el comprobante.
     */
    public function storeLocalIntegration(Request $request): JsonResponse
    {
        if (!$this->isLoopbackRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => 'La integración de ventas solo está disponible desde el equipo local.'
            ], 403);
        }

        $validated = $this->validatePayload($request);

        return $this->cachePayload($validated);
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

    private function validatePayload(Request $request): array
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
            'items.*.tip_afe_igv' => 'nullable|string|in:10,20,30',
            'items.*.aplica_igv' => 'nullable|boolean',
        ]);

        foreach ($validated['items'] as &$item) {
            if (empty($item['tip_afe_igv'])) {
                $item['tip_afe_igv'] = array_key_exists('aplica_igv', $item) && $item['aplica_igv'] === false
                    ? '30'
                    : '10';
            }
        }
        unset($item);

        return $validated;
    }

    private function facturaRucError(array $validated): ?JsonResponse
    {
        if (($validated['tipo_comprobante'] ?? 'boleta') !== 'factura') {
            return null;
        }

        $tipoDocumento = (string) data_get($validated, 'client.tipo_documento', '');
        $numero = preg_replace('/\D/', '', (string) data_get($validated, 'client.numero_documento', ''));

        if ($tipoDocumento === '6' && preg_match('/^\d{11}$/', $numero)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Para preparar una factura debe enviarse un RUC válido de 11 dígitos.'
        ], 422);
    }

    private function cachePayload(array $validated): JsonResponse
    {
        $token = Str::random(48);
        Cache::put('comprobante_preload:' . $token, $validated, now()->addMinutes(15));

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'expires_in_minutes' => 15,
                // El token va también en la ruta y no solo en query string.
                // Esto evita que navegadores embebidos o shells de escritorio
                // eliminen ?preload=... durante el login o una navegación interna.
                'url' => url('/comprobantes/manual/' . $token),
            ],
            'message' => 'Datos de venta preparados para facturación.'
        ], 201);
    }

    private function isLoopbackRequest(Request $request): bool
    {
        return in_array($request->ip(), ['127.0.0.1', '::1'], true);
    }
}
