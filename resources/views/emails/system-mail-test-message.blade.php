<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de e-mail</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: Helvetica, Arial, sans-serif; color: #334155;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    <tr>
                        <td style="padding: 32px 28px;">
                            <h1 style="margin: 0 0 16px; font-size: 24px; color: #0f172a;">Teste de e-mail concluído</h1>

                            <p style="margin: 0 0 12px; font-size: 16px; line-height: 1.6;">
                                Este e-mail confirma que a configuração de envio da escola <strong>{{ $schoolName }}</strong> está funcionando.
                            </p>

                            <div style="background-color: #f1f5f9; border-radius: 6px; padding: 18px; margin: 24px 0;">
                                <p style="margin: 0 0 8px; font-size: 14px;"><strong>Domínio:</strong> {{ $domain }}</p>
                                <p style="margin: 0 0 8px; font-size: 14px;"><strong>Mailer:</strong> {{ $mailer }}</p>
                                <p style="margin: 0; font-size: 14px;"><strong>Enviado em:</strong> {{ $sentAt }}</p>
                            </div>

                            <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                Se você recebeu esta mensagem, o sistema já consegue disparar e-mails com as configurações atuais da escola.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
