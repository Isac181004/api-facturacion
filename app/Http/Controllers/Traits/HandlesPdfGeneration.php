<?php

namespace App\Http\Controllers\Traits;

use App\Services\PdfService;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

trait HandlesPdfGeneration
{
    /**
     * Generar PDF para cualquier tipo de documento.
     *
     * Para la aplicación del Salón & Spa el formato predeterminado es 50mm,
     * que corresponde al ancho imprimible seguro de una impresora térmica
     * con rollo físico de 58mm. A4/A5/80mm siguen disponibles usando ?format=.
     */
    protected function generateDocumentPdf($document, string $documentType, Request $request): JsonResponse
    {
        try {
            $format = $request->get('format', '50mm');

            $pdfService = app(PdfService::class);
            if (!$pdfService->isValidFormat($format)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato no válido. Formatos disponibles: ' . implode(', ', $pdfService->getAvailableFormats())
                ], 400);
            }

            $pdfContent = match($documentType) {
                'invoice' => $pdfService->generateInvoicePdf($document, $format),
                'boleta' => $pdfService->generateBoletaPdf($document, $format),
                'credit-note' => $pdfService->generateCreditNotePdf($document, $format),
                'debit-note' => $pdfService->generateDebitNotePdf($document, $format),
                'dispatch-guide' => $pdfService->generateDispatchGuidePdf($document, $format),
                'daily-summary' => $pdfService->generateDailySummaryPdf($document, $format),
                default => throw new \InvalidArgumentException("Tipo de documento no soportado: {$documentType}")
            };

            $fileService = app(FileService::class);
            $pdfPath = $fileService->savePdf($document, $pdfContent, $format);

            $document->update(['pdf_path' => $pdfPath]);

            return response()->json([
                'success' => true,
                'message' => "PDF generado correctamente en formato {$format}",
                'data' => [
                    'pdf_path' => $pdfPath,
                    'format' => $format,
                    'document_type' => $documentType,
                    'document_id' => $document->id
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar PDF con validación de formato.
     */
    protected function downloadDocumentPdf($document, Request $request)
    {
        try {
            $format = $request->get('format', '50mm');

            $pdfService = app(PdfService::class);
            if (!$pdfService->isValidFormat($format)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato no válido. Formatos disponibles: ' . implode(', ', $pdfService->getAvailableFormats())
                ], 400);
            }

            $fileService = app(FileService::class);
            $download = $fileService->downloadPdf($document);

            if (!$download) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF no encontrado'
                ], 404);
            }

            return $download;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
