<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Confirmação de Agendamento</title>
</head>
<body style="margin:0;padding:0;background:#f7fafc;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7fafc;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                <tr>
                    <td style="background:#1d4ed8;color:#fff;padding:20px 24px;">
                        <h1 style="margin:0;font-size:20px;line-height:1.2;">Marquinhos BarberShop</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <h2 style="margin:0 0 12px 0;font-size:18px;color:#111827;">Olá, {{ $cliente }}!</h2>
                        <p style="margin:0 0 12px 0;">
                            Seu agendamento foi <strong>confirmado</strong> para
                            <strong>{{ $dataPt }}</strong> às <strong>{{ $horaPt }}</strong>.
                        </p>
                        <p style="margin:0 0 16px 0;">
                            Serviço: <strong>{{ $servico }}</strong>
                        </p>

                        <p style="margin:16px 0 0 0;">
                            Obrigado por agendar com a <strong>Marquinhos BarberShop</strong>! 💈
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 24px;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px;">
                        Esta é uma mensagem automática. Por favor, não responda este e-mail.
                    </td>
                </tr>
            </table>

            <div style="color:#9ca3af;font-size:12px;margin-top:12px;">
                &copy; {{ date('Y') }} Marquinhos BarberShop.
            </div>
        </td>
    </tr>
</table>
</body>
</html>
