<?php

namespace App\Http\Controllers\Services;

use App\Models\Admin\MiOrderItem;
use App\Models\MasterIndiaEwayBillTransaction;
use App\Services\EwayBill\MasterIndiaService;
use App\Models\Admin\MiOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class EwayBillController extends Controller
{
    protected $masterIndiaService;
    protected $masterIndiaEwayBillTransaction;
    protected $company_gstn = '05AAABB0639G1Z8';
    protected array $cancellationReasons;
    protected array $extensionReasons;
    protected array $vehicleReasons;
    public function __construct(MasterIndiaService $masterIndiaService, MasterIndiaEwayBillTransaction $masterIndiaEwayBillTransaction)
    {

        $this->masterIndiaService = $masterIndiaService;
        $this->masterIndiaEwayBillTransaction = $masterIndiaEwayBillTransaction;
        $this->cancellationReasons = config('ewaybill.cancellation_reasons', []);
        $this->extensionReasons   = config('ewaybill.extension_reasons', []);
        $this->vehicleReasons     = config('ewaybill.vehicle_update_reasons', []);
    }


    /**
     * Generate Eway Bill
     *
     * Process works as below:
     * 1. Checks if eway bill is not created already
     * 2. Fetch summary from party_sell_order_summary
     * 3. Fetch transporter id from logistics_transporter  if not self-pickup (transporter_name = psos.tracking_partner_name)
     * 4. Fetch party details from party_details (party_id = psos.purchaser_party_id)
     * 5. Fetch company details from company_details (party_id = psos.seller_party_id)
     * 6. Fetch order details from private_db.eway_calc_v
     * 7. Validate party_gstin using Masterindia API
     *
     *
     * Update masterindia_ewaybill_transaction with response data
     * Update party_sell_order_summary fields eway_status=C/E  & eway_status_message
     *
     *
     * @header Content-Type application/x-www-form-urlencoded
     * @header Authorization {API Key Here}
     *
     * @bodyParam sell_invoice_ref_no integer required . Example: 213030
     * @bodyParam eway_service string required Supported Values are: MasterIndia. Example: MasterIndia
     *
     * @response scenario=success {
     * "success": true,
     * "message": "Eway bill has been created by........"
     * }
     *
     * @response 400 scenario=failed {
     * "success": false,
     * "message": "{error message}",
     *  }
     *
     * @response 422 scenario=failed {
     * "success": false,
     * "message": "Invalid or missing parameters"
     * "errors" : []
     * }
     *
     * @response 500 scenario=failed {
     * "success": false,
     * "message": "{error message}",
     *  }
     */
    public function generateEwayBill(Request $request, $order_id)
    {


        $items = MiOrderItem::where('order_id', $order_id)->get();
        $order = MiOrder::with([
            'billFromParty:party_id,party_trade_name,party_gstn,phone,party_legal_name',
            'billToParty:party_id,party_trade_name,party_gstn,phone,party_legal_name',
            'shipToParty:party_id,party_trade_name,party_gstn,phone,party_legal_name',
            'dispatchFromParty:party_id,party_trade_name,party_gstn,phone,party_legal_name',
            'billFromAddress',
            'billToAddress',
            'shipToAddress',
            'dispatchFromAddress',
        ])
            ->where('order_id', $order_id)
            ->first();

        if (!($order)) {

            return response()->json(['status' => 'error', 'message' => 'Order is not found', 'data' => []]);
        }

        if (empty($items)) {

            return response()->json(['status' => 'error', 'message' => 'Order Items Are not added', 'data' => []]);
        }
        if(empty($order->billFromAddress->address_id))
        {
            return response()->json(['status' => false, 'message' => 'please update bill FROM Address', 'data' => []]);
        }
        if(empty($order->billToAddress->address_id))
        {
            return response()->json(['status' => false, 'message' => 'please Update bill TO Address', 'data' => []]);
        }

        if($order->eway_status=='C' )
        {
            return response()->json(['status' => 'error', 'message' => 'Order EwayBill already Created', 'data' => []]);
        }

        if ($order) {

             //validate GSTN
            if (!empty($order->billToParty->party_gstn)) {
                if ($order->supply_type == 'outward') {
                    $valid = $this->masterIndiaService->getGSTINDetails([
                        'buyer_gstin' => $order->billToParty->party_gstn,
                        'sell_invoice_ref_no' => $order->order_invoice_number,
                        'company_gstin' => $this->company_gstn,
                    ]);

                } else {

                    $valid = $this->masterIndiaService->getGSTINDetails([
                        'buyer_gstin' => $order->billFromParty->party_gstn,
                        'sell_invoice_ref_no' => $order->order_invoice_number,
                        'company_gstin' => $this->company_gstn,
                    ]);
                }


                if ($valid instanceof Response) {
                    // update psos for error

                    $order->update([
                        'eway_status' => 'E',
                        'eway_status_message' => json_decode($valid->getContent(), true)['message'] ?? ''
                    ]);
                    return response()->json(['status' => 'error', 'message' => $valid->getContent(), true['message'] ?? '', 'data' => []]);
                }

                if ($valid['gstin_status'] != 'active') {

                    // set error to skip this record for batch process

                    $order->update([
                        'eway_status' => 'E',
                        'eway_status_message' => 'GSTIN not active: ' . ($valid['gstin_status'] ?? 'unknown')
                    ]);
                    return response()->json(['status' => 'error', 'message' => 'GSTIN not active: ' . ($valid['gstin_status'] ?? 'unknown'), 'data' => []]);

                }

            }

            foreach ($items as $item) {
                // In same state
                if ($order->billFromAddress->state_code == $order->billToAddress->state_code) {

                    $igst_rate = 0;
                    $sgst_rate = $item->tax_percentage / 2;
                    $cgst_rate = $item->tax_percentage / 2;
                } else {
                    $igst_rate = $item->tax_percentage;
                    $sgst_rate = 0;
                    $cgst_rate = 0;
                }
                $items_list[] = [
                    "product_name" => $item->item_name,
                    "product_description" => $item->item_description,
                    "hsn_code" => $item->hsn_code,
                    "unit_of_product" => $item->item_unit,  // to be discussed
                    "cgst_rate" => round($cgst_rate, 2),
                    "sgst_rate" => round($sgst_rate, 2),
                    "igst_rate" => round($igst_rate, 2),
                    "cess_rate" => 0,
                    "quantity" => $item->total_item_quantity,
                    "cessNonAdvol" => 0,
                    "taxable_amount" => round($item->taxable_amount, 2)
                ];
            }

            $ewayBillData = [

                "userGstin" => $this->company_gstn,
                "supply_type" => $order->supply_type,//"Outward",
                "sub_supply_type" => $order->sub_supply_type,//"Supply",
                //"sub_supply_description" => "sales from other country", // to be discussed
                "document_type" => $order->document_type,//"Tax Invoice",
                "document_number" => strtoupper($order->order_invoice_number),
                "document_date" => date('d/m/Y', strtotime($order->order_invoice_date)),
                "data_source" => 'erp',
                "other_value" => 0,//round($total_other_charges,2),  need jk to discuss
                "total_invoice_value" => round($order->total_after_tax, 2),
                "taxable_amount" => round($order->total_sale_value, 2),
                "cess_amount" => 0,
                "cess_nonadvol_value" => 0,
                "itemList" => $items_list
            ];

            if ($order->billFromAddress->state_code == $order->billToAddress->state_code) {


                $ewayBillData['cgst_amount'] = round($order->total_tax / 2, 2);
                $ewayBillData['sgst_amount'] = round($order->total_tax / 2, 2);
                $ewayBillData['igst_amount'] = 0;

            } else {
                $ewayBillData['igst_amount'] = round($order->total_tax / 2, 2);
                $ewayBillData['cgst_amount'] = 0;
                $ewayBillData['sgst_amount'] = 0;

            }


            if ($order->billFromParty) {
                $ewayBillData['gstin_of_consignor'] = $order->billFromParty->party_gstn;
                $ewayBillData['legal_name_of_consignor'] = $order->billFromParty->party_legal_name;
            }
            //  Address of consignor i.e. Seller
            if (!empty($order->dispatchFromAddress->address_id)) {
                $ewayBillData['address1_of_consignor'] = $order->dispatchFromAddress->address_line;
                $ewayBillData['address2_of_consignor'] = $order->dispatchFromAddress->address_line;
                $ewayBillData['place_of_consignor'] = strtoupper($order->dispatchFromAddress->city);
                $ewayBillData['pincode_of_consignor'] = $order->dispatchFromAddress->pincode;

                $ewayBillData['state_of_consignor'] = strtoupper($order->dispatchFromAddress->state);
                $ewayBillData['actual_from_state_name'] = strtoupper($order->dispatchFromAddress->state);

            } else {
                $ewayBillData['address1_of_consignor'] = $order->billFromAddress->address_line;
                $ewayBillData['address2_of_consignor'] = $order->billFromAddress->address_line;
                $ewayBillData['place_of_consignor'] = strtoupper($order->billFromAddress->city);
                $ewayBillData['pincode_of_consignor'] = $order->billFromAddress->pincode;
                $ewayBillData['state_of_consignor'] = strtoupper($order->billFromAddress->state);
                $ewayBillData['actual_from_state_name'] = strtoupper($order->billFromAddress->state);
            }
            // address of consignee i.e. purchaser/Buyer
            if ($order->billToParty) {
                $ewayBillData['gstin_of_consignee'] = $order->billToParty->party_gstn;
                $ewayBillData['legal_name_of_consignee'] = $order->billToParty->party_legal_name;
            }
            if (!empty($order->shipToAddress->address_id)) {
                $ewayBillData['address1_of_consignee'] = $order->shipToAddress->address_line;
                $ewayBillData['address2_of_consignee'] = $order->shipToAddress->address_line;
                $ewayBillData['place_of_consignee'] = strtoupper($order->shipToAddress->city);
                $ewayBillData['pincode_of_consignee'] = $order->shipToAddress->pincode;

                $ewayBillData['state_of_supply'] = strtoupper($order->shipToAddress->state);
                $ewayBillData['actual_to_state_name'] = strtoupper($order->shipToAddress->state);

            } else {

                $ewayBillData['address1_of_consignee'] = $order->billToAddress->address_line;
                $ewayBillData['address2_of_consignee'] = $order->billToAddress->address_line;
                $ewayBillData['place_of_consignee'] = strtoupper($order->billToAddress->city);
                $ewayBillData['pincode_of_consignee'] = $order->billToAddress->pincode;

                $ewayBillData['state_of_supply'] = strtoupper($order->billToAddress->state);
                $ewayBillData['actual_to_state_name'] = strtoupper($order->billToAddress->state);
            }
            $txnType= $this->resolveTransactionType($order);
            if ($txnType==5) {

                return response()->json(['status' => 'error', 'message' => 'Issue in valid transaction_type', 'data' => []]);

            }else{

                $ewayBillData['transaction_type'] =$txnType;
            }

            if( $order->transporter_id=='NO_DETAIL')
            {
                // No  Transporter Detail
                return response()->json(['status' => 'error', 'message' => 'Update Transporter Detail', 'data' => []]);
            }
            // check  no documet case
            if ($order->transporter_name == 'NO_GSTN') {
                // Self Pickup
                if(empty($order->vehicle_no))
                {
                    return response()->json(['status' => 'error', 'message' => 'Vehicle Number is required', 'data' => []]);
                }
                $ewayBillData['transportation_mode'] = $order->transportation_mode;
                $ewayBillData['vehicle_number'] = $order->vehicle_no;
                $ewayBillData['vehicle_type'] = $order->vehicle_type;

            } else {

                $ewayBillData['transporter_id'] = $order->transporter_id;// GSTN of transporter

            }

            $data=['order_invoice_number'=>$order->order_invoice_number];

            $response = $this->masterIndiaService->generateEwayBill($data,$ewayBillData);
            if ($response instanceof Response) {
                // update psos for error
                $message=json_decode($response->getContent(), true)['message'] ?? '';
                $order->update([
                    'eway_status' => 'E',
                    'eway_status_message' => $message
                ]);
                return response()->json(['status' => 'error', 'message' => $message, 'data' => []]);
            }

            $order->update([
                'eway_status' => 'C',
                'eway_status_message' => 'Ewaybill has been created  :' . ($response['display_message'] ?? ''),
                'eway_bill_no' => $response['message']['ewayBillNo']
            ]);

            $isRecordCreated = $this->masterIndiaEwayBillTransaction->create(
                [
                    'order_id' => $order->order_id,
                    'eway_bill_no' => $response['message']['ewayBillNo'],
                    'eway_bill_date' => date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $response['message']['ewayBillDate']))),
                    'valid_upto' => date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $response['message']['validUpto']))),
                    'eway_bill_url' => $response['message']['url'],
                    'ebill_status' => 'Created',
                    'alert_message' => $response['message']['alert'],
                    'request_id' => $response['requestId'],
                ]);

            if (!$isRecordCreated)
                return response()->json(['status' => 'error', 'message' => 'Ewaybill created but failed to save response', 'data' => []]);

            return response()->json(['status' => 'success', 'message' => 'EwayBill has been created by :' . ($response['display_message']) ?? '', 'data' => []]);
        }


        return response()->json(['status' => 'error', 'message' => 'Order Not found', 'data' => []]);

    }

    /**
     * Cancel Eway Bill
     *
     * Process works as below:
     * 1. Checks if eway bill is created for order
     * 2. Fetch summary from party_sell_order_summary
     * 3. Fetch company details from company_details (party_id = psos.seller_party_id)
     *
     * Update masterindia_ewaybill_transaction with eway_status,cancellation_reason,cancellation_remarks data
     * Update party_sell_order_summary fields eway_status = X  & eway_status_message
     *
     *
     * @header Content-Type application/x-www-form-urlencoded
     * @header Authorization {API Key Here}
     *
     * @bodyParam sell_invoice_ref_no integer required . Example: 213030
     * @bodyParam eway_service string required Supported Values are: MasterIndia. Example: MasterIndia
     * @bodyParam cancel_reason string required Supported Values are: duplicate,order-cancelled,incorrect-details,others. Example: incorrect-details
     * @bodyParam cancel_remarks string required Some text for cancellation. Example: Need to make correction in details
     *
     * @response scenario=success {
     * "success": true,
     * "message": "Eway bill has been cancelled at ........"
     * }
     *
     * @response 400 scenario=failed {
     * "success": false,
     * "message": "{error message}",
     *  }
     *
     * @response 422 scenario=failed {
     * "success": false,
     * "message": "Invalid or missing parameters"
     * "errors" : []
     * }
     *
     * @response 500 scenario=failed {
     * "success": false,
     * "message": "{error message}",
     *  }
     */
    public function cancelEwayBill(Request $request, $order_id)
    {
        $errors = Validator::make($request->all(),
            [

                'cancel_reason' => 'required|in:duplicate,order-cancelled,incorrect-details,others',
                'cancel_remark' => 'required',

            ])
            ->errors()
            ->toArray();
        if (count($errors)) {
            //return json_response(422, 'Invalid or Missing parameters', compact('errors'));

            return response()->json(['status' => false, 'message' => 'Invalid or Missing parameters', 'data' => []]);
        }
        $order = MiOrder::with([

            'billFromParty:party_id,party_trade_name,party_gstn,phone,party_legal_name',

        ])
            ->where('order_id', $order_id)
            ->first();

        if (!($order)) {

            return response()->json(['status' => 'error', 'message' => 'Order is not found', 'data' => []]);
        }

        if (empty( $order->eway_bill_no) || $order->eway_status != 'N') {

            return response()->json(['status' => 'error', 'message' => 'E-waybill No is required OR It has been Already Cancelled', 'data' => []]);
        }

        $params = [

            "userGstin" => $this->company_gstn,
            "eway_bill_number" => $order->eway_bill_no,
            "reason_of_cancel" => $this->cancellation_reasons[$request->cancel_reason] ?? 'Others',
            "cancel_remark" => $request->cancel_remark ?? '',
            "data_source" => "erp"
        ];

        $data=['order_invoice_number'=>$order->order_invoice_number];
        $response = $this->masterIndiaService->cancelEwayBill($data,$params);

        $isRecordUpdated = $this->masterIndiaEwayBillTransaction
            ->where('order_id', $order->order_id)
            ->update([
                'ebill_status'           => 'Cancelled',
                'cancellation_reason'    => $params['reason_of_cancel'],
                'cancellation_remarks'   => $params['cancel_remark'],
                'updated_at'             => now(), // optional but recommended
            ]);

        if (!$isRecordUpdated)
            return response()->json(['status' => 'error', 'message' => 'Eway Bill cancelled but failed to save response', 'data' => []]);

        $order->update([
            'eway_status' => 'X',
            'eway_status_message' => 'Ewaybill has been cancelled'
        ]);
        return response()->json(['status' => 'success', 'message' => 'Ewaybill has been cancelled', 'data' => []]);
    }

    /**
     * Update Eway Bill
     *
     * Process works as below:
     * 1. Checks if eway bill is created
     * 2. Fetch summary from party_sell_order_summary
     * 3. Fetch company details from company_details (party_id = psos.seller_party_id)
     *
     *
     * @header Content-Type application/x-www-form-urlencoded
     * @header Authorization {API Key Here}
     *
     * @bodyParam sell_invoice_ref_no integer required . Example: 213030
     * @bodyParam eway_service string required Supported Values are: MasterIndia. Example: MasterIndia
     * @bodyParam action string required Supported Values are: update-vehicle,update-transporter,extend-validity. Example: extend-validity
     * @bodyParam extension_reason string Required only if action = extend-validity, Supported Values are: natural-calamity,law-order,transshipment,accident,others. Example: transshipment
     * @bodyParam extension_remarks string Required only if action = extend-validity, Write some text. Example: MasterIndia
     * @bodyParam vehicle_update_reason string Required if action=vehicle-update Supported Values are: break-down,transshipment,others,first-time. Example: MasterIndia
     * @bodyParam vehicle_update_remarks string Required if action=vehicle-update Write some text. Example: MasterIndia
     *
     * @response scenario=success {
     * "success": true,
     * "message": "{message}>"
     * }
     *
     * @response 400 scenario=failed {
     * "success": false,
     * "message": "{error message}",
     *  }
     *
     * @response 422 scenario=failed {
     * "success": false,
     * "message": "Invalid or missing parameters"
     * "errors" : []
     * }
     *
     * @response 500 scenario=failed {
     * "success": false,
     * "message": "{error message}",
     *  }
     */
    public function updateEwayBill(Request $request, $order_id)
    {

        $errors = Validator::make($request->all(),
            [
                'action' => 'required|in:update-vehicle,update-transporter,extend-validity',
                'extension_reason' => 'nullable|required_if:action,extend-validity|in:natural-calamity,law-order,transshipment,accident,others',
                'extension_remarks' => 'required_if:action,extend-validity',
                'vehicle_update_reason' => 'required_if:action,update-vehicle',
                'vehicle_update_remarks' => 'required_if:action,update-vehicle',
            ])
            ->errors()
            ->toArray();

        if (count($errors)) {
            // return json_response(422, 'Invalid or Missing parameters', compact('errors'));
            return response()->json(['status' => false, 'message' => 'Invalid or Missing parameters', 'data' => []]);
        }
        $order = MiOrder::with([
            'billFromParty:party_id,party_trade_name,party_gstn,phone,party_legal_name',
            'billToParty:party_id,party_trade_name,party_gstn,phone,party_legal_name',
            'shipToParty:party_id,party_trade_name,party_gstn,phone,party_legal_name',
            'dispatchFromParty:party_id,party_trade_name,party_gstn,phone,party_legal_name',
            'billFromAddress',
            'billToAddress',
            'shipToAddress',
            'dispatchFromAddress',
        ])
            ->where('order_id', $order_id)
            ->first();

        if (!($order)) {

            return response()->json(['status' => 'error', 'message' => 'Order is not found', 'data' => []]);
        }
         if (empty( $order->eway_bill_no) ||  $order->eway_status !='C') {

            return response()->json(['status' => 'error', 'message' => 'EwayBill is not created', 'data' => []]);
        }

        if ($request->action == 'update-vehicle') {

            $params = [

                "userGstin" => $this->company_gstn,
                "eway_bill_number" => $order->eway_bill_no,
                "place_of_consignor" => strtoupper($order->billFromAddress->city), // change logic
                "state_of_consignor" => strtoupper($order->billFromAddress->state),
                "reason_code_for_vehicle_updation" => $this->vehicle_update_reason[$request->vehicle_update_reason] ?? 'Others',
                "reason_for_vehicle_updation" => $request->vehicle_update_remarks,
                'vehicle_type' => $order->vehicle_type,
                // "transporter_document_number" => strtoupper($data['ext_invoice_ref_no']),
                // "transporter_document_date" => date('d/m/Y', strtotime($data['invoice_date'])),
                "mode_of_transport" => $order->transportation_mode,
                "data_source" => "erp"
            ];
            if( $order->transporter_id=='NO_DETAIL')
            {
                return response()->json(['status' => 'error', 'message' => 'Update Transporter Detail', 'data' => []]);
            }
            // case fo self Transporter
            if ($order->transporter_id == 'NO_GSTN') {
                if(empty($order->vehicle_no))
                {
                    return response()->json(['status' => 'error', 'message' => 'Vehicle No can not be Empty,please Order', 'data' => []]);
                }
                $params['mode_of_transport'] = $order->transportation_mode;
                $params['vehicle_number'] = $order->vehicle_no;


            }else{

                return response()->json(['status' => 'error', 'message' => 'Update Transporter Detail To Self Pickup ', 'data' => []]);
            }


            if(!empty($order->dispatchFromAddress->address_id))
            {
                $params['place_of_consignor']=strtoupper($order->dispatchFromAddress->city);
                $params['state_of_consignor']=strtoupper($order->dispatchFromAddress->state);
            }
            $data=['order_invoice_number' => $order->order_invoice_number];

            $response = $this->masterIndiaService->updateVehicleNumber($data,$params);
            var_dump($response);
            $this->masterIndiaEwayBillTransaction->update(

                ['order_id' => $order->order_id],

                []

            );


        } else if ($request->action == 'update-transporter') {

            $params = [

                "userGstin" => $this->company_gstn,
                "eway_bill_number" => $order->eway_bill_no,
                "transporter_id" => $order->transporter_id // GSTN of transporter
            ];

            if(empty( $order->transporter_id) || $order->transporter_id=='NO_GSTN' || $order->transporter_id=='NO_DETAIL')
            {
                return response()->json(['status' => 'error', 'message' => 'Please Update Transporter Id And Name', 'data' => []]);
            }
            $data=['order_invoice_number' => $order->order_invoice_number];
           echo json_encode($params);
            die;
            $response = $this->masterIndiaService->updateTransporterID($data,$params);

            $this->masterIndiaEwayBillTransaction->update(

                ['order_id' => $order->order_id],

                []

            );

        } else if ($request->action == 'extend-validity') {

            $params = [
                "userGstin" => $this->company_gstn,
                "eway_bill_number" => $order->eway_bill_no,
                "place_of_consignor" => strtoupper($order->billFromParty->city),
                "pincode_of_consignor" => $order->billFromParty->pincode,
                "state_of_consignor" => strtoupper($order->billFromParty->state),
                "remaining_distance" => 250, // to be discused it is required
                // "transporter_document_number" => strtoupper($data['ext_invoice_ref_no']),
                // "transporter_document_date" => date('d/m/Y', strtotime($data['invoice_date'])),
                "extend_validity_reason" => $this->extension_reasons[$request->extension_reason] ?? 'Others',
                "extend_remarks" => $request->extension_remarks,
                "from_pincode" => $order->billFromParty->pincode,
                "consignment_status" => "M", // not required for in movement status
                // "transit_type" => "Road", //Roan,Warehouse not required for consignment status M
                // "address_line1" => "Dehradun", // not required for consignment status M
                // "address_line2" => "Dehradun", // not required for consignment status M
                // "address_line3" => "Dehradun" // not required for consignment status M
            ];

            if(!empty($order->dispatchFromAddress->address_id))
            {
                $params['place_of_consignor']=strtoupper($order->dispatchFromAddress->city);
                $params['state_of_consignor']=strtoupper($order->dispatchFromAddress->state);
                $params['pincode_of_consignor']=$order->dispatchFromAddress->pincode;
            }

            $data=['order_invoice_number' => $order->order_invoice_number];
            if ($order->transporter_name == 'SELF') {
                $params["vehicle_number"] = $order->vehicle_no; // $data['transporter_vehicle_number']; //to be discussed
                $params["mode_of_transport"] = $order->transportation_mode;

            } else {
                // Need to be discussed JK

                if(empty($order->transporter_id))
                {
                    return response()->json(['status' => 'error', 'message' => 'Transporter Id and Name Is required', 'data' => []]);
                }
                $params["transporter_id"] = $order->transporter_id;
            }

            echo json_encode($params);
            die;
            $response = $this->masterIndiaService->extendBillValidity($data,$params);

            $this->masterIndiaEwayBillTransaction->update(

                ['order_id' => $order->order_id],

                []

            );

        } else {

            return json_response(400, 'Invalid Update Action');
        }


    }

    /**
     * Get Eway Bill Details
     *
     * Process works as below:
     * 1. Checks if eway bill is not created already
     * 2. Fetch summary from party_sell_order_summary
     * 3. Fetch company details from company_details (party_id = psos.seller_party_id)
     *
     *
     * @header Content-Type application/x-www-form-urlencoded
     * @header Authorization {API Key Here}
     *
     * @bodyParam sell_invoice_ref_no integer required . Example: 213030
     * @bodyParam eway_service string required Supported Values are: MasterIndia. Example: MasterIndia
     *
     * @response scenario=success {
     * "results": {
     * "message": {
     * "eway_bill_number": 361002822611,
     * "eway_bill_date": "07\/04\/2022 06:02:00 PM",
     * "eway_bill_valid_date": "11\/04\/2022 11:59:00 PM",
     * "number_of_valid_days": 4,
     * "eway_bill_status": "Cancelled",
     * "generate_mode": "API",
     * "userGstin": "05AAABB0639G1Z8",
     * "supply_type": "OUTWARD",
     * "sub_supply_type": "Supply",
     * "document_type": "bill of supply",
     * "document_number": "NOI-2122-1707",
     * "document_date": "19\/10\/2021",
     * "gstin_of_consignor": "05AAABB0639G1Z8",
     * "legal_name_of_consignor": "RELCUBE INDIA PVT. LTD.",
     * "address1_of_consignor": "1st  2nd Floor, B 19, Sector 63",
     * "address2_of_consignor": "",
     * "place_of_consignor": "NOIDA",
     * "pincode_of_consignor": 201301,
     * "state_of_consignor": "UTTAR PRADESH",
     * "actual_from_state_name": "UTTAR PRADESH",
     * "gstin_of_consignee": "05AAABC0181E1ZE",
     * "legal_name_of_consignee": "",
     * "address1_of_consignee": "1st And 2nd Floor, B19",
     * "address2_of_consignee": "Sector 63",
     * "place_of_consignee": "NOIDA",
     * "pincode_of_consignee": 201309,
     * "state_of_supply": "UTTAR PRADESH",
     * "actual_to_state_name": "UTTAR PRADESH",
     * "total_invoice_value": 531,
     * "taxable_amount": 450,
     * "cgst_amount": 40.5,
     * "sgst_amount": 40.5,
     * "igst_amount": 0,
     * "cess_amount": 0,
     * "transporter_id": "05AAABB0639G1Z8",
     * "transporter_name": "BAZPUR SAHKARI KRA VIKRAY SAMITI LIMITED",
     * "transportation_distance": 656,
     * "extended_times": 0,
     * "reject_status": "N",
     * "vehicle_type": "regular",
     * "transaction_type": "Regular",
     * "other_value": 0,
     * "cess_nonadvol_value": 0,
     * "itemList": [
     * {
     * "item_number": 1,
     * "product_id": 0,
     * "product_name": "Preowned \/ Used Apple iPhone 12 Mini 4GB \/ 64GB",
     * "product_description": "",
     * "hsn_code": 851712,
     * "quantity": 1,
     * "unit_of_product": "BOX",
     * "cgst_rate": 9,
     * "sgst_rate": 9,
     * "igst_rate": 0,
     * "cess_rate": 0,
     * "cessNonAdvol": 0,
     * "taxable_amount": 450
     * }
     * ],
     * "VehiclListDetails": [
     * {
     * "update_mode": "API",
     * "vehicle_number": "PVC1234",
     * "place_of_consignor": "NOIDA",
     * "state_of_consignor": "UTTAR PRADESH",
     * "tripshtNo": 0,
     * "userGstin": "05AAABB0639G1Z8",
     * "vehicle_number_update_date": "07\/04\/2022 06:02:00 PM",
     * "transportation_mode": "road",
     * "transporter_document_number": "",
     * "transporter_document_date": "",
     * "group_number": "0"
     * }
     * ]
     * },
     * "status": "Success",
     * "code": 200
     * }
     * }
     *
     * @response 400 scenario=failed {
     * "success": false,
     * "message": "{error message}",
     *  }
     *
     * @response 422 scenario=failed {
     * "success": false,
     * "message": "Invalid or missing parameters"
     * "errors" : []
     * }
     *
     * @response 500 scenario=failed {
     * "success": false,
     * "message": "{error message}",
     *  }
     */
    public function getEwayBillDetails(Request $request,$order_id)
    {
        $order = MiOrder::where('order_id', $order_id)->first();

        if(empty($order)){
            return response()->json(['status' => false, 'message' => 'Order is not found', 'data' => []]);
        }
        $data=['order_invoice_number' => $order->order_invoice_number];
        $params = [

            'action' => 'GetEwayBill',
            "gstin" => $this->company_gstn,
            'eway_bill_number' => $order->eway_bill_no,
            'order_invoice_number' => $order->order_invoice_number,
        ];

        $response= $this->masterIndiaService->getEwayBillDetails($data,$params);
        var_dump($response);
    }

    private function resolveTransactionType(MiOrder $order)
    {
        $billFrom     = $order->billFromAddress?->address_id;
        $billTo       = $order->billToAddress?->address_id;
        $shipTo       = $order->shipToAddress?->address_id;
        $dispatchFrom = $order->dispatchFromAddress?->address_id;

        $hasBillFrom     = !empty($billFrom);
        $hasBillTo       = !empty($billTo);
        $hasShipTo       = !empty($shipTo);
        $hasDispatchFrom = !empty($dispatchFrom);

        if (!$hasBillFrom) {
            return 5;
        }

        if ($hasBillFrom && $hasBillTo && !$hasShipTo && !$hasDispatchFrom) {
            // 1️⃣ Regular
            return 1;
        }

        if ($hasBillFrom && $hasBillTo && $hasShipTo && !$hasDispatchFrom) {
            // 2️⃣ Bill To – Ship To
            return 2;
        }

        if ($hasBillFrom && !$hasBillTo && !$hasShipTo && $hasDispatchFrom) {
            // 3️⃣ Bill From – Dispatch From
            return 3;
        }

        if ($hasBillFrom && $hasBillTo && $hasShipTo && $hasDispatchFrom) {
            // 4️⃣ Combination of 2 & 3
            return 4;
        }

        return 5;
    }


}
