<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark only">
    <meta name="supported-color-schemes" content="dark only">
    <title>Acceso Seguro a Fincab</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #09090b;
            color: #f4f4f5;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table {
            border-spacing: 0;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        td {
            padding: 0;
        }
        img {
            border: 0;
            -ms-interpolation-mode: bicubic;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #09090b;
            padding: 48px 16px;
        }
        .main-card {
            background-color: #121215;
            border: 1px solid #27272a;
            border-radius: 20px;
            max-width: 480px;
            margin: 0 auto;
            padding: 40px 32px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .brand-title {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.03em;
            margin: 0;
        }
        .badge {
            display: inline-block;
            background-color: #1e1b4b;
            border: 1px solid #3730a3;
            color: #a5b4fc;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 5px 12px;
            border-radius: 9999px;
            margin-bottom: 20px;
        }
        .heading {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 12px 0;
            letter-spacing: -0.02em;
            line-height: 1.3;
        }
        .paragraph {
            font-size: 14px;
            line-height: 1.6;
            color: #d4d4d8;
            margin: 0 0 24px 0;
        }
        .code-box {
            background-color: #09090b;
            border: 1px solid #27272a;
            border-radius: 14px;
            padding: 24px;
            text-align: center;
            margin: 28px 0;
        }
        .code-digits {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace;
            font-size: 38px;
            font-weight: 800;
            letter-spacing: 0.28em;
            color: #818cf8;
            margin: 0 0 8px 0;
            text-indent: 0.28em;
        }
        .code-expiry {
            font-size: 13px;
            color: #a1a1aa;
            margin: 0;
        }
        .cta-btn {
            display: inline-block;
            background-color: #4f46e5;
            border: 1px solid #4338ca;
            color: #ffffff !important;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
        }
        .footer-note {
            font-size: 13px;
            line-height: 1.6;
            color: #a1a1aa;
            text-align: center;
            margin-top: 36px;
            padding-top: 24px;
            border-top: 1px solid #27272a;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-card">
            <!-- Brand Lockup -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                <tr>
                    <td>
                        <span class="badge">Acceso Seguro</span>
                        <h1 class="heading">Tu código de verificación</h1>
                    </td>
                </tr>
            </table>

            <p class="paragraph">
                Hemos recibido una solicitud para iniciar sesión en tu panel de <strong style="color: #ffffff;">Fincab</strong> con el correo <strong style="color: #ffffff;">{{ $emailAddress }}</strong>.
            </p>

            <!-- Code Box -->
            <div class="code-box">
                <div class="code-digits">{{ $tokenCode }}</div>
                <p class="code-expiry">Expira en {{ $expiresInMinutes }} minutos • Un solo uso</p>
            </div>

            <!-- 1-Click Access Button -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0;">
                <tr>
                    <td align="center">
                        <p class="paragraph" style="margin-bottom: 14px; font-size: 13px;">O ingresa directamente en tu navegador:</p>
                        <a href="{{ $verificationUrl }}" class="cta-btn" target="_blank">Iniciar Sesión en Fincab &rarr;</a>
                    </td>
                </tr>
            </table>

            <!-- Security Footer -->
            <div class="footer-note">
                Si no solicitaste este acceso, tu cuenta permanece segura y nadie puede acceder a ella sin este código.
                <br><br>
                &copy; {{ date('Y') }} Fincab &bull; Control Presupuestario y Priorización Financiera.
            </div>
        </div>
    </div>
</body>
</html>
