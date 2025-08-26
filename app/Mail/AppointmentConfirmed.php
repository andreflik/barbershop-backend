<?php

namespace App\Mail;

use App\Models\AgendarCorte;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class AppointmentConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AgendarCorte $agendamento) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmação de Agendamento',
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
        );
    }

    public function content(): Content
    {
        $ag = $this->agendamento;

        // Converte com segurança
        $data = Carbon::parse($ag->data_agendamento)
            ->timezone(config('app.timezone'))
            ->format('d/m/Y');

        $hora = is_string($ag->hora_agendamento)
            ? substr($ag->hora_agendamento, 0, 5)
            : Carbon::parse($ag->hora_agendamento)->format('H:i');

        return new Content(
            view: 'emails.confirmacao_agendamento',
            with: [
                'nome'    => $ag->usuario->name ?? 'Cliente',
                'data'    => $data,
                'hora'    => $hora,
                'servico' => $ag->servico->servico ?? '',
            ],
        );
    }
}
