<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'ciudades';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_estado',
        'descripcion'
    ];

    public function state()
    {
        return $this->belongsTo(State::class, 'id_estado');
    }


}
