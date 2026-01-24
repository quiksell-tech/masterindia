<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Admin\MiItem;
use App\Models\Admin\MiOrder;
use App\Models\Admin\MiOrderItem;
use App\Models\Admin\MiTransporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MiCreditnoteTransaction;
use App\Models\Admin\MiCreditnoteItem;

class CreditnoteController extends Controller
{
    /**
     * List credit notes
     * View: creditnote/index
     */
    public function index()
    {
        $creditnotes = MiCreditnoteTransaction::orderByDesc('creditnote_id')->latest()->paginate(10);;

        return view('creditnote.index', compact('creditnotes'));
    }

    /**
     * Edit credit note with items
     * View: creditnote/edit
     */
    public function edit($creditnoteId)
    {
        $creditnote = MiCreditnoteTransaction::with('items')
            ->where('creditnote_id', $creditnoteId)
            ->firstOrFail();

        return view('creditnote.edit', compact('creditnote'));
    }

    /**
     * Store / Update credit note and items
     * - Updates credit note
     * - Deletes all old items
     * - Inserts new items
     */
    public function store(Request $request, $creditnoteId)
    {
        $request->validate([

            'items'                 => 'required|array|min:1',
            'items.*.item_name'     => 'required',
            'items.*.total_item_quantity' => 'required|numeric',
            'items.*.price_per_unit'       => 'required|numeric',
            'return_date'       => 'required|string',
        ]);
        $return_date = Carbon::createFromFormat('d-M-Y', $request->return_date)->format('Y-m-d');

            /** Update credit note */
            $creditnote = MiCreditnoteTransaction::where('creditnote_id', $creditnoteId)->firstOrFail();
        // create new creditnote_invoice_no by add A if IRN is Cancelled


            $creditnoteUpdateData = [
            'return_date'=> $return_date,
            ];

            if ($creditnote->credit_note_status=='X' && !empty($creditnote->creditnote_irn_no) )
            {
                $creditnote_invoice_no= MiCreditnoteTransaction::incrementInvoiceSuffix($creditnote->creditnote_invoice_no);
                $creditnoteUpdateData['creditnote_invoice_no']=$creditnote_invoice_no;
                $creditnoteUpdateData['credit_note_status']='M';

            }

            $creditnote->update($creditnoteUpdateData);
            /** Delete all old items */
            MiCreditnoteItem::where('creditnote_id', $creditnoteId)->delete();

            /** Insert fresh items */
            $items = [];
            foreach ($request->items as $item) {
                $items[] = [
                    'creditnote_id'        => $creditnoteId,
                    'item_id'              => $item['item_id'] ?? null,
                    'item_name'            => $item['item_name'],
                    'item_description'     => $item['item_description'] ?? null,
                    'item_code'            => $item['item_code'] ?? null,
                    'hsn_code'             => $item['hsn_code'] ?? null,
                    'item_unit'            => $item['item_unit'] ?? null,
                    'total_item_quantity'  => $item['total_item_quantity'],
                    'price_per_unit'       => $item['price_per_unit'],
                    'tax_percentage'       => $item['tax_percentage'] ?? 0,
                    'taxable_amount'       => $item['taxable_amount'] ?? 0,
                    'after_tax_value'      => $item['after_tax_value'] ?? 0,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ];
            }

            MiCreditnoteItem::insert($items);

        return redirect()
            ->route('creditnote.edit',$creditnote->creditnote_id)
            ->with('success', 'Credit note updated successfully');
    }
    public function createNewCreditNote(Request $request, $order_id)
    {
        // pump new from Miorder to generate partial Credit note For Order
        $items = MiOrderItem::where('order_id', $order_id)->get();

        $order = MiOrder::with([
            'billFromParty:party_id,party_trade_name',
            'billToParty:party_id,party_trade_name',
            'shipToParty:party_id,party_trade_name',
            'dispatchFromParty:party_id,party_trade_name',

            'billFromAddress',
            'billToAddress',
            'shipToAddress',
            'dispatchFromAddress',
        ])
            ->where('order_id', $order_id)
            ->first();

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order Not found', 'data' => []]);
        }

