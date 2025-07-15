<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emision extends Model
{
    protected $table = 'emisiones';

    protected $fillable = [
        'serie',
        'folio',
        'fecha',
        'forma_pago',
        'metodo_pago',
        'tipo_comprobante',
        'lugar_expedicion',
        'moneda',
        'emisor_rfc',
        'emisor_nombre',
        'emisor_regimen_fiscal',
        'receptor_rfc',
        'receptor_nombre',
        'receptor_domicilio',
        'receptor_regimen_fiscal',
        'receptor_uso_cfdi',
        'sub_total',
        'iva',
        'total',
        'estado',
        'user_id'
    ];

    public function detalles()
    {
        return $this->hasMany(EmisionDetalle::class, 'emision_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
