<?php

namespace App\Http\Controllers;

use App\Services\AgendaCortesService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentConfirmed;

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
    public function getBookedTimes($data): JsonResponse
    {
        $bookedTimes = $this->agendaCortesService->getBookedTimes($data);
        return response()->json(['bookedTimes' => $bookedTimes]);
    }

    /**
     * Lista serviços para o agendamento (público/autenticado).
     */
    public function listarServicos(): JsonResponse
    {
        $servicos = $this->agendaCortesService->listarServicos();
        return response()->json(['servicos' => $servicos]);
    }

    /**
     * Cria um novo agendamento.
     */
    public function salvarAgendamento(Request $request): JsonResponse
    {
        // 1) Validação amigável (422 em caso de erro)
        $v = Validator::make($request->all(), [
            'data_agendamento' => ['required', 'date_format:Y-m-d'],
            'hora_agendamento' => ['required', 'date_format:H:i'],
            'servico_id'       => ['required', 'integer', 'exists:servicos,id'],
        ], [
            'data_agendamento.required' => 'Selecione a data.',
            'data_agendamento.date_format' => 'Data inválida (use Y-m-d).',
            'hora_agendamento.required' => 'Selecione o horário.',
            'hora_agendamento.date_format' => 'Horário inválido (use HH:mm).',
            'servico_id.required' => 'Selecione o serviço.',
            'servico_id.exists' => 'Serviço inválido.',
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Erro de validação.',
                'errors'  => $v->errors(),
            ], 422);
        }

        try {
            if ($request->user()?->email) {
                Mail::to($request->user()->email)->send(new AppointmentConfirmed($agendamento));
            }
            if (env('MAIL_ADMIN')) {
                Mail::to(env('MAIL_ADMIN'))->send(new AppointmentConfirmed($agendamento));
            }
        } catch (\Throwable $mailErr) {
                Log::error('Falha ao enviar e-mail', [
                'msg' => $mailErr->getMessage(),
                'trace' => $mailErr->getTraceAsString(),
            ]);
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
        } catch (\Throwable $e) {
            Log::error('Erro ao excluir agendamento', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao excluir o agendamento.'], 400);
        }
    }
}
