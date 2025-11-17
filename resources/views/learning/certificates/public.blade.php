<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Certificado – {{ $course->title }}</title>
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 2rem;
        }
        .card {
            max-width: 720px;
            margin: 0 auto 1.5rem auto;
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.1);
        }
        .certificate-frame {
            border-radius: 24px;
            border: 1px solid #cbd5f5;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="card">
        <p style="margin:0; color:#94a3b8;">Verificação pública</p>
        <h1 style="margin:0.25rem 0 1rem 0;">Certificado {{ $certificate->number }}</h1>
        <ul style="list-style:none; padding:0; margin:0 0 1rem 0;">
            <li><strong>Aluno:</strong> {{ $user->display_name ?? $user->name }}</li>
            <li><strong>Curso:</strong> {{ $course->title }}</li>
            <li><strong>Emitido em:</strong> {{ $certificate->issued_at->format('d/m/Y') }}</li>
            <li><strong>Carga horária:</strong> {{ $course->duration_minutes ?? '600' }} min</li>
        </ul>
        <p style="margin:0;">Este certificado foi emitido pela plataforma EduX e pode ser confirmado pelo código <strong>{{ $certificate->number }}</strong>.</p>
    </div>

    <div class="card certificate-frame">
        {!! $frontContent !!}
    </div>
    <div class="card certificate-frame">
        {!! $backContent !!}
    </div>
</body>
</html>
