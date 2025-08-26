<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $agendamento) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'BarberShop • Confirmação de agendamento',
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            replyTo: [new Address(config('mail.from.address'), config('mail.from.name'))],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.confirmacao_agendamento',
            with: [
                'nome'    => $this->agendamento->usuario->name ?? 'Cliente',
                'data'    => $this->agendamento->data_agendamento->format('d/m/Y'),
                'hora'    => $this->agendamento->hora_agendamento,
                'servico' => $this->agendamento->servico->servico ?? '',
            ],
        );
    }
}
