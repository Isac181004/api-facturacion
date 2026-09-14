<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'clave_sol', 'certificado_pem', 'certificado_password',
            'gre_client_secret_beta', 'gre_client_secret_produccion', 'gre_clave_sol',
        ];

        DB::table('companies')->orderBy('id')->chunkById(100, function ($companies) use ($columns) {
            foreach ($companies as $company) {
                $updates = [];
                foreach ($columns as $column) {
                    if (!property_exists($company, $column) || blank($company->{$column})) {
                        continue;
                    }

                    try {
                        Crypt::decryptString($company->{$column});
                    } catch (\Throwable) {
                        $updates[$column] = Crypt::encryptString($company->{$column});
                    }
                }

                if ($updates) {
                    DB::table('companies')->where('id', $company->id)->update($updates);
                }
            }
        });
    }

    public function down(): void
    {
        // No se descifran secretos automáticamente para evitar dejarlos en texto plano.
    }
};
