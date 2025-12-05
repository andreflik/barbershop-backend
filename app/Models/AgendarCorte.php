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
        'slots_bloqueados',
        'status',
        'observacao',
    ];

    protected $casts = [
        'data_agendamento' => 'datetime:Y-m-d',
        'hora_agendamento' => 'string',
        'slots_bloqueados' => 'integer',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class, 'servico_id');
    }

    public function servicos()
    {
        return $this->belongsToMany(
            Servico::class,
            'agendamento_servico',
            'agendar_corte_id',
            'servico_id'
        );
    }
}
