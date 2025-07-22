<?php

namespace App\Models;

use App\Models\Models\CfdiConcepto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CfdiArchivo extends Model
{
    const ESTATUS_SUBIDO = 'subido';
    const ESTATUS_SELLADO = 'sellado';
    const ESTATUS_TIMBRADO = 'timbrado';
    const ESTATUS_DEPOSITADO = 'depositado';
    use HasFactory;

    protected $table = 'cfdi_archivos';

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
        'status_upload',
        'pdf_path',

        'serie',
        'folio',
        'forma_pago',
        'metodo_pago',
        'lugar_expedicion',
        'moneda',
        'emisor_nombre',
        'emisor_regimen_fiscal',
        'receptor_nombre',
        'receptor_domicilio',
        'receptor_regimen_fiscal',
        'receptor_uso_cfdi',
        'sub_total',
        'iva',

        'path_xml'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conceptos()
    {
        return $this->hasMany(CfdiConcepto::class, 'cfdi_id', 'id');
    }
}
