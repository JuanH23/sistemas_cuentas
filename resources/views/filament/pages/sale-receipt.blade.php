<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Venta</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background-color: #f5f5f5;
            padding: 20px;
        }

        #receipt {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .business-name {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .business-info {
            font-size: 11px;
            line-height: 1.6;
            color: #333;
        }

        .receipt-type {
            background: #000;
            color: white;
            padding: 8px;
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            font-size: 12px;
        }

        .info-block {
            flex: 1;
        }

        .info-block h3 {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 10px;
            text-decoration: underline;
        }

        .info-line {
            margin-bottom: 5px;
            line-height: 1.5;
        }

        .label {
            display: inline-block;
            width: 80px;
            font-weight: bold;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 12px;
        }

        .products-table thead {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        .products-table th {
            padding: 10px 5px;
            text-align: left;
            font-weight: bold;
        }

        .products-table th:nth-child(3),
        .products-table th:nth-child(4),
        .products-table th:nth-child(5) {
            text-align: right;
        }

        .products-table tbody tr {
            border-bottom: 1px dotted #999;
        }

        .products-table td {
            padding: 12px 5px;
            vertical-align: top;
        }

        .products-table td:nth-child(3),
        .products-table td:nth-child(4),
        .products-table td:nth-child(5) {
            text-align: right;
        }

        .product-name {
            font-weight: bold;
        }

        .product-desc {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
        }

        .totals-section {
            float: right;
            width: 300px;
            margin-bottom: 30px;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 12px;
        }

        .total-line.subtotal {
            border-top: 1px solid #000;
        }

        .total-line.discount {
            color: #d32f2f;
        }

        .total-line.final {
            border-top: 2px solid #000;
            border-bottom: 2px double #000;
            font-weight: bold;
            font-size: 16px;
            padding: 12px 0;
            margin-top: 5px;
        }

        .payment-info {
            clear: both;
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 12px;
        }

        .payment-info h3 {
            font-size: 13px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }

        .badge.paid {
            background: #4caf50;
            color: white;
        }

        .badge.pending {
            background: #ff9800;
            color: white;
        }

        .badge.vip {
            background: #ffd700;
            color: #000;
        }

        .badge.regular {
            background: #2196f3;
            color: white;
        }

        .badge.wholesale {
            background: #4caf50;
            color: white;
        }

        .notes-section {
            background: #fffbea;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 11px;
        }

        .notes-section h4 {
            font-size: 12px;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .receipt-footer {
            text-align: center;
            border-top: 2px solid #000;
            padding-top: 20px;
            font-size: 11px;
            line-height: 1.8;
        }

        .footer-message {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .warning-box {
            background: #ffebee;
            border: 2px solid #d32f2f;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }

        .warning-box p {
            color: #d32f2f;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .barcode {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            background: white;
            border: 1px dashed #999;
        }

        .barcode-number {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            letter-spacing: 3px;
            margin-top: 5px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            #receipt {
                box-shadow: none;
                max-width: 100%;
                padding: 20mm;
            }

            .no-print {
                display: none !important;
            }
        }

        @page {
            size: A4;
            margin: 0;
        }
    </style>
</head>
<body>
    <div id="receipt">
        <!-- HEADER -->
        <div class="receipt-header">
            <div class="business-name">{{ strtoupper(tenant_name() ?? 'MI NEGOCIO') }}</div>
            <div class="business-info">
                {{-- Usar teléfono y email del tenant --}}
                @if(tenant_phone())
                    Tel: {{ tenant_phone() }} /
                @endif
                @if(tenant_email())
                    Email: {{ tenant_email() }}<br>
                @endif
                @if(tenant_nit())
                    NIT: {{ tenant_nit() }} /
                @endif
                @if(tenant_address())
                    Dirección: {{ tenant_address() }}<br>
                @endif
            </div>
        </div>

        <!-- RECEIPT TYPE -->
        <div class="receipt-type">COMPROBANTE DE VENTA</div>

        <!-- INFO SECTIONS -->
        <div class="info-section">
            <div class="info-block">
                <h3>INFORMACIÓN DE VENTA</h3>
                <div class="info-line">
                    <span class="label">Recibo No:</span> 
                    <strong>{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</strong>
                </div>
                <div class="info-line">
                    <span class="label">Fecha:</span> 
                    {{ $sale->created_at->format('d/m/Y') }}
                </div>
                <div class="info-line">
                    <span class="label">Hora:</span> 
                    {{ $sale->created_at->format('h:i A') }}
                </div>
                <div class="info-line">
                    <span class="label">Vendedor:</span> 
                    {{ optional($sale->financialMovement)->user->name ?? 'Sistema' }}
                </div>
            </div>

            <div class="info-block">
                <h3>DATOS DEL CLIENTE</h3>
                <div class="info-line">
                    <span class="label">Nombre:</span> 
                    <strong>{{ $sale->customer_name }}</strong>
                </div>
                @if($sale->customer_phone)
                <div class="info-line">
                    <span class="label">Teléfono:</span> 
                    {{ $sale->customer_phone }}
                </div>
                @endif
                @if($sale->customer_email)
                <div class="info-line">
                    <span class="label">Email:</span> 
                    {{ $sale->customer_email }}
                </div>
                @endif
                <div class="info-line">
                    <span class="label">Tipo:</span> 
                    <span class="badge {{ $sale->customer_type === 'vip' ? 'vip' : ($sale->customer_type === 'wholesale' ? 'wholesale' : 'regular') }}">
                        {{ match($sale->customer_type) {
                            'vip' => 'VIP',
                            'wholesale' => 'MAYORISTA',
                            default => 'REGULAR'
                        } }}
                    </span>
                </div>
            </div>
        </div>

        <!-- PRODUCTS TABLE -->
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width: 40px;">ITEM</th>
                    <th>DESCRIPCIÓN</th>
                    <th style="width: 60px;">CANT.</th>
                    <th style="width: 100px;">P. UNIT.</th>
                    <th style="width: 100px;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->saleDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div class="product-name">{{ $detail->product->name }}</div>
                        @if($detail->product->description)
                        <div class="product-desc">{{ Str::limit($detail->product->description, 80) }}</div>
                        @endif
                    </td>
                    <td>{{ $detail->quantity }}</td>
                    <td>${{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                    <td><strong>${{ number_format($detail->total, 0, ',', '.') }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- TOTALS -->
        <div class="totals-section">
            <div class="total-line subtotal">
                <span>Subtotal:</span>
                <span>${{ number_format($sale->subtotal ?? $sale->total_amount, 0, ',', '.') }}</span>
            </div>
            @if($sale->discount > 0)
            <div class="total-line discount">
                <span>Descuento:</span>
                <span>- ${{ number_format($sale->discount, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="total-line final">
                <span>TOTAL A PAGAR:</span>
                <span>${{ number_format($sale->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- PAYMENT INFO -->
        <div class="payment-info">
            <h3>INFORMACIÓN DE PAGO</h3>
            <div class="payment-grid">
                <div>
                    <strong>Método de Pago:</strong><br>
                    {{ match($sale->payment_method) {
                        'cash' => 'Efectivo',
                        'card' => 'Tarjeta Débito/Crédito',
                        'transfer' => 'Transferencia Bancaria',
                        'credit' => 'Crédito',
                        default => 'Efectivo'
                    } }}
                </div>
                <div>
                    <strong>Estado del Pago:</strong><br>
                    <span class="badge {{ $sale->payment_status === 'paid' ? 'paid' : 'pending' }}">
                        {{ match($sale->payment_status) {
                            'paid' => 'PAGADO',
                            'pending' => 'PENDIENTE',
                            'partial' => 'PARCIAL',
                            default => 'PAGADO'
                        } }}
                    </span>
                </div>
            </div>
        </div>

        <!-- NOTES -->
        @if($sale->notes)
        <div class="notes-section">
            <h4>OBSERVACIONES:</h4>
            <p>{{ $sale->notes }}</p>
        </div>
        @endif

        <!-- WARNING FOR PENDING PAYMENT -->
        @if($sale->payment_status === 'pending')
        <div class="warning-box">
            <p>⚠️ PAGO PENDIENTE</p>
            <p style="font-size: 10px; font-weight: normal;">Este comprobante no es válido hasta completar el pago total</p>
        </div>
        @endif

        <!-- FOOTER -->
        <div class="receipt-footer">
            <div class="footer-message">¡GRACIAS POR SU COMPRA!</div>
            <p>Este documento es un comprobante de venta válido</p>
            <p>No se aceptan devoluciones sin este recibo</p>
            <p style="font-size: 10px; color: #999; margin-top: 10px;">
                {{ tenant_name() }} - Documento generado el {{ now()->format('d/m/Y H:i:s') }}
            </p>
        </div>

        <!-- BARCODE/VERIFICATION -->
        <!-- <div class="barcode">
            <div style="font-size: 10px; color: #666;">CÓDIGO DE VERIFICACIÓN</div>
            <div class="barcode-number">{{ strtoupper(substr(md5($sale->id . $sale->created_at), 0, 12)) }}</div>
            <div style="font-size: 9px; color: #999; margin-top: 5px;">
                Verificable en: www.papeleriaejemplo.com/verificar
            </div>
        </div> -->
    </div>
</body>
</html>