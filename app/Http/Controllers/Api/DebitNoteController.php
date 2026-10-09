<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\IndexDebitNoteRequest;
use App\Http\Requests\StoreDebitNoteRequest;
use App\Models\DebitNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class DebitNoteController extends CompanyDocumentController
{
    protected string $modelClass = DebitNote::class;
    protected string $singularMessage = 'Nota de débito';
    protected string $pluralMessage = 'Notas de débito obtenidas correctamente';
    protected string $pdfType = 'debit-note';
    protected string $apiSegment = 'debit-notes';
    protected string $sunatType = 'debit_note';

    public function index(IndexDebitNoteRequest $request): JsonResponse
    {
        return $this->paginateDocuments($request);
    }

    public function store(StoreDebitNoteRequest $request): JsonResponse
    {
        try {
            $note = $this->documentService->createDebitNote($request->validated());

            return response()->json([
                'success' => true,
                'data' => $note->load(['company', 'branch', 'client']),
                'message' => 'Nota de débito creada correctamente',
            ], 201);
        } catch (Throwable $exception) {
            Log::error('No se pudo crear la nota de débito.', [
                'company_id' => $request->input('company_id'),
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear la nota de débito. Revisa los logs del servidor.',
            ], 500);
        }
    }

    public function motives(): JsonResponse
    {
        $motives = [
            ['code' => '01', 'name' => 'Intereses por mora'],
            ['code' => '02', 'name' => 'Aumento en el valor'],
            ['code' => '03', 'name' => 'Penalidades/otros conceptos'],
            ['code' => '10', 'name' => 'Ajustes en el valor'],
            ['code' => '11', 'name' => 'Otros conceptos'],
        ];

        return response()->json([
            'success' => true,
            'data' => $motives,
            'message' => 'Motivos de nota de débito obtenidos correctamente',
        ]);
    }
}
