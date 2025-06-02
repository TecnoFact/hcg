<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'catalogo_pais';

    protected $primaryKey = 'clave';

    protected $fillable = [
        'nombre',
        'nacionalidad',
        'vigencia_desde',
        'vigencia_hasta'
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }


}
