<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\IndexCreditNoteRequest;
use App\Http\Requests\StoreCreditNoteRequest;
use App\Models\CreditNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreditNoteController extends CompanyDocumentController
{
    protected string $modelClass = CreditNote::class;
    protected string $singularMessage = 'Nota de crédito';
    protected string $pluralMessage = 'Notas de crédito obtenidas correctamente';
    protected string $pdfType = 'credit-note';
    protected string $apiSegment = 'credit-notes';
    protected string $sunatType = 'credit_note';

    public function index(IndexCreditNoteRequest $request): JsonResponse
    {
        return $this->paginateDocuments($request);
    }

    public function store(StoreCreditNoteRequest $request): JsonResponse
    {
        try {
            $note = $this->documentService->createCreditNote($request->validated());

            return response()->json([
                'success' => true,
                'data' => $note->load(['company', 'branch', 'client']),
                'message' => 'Nota de crédito creada correctamente',
            ], 201);
        } catch (Throwable $exception) {
            Log::error('No se pudo crear la nota de crédito.', [
                'company_id' => $request->input('company_id'),
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear la nota de crédito. Revisa los logs del servidor.',
            ], 500);
        }
    }

    public function motives(): JsonResponse
    {
        $motives = [
            ['code' => '01', 'name' => 'Anulación de la operación'],
            ['code' => '02', 'name' => 'Anulación por error en el RUC'],
            ['code' => '03', 'name' => 'Corrección por error en la descripción'],
            ['code' => '04', 'name' => 'Descuento global'],
            ['code' => '05', 'name' => 'Descuento por ítem'],
            ['code' => '06', 'name' => 'Devolución total'],
            ['code' => '07', 'name' => 'Devolución por ítem'],
            ['code' => '08', 'name' => 'Bonificación'],
            ['code' => '09', 'name' => 'Disminución en el valor'],
            ['code' => '10', 'name' => 'Otros conceptos'],
            ['code' => '11', 'name' => 'Ajustes de operaciones de exportación'],
            ['code' => '12', 'name' => 'Ajustes afectos al IVAP'],
            ['code' => '13', 'name' => 'Ajustes - montos y/o fechas de pago'],
        ];

        return response()->json([
            'success' => true,
            'data' => $motives,
            'message' => 'Motivos de nota de crédito obtenidos correctamente',
        ]);
    }
}
