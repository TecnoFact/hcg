<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CfdiArchivo extends Model
{
    const ESTATUS_SUBIDO = 'subido';
    const ESTATUS_SELLADO = 'sellado';
    const ESTATUS_TIMBRADO = 'timbrado';
    const ESTATUS_DEPOSITADO = 'depositado';
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nombre_archivo',
        'ruta',
        'uuid',
        'sello',
        'rfc_emisor',
        'rfc_receptor',
        'total',
        'fecha',
        'tipo_comprobante',
        'estatus',
        'fecha_envio_sat',
        'respuesta_sat',
        'token_sat',
        'intento_envio_sat',
        'status_upload'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
