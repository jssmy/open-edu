@php
    $logoFile = public_path('images/workshops/logo.jpeg');
    if (! file_exists($logoFile)) {
        $logoFile = base_path('public/images/workshops/logo.jpeg');
    }
    $logoBase64 = file_exists($logoFile) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoFile)) : '/images/workshops/logo.jpeg';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado - {{ $courseName }} | {{ $ongName ?? 'Asociación Un Día de Esperanza' }}</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            margin: 0;
            padding: 32px;
            background: #ffffff;
            color: #0f172a;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .certificate-canvas {
            position: relative;
            width: 100%;
            max-width: 1040px;
            margin: 0 auto;
            background: #ffffff;
            border: 4px solid #be123c;
            padding: 14px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }
        .certificate-inner {
            border: 2px solid #d97706;
            padding: 36px 56px 36px;
            text-align: center;
            position: relative;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .top-accent {
            height: 4px;
            width: 120px;
            margin: 0 auto 14px;
            background: linear-gradient(90deg, #be123c 0%, #f59e0b 50%, #dc2626 100%);
            border-radius: 999px;
        }
        .logo-container {
            margin: 0 auto 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }
        .certificate-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .ong-name {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 17px;
            font-weight: 900;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #be123c;
            margin: 0 auto;
            text-align: center;
            width: 100%;
        }
        .certificate-heading {
            font-size: 34px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #0f172a;
            margin: 20px auto 8px;
            text-align: center;
            width: 100%;
        }
        .divider {
            width: 240px;
            height: 2px;
            background: #f59e0b;
            margin: 0 auto 20px;
        }
        .grant-label {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #475569;
            margin: 0 auto 12px;
            text-align: center;
            width: 100%;
        }
        .participant-container {
            width: 100%;
            display: flex;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .participant-name {
            font-size: 32px;
            font-weight: bold;
            color: #be123c;
            border-bottom: 2px solid #f59e0b;
            display: inline-block;
            padding: 0 40px 8px;
            text-align: center;
            margin: 0 auto;
        }
        .completion-text {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #334155;
            margin: 0 auto 8px;
            text-align: center;
            width: 100%;
        }
        .course-title {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
            margin: 8px auto 16px;
            text-align: center;
            width: 100%;
        }
        .motto {
            font-style: italic;
            font-size: 13px;
            color: #64748b;
            margin: 10px auto 28px;
            text-align: center;
            width: 100%;
        }
        .footer-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            width: 100%;
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
            font-family: Helvetica, Arial, sans-serif;
        }
        .signature-block {
            text-align: center;
            width: 240px;
        }
        .signature-line {
            border-top: 1.5px solid #334155;
            padding-top: 6px;
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }
        .signature-role {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .verification-block {
            text-align: right;
            font-size: 11px;
            color: #475569;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="certificate-canvas">
        <div class="certificate-inner">
            <div class="top-accent"></div>
            <div class="logo-container">
                <img src="{{ $logoBase64 }}" alt="Logo {{ $ongName ?? 'Asociación Un Día de Esperanza' }}" class="certificate-logo" />
            </div>
            <p class="ong-name">{{ $ongName ?? 'Asociación Un Día de Esperanza' }}</p>

            <h1 class="certificate-heading">Certificado de Reconocimiento</h1>
            <div class="divider"></div>

            <p class="grant-label">La <strong>{{ $ongName ?? 'Asociación Un Día de Esperanza' }}</strong> otorga el presente certificado a:</p>
            <div class="participant-container">
                <div class="participant-name">{{ $participantName }}</div>
            </div>

            <p class="completion-text">Por haber culminado satisfactoriamente el curso:</p>
            <div class="course-title">{{ $courseName }}</div>

            <p class="motto">“Educando con amor y construyendo futuro en el Perú”</p>

            <div class="footer-row">
                <div class="signature-block">
                    <div class="signature-line">Dirección Académica</div>
                    <div class="signature-role">{{ $ongName ?? 'Asociación Un Día de Esperanza' }}</div>
                </div>
                <div class="verification-block">
                    <div><strong>Fecha de emisión:</strong> {{ $issuedDate }}</div>
                    <div><strong>Código de verificación:</strong> {{ $code }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
