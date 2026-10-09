<?php

namespace App\Models;

use App\Traits\ScopesToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchGuide extends Model
{
    use HasFactory, ScopesToCompany;

    protected $fillable = [
        'company_id', 'branch_id', 'client_id', 'tipo_documento', 'serie', 'correlativo',
        'numero_completo', 'fecha_emision', 'fecha_traslado', 'version', 'cod_traslado',
        'des_traslado', 'mod_traslado', 'peso_total', 'und_peso_total', 'num_bultos',
        'partida', 'llegada', 'transportista', 'vehiculo', 'vehiculos_secundarios',
        'indicadores', 'detalles', 'documentos_relacionados', 'datos_adicionales', 'observaciones',
        'xml_path', 'cdr_path', 'pdf_path', 'estado_sunat', 'respuesta_sunat', 'ticket',
        'codigo_hash', 'usuario_creacion',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_traslado' => 'date',
        'peso_total' => 'decimal:3',
        'num_bultos' => 'integer',
        'partida' => 'array',
        'llegada' => 'array',
        'transportista' => 'array',
        'vehiculo' => 'array',
        'vehiculos_secundarios' => 'array',
        'indicadores' => 'array',
        'detalles' => 'array',
        'documentos_relacionados' => 'array',
        'datos_adicionales' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $guide): void {
            if (empty($guide->numero_completo)) {
                $guide->numero_completo = $guide->serie . '-' . $guide->correlativo;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function getMotivoTrasladoNameAttribute(): string
    {
        return match ((string) $this->cod_traslado) {
            '01' => 'Venta',
            '02' => 'Compra',
            '03' => 'Venta con entrega a terceros',
            '04' => 'Traslado entre establecimientos de la misma empresa',
            '05' => 'Consignación',
            '06' => 'Devolución',
            '07' => 'Recojo de bienes transformados',
            '08' => 'Importación',
            '09' => 'Exportación',
            '13' => 'Otros',
            '14' => 'Venta sujeta a confirmación del comprador',
            '17' => 'Traslado de bienes para transformación',
            '18' => 'Traslado emisor itinerante',
            '19' => 'Traslado a zona primaria',
            default => $this->des_traslado ?: 'Motivo de traslado',
        };
    }

    public function getModalidadTrasladoNameAttribute(): string
    {
        return match ((string) $this->mod_traslado) {
            '01' => 'Transporte público',
            '02' => 'Transporte privado',
            default => 'No especificada',
        };
    }
}
