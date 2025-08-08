<?php

namespace App\Models;

use App\Models\RegimeFiscal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Emisor extends Model
{
    protected $table = 'emisores';

    protected $fillable = [
        'name',
        'rfc',
        'reason_social',
        'website',
        'phone',
        'street',
        'number_exterior',
        'number_interior',
        'colony',
        'postal_code',
        'city_id',
        'state_id',
        'country_id',
        'tax_regimen_id',
        'email',
        'file_certificate',
        'file_key',
        'password_key',
        'user_id',
        'logo',
        'color',
        'due_date',
        'date_from'
    ];

   // get logo attribute and validation if not found
   public function getLogoAttribute($value)
   {

       // Si no hay logo, retorna el avatar por defecto
       if (!$value) {
           return public_path('image/avaatar.png');
       }
       // Si hay logo, verifica si el archivo existe
       $logoPath = Storage::disk('local')->path($value);

       if (file_exists($logoPath)) {
           return $value;
       }
       // Si el archivo no existe, retorna el avatar por defecto
       return public_path('image/avaatar.png');
   }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function regimenFiscal()
    {
        return $this->belongsTo(RegimeFiscal::class, 'tax_regimen_id');
    }


}
