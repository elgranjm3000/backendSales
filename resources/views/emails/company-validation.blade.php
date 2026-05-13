<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Validación - Chrystal</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .content {
            margin-bottom: 30px;
        }
        
        .validation-code {
            background-color: #f8f9fa;
            border: 2px dashed #007bff;
            text-align: center;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            letter-spacing: 5px;
            margin: 10px 0;
        }
        
        .company-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .company-info h4 {
            margin-top: 0;
            color: #495057;
        }
        
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">CHRYSTAL</div>
            <div class="subtitle">Sistema de Gestión Empresarial</div>
        </div>
        
        <div class="content">
            <h2>Código de Validación para Registro Empresarial</h2>
            
            <p>Estimado usuario,</p>
            
            <p>Ha solicitado registrar la siguiente empresa en nuestro sistema:</p>
            
            <div class="company-info">
                <h4>Información de la Empresa</h4>
                <p><strong>Nombre:</strong> {{ $companyName }}</p>
                <p><strong>RIF:</strong> {{ $companyRif }}</p>
            </div>
            
            <p>Para completar el proceso de registro, utilice el siguiente código de validación:</p>
            
            <div class="validation-code">
                <div>Su código de validación es:</div>
                <div class="code">{{ $validationCode }}</div>
                <div><small>Válido por 15 minutos</small></div>
            </div>
            
            <div class="warning">
                <strong>⚠️ Importante:</strong>
                <ul>
                    <li>Este código expirará en <strong>15 minutos</strong></li>
                    <li>Solo puede ser utilizado una vez</li>
                    <li>Si no solicitó este código, ignore este mensaje</li>
                    <li>No comparta este código con terceros</li>
                </ul>
            </div>
            
            <p>Una vez validado el código, podrá crear su clave de acceso para acceder al sistema Chrystal.</p>
            
            <p>Si tiene alguna pregunta o necesita asistencia, no dude en contactar con nuestro equipo de soporte.</p>
        </div>
        
        <div class="footer">
            <p>Este es un mensaje automático, por favor no responder a este correo.</p>
            