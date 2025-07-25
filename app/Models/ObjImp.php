<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObjImp extends Model
{
    protected $table = 'objeto_imp';

    protected $fillable = [
        'descripcion',
        'clave',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getActiveObjects()
    {
        return self::where('is_active', true)->get();
    }
}
