<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; }
        .title { font-size: 18px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; }
        .no-border td { border: none; }
        .right { text-align: right; }
    </style>
</head>
<body>

<div class="header">
    <div class="title">TAX INVOICE</div>
    <small>(Customer Copy)</small>
</div>

<hr>

<table class="no-border" width="100%">
    <tr>
        <!-- Company Details -->
        <td width="60%" valign="top">
            <strong>{{ config('company.name') }}</strong><br>

            {{ config('company.address.line') }}<br>
            {{ config('company.address.city') }},
            {{ config('company.address.state') }} - {{ config('company.address.pincode') }}<br><br>

            <strong>GSTIN:</strong> {{ config('company.gstin') }}<br>
            <strong>Phone:</strong> {{ config('company.phone') }}<br>
            <strong>Email:</strong> {{ config('company.email') }}
        </td>

        <!-- Invoice Meta -->
        <td width="40%" valign="top" align="right">
            <strong>Invoice No:</strong> {{ $order->order_invoice_number }}<br>
            <strong>Date:</strong> {{ \Carbon\Carbon::parse($order->order_invoice_date)->format('d-M-Y') }}
        </td>
    </tr>
</table>

<br>

<table>
    <tr>
        <th width="50%">Bill To</th>
        <th width="50%">Ship To</th>
    </tr>
    <tr>
        <td>
            <strong>{{ $order->billToParty->party_trade_name }}</strong><br>
            {{ $order->billToAddress->address_line }}<br>
            {{ $order->billToAddress->city }},
            {{ $order->billToAddress->state }} - {{ $order->billToAddress->pincode }}
        </td>
        <td>
            <strong>{{ $order->shipToParty->party_trade_name }}</strong><br>
            {{ $order->shipToAddress->address_line }}<br>
            {{ $order->shipToAddress->city }},
            {{ $order->shipToAddress->state }} - {{ $order->shipToAddress->pincode }}
        </td>
    </tr>
</table>

<br>

<table>
    <thead>
    <tr>
        <th>#</th>
        <th>HSN</th>
        <th>Description</th>
        <th>Qty</th>
        <th>Rate</th>
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($order->items as $i => $item)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $item->hsn_code }}</td>
            <td>{{ $item->item_name }}</td>
            <td class="right">{{ $item->quantity }}</td>
            <td class="right">{{ number_format($item->rate, 2) }}</td>
            <td class="right">{{ number_format($item->total, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<br>

<table>
    <tr>
        <td class="right"><strong>Total Tax:</strong></td>
        <td class="right">{{ number_format($order->total_tax, 2) }}</td>
    </tr>
    <tr>
        <td class="right"><strong>Total Value:</strong></td>
        <td class="right">{{ number_format($order->total_after_tax, 2) }}</td>
    </tr>
</table>

<p><strong>Amount in Words:</strong> {{ ucfirst(\NumberFormatter::create('en_IN', NumberFormatter::SPELLOUT)->format($order->total_after_tax)) }} Rupees Only</p>

<hr>

<p style="text-align:center;">
    This is a computer generated invoice and does not require signature.
</p>

</body>
</html>
