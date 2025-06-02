<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'catalogo_pais';

    protected $primaryKey = 'clave';

    protected $fillable = [
        'clave',
        'nombre',
        'nacionalidad',
        'vigencia_desde',
        'vigencia_hasta',
    ];

    public $timestamps = true;

    public function emisores()
    {
        return $this->hasMany(Emisor::class, 'country_id');
    }
}
