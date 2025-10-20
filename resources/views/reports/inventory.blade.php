<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body {
      font-family: Arial, sans-serif;
      color: #333;
      margin: 1cm;
      font-size: 12px;
    }
    h1 {
      color: #2c3e50;
      font-size: 1.5rem;
      border-bottom: 2px solid #2c3e50;
      padding-bottom: 0.3rem;
      margin-bottom: 1rem;
    }
    p.meta {
      font-size: 0.9rem;
      color: #555;
      margin-bottom: 1.5rem;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    thead th {
      background: #2c3e50;
      color: #fff;
      padding: 0.8rem;
      text-align: left;
      font-weight: 600;
    }
    tbody td {
      padding: 0.6rem 0.8rem;
      border-bottom: 1px solid #e0e0e0;
    }
    tbody tr:nth-child(even) {
      background: #f7f9fa;
    }
    /* Resalta productos con precio alto o bajo stock */
    tbody tr.low-stock {
      background: #fdecea;
    }
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
  @php $fechaReporte = now()->format('Y-m-d H:i'); @endphp
  <h1>Inventario Actual</h1>
  <p class="meta">Fecha de reporte: {{ $fechaReporte }}</p>

  <table>
    <thead>
      <tr>
        <th>Producto</th>
        <th>Cantidad</th>
        <th>Precio de Venta ($)</th>
        <th>Precio Unitario ($)</th>
      </tr>
    </thead>
    <tbody>
      @forelse($products as $product)
        <tr class="{{ $product->quantity < 10 ? 'low-stock' : '' }}">
          <td>{{ $product->name }}</td>
          <td>{{ $product->quantity }}</td>
          <td>{{ number_format($product->price, 2) }}</td>
          <td>{{ number_format($product->unit_price, 2) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="4" style="text-align:center; font-style:italic;">No hay productos registrados.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
