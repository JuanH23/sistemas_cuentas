<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 1cm; }
    h1, h2, h3 { margin: .5rem 0; color: #2c3e50; }
    .report-section { margin-top: 1.5rem; }
    .report-title {
      font-size: 1.3rem;
      border-bottom: 2px solid #2c3e50;
      padding-bottom: .2rem;
      margin-bottom: .8rem;
    }
    .kpi-cards { display: flex; gap: 1rem; margin-bottom: 2rem; }
    .kpi-card {
      flex: 1; padding: .8rem; background: #f7f9fa; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .kpi-card h4 { margin: 0; font-size: 1rem; color: #555; }
    .kpi-card p { margin: .3rem 0 0; font-size: 1.2rem; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
    th, td {
      border: 1px solid #ddd;
      padding: .6rem .8rem;
      text-align: left;
    }
    th {
      background: #2c3e50;
      color: #fff;
      font-weight: 600;
    }
    tr:nth-child(even) { background: #f9f9f9; }
    tr:hover { background: #eef2f5; }
    @page { margin: 1cm; }
    @bottom-center {
      content: "Página " counter(page) " de " counter(pages);
      font-size: 10px; color: #7f8c8d;
    }
  </style>
</head>
<body>

  <h1>Reporte de Ingresos y Egresos</h1>
  {{-- KPI Totales --}}
  <div class="report-section">
    <h3 class="report-title">Resumen</h3>
    <p><strong>Total Ingresos:</strong> ${{ number_format($totalIncome, 2) }}</p>
    <p><strong>Total Egresos:</strong> ${{ number_format($totalExpense, 2) }}</p>
    <p><strong>Balance:</strong> ${{ number_format($balance, 2) }}</p>
  </div>

  {{-- Detalle por día --}}
  <div class="report-section">
    <h3 class="report-title">Detalle Diario</h3>
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Categoría</th>
          <th>Descripción</th>
          <th>Ingresos ($)</th>
          <th>Egresos ($)</th>
        </tr>
      </thead>
      <tbody>

        @forelse($byDate as $row)
          <tr>
            <td>{{ \Carbon\Carbon::parse($row->fecha)->format('Y-m-d H:i') }}</td>
            <td>{{ $row->category }}</td>
            <td>{{ $row->description }}</td>
            <td>{{ $row->type === 'income' ? number_format($row->amount, 2) : '-' }}</td>
            <td>{{ $row->type === 'expense' ? number_format($row->amount, 2) : '-' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="3" style="text-align:center; font-style:italic; color:#7f8c8d;">
              No hay movimientos en este periodo.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

</body>
</html>
