<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    /* Tipografía y espacios */
    body {
      font-family: Arial, sans-serif;
      font-size: 13px;
      color: #333;
      margin: 1cm;
    }
    h1, h2, h3 {
      margin: .5rem 0;
      color: #2c3e50;
    }
    /* Título de sección */
    .report-section {
      margin-top: 2rem;
    }
    .report-title {
      font-size: 1.4rem;
      border-bottom: 2px solid #2c3e50;
      padding-bottom: .3rem;
      margin-bottom: 1rem;
    }
    /* Tabla profesional */
    .report-table {
      width: 100%;
      border-collapse: collapse;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      margin-bottom: 1.5rem;
    }
    .report-table th {
      background-color: #ecf0f1;
      color: #2c3e50;
      font-weight: 600;
      padding: 10px;
      border-bottom: 2px solid #bdc3c7;
      text-align: left;
    }
    .report-table td {
      padding: 8px 10px;
      border-bottom: 1px solid #e0e0e0;
    }
    .report-table tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    .report-table tr:hover {
      background-color: #f1f1f1;
    }
    /* Pie de página con número de página */
    @page {
      margin: 1cm;
    }
    @bottom-center {
      content: "Página " counter(page) " de " counter(pages);
      font-size: 10px;
      color: #7f8c8d;
    }
  </style>
</head>
<body>
  @php use Illuminate\Support\Str; @endphp

  <div class="report-section">
    <h3 class="report-title">Detalle de Ventas</h3>
    <table class="report-table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Producto</th>
          <th>Cantidad</th>
          <th>Precio Total ($)</th>
        </tr>
      </thead>
      <tbody>
        @forelse($saleDetails as $item)
          <tr>
            <td>{{ \Carbon\Carbon::parse($item->created_at)->format('Y-m-d') }}</td>
            <td>{{ $item->product->name }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" style="text-align:center; font-style: italic; color: #7f8c8d;">
              No hay ventas detalladas en este periodo.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- …resto del body… --}}
</body>
</html>
