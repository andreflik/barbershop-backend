<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public string $nome;
    public string $data;
    public string $hora;
    public string $servico;

    public function __construct($agendamento)
    {
        try { $agendamento->loadMissing(['servico','user']); } catch (\Throwable $e) {}

        $this->nome    = optional($agendamento->user)->name ?? 'cliente';
        $rawDate       = $agendamento->data_agendamento ?? '';
        $this->data    = (is_string($rawDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate))
            ? date('d/m/Y', strtotime($rawDate)) : (string)$rawDate;
        $rawTime       = $agendamento->hora_agendamento ?? '';
        $this->hora    = is_string($rawTime) ? substr($rawTime, 0, 5) : (string)$rawTime;
        $this->servico = optional($agendamento->servico)->servico ?? 'Serviço';
    }

    public function build()
    {
        return $this->subject('Confirmação de Agendamento - Marquinhos BarberShop')
            ->view('emails.confirmacao_agendamento')
            ->with([
                'nome'    => $this->nome,
                'data'    => $this->data,
                'hora'    => $this->hora,
                'servico' => $this->servico,
            ]);
    }
}
