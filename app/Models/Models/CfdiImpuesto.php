<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Model;

class CfdiImpuesto extends Model
{
    protected $table = 'cfdi_impuestos';

    protected $fillable = [
        'concepto_id',
        'tipo',
        'impuesto',
        'tipo_factor',
        'tasa_cuota',
        'importe',
    ];

    public function cfdi()
    {
        return $this->belongsTo(Cfdi::class, 'cfdi_id');
    }
}
