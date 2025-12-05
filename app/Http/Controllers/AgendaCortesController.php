<?php

namespace App\Http\Controllers;

use App\Services\AgendaCortesService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentConfirmed;
use Illuminate\Auth\Access\AuthorizationException;

class AgendaCortesController extends Controller
{
    protected AgendaCortesService $agendaCortesService;

    public function __construct(AgendaCortesService $agendaCortesService)
    {
        $this->agendaCortesService = $agendaCortesService;
    }

    /**
     * Horários já agendados para a data.
     */
    public function getBookedTimes(string $data): JsonResponse
    {
        // valida data (YYYY-mm-dd) rapidamente
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return response()->json(['message' => 'Data inválida.'], 422);
        }

        $bookedTimes = $this->agendaCortesService->getBookedTimes($data);
        return response()->json(['bookedTimes' => $bookedTimes]);
    }

    /**
     * Lista serviços para o agendamento (público/autenticado).
     * Retorna apenas campos necessários.
     */
    public function listarServicos(): JsonResponse
    {
        $servicos = $this->agendaCortesService->listarServicos();

        // normaliza: id, nome (ou servico), duracao_minutos, preco
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

    /**
     * Cria um novo agendamento.
     */
    public function salvarAgendamento(Request $request): JsonResponse
    {
        // validação
        $validated = $request->validate([
            'data_agendamento' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_agendamento' => ['required', 'date_format:H:i'],
            'servicos'         => ['required', 'array', 'min:1', 'max:3'],
            'servicos.*'       => ['integer', 'distinct', 'exists:servicos,id'],
            'observacao'       => ['nullable', 'string', 'max:255'],
        ]);

        try {

            $agendamento = $this->agendaCortesService->salvarAgendamento($validated);

            // dispara e-mails (best-effort)
            try {
                $user = $request->user();
                if ($user && $user->email) {
                    Mail::to($user->email)->send(new AppointmentConfirmed($agendamento));
                }
                if ($admin = env('MAIL_ADMIN')) {
                    Mail::to($admin)->send(new AppointmentConfirmed($agendamento));
                }
            } catch (\Throwable $mailErr) {
                // log discreto
                Log::error('Falha ao enviar e-mail de confirmação.');
            }

            // resposta segura/minimalista
            return response()->json([
                'success'      => true,
                'message'      => 'Agendamento salvo com sucesso!',
                'agendamento'  => $this->mapAgendamentoSafe($agendamento),
            ], 201);
        } catch (\DomainException $e) {
            // conflito de horário, etc.
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

    /**
     * Exclui um agendamento.
     */
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
        // aceita Model ou array
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
}
