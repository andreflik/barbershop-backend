<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class EmailService
{
    /**
     * Envia confirmação (best-effort). Não lança para o chamador.
     */
    public function enviarConfirmacaoAgendamento(string $nome, string $email, string $data, string $hora, string $servico): bool
    {
        try {
            $dados = [
                'nome'    => $nome,
                'data'    => Carbon::createFromFormat('Y-m-d', $data)->format('d/m/Y'),
                'hora'    => Carbon::createFromFormat('H:i',   $hora)->format('H:i'),
                'servico' => $servico,
            ];

            Mail::send('emails.confirmacao_agendamento', $dados, function ($message) use ($email, $nome) {
                $message->to($email, $nome)
                    ->subject('Confirmação de Agendamento de Corte');

                if ($copy = env('MAIL_COPY_ADDRESS')) {
                    $message->bcc($copy, env('MAIL_COPY_NAME', 'Admin'));
                }

                if ($admin = env('MAIL_ADMIN')) {
                    $message->bcc($admin, 'Admin');
                }
            });

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Envia cancelamento (best-effort). Não lança para o chamador.
     */
    public function enviarCancelamentoAgendamento(string $nome, string $email, string $data, string $hora, string $servico): bool
    {
        try {
            $dados = [
                'nome'    => $nome,
                'data'    => Carbon::createFromFormat('Y-m-d', $data)->format('d/m/Y'),
                'hora'    => Carbon::createFromFormat('H:i',   $hora)->format('H:i'),
                'servico' => $servico,
            ];

            Mail::send('emails.cancelamento_agendamento', $dados, function ($message) use ($email, $nome) {
                $message->to($email, $nome)
                    ->subject('Cancelamento de Agendamento de Corte');

                if ($copy = env('MAIL_COPY_ADDRESS')) {
                    $message->bcc($copy, env('MAIL_COPY_NAME', 'Admin'));
                }

                if ($admin = env('MAIL_ADMIN')) {
                    $message->bcc($admin, 'Admin');
                }
            });

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
