<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'gre_client_id_beta' => 255,
        'gre_client_secret_beta' => 255,
        'gre_client_id_produccion' => 255,
        'gre_client_secret_produccion' => 255,
        'gre_ruc_proveedor' => 11,
        'gre_usuario_sol' => 50,
        'gre_clave_sol' => 100,
    ];

    public function up(): void
    {
        foreach ($this->columns as $column => $length) {
            if (!Schema::hasColumn('companies', $column)) {
                Schema::table('companies', function (Blueprint $table) use ($column, $length) {
                    $table->string($column, $length)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        $columnsToDrop = array_keys(array_filter(
            $this->columns,
            fn ($length, $column) => Schema::hasColumn('companies', $column),
            ARRAY_FILTER_USE_BOTH
        ));

        if ($columnsToDrop !== []) {
            Schema::table('companies', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
