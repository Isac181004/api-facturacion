<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use LogicException;

class ConsultaDocumentoController extends Controller
{
    public function __construct(private readonly DocumentLookupService $lookupService)
    {
    }

    public function consultar(Request $request)
    {
        return $this->respond(
            (string) $request->query('tipo', ''),
            (string) $request->query('numero', '')
        );
    }

    public function consultarDni(string $numero)
    {
        return $this->respond('1', $numero);
    }

    public function consultarRuc(string $numero)
    {
        return $this->respond('6', $numero);
    }

    private function respond(string $type, string $number)
    {
        $number = preg_replace('/\D/', '', $number) ?? '';
        $validator = Validator::make(
            ['tipo' => $type, 'numero' => $number],
            [
                'tipo' => ['required', 'in:1,6'],
                'numero' => [
                    'required',
                    $type === '1' ? 'digits:8' : 'digits:11',
                ],
            ],
            [
                'tipo.in' => 'El tipo de documento debe ser DNI (1) o RUC (6).',
                'numero.digits' => $type === '1'
                    ? 'El DNI debe tener exactamente 8 dígitos.'
                    : 'El RUC debe tener exactamente 11 dígitos.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            return response()->json([
                'success' => true,
                'data' => $this->lookupService->lookup($type, $number),
            ]);
        } catch (LogicException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 503);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 502);
        }
    }
}
