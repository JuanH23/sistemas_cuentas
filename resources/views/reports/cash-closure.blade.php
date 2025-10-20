{{-- resources/views/reports/cash-closure.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja - {{ $closure->date->format('d/m/Y') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 24px;
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        .header .subtitle {
            font-size: 14px;
            color: #666;
        }
        
        .info-section {
            margin-bottom: 20px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #2563eb;
        }
        
        .info-section h2 {
            font-size: 14px;
            color: #1e40af;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-label {
            font-weight: bold;
            color: #475569;
        }
        
        .info-value {
            color: #0f172a;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .card {
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        
        .card-success {
            background: #dcfce7;
            border: 1px solid #86efac;
        }
        
        .card-danger {
            background: #fee2e2;
            border: 1px solid #fca5a5;
        }
        
        .card-primary {
            background: #dbeafe;
            border: 1px solid #93c5fd;
        }
        
        .card-title {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .card-amount {
            font-size: 20px;
            font-weight: bold;
        }
        
        .movements-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        
        .movements-table thead {
            background: #1e40af;
            color: white;
        }
        
        .movements-table th {
            padding: 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .movements-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        
        .movements-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        
        .type-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .type-income {
            background: #dcfce7;
            color: #166534;
        }
        
        .type-expense {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .amount-positive {
            color: #16a34a;
            font-weight: bold;
        }
        
        .amount-negative {
            color: #dc2626;
            font-weight: bold;
        }
        
        .difference-box {
            margin: 20px 0;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
        }
        
        .difference-success {
            background: #dcfce7;
            border: 2px solid #16a34a;
            color: #166534;
        }
        
        .difference-warning {
            background: #fef3c7;
            border: 2px solid #eab308;
            color: #854d0e;
        }
        
        .difference-danger {
            background: #fee2e2;
            border: 2px solid #dc2626;
            color: #991b1b;
        }
        
        .signatures {
            margin-top: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        .signature-line {
            border-top: 2px solid #333;
            padding-top: 10px;
            text-align: center;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    {{-- Encabezado --}}
    <div class="header">
        <h1> CIERRE DE CAJA</h1>
        <p class="subtitle">Reporte generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    {{-- Información general --}}
    <div class="info-section">
        <h2> Información del Cierre</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Fecha:</span>
                <span class="info-value">{{ $closure->date->format('d/m/Y') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Usuario:</span>
                <span class="info-value">{{ $cashFlow->user->name ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Hora Cierre:</span>
                <span class="info-value">{{ $closure->created_at->format('H:i:s') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Total Movimientos:</span>
                <span class="info-value">{{ $movements->count() }}</span>
            </div>
        </div>
    </div>

    {{-- Resumen financiero --}}
    <div class="summary-cards">
        <div class="card card-success">
            <div class="card-title" style="color: #166534;"> Ingresos</div>
            <div class="card-amount" style="color: #16a34a;">
                ${{ number_format($closure->total_income, 0, ',', '.') }}
            </div>
        </div>
        
        <div class="card card-danger">
            <div class="card-title" style="color: #991b1b;"> Egresos</div>
            <div class="card-amount" style="color: #dc2626;">
                ${{ number_format($closure->total_expense, 0, ',', '.') }}
            </div>
        </div>
        
        <div class="card card-primary">
            <div class="card-title" style="color: #1e40af;"> Saldo Esperado</div>
            <div class="card-amount" style="color: #2563eb;">
                ${{ number_format($closure->expected_balance, 0, ',', '.') }}
            </div>
        </div>
    </div>

    {{-- Diferencia --}}
    @php
        $difference = $closure->real_balance - $closure->expected_balance;
        $diffClass = $difference == 0 ? 'success' : ($difference > 0 ? 'warning' : 'danger');
        $diffText = $difference == 0 ? 'Sin diferencias' : 
                   ($difference > 0 ? 'Sobrante' : 'Faltante');
    @endphp

    <div class="difference-box difference-{{ $diffClass }}">
        <strong> {{ $diffText }}</strong><br>
        Saldo Real: <strong>${{ number_format($closure->real_balance, 0, ',', '.') }}</strong>
        @if ($difference != 0)
            <br>Diferencia: <strong>${{ number_format(abs($difference), 0, ',', '.') }}</strong>
        @endif
    </div>

    {{-- Detalle de movimientos --}}
    @if ($movements->count() > 0)
        <div class="info-section">
            <h2> Detalle de Movimientos</h2>
        </div>

        <table class="movements-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Hora</th>
                    <th style="width: 15%;">Tipo</th>
                    <th style="width: 25%;">Categoría</th>
                    <th style="width: 30%;">Descripción</th>
                    <th style="width: 15%; text-align: right;">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at->format('H:i:s') }}</td>
                        <td>
                            <span class="type-badge type-{{ $movement->type }}">
                                {{ $movement->type === 'income' ? ' Ingreso' : ' Egreso' }}
                            </span>
                        </td>
                        <td>{{ $movement->category ?? 'Sin categoría' }}</td>
                        <td>{{ $movement->description ?? '-' }}</td>
                        <td style="text-align: right;">
                            <span class="amount-{{ $movement->type === 'income' ? 'positive' : 'negative' }}">
                                ${{ number_format($movement->amount, 0, ',', '.') }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 40px; color: #666;">
            No se registraron movimientos en este día
        </div>
    @endif

    {{-- Observaciones --}}
    @if ($closure->observations ?? false)
        <div class="info-section">
            <h2> Observaciones</h2>
            <p style="margin-top: 10px;">{{ $closure->observations }}</p>
        </div>
    @endif

    {{-- Firmas --}}
    <div class="signatures">
        <div class="signature-line">
            <strong>Realizado por</strong><br>
            {{ $cashFlow->user->name ?? 'N/A' }}
        </div>
        <div class="signature-line">
            <strong>Revisado por</strong><br>
            _______________________
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>Este documento fue generado automáticamente por el sistema</p>
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>