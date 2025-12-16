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

        // Cliente
        $cliente = optional($ag->usuario)->name ?: 'Cliente';

        // 🔥 SERVIÇOS (corrigido para múltiplos)
        if ($ag->relationLoaded('servicos') && $ag->servicos->count() > 0) {
            $servico = $ag->servicos
                ->pluck('servico')
                ->filter()
                ->implode(', ');
        } else {
            // fallback seguro (caso raro)
            $servico = optional($ag->servico)->servico ?: 'Serviço';
        }

        // Data formatada
        $dataPt = !empty($ag->data_agendamento)
            ? date('d/m/Y', strtotime((string) $ag->data_agendamento))
            : '-';

        // Hora formatada
        $horaPt = !empty($ag->hora_agendamento)
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
                'servico' => $servico, // agora pode ser 1, 2 ou 3 serviços
                'dataPt'  => $dataPt,
                'horaPt'  => $horaPt,
            ]);
    }
}
