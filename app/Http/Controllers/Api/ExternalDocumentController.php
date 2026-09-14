<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\Boleta\StoreBoletaRequest;
use App\Models\Invoice;
use App\Models\Boleta;
use App\Services\DocumentService;

class ExternalDocumentController extends Controller
{
    public function __construct(private DocumentService $documents)
    {
    }

    public function storeInvoice(StoreInvoiceRequest $request)
    {
        $invoice = $this->documents->createInvoice($request->validated());

        return response()->json(['success' => true, 'data' => $invoice], 201);
    }

    public function sendInvoice(int $id)
    {
        $company = request()->attributes->get('integration_company');
        $invoice = Invoice::with(['company', 'branch', 'client'])
            ->where('company_id', $company->id)->findOrFail($id);

        return $this->send($invoice, 'invoice');
    }

    public function storeBoleta(StoreBoletaRequest $request)
    {
        $boleta = $this->documents->createBoleta($request->validated());

        return response()->json(['success' => true, 'data' => $boleta], 201);
    }

    public function sendBoleta(int $id)
    {
        $company = request()->attributes->get('integration_company');
        $boleta = Boleta::with(['company', 'branch', 'client'])
            ->where('company_id', $company->id)->findOrFail($id);

        return $this->send($boleta, 'boleta');
    }

    public function show(string $type, int $id)
    {
        $company = request()->attributes->get('integration_company');
        $model = match ($type) {
            'invoice' => Invoice::class,
            'boleta' => Boleta::class,
            default => abort(404),
        };

        $document = $model::where('company_id', $company->id)->findOrFail($id);

        return response()->json(['success' => true, 'data' => $document]);
    }

    private function send($document, string $type)
    {
        if ($document->estado_sunat === 'ACEPTADO') {
            return response()->json(['success' => false, 'message' => 'El comprobante ya fue aceptado por SUNAT.'], 409);
        }

        $result = $this->documents->sendToSunat($document, $type);
        $status = $result['success'] ? 200 : 422;

        return response()->json([
            'success' => $result['success'],
            'data' => $result['document']->fresh(),
            'message' => $result['success'] ? 'Comprobante aceptado por SUNAT.' : 'No se pudo completar el envío a SUNAT.',
        ], $status);
    }
}
