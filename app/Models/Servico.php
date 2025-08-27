<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servico extends Model
{
    use HasFactory;

    protected $table = 'servicos';

    protected $fillable = [
        'codigo',
        'servico',
        'preco',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
    ];

    public function agendamentos()
    {
        return $this->hasMany(AgendarCorte::class, 'servico_id');
    }
}
