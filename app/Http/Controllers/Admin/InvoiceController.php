<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\MiOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function generate($orderId)
    {
        $order = MiOrder::with([
            'items',
            'billFromParty',
            'billFromAddress',
            'billToParty',
            'billToAddress',
            'shipToParty',
            'shipToAddress',
        ])->findOrFail($orderId);

        $pdf = Pdf::loadView('order.tax-invoice', compact('order'))
            ->setPaper('A4', 'portrait');

        return $pdf->download('Invoice-'.$order->order_invoice_number.'.pdf');
        // OR ->stream() to preview in browser
    }
}
