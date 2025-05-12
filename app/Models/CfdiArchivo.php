<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CfdiArchivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nombre_archivo',
        'ruta',
        'rfc_emisor',
        'rfc_receptor',
        'total',
        'fecha',
        'tipo_comprobante',
        'estatus',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
