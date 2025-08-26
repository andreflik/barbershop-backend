<?php

namespace App\Services;

use App\Repositories\AgendaCortesRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Services\EmailService;

class AgendaCortesService
{
    protected AgendaCortesRepository $agendaCortesRepository;
    protected EmailService $emailService;

    public function __construct(AgendaCortesRepository $agendaCortesRepository, EmailService $emailService)
    {
        $this->agendaCortesRepository = $agendaCortesRepository;
        $this->emailService = $emailService;
    }

    /**
     * Obtém horários agendados para uma data específica.
     */
    public function getBookedTimes(string $data)
    {
        return $this->agendaCortesRepository->getBookedTimes($data);
    }

    /**
     * Salva um novo agendamento, garantindo que o usuário esteja autenticado e os dados sejam válidos.
     */
    public function salvarAgendamento(array $dados, $user)
    {
        // Conflito?
        $existe = AgendarCorte::where('data_agendamento', $dados['data_agendamento'])
            ->where('hora_agendamento', $dados['hora_agendamento'])
            ->exists();

        if ($existe) {
            throw new \DomainException('Esse horário já está agendado.');
        }

        $ag = AgendarCorte::create([
            'user_id'          => $user->id,
            'servico_id'       => $dados['servico_id'],
            'data_agendamento' => $dados['data_agendamento'],
            'hora_agendamento' => $dados['hora_agendamento'],
        ]);

        return $ag->load('servico','user');
    }


    public function listarServicos()
    {
        return $this->agendaCortesRepository->listarServicos();
    }

    public function excluirAgendamento(int $id)
    {
        $agendamento = $this->agendaCortesRepository->buscarPorId($id);

        if (!$agendamento) {
            throw new \Exception('Agendamento não encontrado.');
        }

        $user = Auth::user();

        if ($agendamento->usuario_id !== $user->id) {
            throw new \Exception('Ação não permitida.');
        }

        $this->emailService->enviarCancelamentoAgendamento(
            $user->name,
            $user->email,
            $agendamento->data_agendamento,
            $agendamento->hora_agendamento,
            $agendamento->servico->servico ?? 'Não informado'
        );

        $this->agendaCortesRepository->excluir($agendamento);
    }


}
