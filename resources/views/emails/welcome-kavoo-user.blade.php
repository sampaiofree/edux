<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo(a) ao Portal Jovem Empreendedor</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #334155;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td style="background-color: #0f172a; padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Portal Jovem Empreendedor</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #0f172a; margin-top: 0;">Olá, {{ $user->preferredName() }}!</h2>
                            <p style="font-size: 16px; line-height: 1.6;">Seu acesso à nossa plataforma foi liberado. Estamos felizes em ter você conosco!</p>
                            
                            <div style="background-color: #f1f5f9; border-radius: 6px; padding: 20px; margin: 25px 0;">
                                <p style="margin: 0 0 10px 0;"><strong>Seus dados de acesso:</strong></p>
                                <p style="margin: 5px 0; font-size: 14px;"><strong>E-mail:</strong> {{ $user->email }}</p>
                                <p style="margin: 5px 0; font-size: 14px;"><strong>Senha temporária:</strong> <code style="background: #e2e8f0; padding: 2px 4px; border-radius: 4px;">{{ $plainPassword }}</code></p>
                            </div>

                            <table border="0" cellspacing="0" cellpadding="0" style="margin-top: 30px;">
                                <tr>
                                    <td align="center" bgcolor="#0f172a" style="border-radius: 6px;">
                                        <a href="{{ $loginUrl }}" target="_blank" style="display: inline-block; padding: 14px 30px; font-size: 16px; color: #ffffff; text-decoration: none; font-weight: bold;">Acessar Minha Conta</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 14px; color: #64748b; margin-top: 30px;">
                                * Por segurança, recomendamos alterar sua senha logo no primeiro acesso.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #94a3b8;">
                            &copy; {{ date('Y') }} Portal Jovem Empreendedor. Todos os direitos reservados.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>