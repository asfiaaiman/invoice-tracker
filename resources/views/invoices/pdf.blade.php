<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            color: #1a1a1a;
            font-weight: bold;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .section {
            width: 48%;
        }
        .section h3 {
            margin-top: 0;
            font-size: 16px;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
            color: #1a1a1a;
            font-weight: bold;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .items-table td.text-right {
            text-align: right;
        }
        .totals {
            float: right;
            width: 300px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 5px;
        }
        .totals td:last-child {
            text-align: right;
        }
        .totals .total-row {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
        }
        .footer {
            margin-top: 50px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVOICE</h1>
        <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
        <p><strong>Issue Date:</strong> {{ $invoice->issue_date->format('Y-m-d') }}</p>
        @if($invoice->due_date)
            <p><strong>Due Date:</strong> {{ $invoice->due_date->format('Y-m-d') }}</p>
        @endif
    </div>

    <div class="invoice-info">
        <div class="section">
            <h3>From</h3>
            <p><strong>{{ $invoice->agency->name }}</strong></p>
            @if($invoice->agency->tax_id)
                <p>Tax ID: {{ $invoice->agency->tax_id }}</p>
            @endif
            @if($invoice->agency->address)
                <p>{{ $invoice->agency->address }}</p>
            @endif
            @if($invoice->agency->city)
                <p>{{ $invoice->agency->city }}{!! $invoice->agency->zip_code ? ' ' . $invoice->agency->zip_code : '' !!}</p>
            @endif
            @if($invoice->agency->country)
                <p>{{ $invoice->agency->country }}</p>
            @endif
            @if($invoice->agency->phone)
                <p>Phone: {{ $invoice->agency->phone }}</p>
            @endif
            @if($invoice->agency->email)
                <p>Email: {{ $invoice->agency->email }}</p>
            @endif
            @if($invoice->agency->website)
                <p>Website: {{ $invoice->agency->website }}</p>
            @endif
        </div>

        <div class="section">
            <h3>To</h3>
            <p><strong>{{ $invoice->client->name }}</strong></p>
            @if($invoice->client->tax_id)
                <p>Tax ID: {{ $invoice->client->tax_id }}</p>
            @endif
            @if($invoice->client->address)
                <p>{{ $invoice->client->address }}</p>
            @endif
            @if($invoice->client->city)
                <p>{{ $invoice->client->city }}{!! $invoice->client->zip_code ? ' ' . $invoice->client->zip_code : '' !!}</p>
            @endif
            @if($invoice->client->country)
                <p>{{ $invoice->client->country }}</p>
            @endif
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        @if($item->product)
                            <strong>{{ $item->product->name }}</strong>
                            @if($item->description)
                                <br><small>{{ $item->description }}</small>
                            @endif
                        @elseif($item->description)
                            <strong>{{ $item->description }}</strong>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}@if($item->product && $item->product->unit) {{ $item->product->unit }}@endif</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }} RSD</td>
                    <td class="text-right">{{ number_format($item->total, 2) }} RSD</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td>{{ number_format($invoice->subtotal, 2) }} RSD</td>
            </tr>
            <tr>
                <td>VAT (20%):</td>
                <td>{{ number_format($invoice->tax_amount, 2) }} RSD</td>
            </tr>
            <tr class="total-row">
                <td>Total:</td>
                <td>{{ number_format($invoice->total, 2) }} RSD</td>
            </tr>
        </table>
    </div>

    @if($invoice->notes)
        <div class="footer" style="clear: both; margin-top: 30px;">
            <h4>Notes:</h4>
            <p>{{ $invoice->notes }}</p>
        </div>
    @endif

    <div class="footer" style="clear: both; margin-top: 50px;">
        <p>This is a computer-generated invoice.</p>
    </div>
</body>
</html>

