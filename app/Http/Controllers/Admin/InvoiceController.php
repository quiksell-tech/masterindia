<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\MiInwardOrder;
use App\Models\Admin\MiOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\MiCreditnoteTransaction;
use App\Models\Admin\MiCreditnoteItem;
use \NumberFormatter;
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
        $amountWords=$this-> amount_in_words_pdf($order->total_after_tax);
        $pdf = Pdf::loadView('order.tax-invoice', compact('order', 'amountWords'))
            ->setPaper('A4', 'portrait');

        return $pdf->download('Invoice-'.$order->order_invoice_number.'.pdf');
        // OR ->stream() to preview in browser
    }

    public function inwardOrderTaxInvocie($orderId)
    {
        $order = MiInwardOrder::with([
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
        $amountWords=$this-> amount_in_words_pdf($order->total_after_tax);
        $pdf = Pdf::loadView('inwardorders.tax-invoice', compact('order', 'amountWords'))
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
        $amountWords=$this-> amount_in_words_pdf($creditnote->total_after_tax);
        $pdf = Pdf::loadView('creditnote.creditnote-tax-invoice', compact('order', 'creditnote', 'amountWords'))
            ->setPaper('A4', 'portrait');

        return $pdf->download('Invoice-'.$creditnote->creditnote_invoice_no.'.pdf');
        // OR ->stream() to preview in browser
    }


    function amount_in_words_pdf($amount)
    {
        $amount = number_format((float)$amount, 2, '.', '');

        $rupees = (int) $amount;
        $paise  = (int) round(($amount - $rupees) * 100);

        $formatter = new NumberFormatter('en_IN', NumberFormatter::SPELLOUT);

        $rupeesWords = ucwords(strtolower($formatter->format($rupees)));
        $rupeesWords = str_replace('-', ' ', $rupeesWords);

        $words = 'Rupees ' . $rupeesWords;

        if ($paise > 0) {
            $paiseWords = ucwords(strtolower($formatter->format($paise)));
            $paiseWords = str_replace('-', ' ', $paiseWords);

            $words .= ' and ' . $paiseWords . ' Paise';
        }

        return $words . ' Only';
    }

}
