<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $table = 'taxes';

    protected $fillable = [
        'name',
        'code',
        'rate',
        'is_active',
        'tipo_factor'
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getActiveTaxes()
    {
        return self::where('is_active', true)->get();
    }
}
