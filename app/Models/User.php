<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Log;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_picture', // Newly added field
    ];

    /**
     * The attributes that should be hidden for arrays and JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getFilamentAvatarUrl(): ?string
    {
        // O cualquier lógica que tengas para la URL
        if ($this->profile_picture) {
            Log::info('User profile picture URL: ' . Storage::disk('public')->exists($this->profile_picture));
        }

        return $this->profile_picture
            ? Storage::disk('public')->url($this->profile_picture)
            : null; // o un avatar por defecto
    }
}
