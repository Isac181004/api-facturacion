<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ConsultaDocumentoController extends Controller
{
    public function consultar(Request $request)
    {
        $tipo = (string) $request->query('tipo', '');
        $numero = preg_replace('/\D/', '', (string) $request->query('numero', ''));

        /*
        |--------------------------------------------------------------------------
        | VALIDAR TIPO
        |--------------------------------------------------------------------------
        | 1 = DNI
        | 6 = RUC
        */
        if (!in_array($tipo, ['1', '6'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de documento inválido.'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR NÚMERO
        |--------------------------------------------------------------------------
        */
        if ($tipo === '1' && !preg_match('/^\d{8}$/', $numero)) {
            return response()->json([
                'success' => false,
                'message' => 'El DNI debe tener exactamente 8 dígitos.'
            ], 422);
        }

        if ($tipo === '6' && !preg_match('/^\d{11}$/', $numero)) {
            return response()->json([
                'success' => false,
                'message' => 'El RUC debe tener exactamente 11 dígitos.'
            ], 422);
        }

        $apiKey = config('services.apiinti.key');

        $baseUrl = rtrim(
            config(
                'services.apiinti.base_url',
                'https://app.apiinti.dev/api/v1'
            ),
            '/'
        );

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'La API Key de ApiInti no está configurada.'
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | CACHE 24 HORAS
        |--------------------------------------------------------------------------
        */
        $cacheKey = "consulta_documento:{$tipo}:{$numero}";

        try {

            $datos = Cache::remember(
                $cacheKey,
                now()->addHours(24),
                function () use (
                    $tipo,
                    $numero,
                    $apiKey,
                    $baseUrl
                ) {

                    $endpoint = $tipo === '1'
                        ? "{$baseUrl}/dni/{$numero}"
                        : "{$baseUrl}/ruc/{$numero}";

                    $response = Http::withToken($apiKey)
                        ->acceptJson()
                        ->timeout(30)
                        ->get($endpoint);

                    if (!$response->successful()) {

                        $mensaje =
                            $response->json('error.message')
                            ?? $response->json('message')
                            ?? 'No se pudo consultar el documento.';

                        throw new \RuntimeException($mensaje);
                    }

                    $json = $response->json();

                    if (
                        isset($json['success']) &&
                        $json['success'] === false
                    ) {
                        throw new \RuntimeException(
                            $json['error']['message']
                            ?? $json['message']
                            ?? 'El proveedor rechazó la consulta.'
                        );
                    }

                    $data = $json['data'] ?? [];

                    if (!is_array($data) || empty($data)) {
                        throw new \RuntimeException(
                            'No se encontraron datos para el documento.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | DNI
                    |--------------------------------------------------------------------------
                    */
                    if ($tipo === '1') {

                        $nombres = strtoupper(trim(
                            $data['nombres'] ?? ''
                        ));

                        $apellidoPaterno = strtoupper(trim(
                            $data['apellidoPaterno']
                            ?? $data['apellido_paterno']
                            ?? ''
                        ));

                        $apellidoMaterno = strtoupper(trim(
                            $data['apellidoMaterno']
                            ?? $data['apellido_materno']
                            ?? ''
                        ));

                        $nombreCompleto = strtoupper(trim(
                            $data['nombreCompleto']
                            ?? $data['nombre_completo']
                            ?? implode(' ', array_filter([
                                $nombres,
                                $apellidoPaterno,
                                $apellidoMaterno
                            ]))
                        ));

                        return [
                            'tipo' => '1',
                            'numero_documento' => $numero,
                            'razon_social' => $nombreCompleto,
                            'nombres' => $nombres,
                            'apellido_paterno' => $apellidoPaterno,
                            'apellido_materno' => $apellidoMaterno,
                            'direccion' => '',
                            'distrito' => '',
                            'provincia' => '',
                            'departamento' => ''
                        ];
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | RUC
                    |--------------------------------------------------------------------------
                    */

                    return [
                        'tipo' => '6',

                        'numero_documento' =>
                            $data['ruc'] ?? $numero,

                        'razon_social' => strtoupper(trim(
                            $data['razonSocial']
                            ?? $data['razon_social']
                            ?? $data['nombre']
                            ?? ''
                        )),

                        'direccion' => strtoupper(trim(
                            $data['direccion']
                            ?? $data['domicilioFiscal']
                            ?? $data['domicilio_fiscal']
                            ?? ''
                        )),

                        'distrito' => strtoupper(trim(
                            $data['distrito'] ?? ''
                        )),

                        'provincia' => strtoupper(trim(
                            $data['provincia'] ?? ''
                        )),

                        'departamento' => strtoupper(trim(
                            $data['departamento'] ?? ''
                        )),

                        'estado' =>
                            $data['estado']
                            ?? $data['estadoContribuyente']
                            ?? null,

                        'condicion' =>
                            $data['condicion']
                            ?? $data['condicionDomicilio']
                            ?? null
                    ];
                }
            );

            return response()->json([
                'success' => true,
                'data' => $datos
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 502);
        }
    }
}