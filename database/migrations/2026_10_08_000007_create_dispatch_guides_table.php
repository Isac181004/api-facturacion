<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_guides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('tipo_documento', 2)->default('09');
            $table->string('serie', 4);
            $table->string('correlativo', 8);
            $table->string('numero_completo', 15);
            $table->date('fecha_emision');
            $table->date('fecha_traslado');
            $table->string('version', 10)->default('2022');
            $table->string('cod_traslado', 2);
            $table->string('des_traslado', 250)->nullable();
            $table->string('mod_traslado', 2);
            $table->decimal('peso_total', 12, 3);
            $table->string('und_peso_total', 3);
            $table->unsignedInteger('num_bultos')->nullable();
            $table->json('partida');
            $table->json('llegada');
            $table->json('transportista')->nullable();
            $table->json('vehiculo')->nullable();
            $table->json('vehiculos_secundarios')->nullable();
            $table->json('indicadores')->nullable();
            $table->json('detalles');
            $table->json('documentos_relacionados')->nullable();
            $table->json('datos_adicionales')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('estado_sunat', 20)->default('PENDIENTE');
            $table->text('respuesta_sunat')->nullable();
            $table->string('ticket')->nullable();
            $table->string('codigo_hash')->nullable();
            $table->string('usuario_creacion')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'serie', 'correlativo'], 'dispatch_guides_company_series_correlative_unique');
            $table->index(['company_id', 'branch_id', 'fecha_emision'], 'dispatch_guides_tenant_date_index');
            $table->index(['company_id', 'estado_sunat'], 'dispatch_guides_tenant_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_guides');
    }
};
