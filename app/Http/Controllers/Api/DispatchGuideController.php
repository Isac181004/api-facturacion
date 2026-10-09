<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\IndexDispatchGuideRequest;
use App\Http\Requests\StoreDispatchGuideRequest;
use App\Models\DispatchGuide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchGuideController extends CompanyDocumentController
{
    protected string $modelClass = DispatchGuide::class;
    protected string $singularMessage = 'Guía de remisión';
    protected string $pluralMessage = 'Guías de remisión obtenidas correctamente';
    protected string $pdfType = 'dispatch-guide';
    protected string $apiSegment = 'dispatch-guides';
    protected string $sunatType = 'dispatch-guide';

    public function index(IndexDispatchGuideRequest $request): JsonResponse
    {
        return $this->paginateDocuments($request);
    }

    public function store(StoreDispatchGuideRequest $request): JsonResponse
    {
        try {
            $guide = $this->documentService->createDispatchGuide($request->validated());

            return response()->json([
                'success' => true,
                'data' => $guide->load(['company', 'branch', 'destinatario']),
                'message' => 'Guía de remisión creada correctamente',
            ], 201);
        } catch (Throwable $exception) {
            Log::error('No se pudo crear la guía de remisión.', [
                'company_id' => $request->input('company_id'),
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear la guía de remisión. Revisa los logs del servidor.',
            ], 500);
        }
    }

    protected function sendDocumentToSunat(Model $document): array
    {
        return $this->documentService->sendDispatchGuideToSunat($document);
    }

    public function checkStatus(int $id): JsonResponse
    {
        $guide = $this->documentQuery()->findOrFail($id);

        try {
            $result = $this->documentService->checkDispatchGuideStatus($guide);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'data' => $result,
                'message' => ($result['success'] ?? false)
                    ? 'Estado de la guía consultado correctamente.'
                    : 'No se pudo consultar el estado de la guía.',
            ], ($result['success'] ?? false) ? 200 : 422);
        } catch (Throwable $exception) {
            Log::error('No se pudo consultar el estado SUNAT de la guía.', [
                'document_id' => $guide->id,
                'company_id' => $guide->company_id,
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo consultar el estado de la guía. Revisa la configuración y los logs del servidor.',
            ], 500);
        }
    }

    public function transferReasons(): JsonResponse
    {
        $reasons = [
            ['code' => '01', 'name' => 'Venta'],
            ['code' => '02', 'name' => 'Compra'],
            ['code' => '03', 'name' => 'Venta con entrega a terceros'],
            ['code' => '04', 'name' => 'Traslado entre establecimientos de la misma empresa'],
            ['code' => '05', 'name' => 'Consignación'],
            ['code' => '06', 'name' => 'Devolución'],
            ['code' => '07', 'name' => 'Recojo de bienes transformados'],
            ['code' => '08', 'name' => 'Importación'],
            ['code' => '09', 'name' => 'Exportación'],
            ['code' => '13', 'name' => 'Otros'],
            ['code' => '14', 'name' => 'Venta sujeta a confirmación del comprador'],
            ['code' => '17', 'name' => 'Traslado de bienes para transformación'],
            ['code' => '18', 'name' => 'Traslado emisor itinerante'],
            ['code' => '19', 'name' => 'Traslado a zona primaria'],
        ];

        return response()->json([
            'success' => true,
            'data' => $reasons,
            'message' => 'Motivos de traslado obtenidos correctamente',
        ]);
    }

    public function transportModes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['code' => '01', 'name' => 'Transporte público'],
                ['code' => '02', 'name' => 'Transporte privado'],
            ],
            'message' => 'Modalidades de transporte obtenidas correctamente',
        ]);
    }
}
