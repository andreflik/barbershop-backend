<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgendarCorte extends Model
{
    protected $table = 'agendar_cortes';

    protected $fillable = [
        'usuario_id',
        'servico_id',
        'data_agendamento',
        'hora_agendamento',
        'status',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class, 'servico_id');
    }
}
