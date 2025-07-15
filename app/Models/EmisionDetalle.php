<?php

namespace App\Models;

use App\Models\Emision;
use Illuminate\Database\Eloquent\Model;

class EmisionDetalle extends Model
{
    protected $table = 'emisiones_detalle';

    protected $fillable = [
        'emision_id',
        'clave_prod_serv',
        'numero_identificacion',
        'cantidad',
        'clave_unidad',
        'unidad',
        'valor_unitario',
        'descripcion',
        'tipo_impuesto',
        'importe'
    ];

    public function emision()
    {
        return $this->belongsTo(Emision::class, 'emision_id');
    }
}
