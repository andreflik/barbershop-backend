<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',            // (opcional) se você guardar o id do Google
        'google_access_token',  // será criptografado via cast
        'role',
    ];

    /** 🔒 Nunca exponha estes campos em JSON */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'google_access_token',
    ];

    /** Casts seguros */
    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'password'            => 'hashed',
            'google_access_token' => 'encrypted', // ou 'encrypted:json' se guardar objeto
        ];
    }

    /** Normaliza e-mail sempre minúsculo */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => is_string($value) ? mb_strtolower($value) : $value
        );
    }

    // Relacionamentos úteis (opcional)
    public function agendamentos()
    {
        return $this->hasMany(AgendarCorte::class, 'usuario_id');
    }
}
