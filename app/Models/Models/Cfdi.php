<?php

namespace App\Models\Models;

use App\Models\User;
use App\Models\CfdiArchivo;
use App\Models\Models\CfdiConcepto;
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

        'path_xml',
        'user_id',
        'cfdi_archivos_id'
    ];

    public function emisor()
    {
        return $this->belongsTo(CfdiEmisor::class, 'emisor_id');
    }

    public function receptor()
    {
        return $this->belongsTo(CfdiReceptor::class, 'receptor_id');
    }

    public function conceptos()
    {
        return $this->hasMany(CfdiConcepto::class, 'cfdi_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cfdiArchivo()
    {
        return $this->belongsTo(CfdiArchivo::class, 'cfdi_archivos_id');
    }

}
