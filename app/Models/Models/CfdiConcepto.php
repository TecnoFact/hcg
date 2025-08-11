<?php

namespace App\Models\Models;

use App\Models\CfdiArchivo;
use App\Models\RetencionCfdi;
use App\Models\Tax;
use App\Models\TrasladoCfdi;
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
        'tax_id',
        'obj_imp_id'
    ];

    public function cfdi()
    {
        return $this->belongsTo(Cfdi::class, 'cfdi_id', 'id');
    }

    public function traslados()
    {
        return $this->hasMany(TrasladoCfdi::class, 'concepto_id');
    }

    public function retenciones()
    {
        return $this->hasMany(RetencionCfdi::class, 'concepto_id');
    }

    public function impuestos()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
