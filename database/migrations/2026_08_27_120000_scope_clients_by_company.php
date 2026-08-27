<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_company_document_unique');
            $table->unique(
                ['tipo_documento', 'numero_documento'],
                'clients_tipo_documento_numero_documento_unique'
            );
        });
    }
};
