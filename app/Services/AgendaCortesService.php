<?php

namespace App\Services;

use App\Models\AgendarCorte;
use App\Models\Servico;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AgendaCortesService
{
    public function getBookedTimes(string $data): array
    {
        return AgendarCorte::whereDate('data_agendamento', $data)
            ->orderBy('hora_agendamento')
            ->pluck('hora_agendamento')
            ->toArray();
    }

    public function listarServicos()
    {
        return Servico::orderBy('servico')
            ->get(['id', 'codigo', 'servico', 'preco']);
    }

    public function salvarAgendamento(array $input): AgendarCorte
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new \Exception('Usuário não autenticado.');
        }

        $data = Validator::make($input, [
            'servico_id'       => 'required|exists:servicos,id',
            'data_agendamento' => 'required|date_format:Y-m-d',
            'hora_agendamento' => 'required|date_format:H:i',
        ])->validate();

        return DB::transaction(function () use ($data, $userId) {
            $conflito = AgendarCorte::whereDate('data_agendamento', $data['data_agendamento'])
                ->where('hora_agendamento', $data['hora_agendamento'])
                ->lockForUpdate()
                ->exists();

            if ($conflito) {
                throw new \DomainException('Esse horário já está agendado.');
            }

            $ag = AgendarCorte::create([
                'usuario_id'       => $userId,
                'servico_id'       => $data['servico_id'],
                'data_agendamento' => $data['data_agendamento'],
                'hora_agendamento' => $data['hora_agendamento'],
            ]);

            return $ag->load(['servico', 'usuario']);
        });
    }

    public function excluirAgendamento(int $id): void
    {
        $ag = AgendarCorte::findOrFail($id);
        $ag->delete();
    }
}
