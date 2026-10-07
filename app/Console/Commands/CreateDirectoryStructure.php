<?php

namespace App\Console\Commands;

use App\Services\FileService;
use Illuminate\Console\Command;

class CreateDirectoryStructure extends Command
{
    protected $signature = 'storage:create-structure';

    protected $description = 'Preparar almacenamiento privado de comprobantes por empresa y fecha';

    public function handle()
    {
        $this->info('Preparando almacenamiento privado para comprobantes...');

        $fileService = app(FileService::class);
        $fileService->createDirectoryStructure();

        $this->info('✓ Directorio privado de empresas preparado.');
        $this->info('Los archivos se guardan automáticamente por empresa, tipo de documento y fecha.');
        $this->line('Ejemplo: companies/{company_id}/facturas/xml/{ddmmaaaa}/F001-000001.xml');
        
        return Command::SUCCESS;
    }
}