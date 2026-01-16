<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\MiOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\MiCreditnoteTransaction;
use App\Models\Admin\MiCreditnoteItem;
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
            'dispatchFromParty',
            'dispatchFromAddress',
        ])->findOrFail($orderId);

        $pdf = Pdf::loadView('order.tax-invoice', compact('order'))
            ->setPaper('A4', 'portrait');

        return $pdf->download('Invoice-'.$order->order_invoice_number.'.pdf');
        // OR ->stream() to preview in browser
    }
    public function generateCreditNoteInvoce($creditnoteId)
    {
        $creditnote = MiCreditnoteTransaction::with('items')
            ->where('creditnote_id', $creditnoteId)
            ->firstOrFail();

        $order = MiOrder::with([

            'billFromParty',
            'billFromAddress',
            'billToParty',
            'billToAddress',
            'shipToParty',
            'shipToAddress',
            'dispatchFromParty',
            'dispatchFromAddress',
        ])->findOrFail($creditnote->order_id);

        $pdf = Pdf::loadView('creditnote.creditnote-tax-invoice', compact('order', 'creditnote'))
            ->setPaper('A4', 'portrait');

        return $pdf->download('Invoice-'.$creditnote->creditnote_invoice_no.'.pdf');
        // OR ->stream() to preview in browser
    }

}
