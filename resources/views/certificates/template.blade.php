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
            background: #faf7f2;
            color: #0f172a;
        }
        .certificate-canvas {
            position: relative;
            max-width: 1040px;
            margin: 0 auto;
            background: #fffcf7;
            border: 4px solid #be123c;
            padding: 14px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
        }
        .certificate-inner {
            border: 2px solid #d97706;
            padding: 48px 56px 40px;
            text-align: center;
            position: relative;
        }
        .top-accent {
            height: 6px;
            width: 180px;
            margin: 0 auto 20px;
            background: linear-gradient(90deg, #be123c 0%, #f59e0b 50%, #dc2626 100%);
            border-radius: 999px;
        }
        .ong-name {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 3.5px;
            text-transform: uppercase;
            color: #be123c;
            margin: 0;
        }
        .certificate-heading {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #0f172a;
            margin: 26px 0 8px;
        }
        .divider {
            width: 240px;
            height: 2px;
            background: #f59e0b;
            margin: 0 auto 24px;
        }
        .grant-label {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #475569;
            margin-bottom: 14px;
        }
        .participant-name {
            font-size: 32px;
            font-weight: bold;
            color: #be123c;
            border-bottom: 2px solid #f59e0b;
            display: inline-block;
            padding: 0 40px 8px;
            margin-bottom: 22px;
        }
        .completion-text {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #334155;
            margin-bottom: 10px;
        }
        .course-title {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
            margin: 8px 0 18px;
        }
        .motto {
            font-style: italic;
            font-size: 13px;
            color: #64748b;
            margin: 12px 0 32px;
        }
        .footer-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-top: 32px;
            padding-top: 18px;
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
            <p class="ong-name">{{ $ongName ?? 'Asociación Un Día de Esperanza' }}</p>

            <h1 class="certificate-heading">Certificado de Reconocimiento</h1>
            <div class="divider"></div>

            <p class="grant-label">La <strong>{{ $ongName ?? 'Asociación Un Día de Esperanza' }}</strong> otorga el presente certificado a:</p>
            <div class="participant-name">{{ $participantName }}</div>

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
