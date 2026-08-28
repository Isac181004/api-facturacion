<?php

namespace App\Services;

class PdfTemplateService
{
    /**
     * Available PDF formats
     */
    public const FORMATS = [
        'A4' => 'A4 (210x297mm)',
        'a4' => 'A4 (210x297mm)',
        'A5' => 'A5 (148x210mm)',
        'a5' => 'A5 (148x210mm)',
        '80mm' => '80mm Ticket (80x200mm)',
        '50mm' => 'Impresora térmica 58mm (50mm imprimibles)',
        'ticket' => 'Ticket térmico 58mm', // Legacy: usa zona segura de 50mm
    ];

    /**
     * Document types mapping
     */
    public const DOCUMENT_TYPES = [
        'invoice' => 'invoice',
        'boleta' => 'boleta',
        'credit-note' => 'credit-note',
        'debit-note' => 'debit-note',
        'dispatch-guide' => 'dispatch-guide',
        'daily-summary' => 'daily-summary',
        'retention' => 'retention'
    ];

    /**
     * Get optimized template path
     */
    public function getTemplatePath(string $documentType, string $format, bool $useOptimized = true): string
    {
        $normalizedFormat = $this->normalizeFormat($format);

        if ($this->templateExists($documentType, $normalizedFormat)) {
            return "pdf.{$normalizedFormat}.{$documentType}";
        }

        if ($this->templateExists($documentType, $format)) {
            return "pdf.{$format}.{$documentType}";
        }

        if ($this->templateExists($documentType, 'a4')) {
            return "pdf.a4.{$documentType}";
        }

        return "pdf.a4.invoice";
    }

    public function templateExists(string $documentType, string $format): bool
    {
        $templatePath = resource_path("views/pdf/{$format}/{$documentType}.blade.php");
        return file_exists($templatePath);
    }

    public function optimizedTemplateExists(string $documentType, string $format): bool
    {
        return $this->templateExists($documentType, $format);
    }

    public function normalizeFormat(string $format): string
    {
        $formatMap = [
            'A4' => 'a4',
            'a4' => 'a4',
            'A5' => 'a5',
            'a5' => 'a5',
            '80mm' => '80mm',
            '50mm' => '50mm',
            'ticket' => '50mm',
        ];

        return $formatMap[$format] ?? 'a4';
    }

    public function getAvailableFormats(): array
    {
        return self::FORMATS;
    }

    public function getSupportedDocumentTypes(): array
    {
        return self::DOCUMENT_TYPES;
    }

    public function getTemplateVariables(string $documentType): array
    {
        $baseVariables = [
            'company',
            'client',
            'document',
            'detalles',
            'fecha_emision',
            'tipo_documento_nombre',
            'leyendas',
            'qr_data',
            'hash_cdr'
        ];

        $specificVariables = [
            'credit-note' => ['documento_afectado', 'motivo'],
            'debit-note' => ['documento_afectado', 'motivo'],
            'dispatch-guide' => ['origen', 'destino', 'transportista'],
            'daily-summary' => ['boletas_incluidas', 'fecha_referencia'],
            'retention' => ['documentos_retenidos', 'tasa_retencion']
        ];

        return array_merge(
            $baseVariables,
            $specificVariables[$documentType] ?? []
        );
    }

    public function validateTemplateData(string $documentType, array $data): array
    {
        $requiredVars = ['company', 'client', 'document', 'detalles'];
        $missing = [];

        foreach ($requiredVars as $var) {
            if (!isset($data[$var]) || empty($data[$var])) {
                $missing[] = $var;
            }
        }

        return $missing;
    }

    public function getCssHelpers(string $format): array
    {
        $normalizedFormat = $this->normalizeFormat($format);
        $isTicket = in_array($normalizedFormat, ['50mm', '80mm'], true);

        return [
            'format' => $normalizedFormat,
            'isTicket' => $isTicket,
            'isA4' => $normalizedFormat === 'a4',
            'containerClass' => $isTicket ? 'ticket-container' : 'page-container',
            'fontSize' => $isTicket ? '7px' : '12px'
        ];
    }
}
