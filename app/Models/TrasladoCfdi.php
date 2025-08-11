<?php

namespace App\Models;

use App\Models\Models\Cfdi;
use App\Models\Models\CfdiConcepto;
use Illuminate\Database\Eloquent\Model;

class TrasladoCfdi extends Model
{
    protected $table = 'traslados_cfdi';

    protected $fillable = [
        'concepto_id',
        'base',
        'impuesto',
        'tipo_factor',
        'tasa',
        'importe',
    ];

    public function cfdi()
    {
        return $this->belongsTo(CfdiConcepto::class);
    }
}
