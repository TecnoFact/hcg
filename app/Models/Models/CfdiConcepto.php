<?php

namespace App\Models\Models;

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
        'importe',
        'descuento',
    ];

    public function cfdi()
    {
        return $this->belongsTo(Cfdi::class, 'cfdi_id');
    }
}
