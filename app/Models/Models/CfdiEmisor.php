<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Model;

class CfdiEmisor extends Model
{
        protected $table = 'cfdi_emisores';

        protected $fillable = [
            'rfc',
            'nombre',
            'regimen_fiscal'
        ];
}