        $creditnoteInvoice = MiCreditnoteTransaction::generateInvoiceNumberForCreditNote('CREW');
        $creditnote = MiCreditnoteTransaction::create([
            'creditnote_invoice_no' => $creditnoteInvoice['invoice_no'],
            'financial_year' => $creditnoteInvoice['financial_year'],
            'sequence_no' => $creditnoteInvoice['sequence_no'],
            'order_id' => $order_id,
            'order_invoice_number' => $order->order_invoice_number,
            'credit_note_status' => 'N',
            'return_type' => 'SALES_RETURN',
            'credit_note_date' => now(),
        ]);
        /** 4. Insert items from order items */
        $creditnoteItems = [];

        foreach ($items as $item) {
            $creditnoteItems[] = [
                'creditnote_id' => $creditnote->creditnote_id,
                'item_id' => $item->item_id,
                'item_name' => $item->item_name,
                'item_description' => $item->item_description,
                'item_code' => $item->item_code,
                'hsn_code' => $item->hsn_code,
                'item_unit' => $item->item_unit,
                'total_item_quantity' => $item->total_item_quantity,
                'price_per_unit' => $item->price_per_unit,
                'tax_percentage' => $item->tax_percentage,
                'taxable_amount' => $item->taxable_amount,
                'after_tax_value' => $item->after_tax_value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        MiCreditnoteItem::insert($creditnoteItems);
        return response()->json(['status' => 'success', 'message' => 'CreditNote data has been inserted', 'data' => ['creditnote_id' => $creditnote->creditnote_id]]);
    }

    public function addNewItemsToExitingCreditNote(Request $request, $creditnoteId)
    {
        $creditnote = MiCreditnoteTransaction::with('items')
            ->where('creditnote_id', $creditnoteId)
            ->first();

        if (!$creditnote) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order Not found',
                'data' => []
            ]);
        }

        // 1️⃣ Get already added item_ids in credit note
        $existingItemIds = $creditnote->items->pluck('item_id')->toArray();

        // 2️⃣ Get order items NOT present in credit note
        $items = MiOrderItem::where('order_id', $creditnote->order_id)
            ->whereNotIn('item_id', $existingItemIds)
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No new items to add',
                'data' => []
            ]);
        }

        // 3️⃣ Prepare insert data
        $newCreditnoteItems = [];

        foreach ($items as $item) {
            $newCreditnoteItems[] = [
                'creditnote_id' => $creditnote->creditnote_id,
                'item_id' => $item->item_id,
                'item_name' => $item->item_name,
                'item_description' => $item->item_description,
                'item_code' => $item->item_code,
                'hsn_code' => $item->hsn_code,
                'item_unit' => $item->item_unit,
                'total_item_quantity' => 0,
                'price_per_unit' => $item->price_per_unit,
                'tax_percentage' => $item->tax_percentage,
                'taxable_amount' => $item->taxable_amount,
                'after_tax_value' => $item->after_tax_value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // 4️⃣ Bulk insert
        MiCreditnoteItem::insert($newCreditnoteItems);

        // create new creditnote_invoice_no by add A if IRN is Cancelled
        if ($creditnote->credit_note_status=='X')
        {
            $creditnote_invoice_no= MiCreditnoteTransaction::incrementInvoiceSuffix($creditnote->creditnote_invoice_no);

            $creditnote->update([
                'credit_note_status' => 'M',
                'creditnote_invoice_no' => $creditnote_invoice_no,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'New items added to credit note successfully',
            'data' => [
                'inserted_count' => count($newCreditnoteItems)
            ]
        ]);
    }
    private function getNextSequence(string $previous)
    {
        $parts = explode('-', $previous);
        $count = count($parts);

        // Case 1: Initial invoice number (3 parts)
        if ($count === 3) {
            return $previous . '-01';
        }

        // Case 2: Already has a sequence (4 or more parts)
        if ($count >= 4) {
            $seq = array_pop($parts); // last part is sequence

            // Ensure the last part is numeric
            if (!is_numeric($seq)) {
                return false; // invalid format
            }

            $nextSeq = str_pad(((int)$seq) + 1, 2, '0', STR_PAD_LEFT);
            return implode('-', $parts) . '-' . $nextSeq;
        }

        // Anything else is invalid
        return false;
    }



}
