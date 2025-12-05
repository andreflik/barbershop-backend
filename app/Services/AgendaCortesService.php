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
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return [];
        }

        $tz = config('app.timezone', 'America/Sao_Paulo');
        $intervaloMinutos = 30;

        $agendamentos = AgendarCorte::whereDate('data_agendamento', $data)
            ->orderBy('hora_agendamento')
            ->get(['hora_agendamento', 'slots_bloqueados']);

        $slots = [];

        foreach ($agendamentos as $ag) {
            $inicio = Carbon::createFromFormat('H:i:s', $ag->hora_agendamento, $tz);
            $qtdSlots = max(1, (int) ($ag->slots_bloqueados ?? 1));

            for ($i = 0; $i < $qtdSlots; $i++) {
                $slots[] = $inicio
                    ->copy()
                    ->addMinutes($intervaloMinutos * $i)
                    ->format('H:i');
            }
        }

        // remove duplicados e reordena
        $slots = array_values(array_unique($slots));
        sort($slots);

        return $slots;
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
            'servicos'         => 'required|array|min:1|max:3',
            'servicos.*'       => 'integer|distinct|exists:servicos,id',
            'data_agendamento' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora_agendamento' => 'required|date_format:H:i',
            'observacao'       => 'nullable|string|max:255',
        ])->validate();

        // Impede agendamento em horário passado (no fuso configurado)
        $tz = config('app.timezone', 'America/Sao_Paulo');
        $agDateTime = Carbon::createFromFormat('Y-m-d H:i', $data['data_agendamento'] . ' ' . $data['hora_agendamento'], $tz);
        if ($agDateTime->lt(Carbon::now($tz))) {
            throw new \DomainException('Horário indisponível (no passado).');
        }

        try {
            return DB::transaction(function () use ($data, $user) {
                $intervaloMinutos = 30;
                $qtdServicos = count($data['servicos']);

                // 1 serviço => 1 slot | 2 ou 3 serviços => 4 slots (2 horas)
                $slotsBloqueados = $qtdServicos >= 2 ? 4 : 1;

                $tz = config('app.timezone', 'America/Sao_Paulo');
                $inicio = Carbon::createFromFormat('H:i', $data['hora_agendamento'], $tz);
                $fim    = (clone $inicio)->addMinutes($intervaloMinutos * $slotsBloqueados);

                // Busca todos os agendamentos do dia e checa sobreposição
                $existentes = AgendarCorte::whereDate('data_agendamento', $data['data_agendamento'])
                    ->lockForUpdate()
                    ->get(['hora_agendamento', 'slots_bloqueados']);

                foreach ($existentes as $ag) {
                    $agInicio = Carbon::createFromFormat('H:i:s', $ag->hora_agendamento, $tz);
                    $agSlots  = max(1, (int) ($ag->slots_bloqueados ?? 1));
                    $agFim    = (clone $agInicio)->addMinutes($intervaloMinutos * $agSlots);

                    // intervalo [inicio, fim) sobreposto?
                    if ($agInicio < $fim && $inicio < $agFim) {
                        throw new \DomainException('Esse horário já está agendado.');
                    }
                }

                $ag = AgendarCorte::create([
                    'usuario_id'       => $user->id,
                    'servico_id'       => $data['servicos'][0] ?? null, // guarda o primeiro como “principal”
                    'data_agendamento' => $data['data_agendamento'],
                    'hora_agendamento' => $data['hora_agendamento'],
                    'slots_bloqueados' => $slotsBloqueados,
                    'observacao'       => $data['observacao'] ?? null,
                ]);

                // relaciona todos os serviços na pivot
                $ag->servicos()->sync($data['servicos']);

                return $ag->load([
                    'usuario:id,name,email',
                    'servicos:id,servico,preco',
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
