<?php

namespace App\Models;

use App\Traits\ScopesToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNote extends Model
{
    use HasFactory, ScopesToCompany;

    protected $fillable = [
        'company_id', 'branch_id', 'client_id', 'tipo_documento', 'serie', 'correlativo',
        'numero_completo', 'tipo_doc_afectado', 'num_doc_afectado', 'cod_motivo', 'des_motivo',
        'fecha_emision', 'ubl_version', 'moneda', 'forma_pago_tipo', 'forma_pago_cuotas',
        'valor_venta', 'mto_oper_gravadas', 'mto_oper_exoneradas', 'mto_oper_inafectas',
        'mto_oper_exportacion', 'mto_oper_gratuitas', 'mto_igv', 'mto_base_ivap', 'mto_ivap',
        'mto_isc', 'mto_icbper', 'total_impuestos', 'sub_total', 'mto_imp_venta', 'detalles',
        'leyendas', 'guias', 'datos_adicionales', 'xml_path', 'cdr_path', 'pdf_path',
        'estado_sunat', 'respuesta_sunat', 'codigo_hash', 'usuario_creacion',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'forma_pago_cuotas' => 'array',
        'valor_venta' => 'decimal:2',
        'mto_oper_gravadas' => 'decimal:2',
        'mto_oper_exoneradas' => 'decimal:2',
        'mto_oper_inafectas' => 'decimal:2',
        'mto_oper_exportacion' => 'decimal:2',
        'mto_oper_gratuitas' => 'decimal:2',
        'mto_igv' => 'decimal:2',
        'mto_base_ivap' => 'decimal:2',
        'mto_ivap' => 'decimal:2',
        'mto_isc' => 'decimal:2',
        'mto_icbper' => 'decimal:2',
        'total_impuestos' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'mto_imp_venta' => 'decimal:2',
        'detalles' => 'array',
        'leyendas' => 'array',
        'guias' => 'array',
        'datos_adicionales' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $note): void {
            if (empty($note->numero_completo)) {
                $note->numero_completo = $note->serie . '-' . $note->correlativo;
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
}
