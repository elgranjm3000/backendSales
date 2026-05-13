<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background-color: #004856;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .email-body {
            padding: 40px 30px;
        }
        .email-body h2 {
            color: #333333;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .email-body p {
            color: #666666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .code-box {
            background-color: #f8f9fa;
            border: 2px dashed #004856;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .code-box .code {
            font-size: 32px;
            font-weight: bold;
            color: #004856;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        .code-box .code-label {
            font-size: 14px;
            color: #666666;
            margin-bottom: 10px;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0;
            color: #0c5460;
            font-size: 14px;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #999999;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #004856;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🔐 Recuperación de Contraseña</h1>
        </div>
        
        <div class="email-body">
            <h2>Hola {{ $user->name }},</h2>
            
            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.</p>
            
            <p>Para continuar con el proceso de recuperación, utiliza el siguiente código de verificación:</p>
            
            <div class="code-box">
                <div class="code-label">Tu código de verificación es:</div>
                <div class="code">{{ $resetCode }}</div>
            </div>
            
            <div class="info-box">
                <p><strong>⏱️ Este código es válido por 15 minutos</strong></p>
            </div>
            
            <p>Ingresa este código en la aplicación para continuar con el restablecimiento de tu contraseña.</p>
            
            <div class="warning">
                <p><strong>⚠️ Importante:</strong> Si no solicitaste restablecer tu contraseña, puedes ignorar este correo de forma segura. Tu contraseña actual permanecerá sin cambios.</p>
            </div>
            
            <p style="margin-top: 30px;">Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.</p>
            
            <p>Saludos,<br><strong>El equipo de Chrystal</strong></p>
        </div>
        
        <div class="email-footer">
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
            <p>&copy; {{ date('Y') }} Chrystal. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>