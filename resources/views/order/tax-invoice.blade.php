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
            <strong>Name:</strong> {{ $order->billToParty->party_trade_name }}<br>
            <strong>Mobile:</strong> {{ $order->billToParty->phone }}<br>
            <strong>Email:</strong> {{ $order->billToParty->email }}<br>
            <strong>GST:</strong> {{ $order->billToParty->party_gstn }}<br>
            <strong>Address:</strong>

            {{ $order->billToAddress->address_line }}<br>
            {{ $order->billToAddress->city }},
            {{ $order->billToAddress->state }} - {{ $order->billToAddress->pincode }}<br>
            <strong>Place of Supply:</strong> {{ $order->billToAddress->state }}<br>
            <strong>Place of Supply:</strong> {{ $order->billToAddress->state_code }}
        </td>
        <td>
            @if(!empty($order->shipToParty->ship_to_party_id))

                <strong>Name:</strong> {{ $order->shipToParty->party_trade_name }}<br>
                <strong>Mobile:</strong> {{ $order->shipToParty->phone }}<br>
                <strong>Email:</strong> {{ $order->shipToParty->email }}<br>
                <strong>GST:</strong> {{ $order->shipToParty->party_gstn }}<br>
                <strong>Address:</strong>

                {{ $order->shipToAddress->address_line }}<br>
                {{ $order->shipToAddress->city }},
                {{ $order->shipToAddress->state }} - {{ $order->shipToAddress->pincode }}<br>
                <strong>Place of Supply:</strong> {{ $order->shipToAddress->state }}<br>
                <strong>Place of Supply:</strong> {{ $order->shipToAddress->state_code }}
            @else
                <strong>Name:</strong> {{ $order->billToParty->party_trade_name }}<br>
                <strong>Mobile:</strong> {{ $order->billToParty->phone }}<br>
                <strong>Email:</strong> {{ $order->billToParty->email }}<br>
                <strong>GST:</strong> {{ $order->billToParty->party_gstn }}<br>
                <strong>Address:</strong>

                {{ $order->billToAddress->address_line }}<br>
                {{ $order->billToAddress->city }},
                {{ $order->billToAddress->state }} - {{ $order->billToAddress->pincode }}<br>
                <strong>Place of Supply:</strong> {{ $order->billToAddress->state }}<br>
                <strong>Place of Supply:</strong> {{ $order->billToAddress->state_code }}
            @endif

        </td>
    </tr>
    <tr>
        <td><span class="text-center" ><strong>Dispatch From:</strong></span><br>
        @if(!empty($order->dispatchFromParty->dispatch_from_party_id))
                <strong>Name:</strong> {{ $order->dispatchFromParty->party_trade_name }}<br>
                <strong>Mobile:</strong> {{ $order->dispatchFromParty->phone }}<br>
                <strong>Email:</strong> {{ $order->dispatchFromParty->email }}<br>
                <strong>GST:</strong> {{ $order->dispatchFromParty->party_gstn }}<br>
                <strong>Address:</strong>

                {{ $order->dispatchFromAddress->address_line }}<br>
                {{ $order->dispatchFromAddress->city }},
                {{ $order->dispatchFromAddress->state }} - {{ $order->dispatchFromAddress->pincode }}<br>
                <strong>Place of Supply:</strong> {{ $order->dispatchFromAddress->state }}<br>
                <strong>Place of Supply:</strong> {{ $order->dispatchFromAddress->state_code }}
            @else
{{--                <strong>Name:</strong> {{ $order->billFromParty->party_trade_name }}<br>--}}
{{--                <strong>Mobile:</strong> {{ $order->billFromParty->phone }}<br>--}}
{{--                <strong>Email:</strong> {{ $order->billFromParty->email }}<br>--}}
{{--                <strong>GST:</strong> {{ $order->billFromParty->party_gstn }}<br>--}}
{{--                <strong>Address:</strong>--}}

{{--                {{ $order->billFromAddress->address_line }}<br>--}}
{{--                {{ $order->billFromAddress->city }},--}}
{{--                {{ $order->billFromAddress->state }} - {{ $order->billFromAddress->pincode }}<br>--}}
{{--                <strong>Place of Supply:</strong> {{ $order->billFromAddress->state }}<br>--}}
{{--                <strong>Place of Supply:</strong> {{ $order->billFromAddress->state_code }}--}}
        @endif
        </td>
        <td></td>
    </tr>
</table>

<br>

<table>
    <thead>
    <tr>
        <th>S.No</th>
        <th>HSN</th>
        <th>ItmCode</th>
        <th>Name</th>
        <th>Description</th>
        <th>Qty</th>
        <th>Unit Price</th>
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($order->items as $i => $item)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $item->hsn_code }}</td>
            <td>{{ $item->item_code }}</td>
            <td>{{ $item->item_name }}</td>
            <td>{{ $item->item_description }}</td>
            <td class="right">{{ $item->total_item_quantity }}</td>
            <td class="right">{{ number_format($item->price_per_unit, 2) }}</td>
            <td class="right">{{ number_format(($item->total_item_quantity*$item->price_per_unit), 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<br>

<table>
    <tr>
        <td class="right"><strong>Total Tax(@18%):</strong></td>
        <td class="right">{{ number_format($order->total_tax, 2) }}</td>
    </tr>
    <tr>
        <td class="right"><strong>Total Value:</strong></td>
        <td class="right">{{ number_format($order->total_after_tax, 2) }}</td>
    </tr>
</table>
<table>
    <thead>
    <tr>

        <th>GST Bifurcation</th>
        <th>CGST</th>
        <th>SGST</th>
        <th>IGST</th>
        <th>Total GST</th>
    </tr>
    </thead>
    <tbody>
        <tr>
        <td>-</td>
        <td>0</td>
        <td>0</td>
        <td>{{ number_format($order->total_tax, 2) }}</td>
        <td>{{ number_format($order->total_tax, 2) }}</td>
        </tr>
    </tbody>
</table>

<p><strong>Amount in Words:</strong> {{ ucfirst(\NumberFormatter::create('en_IN', NumberFormatter::SPELLOUT)->format($order->total_after_tax)) }} Rupees Only</p>

<hr>

<p style="text-align:center;">
    This is a computer generated invoice and does not require signature.
</p>

</body>
</html>
