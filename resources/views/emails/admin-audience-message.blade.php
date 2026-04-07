<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #334155;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td style="background-color: #0f172a; padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">{{ $schoolName }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #0f172a; margin-top: 0;">Olá, {{ $user->preferredName() }}!</h2>
                            <div style="font-size: 16px; line-height: 1.7; color: #334155;">
                                {!! nl2br(e($bodyText)) !!}
                            </div>

                            @if ($buttonText && $buttonUrl)
                                <table border="0" cellspacing="0" cellpadding="0" style="margin-top: 30px;">
                                    <tr>
                                        <td align="center" bgcolor="#0f172a" style="border-radius: 6px;">
                                            <a href="{{ $buttonUrl }}" target="_blank" rel="noopener" style="display: inline-block; padding: 14px 30px; font-size: 16px; color: #ffffff; text-decoration: none; font-weight: bold;">{{ $buttonText }}</a>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #94a3b8;">
                            &copy; {{ date('Y') }} {{ $schoolName }}. Todos os direitos reservados.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
