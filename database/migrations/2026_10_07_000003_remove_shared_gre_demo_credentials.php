<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sharedDemoClientId = 'test-85e5b0ae-255c-4891-a595-0b98c65c9854';
        $sharedDemoClientSecret = 'test-Hty/M6QshYvPgItX2P0+Kw==';

        foreach (DB::table('company_configurations')->where('config_type', 'sunat_credentials')->get() as $configuration) {
            $data = json_decode($configuration->config_data, true);
            if (!is_array($data)) {
                continue;
            }

            if (($data['client_id'] ?? null) !== $sharedDemoClientId ||
                ($data['client_secret'] ?? null) !== $sharedDemoClientSecret) {
                continue;
            }

            foreach (['client_id', 'client_secret', 'ruc_proveedor', 'usuario_sol', 'clave_sol'] as $secretField) {
                $data[$secretField] = null;
            }

            DB::table('company_configurations')
                ->where('id', $configuration->id)
                ->update([
                    'config_data' => json_encode($data),
                    'description' => 'Credenciales GRE compartidas de demostración eliminadas; configura credenciales propias.',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Deliberately irreversible: shared demo credentials must never be restored.
    }
};
