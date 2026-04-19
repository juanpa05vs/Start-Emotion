<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Bio-Sync - Start-Emotion</title>
    <style>
        @page { margin: 1cm; }
        body {
            font-family: 'Helvetica', sans-serif;
            color: #333;
            line-height: 1.4;
        }
        /* Encabezado Estilo Tecnológico */
        .header {
            border-bottom: 3px solid #a855f7;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #22d3ee;
            letter-spacing: 2px;
        }
        .subtitle {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
        }
        /* Información del Usuario */
        .user-info {
            margin-bottom: 30px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
        }
        /* Tabla de Datos */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th {
            background-color: #f3f4f6;
            color: #4b5563;
            text-transform: uppercase;
            padding: 10px;
            border-bottom: 2px solid #e5e7eb;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #f3f4f6;
        }
        .status-badge {
            font-weight: bold;
            color: #a855f7;
        }
        .energy-val {
            color: #22d3ee;
            font-weight: bold;
        }
        /* Pie de Página */
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #aaa;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">S-EMOTION</div>
        <div class="subtitle">Análisis Conductual // Reporte de Sincronización Bio-Sync</div>
    </div>

    <div class="user-info">
        <p style="margin: 0;"><strong>Operador:</strong> {{ $user->nombre }}</p>
        <p style="margin: 5px 0 0 0;"><strong>ID de Sistema:</strong> #00{{ $user->id }} | <strong>Fecha de Reporte:</strong> {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Estado Emocional</th>
                <th>Energía</th>
                <th>Estrés</th>
                <th>Recomendación del Motor IA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($historial as $registro)
            <tr>
                <td>{{ $registro->created_at->format('d/m/Y H:i') }}</td>
                <td class="status-badge">{{ $registro->emocion }}</td>
                <td class="energy-val">{{ $registro->energia }}%</td>
                <td>{{ $registro->nivel_estres_estimado }}%</td>
                <td style="font-style: italic; color: #666;">"{{ $registro->recomendacion }}"</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        PROYECTO START-EMOTION - TESVB INGENIERÍA EN SISTEMAS - ESTE DOCUMENTO ES UN REGISTRO DE DATOS SIMULADOS.
    </div>
</body>
</html>
