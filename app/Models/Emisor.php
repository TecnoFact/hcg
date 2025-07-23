<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'regimen_fiscal_id',
        'color'
    ];

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


}
