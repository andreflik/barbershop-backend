<?php

namespace App\Services;

use App\Models\AgendarCorte;
use App\Models\Servico;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class AgendaCortesService
{
    /**
     * Retorna horários já ocupados (HH:mm) para a data (YYYY-mm-dd).
     */
    public function getBookedTimes(string $data): array
    {
        // defesa: formato de data
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return [];
        }

        return AgendarCorte::whereDate('data_agendamento', $data)
            ->orderBy('hora_agendamento')
            ->pluck('hora_agendamento')
            ->map(fn ($t) => substr((string) $t, 0, 5))
            ->toArray();
    }

    /**
     * Lista de serviços (apenas campos necessários).
     */
    public function listarServicos()
    {
        return Servico::query()
            ->orderBy('servico')
            ->get(['id', 'codigo', 'servico', 'preco']);
    }

    /**
     * Cria um agendamento com validações e lock simples.
     * - Bloqueia horário já ocupado (DomainException)
     * - Impede agendar no passado (data==hoje e hora < agora)
     * - Trata "duplicate key" caso exista índice único no banco
     */
    public function salvarAgendamento(array $input): AgendarCorte
    {
        $user = Auth::user();
        if (!$user) {
            throw new AuthorizationException('Não autenticado.');
        }

        $data = Validator::make($input, [
            'servico_id'       => 'required|exists:servicos,id',
            'data_agendamento' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora_agendamento' => 'required|date_format:H:i',
            'observacao'       => 'nullable|string|max:255',
        ])->validate();

        // Impede agendamento em horário passado (no fuso configurado)
        $tz = config('app.timezone', 'America/Sao_Paulo');
        $agDateTime = Carbon::createFromFormat('Y-m-d H:i', $data['data_agendamento'].' '.$data['hora_agendamento'], $tz);
        if ($agDateTime->lt(Carbon::now($tz))) {
            throw new \DomainException('Horário indisponível (no passado).');
        }

        try {
            return DB::transaction(function () use ($data, $user) {
                // Verifica conflito simples
                $conflito = AgendarCorte::whereDate('data_agendamento', $data['data_agendamento'])
                    ->where('hora_agendamento', $data['hora_agendamento'])
                    ->lockForUpdate()
                    ->exists();

                if ($conflito) {
                    throw new \DomainException('Esse horário já está agendado.');
                }

                $ag = AgendarCorte::create([
                    'usuario_id'       => $user->id,
                    'servico_id'       => $data['servico_id'],
                    'data_agendamento' => $data['data_agendamento'],
                    'hora_agendamento' => $data['hora_agendamento'],
                ]);

                // Carrega apenas o necessário
                return $ag->load([
                    'servico:id,servico',
                    'usuario:id,name',
                ]);
            });
        } catch (QueryException $e) {
            // Se existir UNIQUE INDEX em (data_agendamento, hora_agendamento), cai aqui em corrida
            if ((string) $e->getCode() === '23000') {
                throw new \DomainException('Esse horário já está agendado.');
            }
            throw $e;
        }
    }

    /**
     * Exclui um agendamento (somente dono ou admin).
     */
    public function excluirAgendamento(int $id): void
    {
        $user = Auth::user();
        if (!$user) {
            throw new AuthorizationException('Não autenticado.');
        }

        $ag = AgendarCorte::findOrFail($id);

        $isOwner = ((int) $ag->usuario_id === (int) $user->id);
        $isAdmin = $user->can('admin'); // Gate definido no AuthServiceProvider

        if (!$isOwner && !$isAdmin) {
            throw new AuthorizationException('Operação não permitida.');
        }

        $ag->delete();
    }
}
