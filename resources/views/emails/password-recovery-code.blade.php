<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recuperar senha</title>
</head>
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <p style="margin:0 0 8px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#2563eb;">Recuperar senha</p>
        <h1 style="margin:0 0 16px;font-size:28px;line-height:1.2;color:#1d4ed8;">Seu código chegou</h1>

        <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
            Use este código para criar uma nova senha na plataforma {{ $schoolName }}.
        </p>

        <div style="margin:24px 0;padding:20px;border-radius:14px;background:#eff6ff;text-align:center;">
            <p style="margin:0 0 8px;font-size:13px;color:#475569;">Digite este código</p>
            <p style="margin:0;font-size:38px;font-weight:700;letter-spacing:.18em;color:#1d4ed8;">{{ $code }}</p>
        </div>

        <p style="margin:0 0 12px;font-size:15px;line-height:1.6;">
            Este código vale por {{ $expiresInMinutes }} minutos.
        </p>
        <p style="margin:0;font-size:14px;line-height:1.6;color:#64748b;">
            Se você não pediu a troca de senha, pode ignorar este e-mail.
        </p>
    </div>
</body>
</html>
