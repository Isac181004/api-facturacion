<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\DispatchGuide;
use App\Services\DocumentService;
use App\Services\FileService;
use App\Services\PdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class CompanyDocumentController extends Controller
{
    protected string $modelClass;
    protected string $singularMessage;
    protected string $pluralMessage;
    protected string $pdfType;
    protected string $apiSegment;
    protected string $sunatType;

    public function __construct(
        protected DocumentService $documentService,
        protected FileService $fileService,
        protected PdfService $pdfService,
    ) {
    }

    protected function documentQuery(): Builder
    {
        $modelClass = $this->modelClass;

        return $modelClass::query()->with(['company', 'branch', 'client']);
    }

    protected function paginateDocuments(Request $request): JsonResponse
    {
        $query = $this->documentQuery();

        foreach (['company_id', 'branch_id', 'estado_sunat'] as $filter) {
            $value = $request->input($filter);
            if ($value !== null && $value !== '') {
                $query->where($filter, $value);
            }
        }

        if ($request->filled('tipo_doc_afectado') && in_array($this->modelClass, [CreditNote::class, DebitNote::class], true)) {
            $query->where('tipo_doc_afectado', $request->input('tipo_doc_afectado'));
        }

        if ($request->filled('cod_traslado') && $this->modelClass === DispatchGuide::class) {
            $query->where('cod_traslado', $request->input('cod_traslado'));
        }

        if ($request->filled('mod_traslado') && $this->modelClass === DispatchGuide::class) {
            $query->where('mod_traslado', $request->input('mod_traslado'));
        }

        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $query->whereBetween('fecha_emision', [
                $request->input('fecha_desde'),
                $request->input('fecha_hasta'),
            ]);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $documents = $query->orderByDesc('created_at')->paginate($perPage)->appends($request->query());

        return response()->json([
            'success' => true,
            'data' => $documents,
            'message' => $this->pluralMessage,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $document = $this->documentQuery()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $document,
            'message' => $this->singularMessage . ' obtenida correctamente',
        ]);
    }

    public function sendToSunat(int $id): JsonResponse
    {
        $document = $this->documentQuery()->findOrFail($id);

        if ($document->estado_sunat === 'ACEPTADO') {
            return response()->json([
                'success' => false,
                'message' => 'El documento ya fue aceptado por SUNAT y no se puede reenviar.',
            ], 409);
        }

        try {
            $result = $this->sendDocumentToSunat($document);
            $success = (bool) ($result['success'] ?? false);
            $updatedDocument = $result['document'] ?? $document->fresh();
            $async = $success && !empty($result['ticket']);

            return response()->json([
                'success' => $success,
                'data' => $updatedDocument,
                'message' => $success
                    ? ($async ? 'Documento enviado a SUNAT para procesamiento.' : 'Documento enviado correctamente a SUNAT.')
                    : 'SUNAT no aceptó el documento.',
                'ticket' => $result['ticket'] ?? null,
                'error' => $success ? null : $this->sunatErrorMessage($result['error'] ?? null),
            ], $success ? ($async ? 202 : 200) : 422);
        } catch (Throwable $exception) {
            Log::error('No se pudo enviar el documento a SUNAT.', [
                'document_type' => $this->modelClass,
                'document_id' => $document->id,
                'company_id' => $document->company_id,
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar el envío a SUNAT. Revisa la configuración de la empresa y los logs.',
            ], 500);
        }
    }

    protected function sendDocumentToSunat(Model $document): array
    {
        return $this->documentService->sendToSunat($document, $this->sunatType);
    }

    protected function sunatErrorMessage(mixed $error): ?string
    {
        if (is_string($error)) {
            return $error;
        }

        if (is_object($error) && method_exists($error, 'getMessage')) {
            return $error->getMessage();
        }

        return null;
    }

    public function generatePdf(int $id, Request $request): JsonResponse
    {
        $document = $this->documentQuery()->findOrFail($id);
        $format = (string) $request->input('format', 'A4');

        if (!$this->pdfService->isValidFormat($format)) {
            return response()->json([
                'success' => false,
                'message' => 'Formato PDF no válido.',
                'available_formats' => array_keys($this->pdfService->getAvailableFormats()),
            ], 422);
        }

        try {
            $content = match ($this->pdfType) {
                'credit-note' => $this->pdfService->generateCreditNotePdf($document, $format),
                'debit-note' => $this->pdfService->generateDebitNotePdf($document, $format),
                'dispatch-guide' => $this->pdfService->generateDispatchGuidePdf($document, $format),
                default => throw new \LogicException('Tipo de PDF no soportado.'),
            };

            $path = $this->fileService->savePdf($document, $content, $format);
            $document->forceFill(['pdf_path' => $path])->save();
            $document->loadMissing(['company', 'branch', 'client']);
            $apiPrefix = str_starts_with($request->path(), 'api/v1/external/')
                ? '/api/v1/external/'
                : '/api/v1/';
            $downloadUrl = url($apiPrefix . $this->apiSegment . '/' . $document->id . '/download-pdf');

            return response()->json([
                'success' => true,
                'message' => 'PDF de ' . lcfirst($this->singularMessage) . ' generado correctamente',
                'data' => $this->pdfResponseData($document, $path, $downloadUrl),
            ]);
        } catch (Throwable $exception) {
            Log::error('No se pudo generar el PDF del documento.', [
                'document_type' => $this->modelClass,
                'document_id' => $document->id,
                'company_id' => $document->company_id,
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo generar el PDF del documento.',
            ], 500);
        }
    }

    protected function pdfResponseData(Model $document, string $path, string $downloadUrl): array
    {
        $client = $document->client;
        $data = [
            'id' => $document->id,
            'serie' => $document->serie,
            'correlativo' => $document->correlativo,
            'numero_documento' => $document->numero_completo,
            'fecha_emision' => $document->fecha_emision?->toDateString(),
            'pdf_path' => $path,
            'pdf_url' => $downloadUrl,
            'download_url' => $downloadUrl,
            'estado_sunat' => $document->estado_sunat,
            'mto_imp_venta' => $document->mto_imp_venta ?? null,
            'moneda' => $document->moneda ?? null,
        ];

        if ($this->modelClass === DispatchGuide::class) {
            $data['fecha_traslado'] = $document->fecha_traslado?->toDateString();
            $data['modalidad_traslado'] = $document->modalidad_traslado_name;
            $data['motivo_traslado'] = $document->motivo_traslado_name;
            $data['peso_total'] = $document->peso_total;
            $data['destinatario'] = $client ? [
                'numero_documento' => $client->numero_documento,
                'razon_social' => $client->razon_social,
            ] : null;
        } else {
            $data['client'] = $client ? [
                'numero_documento' => $client->numero_documento,
                'razon_social' => $client->razon_social,
            ] : null;
        }

        return $data;
    }

    public function downloadXml(int $id): mixed
    {
        return $this->downloadDocumentFile($id, 'xml');
    }

    public function downloadCdr(int $id): mixed
    {
        return $this->downloadDocumentFile($id, 'cdr');
    }

    public function downloadPdf(int $id): mixed
    {
        return $this->downloadDocumentFile($id, 'pdf');
    }

    protected function downloadDocumentFile(int $id, string $type): mixed
    {
        $document = $this->documentQuery()->findOrFail($id);
        $download = match ($type) {
            'xml' => $this->fileService->downloadXml($document),
            'cdr' => $this->fileService->downloadCdr($document),
            'pdf' => $this->fileService->downloadPdf($document),
            default => null,
        };

        if ($download === null) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo solicitado no existe.',
            ], 404);
        }

        return $download;
    }
}
