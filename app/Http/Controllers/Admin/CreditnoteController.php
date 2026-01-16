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
        ]);
        $return_date = Carbon::createFromFormat('d-M-Y', $request->return_date)->format('Y-m-d');

            /** Update credit note */
            $creditnote = MiCreditnoteTransaction::where('creditnote_id', $creditnoteId)
                ->firstOrFail();

            $creditnote->update([

                'return_date'=> $return_date,
            ]);

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
            ->route('creditnote.index')
            ->with('success', 'Credit note updated successfully');
    }

}
