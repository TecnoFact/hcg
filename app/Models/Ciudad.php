<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    protected $table = 'ciudades';

    protected $fillable = [
        'descripcion',
        'id_estado',
        'vigencia_desde',
        'vigencia_hasta',
    ];

    public function estado()
    {
        return $this->belongsTo(State::class, 'id_estado');
    }
}
