<?php

namespace App\Services;

use App\Models\AgendarCorte;
use App\Models\Servico;
use Illuminate\Support\Facades\DB;

class AgendaCortesService
{
    public function getBookedTimes(string $data): array
    {
        return AgendarCorte::where('data_agendamento', $data)
            ->orderBy('hora_agendamento')
            ->pluck('hora_agendamento')
            ->toArray();
    }

    public function listarServicos()
    {
        // ajuste os campos conforme seu model (ex.: 'preco' se existir)
        return Servico::orderBy('servico')
            ->get(['id', 'codigo', 'servico', 'preco']);
    }

    /**
     * Cria um agendamento; lança DomainException se horário já estiver ocupado.
     */
    public function salvarAgendamento(array $dados, $user): AgendarCorte
    {
        return DB::transaction(function () use ($dados, $user) {
            $conflito = AgendarCorte::where('data_agendamento', $dados['data_agendamento'])
                ->where('hora_agendamento', $dados['hora_agendamento'])
                ->lockForUpdate()
                ->exists();

            if ($conflito) {
                throw new \DomainException('Esse horário já está agendado.');
            }

            $ag = AgendarCorte::create([
                'user_id'          => $user->id,
                'servico_id'       => $dados['servico_id'],
                'data_agendamento' => $dados['data_agendamento'],
                'hora_agendamento' => $dados['hora_agendamento'],
            ]);

            return $ag->load(['user', 'servico']);
        });
    }

    public function excluirAgendamento(int $id): void
    {
        $ag = AgendarCorte::findOrFail($id);
        $ag->delete();
    }
}
