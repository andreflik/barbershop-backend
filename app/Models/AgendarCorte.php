<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int         $id
 * @property int         $usuario_id
 * @property int|null    $servico_id
 * @property \Carbon\CarbonInterface $data_agendamento
 * @property string      $hora_agendamento
 * @property string|null $status
 */
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

    protected $casts = [
        'data_agendamento' => 'string',
        'hora_agendamento' => 'string',
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
