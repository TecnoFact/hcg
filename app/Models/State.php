<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $table = 'catalogo_estado';

    protected $primaryKey = 'clave';

    protected $fillable = [
        'clave',
        'nombre',
        'nacionalidad',
        'vigencia_desde',
        'vigencia_hasta'
    ];

    public function cities()
    {
        return $this->hasMany(City::class, 'state_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
