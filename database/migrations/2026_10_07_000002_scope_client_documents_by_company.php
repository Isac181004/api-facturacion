<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_tipo_documento_numero_documento_unique');
            $table->unique(
                ['company_id', 'tipo_documento', 'numero_documento'],
                'clients_company_document_unique'
            );
        });

        // Older installs used one global client row for documents from multiple companies.
        // Split those associations so enabling tenant scopes will not hide or cross-share history.
        foreach (DB::table('clients')->orderBy('id')->get() as $client) {
            $invoiceCompanies = DB::table('invoices')
                ->where('client_id', $client->id)
                ->whereNotNull('company_id')
                ->distinct()
                ->pluck('company_id');
            $boletaCompanies = DB::table('boletas')
                ->where('client_id', $client->id)
                ->whereNotNull('company_id')
                ->distinct()
                ->pluck('company_id');
            $companyIds = $invoiceCompanies->merge($boletaCompanies)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($companyIds->isEmpty()) {
                continue;
            }

            $ownerCompanyId = $client->company_id !== null && $companyIds->contains((int) $client->company_id)
                ? (int) $client->company_id
                : (int) $companyIds->first();

            DB::table('clients')->where('id', $client->id)->update(['company_id' => $ownerCompanyId]);

            foreach ($companyIds as $companyId) {
                if ($companyId === $ownerCompanyId) {
                    continue;
                }

                $copy = (array) $client;
                unset($copy['id']);
                $copy['company_id'] = $companyId;
                $copy['created_at'] = $client->created_at;
                $copy['updated_at'] = now();
                $newClientId = DB::table('clients')->insertGetId($copy);

                DB::table('invoices')
                    ->where('client_id', $client->id)
                    ->where('company_id', $companyId)
                    ->update(['client_id' => $newClientId]);
                DB::table('boletas')
                    ->where('client_id', $client->id)
                    ->where('company_id', $companyId)
                    ->update(['client_id' => $newClientId]);
            }
        }
    }

    public function down(): void
    {
        $hasCrossCompanyDuplicates = DB::table('clients')
            ->select('tipo_documento', 'numero_documento')
            ->groupBy('tipo_documento', 'numero_documento')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasCrossCompanyDuplicates) {
            throw new RuntimeException('Cannot restore the global client-document unique index after tenant-specific clients have been created.');
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_company_document_unique');
            $table->unique(['tipo_documento', 'numero_documento']);
        });
    }
};
