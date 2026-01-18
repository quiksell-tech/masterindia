<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\MiOrderItem;
use App\Models\Admin\MiItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
        public function searchItems(Request $request)
        {
            return MiItem::where('item_name', 'like', "%{$request->q}%")
            ->where('is_active', 'Y')
            ->get(['item_id','item_name']);
        }


    public function saveItems(Request $request, $orderId)
    {
        // Delete existing items (optional – common approach)
        MiOrderItem::where('order_id', $orderId)->delete();


        if (!empty($request->items)) {
            foreach ($request->items as $item) {

                // skip empty rows
                if (empty($item['item_id']) || empty($item['price_per_unit']) || empty($item['total_item_quantity'])) {
                    continue;
                }
                $itemData = MiItem::select(
                    'item_name',
                    'item_description',
                    'item_code',
                    'hsn_code',
                    'tax_percentage'
                )
                    ->where('item_id', $item['item_id'])
                    ->first();


                if (empty($itemData)) {
                    continue;
                }

                MiOrderItem::create([
                    'order_id'            => $orderId,
                    'item_id'             => $item['item_id'],
                    'total_item_quantity' => $item['total_item_quantity'] ?? 0,
                    'item_unit'           => $item['item_unit'] ?? null,
                    'price_per_unit'      => $item['price_per_unit'] ?? 0,
                    'after_tax_value'     => $item['after_tax_value'] ?? 0,
                    'taxable_amount'      => $item['taxable_amount'] ?? 0,
                    'item_name'           => $itemData->item_name ,
                    'item_description'    => $itemData->item_description ,
                    'item_code'           => $itemData->item_code ,
                    'hsn_code'            => $itemData->hsn_code ,
                    'tax_percentage'      => $itemData->tax_percentage ?? 0,

                ]);
            }
        }

        // 3️⃣ Redirect back to edit page
        return redirect()
            ->route('orders.edit', $orderId)
            ->with('success', 'Order items saved successfully');
    }


    public function destroy(MiOrderItem $item)
    {
        $item->delete();
        return back()->with('success', 'Item removed');
    }
}
