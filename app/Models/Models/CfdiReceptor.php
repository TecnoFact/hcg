<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Model;

class CfdiReceptor extends Model
{
    protected $table = 'cfdi_receptores';

    protected $fillable = [
        'rfc',
        'nombre',
        'uso_cfdi',
        'domicilio_fiscal',
        'regimen_fiscal'
    ];

}
