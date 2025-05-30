<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Model;

class Cfdi extends Model
{
    protected $table = 'cfdis';

    protected $fillable = [
        'emisor_id',
        'receptor_id',
        'serie',
        'folio',
        'fecha',
        'subtotal',
        'descuento',
        'total',
        'forma_pago',
        'metodo_pago',
        'moneda',
        'tipo_de_comprobante',
        'exportacion',
        'lugar_expedicion',
    ];
    public function emisor()
    {
        return $this->belongsTo(CfdiEmisor::class, 'emisor_id');
    }
    public function receptor()
    {
        return $this->belongsTo(CfdiReceptor::class, 'receptor_id');
    }
}
