<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acuse de Recepción CFDI</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f8fb;
            color: #1a237e;
            margin: 0;
            padding: 0;
        }
        .container {
            background: #fff;
            max-width: 600px;
            margin: 40px auto;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.08);
            padding: 32px 40px 32px 40px;
        }
        h1 {
            color: #1565c0;
            font-size: 2.2rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #1976d2;
            padding-bottom: 0.5rem;
            letter-spacing: 1px;
        }
        h2 {
            color: #1976d2;
            font-size: 1.3rem;
            margin-top: 2rem;
            margin-bottom: 0.7rem;
        }
        .info {
            margin-bottom: 1.5rem;
        }
        .info p {
            margin: 0.3rem 0;
            font-size: 1.08rem;
        }
        .incidencia {
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }
        .incidencia p {
            margin: 0.2rem 0;
            color: #0d47a1;
        }
        .footer {
            margin-top: 2.5rem;
            text-align: center;
            color: #90caf9;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Acuse de Recepción CFDI</h1>
        <div class="info">
            <p><strong>UUID:</strong> {{ $uuid }}</p>
            <p><strong>Código de Estatus:</strong> {{ $codigo }}</p>
            <p><strong>Fecha de Acuse:</strong> {{ $fechaAcuse }}</p>
            <p><strong>No. Certificado SAT:</strong> {{ $noCertificadoSAT }}</p>
        </div>
        @if(isset($incidenciaData) && $incidenciaData)
            <h2>Incidencia</h2>
            <div class="incidencia">
                <p><strong>Mensaje:</strong> {{ $incidenciaData['mensaje'] }}</p>
                <p><strong>Código de Error:</strong> {{ $incidenciaData['codigo_error'] }}</p>
                <p><strong>RFC Emisor:</strong> {{ $incidenciaData['rfc_emisor'] }}</p>
                <p><strong>ID Incidencia:</strong> {{ $incidenciaData['id_incidencia'] }}</p>
                <p><strong>Fecha Registro:</strong> {{ $incidenciaData['fecha_registro'] }}</p>
            </div>
        @endif
        <div class="footer">
            Generado automáticamente - {{ date('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
