<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegimeFiscal extends Model
{
    protected $table = 'catalogo_regimen_fiscal';

    protected $primaryKey = 'clave';

    protected $fillable = [
        'clave',
        'descripcion',
        'persona_fisica',
        'persona_moral',
        'vigencia_desde',
        'vigencia_hasta',
    ];

    public $timestamps = false;

    public function emisores()
    {
        return $this->hasMany(Emisor::class, 'regimen_fiscal_id');
    }
}
