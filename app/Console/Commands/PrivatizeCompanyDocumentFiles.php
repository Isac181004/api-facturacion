<?php

namespace App\Console\Commands;

use App\Models\Boleta;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\DispatchGuide;
use App\Models\Invoice;
use App\Services\FileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PrivatizeCompanyDocumentFiles extends Command
{
    protected $signature = 'sunat:privatize-document-files';
    protected $description = 'Move legacy XML, CDR and PDF files from public storage to per-company private storage';

    public function handle(FileService $files): int
    {
        $legacyPaths = [];
        $migrated = 0;
        $skipped = 0;

        foreach ([Invoice::class, Boleta::class, CreditNote::class, DebitNote::class, DispatchGuide::class] as $modelClass) {
            $modelClass::withoutGlobalScopes()->orderBy('id')->chunkById(100, function ($documents) use ($files, &$legacyPaths, &$migrated, &$skipped) {
                foreach ($documents as $document) {
                    foreach (['xml_path', 'cdr_path', 'pdf_path'] as $attribute) {
                        $path = $document->{$attribute};
                        if (!$path || str_starts_with($path, 'companies/')) {
                            continue;
                        }

                        $legacyPaths[$path] = true;
                        if ($files->privatizeLegacyDocumentFile($document, $attribute)) {
                            $migrated++;
                        } else {
                            $skipped++;
                            $this->warn("No se migró {$attribute} del documento {$document->id} (empresa {$document->company_id}); verifica la ruta o si el archivo existe.");
                        }
                    }
                }
            });
        }

        foreach (array_keys($legacyPaths) as $path) {
            if (!$this->isStillReferenced($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $this->info("Archivos movidos a almacenamiento privado: {$migrated}.");
        if ($skipped > 0) {
            $this->error("Archivos omitidos para revisión manual: {$skipped}. Las rutas omitidas pueden seguir siendo públicas.");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function isStillReferenced(string $path): bool
    {
        foreach (['invoices', 'boletas', 'credit_notes', 'debit_notes', 'dispatch_guides'] as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }

            $referenced = DB::table($table)
                ->where(fn ($query) => $query
                    ->where('xml_path', $path)
                    ->orWhere('cdr_path', $path)
                    ->orWhere('pdf_path', $path))
                ->exists();

            if ($referenced) {
                return true;
            }
        }

        return false;
    }
}
