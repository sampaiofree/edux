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
            line-height: 1.6;
            text-align: left;
        }

        .content p {
            margin: 0 0 1.25rem;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
<div class="page">
    @if (!empty($backgroundImagePath))
        <img
            class="background"
            src="file://{{ str_replace('\\', '/', $backgroundImagePath) }}"
            alt="Fundo do verso do certificado"
        />
    @else
        <div class="background" style="background-color:#ffffff;"></div>
    @endif

    <div class="content">
        @forelse ($paragraphs as $paragraph)
            <p>{{ $paragraph }}</p>
        @empty
            <p>Conteúdo do curso em atualização.</p>
        @endforelse
    </div>
</div>
</body>
</html>
