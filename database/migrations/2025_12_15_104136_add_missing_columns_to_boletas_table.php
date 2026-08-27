<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('boletas', 'metodo_envio')) {
            Schema::table('boletas', function (Blueprint $table) {
                $table->string('metodo_envio', 20)
                    ->default('individual')
                    ->after('moneda');
            });
        }

        if (!Schema::hasColumn('boletas', 'daily_summary_id')) {
            Schema::table('boletas', function (Blueprint $table) {
                $table->unsignedBigInteger('daily_summary_id')
                    ->nullable()
                    ->after('client_id')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('boletas', 'daily_summary_id')) {
            Schema::table('boletas', function (Blueprint $table) {
                $table->dropColumn('daily_summary_id');
            });
        }

        if (Schema::hasColumn('boletas', 'metodo_envio')) {
            Schema::table('boletas', function (Blueprint $table) {
                $table->dropColumn('metodo_envio');
            });
        }
    }
};