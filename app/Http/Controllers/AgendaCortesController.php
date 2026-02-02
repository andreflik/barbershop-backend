<?php

namespace App\Http\Controllers;

use App\Services\AgendaCortesService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentConfirmed;
use App\Models\AgendarCorte;
use App\Models\BlockedDate;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use App\Services\BlockedPeriodService;

class AgendaCortesController extends Controller
{
    protected AgendaCortesService $agendaCortesService;
    protected BlockedPeriodService $blockedPeriodService;

    public function __construct(AgendaCortesService $agendaCortesService, BlockedPeriodService $blockedPeriodService)
    {
        $this->agendaCortesService = $agendaCortesService;
        $this->blockedPeriodService = $blockedPeriodService;
    }

    public function getBookedTimes(string $data): JsonResponse
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return response()->json(['bookedTimes' => []], 422);
        }

        if ($this->blockedPeriodService->isDayBlocked($data)) {
            return response()->json([
                'bookedTimes' => ['FULL_DAY']
            ]);
        }

        $tz = config('app.timezone', 'America/Sao_Paulo');
        $intervaloMinutos = 30;

        $agendamentos = AgendarCorte::whereDate('data_agendamento', $data)
            ->orderBy('hora_agendamento')
            ->get(['hora_agendamento', 'slots_bloqueados']);

        $slots = [];

        foreach ($agendamentos as $ag) {
            $inicio = Carbon::parse($ag->hora_agendamento, $tz);

            $qtdSlots = max(1, (int) ($ag->slots_bloqueados ?? 1));

            for ($i = 0; $i < $qtdSlots; $i++) {
                $slots[] = $inicio
                    ->copy()
                    ->addMinutes($intervaloMinutos * $i)
                    ->format('H:i');
            }
        }

        $bloqueados = $this->blockedPeriodService->getBlockedTimes($data);

        $final = array_values(array_unique(array_merge($slots, $bloqueados)));

        return response()->json([
            'bookedTimes' => $final
        ]);
    }

    public function listarServicos(): JsonResponse
    {
        $servicos = $this->agendaCortesService->listarServicos();

        $safe = collect($servicos)->map(function ($s) {
            $arr = is_array($s) ? $s : $s->toArray();
            return [
                'id'               => $arr['id'] ?? null,
                'nome'             => $arr['nome'] ?? ($arr['servico'] ?? null),
                'duracao_minutos'  => $arr['duracao_minutos'] ?? null,
                'preco'            => $arr['preco'] ?? null,
            ];
        })->values();

        return response()->json(['servicos' => $safe]);
    }

    public function salvarAgendamento(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data_agendamento' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_agendamento' => ['required', 'date_format:H:i'],
            'servicos'         => ['required', 'array', 'min:1', 'max:3'],
            'servicos.*'       => ['integer', 'distinct', 'exists:servicos,id'],
            'observacao'       => ['nullable', 'string', 'max:255'],
        ]);

        $data = $validated['data_agendamento'];
        $hora = $validated['hora_agendamento'];

        $slots = match (count($validated['servicos'])) {
            1 => 1,
            2 => 3,
            default => 4,
        };

        if (! $this->blockedPeriodService->canSchedule(
            $data,
            $hora,
            $slots
        )) {
            return response()->json([
                'message' => 'Horário indisponível para agendamento.'
            ], 422);
        }

        try {

            $agendamento = $this->agendaCortesService->salvarAgendamento($validated);

            try {
                $user = $request->user();
                if ($user && $user->email) {
                    Mail::to($user->email)->send(new AppointmentConfirmed($agendamento));
                }
                if ($admin = env('MAIL_ADMIN')) {
                    Mail::to($admin)->send(new AppointmentConfirmed($agendamento));
                }
            } catch (\Throwable $mailErr) {
                Log::error('Falha ao enviar e-mail de confirmação.');
            }

            return response()->json([
                'success'      => true,
                'message'      => 'Agendamento salvo com sucesso!',
                'agendamento'  => $this->mapAgendamentoSafe($agendamento),
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Operação não permitida.'], 403);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 400);
        }
    }

    public function excluirAgendamento(int $id): JsonResponse
    {
        try {
            $this->agendaCortesService->excluirAgendamento($id);
            return response()->json(['message' => 'Agendamento excluído com sucesso.']);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Operação não permitida.'], 403);
        } catch (\Throwable $e) {
            Log::error('Erro ao excluir agendamento.');
            return response()->json(['message' => 'Erro ao excluir o agendamento.'], 400);
        }
    }

    private function mapAgendamentoSafe($ag): array
    {
        $get = function ($obj, $key, $default = null) {
            if (is_array($obj)) return $obj[$key] ?? $default;
            return $obj->{$key} ?? $default;
        };

        $serv = $get($ag, 'servico');
        $servId = $get($ag, 'servico_id');
        $servNome = null;

        if (is_array($serv)) {
            $servNome = $serv['nome'] ?? ($serv['servico'] ?? null);
            $servId   = $serv['id'] ?? $servId;
        } elseif (is_object($serv)) {
            $servNome = $serv->nome ?? ($serv->servico ?? null);
            $servId   = $serv->id ?? $servId;
        }

        return [
            'id'               => $get($ag, 'id'),
            'data_agendamento' => $get($ag, 'data_agendamento'),
            'hora_agendamento' => $get($ag, 'hora_agendamento'),
            'servico'          => [
                'id'   => $servId,
                'nome' => $servNome,
            ],
        ];
    }

    public function blockedDates()
    {
        return response()->json(
            BlockedDate::select('data', 'motivo')->get()
        );
    }
}
