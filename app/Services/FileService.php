<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FileService
{
    private const PRIVATE_DISK = 'local';
    private const PRIVATE_ROOT = 'companies';

    public function saveXml($document, string $xmlContent): string
    {
        return $this->saveDocumentFile($document, 'xml', $xmlContent);
    }

    public function saveCdr($document, string $cdrContent): string
    {
        return $this->saveDocumentFile($document, 'zip', $cdrContent);
    }

    public function savePdf($document, string $pdfContent, string $format = 'A4'): string
    {
        return $this->saveDocumentFile($document, 'pdf', $pdfContent, $format);
    }

    protected function generatePath($document, string $extension, string $format = 'A4'): string
    {
        $companyId = (int) ($document->company_id ?? 0);
        if ($companyId < 1) {
            throw new RuntimeException('No se puede guardar un archivo de documento sin empresa asociada.');
        }

        $date = Carbon::parse($document->fecha_emision);
        $dateFolder = $date->format('dmY');
        $fileName = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $document->numero_completo);
        $tipoComprobante = $this->getDocumentTypeName($document);
        $tipoArchivo = $extension === 'zip' ? 'cdr' : $extension;
        $prefix = $extension === 'zip' ? 'R-' : '';

        if ($extension === 'pdf' && $format !== 'A4') {
            $fileName .= '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $format);
        }

        return self::PRIVATE_ROOT . "/{$companyId}/{$tipoComprobante}/{$tipoArchivo}/{$dateFolder}/{$prefix}{$fileName}.{$extension}";
    }

    protected function getDocumentTypeName($document): string
    {
        if (isset($document->tipo_documento)) {
            return match($document->tipo_documento) {
                '01' => 'facturas',
                '03' => 'boletas',
                '07' => 'notas-credito',
                '08' => 'notas-debito',
                '09' => 'guias-remision',
                '20' => 'percepciones',
                '21' => 'retenciones',
                default => 'otros-comprobantes'
            };
        }

        return match(class_basename($document)) {
            'Invoice' => 'facturas',
            'Boleta' => 'boletas',
            'CreditNote' => 'notas-credito',
            'DebitNote' => 'notas-debito',
            'DispatchGuide' => 'guias-remision',
            'Percepcion' => 'percepciones',
            'Retencion' => 'retenciones',
            'DailySummary' => 'resumenes-diarios',
            default => 'otros-comprobantes'
        };
    }

    public function getXmlPath($document): ?string
    {
        return $this->absoluteDocumentPath($document, $document->xml_path);
    }

    public function getCdrPath($document): ?string
    {
        return $this->absoluteDocumentPath($document, $document->cdr_path);
    }

    public function getPdfPath($document): ?string
    {
        return $this->absoluteDocumentPath($document, $document->pdf_path);
    }

    public function downloadXml($document)
    {
        return $this->downloadDocumentFile(
            $document,
            $document->xml_path,
            $document->numero_completo . '.xml',
            ['Content-Type' => 'application/xml']
        );
    }

    public function downloadCdr($document)
    {
        return $this->downloadDocumentFile(
            $document,
            $document->cdr_path,
            'R-' . $document->numero_completo . '.zip',
            ['Content-Type' => 'application/zip']
        );
    }

    public function downloadPdf($document)
    {
        return $this->downloadDocumentFile(
            $document,
            $document->pdf_path,
            $document->numero_completo . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    /** Move a legacy public-disk document file into tenant-specific private storage. */
    public function privatizeLegacyDocumentFile($document, string $attribute): bool
    {
        $extensions = ['xml_path' => 'xml', 'cdr_path' => 'zip', 'pdf_path' => 'pdf'];
        if (!isset($extensions[$attribute])) {
            throw new RuntimeException('Campo de archivo de documento no permitido.');
        }

        $source = $document->{$attribute};
        $companyId = (int) ($document->company_id ?? 0);
        if ($this->diskForPath($source, $companyId) !== 'public' || !Storage::disk('public')->exists($source)) {
            return false;
        }

        $contents = Storage::disk('public')->get($source);
        if (!is_string($contents)) {
            return false;
        }

        $target = $this->generatePath($document, $extensions[$attribute]);
        Storage::disk(self::PRIVATE_DISK)->makeDirectory(dirname($target));
        if (!Storage::disk(self::PRIVATE_DISK)->put($target, $contents)) {
            return false;
        }

        $document->{$attribute} = $target;
        if (!$document->save()) {
            Storage::disk(self::PRIVATE_DISK)->delete($target);
            return false;
        }

        return true;
    }

    /** Compatibility helper for existing callers; private paths require an explicit tenant ID. */
    public function fileExists(?string $path, ?int $companyId = null): bool
    {
        $disk = $this->diskForPath($path, $companyId);
        return $disk !== null && Storage::disk($disk)->exists($path);
    }

    /** Compatibility helper for existing callers; use document download methods for tenant checks. */
    public function downloadFile(string $path, string $fileName, array $headers = [], ?int $companyId = null)
    {
        $disk = $this->diskForPath($path, $companyId);
        if ($disk === null || !Storage::disk($disk)->exists($path)) {
            return null;
        }

        return Storage::disk($disk)->download($path, $fileName, $headers);
    }

    public function createDirectoryStructure(): void
    {
        Storage::disk(self::PRIVATE_DISK)->makeDirectory(self::PRIVATE_ROOT);
    }

    public function ensureDirectoryExists($document, string $extension): void
    {
        $path = $this->generatePath($document, $extension);
        Storage::disk(self::PRIVATE_DISK)->makeDirectory(dirname($path));
    }

    private function saveDocumentFile($document, string $extension, string $contents, string $format = 'A4'): string
    {
        $path = $this->generatePath($document, $extension, $format);
        Storage::disk(self::PRIVATE_DISK)->makeDirectory(dirname($path));
        if (!Storage::disk(self::PRIVATE_DISK)->put($path, $contents)) {
            throw new RuntimeException('No se pudo guardar el archivo del documento en almacenamiento privado.');
        }

        return $path;
    }

    private function downloadDocumentFile($document, ?string $path, string $fileName, array $headers = [])
    {
        $companyId = (int) ($document->company_id ?? 0);
        $disk = $this->diskForPath($path, $companyId);
        if ($disk === null || !Storage::disk($disk)->exists($path)) {
            return null;
        }

        return Storage::disk($disk)->download($path, $fileName, $headers);
    }

    private function absoluteDocumentPath($document, ?string $path): ?string
    {
        $companyId = (int) ($document->company_id ?? 0);
        $disk = $this->diskForPath($path, $companyId);
        if ($disk === null || !Storage::disk($disk)->exists($path)) {
            return null;
        }

        return Storage::disk($disk)->path($path);
    }

    private function diskForPath(?string $path, ?int $companyId): ?string
    {
        if (!$path || str_contains($path, "\0") || str_starts_with($path, '/') || preg_match('#(^|/)\.\.?(/|$)#', $path)) {
            return null;
        }

        if (str_starts_with($path, self::PRIVATE_ROOT . '/')) {
            if (!$companyId || !str_starts_with($path, self::PRIVATE_ROOT . '/' . $companyId . '/')) {
                return null;
            }

            return self::PRIVATE_DISK;
        }

        // Existing installations may still have public-disk document paths. Continue serving
        // only known document directories, and only when exactly one company references that path.
        $legacyDocumentDirectories = [
            'facturas', 'boletas', 'notas-credito', 'notas-debito', 'guias-remision',
            'percepciones', 'retenciones', 'resumenes-diarios', 'otros-comprobantes',
        ];
        if (!in_array(explode('/', $path, 2)[0], $legacyDocumentDirectories, true) ||
            !$companyId || !$this->legacyPathBelongsToCompany($path, $companyId)) {
            return null;
        }

        return 'public';
    }

    private function legacyPathBelongsToCompany(string $path, int $companyId): bool
    {
        $companyIds = collect();
        foreach (['invoices', 'boletas'] as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }

            $companyIds = $companyIds->merge(
                DB::table($table)
                    ->where(function ($query) use ($path) {
                        $query->where('xml_path', $path)
                            ->orWhere('cdr_path', $path)
                            ->orWhere('pdf_path', $path);
                    })
                    ->distinct()
                    ->pluck('company_id')
            );
        }

        $companyIds = $companyIds->map(fn ($id) => (int) $id)->unique()->values();

        return $companyIds->count() === 1 && $companyIds->first() === $companyId;
    }
}
