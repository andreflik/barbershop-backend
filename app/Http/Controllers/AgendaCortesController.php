<?php

namespace App\Http\Controllers;

use App\Services\AgendaCortesService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
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
     * Obtém os horários já agendados para uma data específica.
     */
    public function getBookedTimes($data): JsonResponse
    {
        $bookedTimes = $this->agendaCortesService->getBookedTimes($data);
        return response()->json(['bookedTimes' => $bookedTimes]);
    }

    /**
     * Salva um novo agendamento e envia e-mail de confirmação.
     */
    public function salvarAgendamento(Request $request): JsonResponse
    {
        try {
            // se quiser validar aqui, descomente/ajuste:
            // $validated = $request->validate([
            //     'data_agendamento' => 'required|date_format:Y-m-d',
            //     'hora_agendamento' => 'required|date_format:H:i',
            //     'servico_id'       => 'required|integer|exists:servicos,id',
            // ]);

            // garante user_id no payload, caso o service utilize:
            $payload = array_merge($request->all(), [
                'user_id' => $request->user()->id ?? null,
            ]);

            $agendamento = $this->agendaCortesService->salvarAgendamento($payload);

            // Carrega relações para montar e-mail (se existirem)
            try {
                if (method_exists($agendamento, 'loadMissing')) {
                    $agendamento->loadMissing(['servico', 'user']);
                }
            } catch (\Throwable $e) {
                Log::warning('Falha ao carregar relações do agendamento para e-mail', ['err' => $e->getMessage()]);
            }

            // ====== Envio de e-mail de confirmação ======
            $this->enviarEmailConfirmacao($request, $agendamento);

            return response()->json([
                'success'      => true,
                'message'      => 'Agendamento salvo com sucesso! Enviamos um e-mail de confirmação (se configurado).',
                'agendamento'  => $agendamento,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao salvar agendamento', ['err' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Lista serviços para o usuário.
     */
    public function listarServicos(): JsonResponse
    {
        $servicos = $this->agendaCortesService->listarServicos();
        return response()->json(['servicos' => $servicos]);
    }

    /**
     * Exclui um agendamento.
     */
    public function excluirAgendamento(int $id): JsonResponse
    {
        try {
            $this->agendaCortesService->excluirAgendamento($id);
            return response()->json(['message' => 'Agendamento excluído com sucesso.']);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir agendamento', ['err' => $e->getMessage()]);
            return response()->json(['error' => 'Erro ao excluir o agendamento.'], 400);
        }
    }

    /**
     * Dispara o e-mail de confirmação (usa Mailable se existir; senão, fallback com Mail::raw).
     */
    protected function enviarEmailConfirmacao(Request $request, $agendamento): void
    {
        try {
            $to = optional($request->user())->email
                ?? optional($agendamento->user)->email
                ?? null;

            if (!$to) {
                Log::warning('Usuário sem e-mail; não foi possível enviar confirmação.');
                return;
            }

            Mail::to($to)->send(new AppointmentConfirmed($agendamento));

            if ($admin = env('MAIL_ADMIN')) {
                Mail::to($admin)->send(new AppointmentConfirmed($agendamento));
            }
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar e-mail de confirmação de agendamento', [
                'err' => $e->getMessage()
            ]);
        }
    }

}
