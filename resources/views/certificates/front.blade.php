<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
        }

        body {
            margin: 0;
            font-family: 'Helvetica', Arial, sans-serif;
            color: #0f172a;
        }

        .page {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .background {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .content {
            position: relative;
            z-index: 1;
            padding: 90px 120px;
            display: flex;
            flex-direction: column;
            font-size: {{ $fontSize }}px;
            line-height: 1.4;
            text-align: left;
        }

        .content p {
            margin: 0 0 0.75rem;
            white-space: pre-wrap;
        }

        .title {
            font-size: {{ $fontSize - 1 }}px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }

        .student-name {
            font-size: {{ $fontSize + 2 }}px;
            font-weight: 700;
        }

        .course-name {
            font-size: {{ $fontSize + 1 }}px;
            font-weight: 600;
        }

        .meta {
            font-size: {{ $fontSize }}px;
            font-weight: 400;
        }
    </style>
</head>
<body>
<div class="page">
    @if (!empty($backgroundImagePath))
        <img
            class="background"
            src="file://{{ str_replace('\\', '/', $backgroundImagePath) }}"
            alt="Fundo da frente do certificado"
        />
    @else
        <div class="background" style="background-color:#ffffff;"></div>
    @endif

    <div class="content">
        <p class="title">Certificado de Conclusão</p>
        <p class="student-name">{{ $studentName }}</p>
        <p class="course-name">{{ $courseName }}</p>
        <p class="meta">Concluído em {{ $completedAtLabel }}</p>
        @if (!empty($cpf))
            <p class="meta">CPF {{ $cpf }}</p>
        @endif
    </div>
</div>
</body>
</html>
