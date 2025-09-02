<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    /** @var mixed */
    public $agendamento;

    public function __construct($agendamento)
    {
        $this->agendamento = $agendamento;
    }

    public function build()
    {
        $ag = $this->agendamento;

        // Protege contra nulos/strings e evita ->format() em string
        $cliente = optional($ag->usuario)->name ?: 'Cliente';
        $servico = optional($ag->servico)->servico ?: 'Serviço';
        $dataPt  = !empty($ag->data_agendamento)
            ? date('d/m/Y', strtotime((string) $ag->data_agendamento))
            : '-';
        $horaPt  = !empty($ag->hora_agendamento)
            ? substr((string) $ag->hora_agendamento, 0, 5)
            : '-';

        // Assunto amigável
        $subject = "Confirmação de Agendamento — {$dataPt} às {$horaPt}";

        return $this->from(
            config('mail.from.address'),
            config('mail.from.name')
        )
            ->subject($subject)
            ->view('emails.confirmacao_agendamento')
            ->with([
                'cliente' => $cliente,
                'servico' => $servico,
                'dataPt'  => $dataPt,
                'horaPt'  => $horaPt,
            ]);
    }
}
