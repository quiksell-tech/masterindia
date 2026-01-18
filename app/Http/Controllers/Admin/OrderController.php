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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class OrderController extends Controller
{

    public function __construct() {



    }
    public function index()
    {
        $orders = MiOrder::with('items')->latest()->paginate(10);
        return view('order.index', compact('orders'));
    }

    public function create()
    {
        $transporters = MiTransporter::orderBy('name', 'desc')->get();
        $billFromAddress=MiCompanyAddress::where('address_id','1')->first();
        $billFromParty=MiParty::where('party_id','1')->first();

        // Get latest order_invoice_date
        $latestDate = MiOrder::max('order_invoice_date');

        $today = now()->format('Y-m-d');
        $lastDateOfMonth = now()->endOfMonth()->format('Y-m-d');

        if ($latestDate) {
            // ✅ Use latest invoice date as-is
            $latestDate = Carbon::parse($latestDate)->format('Y-m-d');
        } else {
            // ✅ First order case
            $latestDate = Carbon::today()->format('Y-m-d');
        }

        $defaultDate = $today < $latestDate ? $latestDate : $today;


        return view('order.create', compact('transporters','billFromAddress','billFromParty','today','latestDate','lastDateOfMonth','defaultDate'));
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
            'vehicle_no' => [
                'nullable',
                Rule::requiredIf(fn () => $request->transporter_id != 'NO_DETAIL'),
                'string',
                'max:20'
                ],
            ];

        if (strtolower($request->supply_type) === 'inward') {

            $rules['order_invoice_number'] = [
                'required',
                'string',
                'max:50',
                Rule::unique('mi_orders', 'order_invoice_number'),
            ];
        }

        $validated = $request->validate($rules);
        $order = [];
        $order['transporter_id']     = $validated['transporter_id'];
        $order['bill_from_party_id']   = $validated['bill_from_party_id'];
        $order['bill_from_address_id'] = $validated['bill_from_address_id'];
        $order['bill_to_party_id']     = $validated['bill_to_party_id'];
        $order['bill_to_address_id']   = $validated['bill_to_address_id'];
        $order['supply_type']          = $validated['supply_type'];

        if ($validated['supply_type'] === 'inward') {

            $order['order_invoice_number'] = $validated['order_invoice_number'];

        }else{

            $invoiceData = Miorder::generateInvoiceNumber();
            $order['order_invoice_number'] =$invoiceData['invoice_no'];
            $order['financial_year'] =$invoiceData['financial_year'];
            $order['invoice_sequence_no'] =$invoiceData['sequence_no'];
        }

        $order['sub_supply_type'] =$request->sub_supply_type;
        $order['document_type'] =$request->document_type;
        $order['transportation_mode'] =$request->transportation_mode;
        $order['vehicle_type'] =$request->vehicle_type;
        $order['vehicle_no'] =$request->vehicle_no;
        //$order['transporter_id'] =$request->transporter_id;
        $order['transporter_name'] =$request->transporter_name;


        $order_invoice_date = Carbon::createFromFormat('d-M-Y', $request->order_invoice_date)
            ->format('Y-m-d');

        $order['order_invoice_date'] =$order_invoice_date;
        $order['transporter_document_no'] =$request->transporter_document_no;
        if(!empty($request->transportation_date))
        {
            $transportation_date = Carbon::createFromFormat('d-M-Y', $request->transportation_date)
                ->format('Y-m-d');
            $order['transportation_date'] =$transportation_date;
        }

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
        // Get latest order_invoice_date
        $latestDate = MiOrder::max('order_invoice_date');
        $orderInvoiceDate = Carbon::parse($order->order_invoice_date)->format('Y-m-d');
        $today = now()->format('Y-m-d');
        $lastDateOfMonth = now()->endOfMonth()->format('Y-m-d');

        if ($latestDate) {
            // ✅ Use latest invoice date as-is
            $latestDate = Carbon::parse($latestDate)->format('Y-m-d');
        } else {
            // ✅ First order case
            $latestDate = Carbon::today()->format('Y-m-d');
        }
        $defaultDate = $today > $latestDate ? $latestDate : $today;

        if($defaultDate>$orderInvoiceDate)
        {
            $defaultDate=$orderInvoiceDate;
        }

        return view('order.edit', compact('order', 'order','items','allItems','transporters','latestDate','today','latestDate','lastDateOfMonth','defaultDate','orderInvoiceDate'));
    }
    public function invoiceData(MiOrder $order)
    {
        $order->load([
            'billFromParty:party_id,party_trade_name,phone,email',
            'billToParty:party_id,party_trade_name,phone,email',
            'shipToParty:party_id,party_trade_name,phone,email',
            'dispatchFromParty:party_id,party_trade_name,phone,email',

            'billFromAddress',
            'billToAddress',
            'shipToAddress',
            'dispatchFromAddress',
        ]);

        $orderData=[
            'order_invoice_number'=>$order->order_invoice_number,
            'transporter_name'=>$order->transporter_name,
            'transporter_id'=>$order->transporter_id,
            'supply_type'=>$order->supply_type,
            'sub_supply_type'=>$order->sub_supply_type,
            'document_type'=>$order->document_type,
            'transportation_mode'=>$order->transportation_mode,
            'order_invoice_date'=>$order->order_invoice_date,
            'vehicle_type'=>$order->vehicle_type,
            'vehicle_no'=>$order->vehicle_no,
            'total_sale_value'=>$order->total_sale_value,
            'total_tax'=>$order->total_tax,
            'total_after_tax'=>$order->total_after_tax,

            'bill_from' => $this->renderAddress(

                $order->billFromParty->party_trade_name,
                $order->billFromAddress->city,
                $order->billFromAddress->state,
                $order->billFromAddress->pincode,
                $order->billFromAddress->address_line,
                $order->billFromParty->email,
                $order->billFromParty->phone
            ),

            'bill_to' => $this->renderAddress(
                optional($order->billToParty)->party_trade_name,
                optional($order->billToAddress)->city,
                optional($order->billToAddress)->state,
                optional($order->billToAddress)->pincode,
                optional($order->billToAddress)->address_line,
                optional($order->billToParty)->email,
                optional($order->billToParty)->phone
            ),


            'ship_to' => $this->renderAddress(
                optional($order->shipToParty)->party_trade_name,
                optional($order->shipToAddress)->city,
                optional($order->shipToAddress)->state,
                optional($order->shipToAddress)->pincode,
                optional($order->shipToAddress)->address_line,
                optional($order->shipToParty)->email,
                optional($order->shipToParty)->phone
            ),


            'dispatch_from' => $this->renderAddress(

                optional($order->dispatchFromParty)->party_trade_name,
                optional($order->dispatchFromAddress)->city,
                optional($order->dispatchFromAddress)->state,
                optional($order->dispatchFromAddress)->pincode,
                optional($order->dispatchFromAddress)->address_line,
                optional($order->dispatchFromParty)->email,
                optional($order->dispatchFromParty)->phone
            ),



        ];

        return response()->json($orderData);
    }
    public function update(Request $request, MiOrder $order)
    {
        $rules = [
            'transporter_id'     => ['required', 'string'],
            'order_invoice_date'    => ['required', 'string'],
            'order_invoice_number'  => ['required', 'string', 'max:50'],
            'bill_from_party_id'   => ['required', 'integer'],
            'bill_from_address_id' => ['required', 'integer'],
            'bill_to_party_id'     => ['required', 'integer'],
            'bill_to_address_id'   => ['required', 'integer'],
            'supply_type'          => ['required', Rule::in(['outward', 'inward'])],
            'vehicle_no' => [
                'nullable',
                Rule::requiredIf(fn () => $request->transporter_id == 'NO_GSTN'),
                'string',
                'max:20'
            ],
        ];

        if ($request->supply_type === 'inward') {

            $rules['order_invoice_number'] = [
                'required',
                'string',
                'max:50',

                // UNIQUE except current record
                Rule::unique('mi_order', 'order_invoice_number')
                    ->ignore($order->id),
            ];
         }

        $validated = $request->validate($rules);
        $data = [];
        $data['transporter_id']       = $validated['transporter_id'];
        $data['bill_from_party_id']   = $validated['bill_from_party_id'];
        $data['bill_from_address_id'] = $validated['bill_from_address_id'];
        $data['bill_to_party_id']     = $validated['bill_to_party_id'];
        $data['bill_to_address_id']   = $validated['bill_to_address_id'];
        $data['supply_type']          = $validated['supply_type'];

        $order_invoice_date = Carbon::createFromFormat('d-M-Y', $validated['order_invoice_date'])
            ->format('Y-m-d');

        $order['order_invoice_date'] =$order_invoice_date;

        $data['sub_supply_type'] =$request->sub_supply_type;
        $data['document_type'] =$request->document_type;
        $data['transportation_mode'] =$request->transportation_mode;
        $data['vehicle_type'] =$request->vehicle_type;
        $data['vehicle_no'] =$request->vehicle_no;

        $data['transporter_name'] =$request->transporter_name;
        $order['transporter_document_no'] =$request->transporter_document_no;
        if(!empty($request->transportation_date))
        {
            $transportation_date = Carbon::createFromFormat('d-M-Y', $request->transportation_date)
                ->format('Y-m-d');
            $order['transportation_date'] =$transportation_date;
        }
        $data['is_active'] ='Y';

        if ($validated['supply_type'] === 'inward') {

            $data['order_invoice_number'] = $validated['order_invoice_number'];
        }

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
                'party_gstn',
                DB::raw('SUBSTRING(party_gstn, 1, 2) as state_code')
            ]);
    }

    public function companyAddresses($companyId,$partyId)
    {
        return MiCompanyAddress::where('company_id', $companyId)
            //->where('party_id', $partyId)
            ->where('is_active','Y')->get(['address_id', 'address_line', 'city', 'state', 'pincode','party_id']);
    }
    protected function renderAddress(
        $name,
        $city,
        $state,
        $pincode,
        $address_line,
        $email = null,
        $phone = null
    ) {
        return view('order.address', [
            'name'         => $name,
            'address_line' => $address_line,
            'city'         => $city,
            'state'        => $state,
            'pincode'      => $pincode,
            'email'        => $email,
            'phone'        => $phone,
        ])->render();
    }


}
