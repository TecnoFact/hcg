<?php

namespace App\Models\Models;

use App\Models\CfdiArchivo;
use Illuminate\Database\Eloquent\Model;

class CfdiConcepto extends Model
{
    protected $table = 'cfdi_conceptos';

    protected $fillable = [
        'cfdi_id',
        'clave_prod_serv',
        'no_identificacion',
        'cantidad',
        'clave_unidad',
        'unidad',
        'descripcion',
        'valor_unitario',
        'tipo_impuesto',
        'importe',
        'descuento',
    ];

    public function cfdi()
    {
        return $this->belongsTo(CfdiArchivo::class, 'cfdi_id', 'id');
    }
}
