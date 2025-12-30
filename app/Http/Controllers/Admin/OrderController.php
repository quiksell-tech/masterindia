<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\MiItem;
use App\Models\Admin\MiOrder;
use App\Models\Admin\MiCompany;
use App\Models\Admin\MiCompanyAddress;
use App\Models\Admin\MiOrderItem;
use App\Models\Admin\MiParty;
use App\Models\Admin\MiTransporter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class OrderController extends Controller
{
    public function index()
    {
        $orders = MiOrder::with('items')->latest()->paginate(10);

        $orders->getCollection()->transform(function ($order) {

            $totalSaleValue = 0;
            $totalTax       = 0;
            $totalAfterTax  = 0;

//            foreach ($order->items as $item) {
//
//                $taxableAmount = $item->total_item_quantity * $item->price_per_unit;
//                $taxAmount     = ($taxableAmount * $item->tax_percentage) / 100;
//                $afterTax      = $taxableAmount + $taxAmount;
//
//                $totalSaleValue += $taxableAmount;
//                $totalTax       += $taxAmount;
//                $totalAfterTax  += $afterTax;
//            }

            // attach calculated values to model (runtime only)
//            $order->total_sale_value = round($totalSaleValue, 2);
//            $order->total_tax        = round($totalTax, 2);
//            $order->total_after_tax  = round($totalAfterTax, 2);

            return $order;
        });

        return view('order.index', compact('orders'));
    }

    public function create()
    {
        $transporters = MiTransporter::orderBy('name', 'desc')->get();;
        return view('order.create', compact('transporters'));
    }

    public function store(Request $request)
    {

    $rules = [
        'transporter_id'     => ['required', 'string'],
        'order_invoice_date'     => ['required', 'string'],
        'bill_from_party_id'   => ['required', 'integer'],
        'bill_from_address_id' => ['required', 'integer'],
        'bill_to_party_id'     => ['required', 'integer'],
        'bill_to_address_id'   => ['required', 'integer'],
        'supply_type'          => ['required', Rule::in(['outward', 'inward'])],
    ];

    // 🔁 Conditional validation for Inward
    if (strtolower($request->supply_type) === 'inward') {
        $rules['order_invoice_number'] = ['required', 'string', 'max:50'];
    }
        $validated = $request->validate($rules);
        $order = [];
        $order['transporter_gstn']     = $validated['transporter_gstn'];
        $order['bill_from_party_id']   = $validated['bill_from_party_id'];
        $order['bill_from_address_id'] = $validated['bill_from_address_id'];
        $order['bill_to_party_id']     = $validated['bill_to_party_id'];
        $order['bill_to_address_id']   = $validated['bill_to_address_id'];
        $order['supply_type']          = $validated['supply_type'];

        if ($validated['supply_type'] === 'inward') {
            $order['order_invoice_number'] = $validated['order_invoice_number'];
        }else{
            $order['bill_to_invoice_number'] = 'sasa'.rand(100000,999999);
        }

        $order['sub_supply_type'] =$request->sub_supply_type;
        $order['document_type'] =$request->document_type;
        $order['transportation_mode'] =$request->transportation_mode;
        $order['vehicle_type'] =$request->vehicle_type;
        $order['vehicle_no'] =$request->vehicle_no;
        $order['transporter_id'] =$request->transporter_id;
        $order['transporter_name'] =$request->transporter_name;
        $order['order_invoice_date'] =$request->order_invoice_date;
        $order['is_active'] ='Y';


        if (!empty($request->dispatch_from_address_id)) {

            $order['dispatch_from_address_id'] =$request->dispatch_from_address_id;
            $order['dispatch_from_party_id'] = $request->dispatch_from_party_id;
        }
        if (!empty($request->ship_to_address_id)) {

            $order['ship_to_address_id'] = $request->ship_to_address_id;
            $order['ship_to_party_id'] = $request->ship_to_address_id;
        }

        $order=MiOrder::create($order);


        return redirect()
            ->route('orders.edit', $order->order_id)
            ->with('success', 'Order created successfully. You can continue editing.');
    }

    public function edit(MiOrder $order)
    {
        $items = MiOrderItem::where('order_id', $order->order_id)->get();
        $allItems  = MiItem::where('is_active', 'Y')->orderBy('item_name')->get();
        $transporters = MiTransporter::orderBy('name', 'desc')->get();
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
            ->where('order_id', $order->order_id)
            ->firstOrFail();

        return view('order.edit', compact('order', 'order','items','allItems','transporters'));
    }

    public function update(Request $request, MiOrder $order)
    {
        $rules = [
            'transporter_id'     => ['required', 'string'],
            'bill_from_party_id'   => ['required', 'integer'],
            'bill_from_address_id' => ['required', 'integer'],
            'bill_to_party_id'     => ['required', 'integer'],
            'bill_to_address_id'   => ['required', 'integer'],
            'order_invoice_number'   => ['required', 'string'],
            'order_invoice_date'   => ['required', 'string'],
            'supply_type'          => ['required', Rule::in(['outward', 'inward'])],
        ];


        $validated = $request->validate($rules);
        $data = [];
        $data['transporter_id']       = $validated['transporter_id'];
        $data['bill_from_party_id']   = $validated['bill_from_party_id'];
        $data['bill_from_address_id'] = $validated['bill_from_address_id'];
        $data['bill_to_party_id']     = $validated['bill_to_party_id'];
        $data['bill_to_address_id']   = $validated['bill_to_address_id'];
        $data['supply_type']          = $validated['supply_type'];
        $data['order_invoice_number'] = $validated['order_invoice_number'];
        $data['order_invoice_date']   = $validated['order_invoice_date'];

        $data['sub_supply_type'] =$request->sub_supply_type;
        $data['document_type'] =$request->document_type;
        $data['transportation_mode'] =$request->transportation_mode;
        $data['vehicle_type'] =$request->vehicle_type;
        $data['vehicle_no'] =$request->vehicle_no;

        $data['transporter_name'] =$request->transporter_name;

        $data['is_active'] ='Y';


        if (!empty($request->dispatch_from_address_id)) {

            $data['dispatch_from_address_id'] =$request->dispatch_from_address_id;
            $data['dispatch_from_party_id'] = $request->dispatch_from_party_id;
        }
        if (!empty($request->ship_to_address_id)) {

            $data['ship_to_address_id'] = $request->ship_to_address_id;
            $data['ship_to_party_id'] = $request->ship_to_address_id;
        }

        $order->update($data);

        // ✅ Redirect back to edit page
        return redirect()
            ->route('orders.edit', $order->order_id)
            ->with('success', 'Order updated successfully');
    }


    /* ================= AJAX ================= */

    public function searchParty(Request $request)
    {

        return MiParty::where('is_active', 'Y')
            ->where(function ($q) use ($request) {
                $q->where('party_trade_name', 'like', "%{$request->q}%")
                    ->orWhere('party_gstn', 'like', "%{$request->q}%");
            })
            ->get([
                'party_id',
                'company_id',
                'party_trade_name',
                'party_gstn'
            ]);
    }

    public function companyAddresses($companyId)
    {
        return MiCompanyAddress::where('company_id', $companyId)
            ->get(['address_id', 'address_line', 'city', 'state', 'pincode','party_id']);
    }
}
