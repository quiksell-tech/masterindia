<?php

namespace App\Http\Controllers\Services;

use App\Models\Admin\MiItem;
use App\Models\Admin\MiOrderItem;
use App\Models\Admin\MiTransporter;
use App\Repositories\Interfaces\EwayBillDataInterface;
use App\Services\EwayBill\MasterIndiaService;
use App\Models\Admin\MiOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class EwayBillController extends Controller
{
    protected $masterIndiaService;
    protected $company_gstn='06AAICR4029F1ZI';
    public function __construct(MasterIndiaService $masterIndiaService,EwayBillDataInterface $ewayBillData){

        $this->masterIndiaService=$masterIndiaService;
        $this->ewayBillData = $ewayBillData;
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
    public function generateEwayBill(MiOrder $order){


        $items = MiOrderItem::where('order_id', $order->order_id)->get();
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
            ->where('order_id', $order->order_id)
            ->first();
        if(empty($items)){
            return response()->json(['status'=>false,'message'=>'Order Items Are not added','data'=>[]]);
        }

        if($order){

            // validate GSTN
            if(!empty($order->billToParty->party_gstn)){
                if($order->supply_type=='outward')
                {
                    $valid = $this->masterIndiaService->getGSTINDetails([
                        'buyer_gstin' =>$order->billToParty->party_gstn,
                        'sell_invoice_ref_no' => $order->order_invoice_number,
                        'company_gstin'=> $this->company_gstn,
                    ]);

                }else{

                    $valid = $this->masterIndiaService->getGSTINDetails([
                        'buyer_gstin' =>$order->billFromParty->party_gstn,
                        'sell_invoice_ref_no' => $this->company_gstn,
                        'company_gstin'=> '',
                    ]);
                }


                if($valid instanceof Response){
                    // update psos for error

                    $order->update( [
                        'eway_status' => 'E',
                        'eway_status_message' => json_decode($valid->getContent(), true)['message']??''
                    ]);
                    return response()->json(['status'=>false,'message'=>$valid->getContent(), true['message']??'','data'=>[]]);
                }

                if($valid['gstin_status']!='active'){

                    // set error to skip this record for batch process

                    $order->update([
                        'eway_status' => 'E',
                        'eway_status_message' => 'GSTIN not active: '.($valid['gstin_status'] ?? 'unknown')
                    ]);
                    return response()->json( ['status'=>false,'message'=>'GSTIN not active: '.($valid['gstin_status'] ?? 'unknown'),'data'=>[] ] );

                }

            }

            foreach($items as $item){
                // In same state
                if($order->billFromAddress->state_code == $order->billFromAddress->state_code){

                    $igst_rate = 0;
                    $sgst_rate = $item->tax_percentage/2;
                    $cgst_rate = $item->tax_percentage/2;
                }else{
                    $igst_rate = $item->tax_percentage;
                    $sgst_rate = 0;
                    $cgst_rate = 0;
                }
                $items_list[] = [
                    "product_name" => $item->item_name,
                    "product_description" =>$item->item_description,
                    "hsn_code" => $item->hsn_code,
                    "unit_of_product" =>$item->item_unit ,  // to be discussed
                    "cgst_rate" => round($cgst_rate, 2),
                    "sgst_rate" => round($sgst_rate,2),
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
                "data_source"=>'erp',
                "other_value" => 0,//round($total_other_charges,2),  need jk to discuss
                "total_invoice_value" =>  round($order->total_after_tax,2),
                "taxable_amount" =>  round($order->total_sale_value,2),
                "cess_amount" => 0,
                "cess_nonadvol_value" => 0,
                "itemList" => $items_list
            ];

            if($order->billFromAddress->state_code == $order->billToAddress->state_code){


                $ewayBillData['cgst_amount']=round($order->total_tax/2,2);
                $ewayBillData['sgst_amount']=round($order->total_tax/2,2);
                $ewayBillData['igst_amount']=0;

            }else{
                $ewayBillData['igst_amount']=round($order->total_tax/2,2);
                $ewayBillData['cgst_amount']=0;
                $ewayBillData['sgst_amount']=0;

            }



            if($order->billFromParty)
            {
                $ewayBillData['gstin_of_consignor']=$order->billFromParty->party_gstn;
                $ewayBillData['legal_name_of_consignor']=$order->billFromParty->party_legal_name;
            }
            //  Address of consignor i.e. Seller
            if(!empty($order->dispatchFromAddress->address_id))
            {
                $ewayBillData['address1_of_consignor']=$order->dispatchFromAddress->address_line;
                $ewayBillData['address2_of_consignor']=$order->dispatchFromAddress->address_line;
                $ewayBillData['place_of_consignor']=strtoupper($order->dispatchFromAddress->city);
                $ewayBillData['pincode_of_consignor']=$order->dispatchFromAddress->pincode;

                $ewayBillData['state_of_consignor']=strtoupper($order->dispatchFromAddress->state);
                $ewayBillData['actual_from_state_name']=strtoupper($order->dispatchFromAddress->state);
            }
            else{
                $ewayBillData['address1_of_consignor']=$order->billFromAddress->address_line;
                $ewayBillData['address2_of_consignor']=$order->billFromAddress->address_line;
                $ewayBillData['place_of_consignor']=strtoupper($order->billFromAddress->city);
                $ewayBillData['pincode_of_consignor']=$order->billFromAddress->pincode;
                $ewayBillData['state_of_consignor']=strtoupper($order->billFromAddress->state);
                $ewayBillData['actual_from_state_name']=strtoupper($order->billFromAddress->state);
            }
            // address of consignee i.e. purchaser/Buyer
            if($order->billFromParty)
            {
                $ewayBillData['gstin_of_consignee']=$order->billToParty->party_gstn;
                $ewayBillData['legal_name_of_consignee']=$order->billToParty->party_legal_name;
            }
            if(!empty($order->shipToAddress->address_id))
            {
                $ewayBillData['address1_of_consignee']=$order->shipToAddress->address_line;
                $ewayBillData['address2_of_consignee']=$order->shipToAddress->address_line;
                $ewayBillData['place_of_consignee']=strtoupper($order->shipToAddress->city);
                $ewayBillData['pincode_of_consignee']=$order->shipToAddress->pincode;

                $ewayBillData['state_of_supply']=strtoupper($order->shipToAddress->state);
                $ewayBillData['actual_to_state_name']=strtoupper($order->shipToAddress->state);

            }else{

                $ewayBillData['address1_of_consignee']=$order->billToAddress->address_line;
                $ewayBillData['address2_of_consignee']=$order->billToAddress->address_line;
                $ewayBillData['place_of_consignee']=strtoupper($order->billToAddress->city);
                $ewayBillData['pincode_of_consignee']=$order->billToAddress->pincode;

                $ewayBillData['state_of_supply']=strtoupper($order->billToAddress->state);
                $ewayBillData['actual_to_state_name']=strtoupper($order->billToAddress->state);
            }

            if($order->shipToAddress->address_id && $order->dispatchFromAddress->address_line)
            {
                $ewayBillData['transaction_type']=1;
            }


            if($order->transporter_name=='SELF')
            {
                $ewayBillData['transportation_mode']=$order->transportation_mode;
                $ewayBillData['vehicle_number']=$order->vehicle_no;
                $ewayBillData['vehicle_type']=$order->vehicle_type;

            }else{
                $ewayBillData['transporter_id'] = $order->transporter_id ;// GSTN of transporter

            }

            $response= $this->masterIndiaService->generateEwayBill($ewayBillData);
            if($response instanceof Response){
                // update psos for error

                $order->update( [
                    'eway_status' => 'E',
                    'eway_status_message' => json_decode($valid->getContent(), true)['message']??''
                ]);
                return response()->json(['status'=>false,'message'=>$valid->getContent(), true['message']??'','data'=>[]]);
            }

            $order->update([
                'eway_status' => 'C',
                'eway_status_message' => 'Ewaybill has been created by :'.($response['display_message']??''),
                'eway_bill_no'=>$response['message']['ewayBillNo']
            ]);

            if(!$this->ewayBillData->saveEwayBillData($ewayBillData, $response))

                return response()->json(['status'=>false,'message'=>'Ewaybill created but failed to save response','data'=>[]]);


                return response()->json(['status'=>true,'message'=> 'EwayBill has been created by :'.($response['display_message'])??'','data'=>[]]);
        }


        return response()->json(['status'=>true,'message'=>'Order Not found','data'=>[]]);

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
    public function cancelEwayBill(Request $request,MiOrder $order){

        $order = MiOrder::with([

            'billFromParty:party_id,party_trade_name,party_gstn,phone,party_legal_name',

        ])
            ->where('order_id', $order->order_id)
            ->first();
      $errors=Validator::make($request->all(),
          [
              'order_id'=>'required|integer',
              'cancel_reason' => 'required|in:duplicate,order-cancelled,incorrect-details,others',
              'cancel_remarks' => 'required',

          ])
          ->errors()
          ->toArray();
         if(!$order->eway_bill_no)
         {
             return response()->json(['status'=>false,'message'=>'Ewaybill is not created','data'=>[]]);
         }

          if (count($errors)) {
              //return json_response(422, 'Invalid or Missing parameters', compact('errors'));

              return response()->json(['status'=>false,'message'=>'Invalid or Missing parameters','data'=>[]]);
          }

        $params =[

            "userGstin" => $order->billFromParty->party_gstn,
            "eway_bill_number" => $order->eway_bill_no,
            "reason_of_cancel" => $this->cancellation_reasons[$request->cancel_reason]??'Others',
            "cancel_remark" =>$request->cancel_remarks ?? '',
            "data_source" => "erp"
        ];

      $response= $this->masterIndiaService->cancelEwayBill($params);

        if(!$this->ewayBillData->cancelEwayBill($this->ewayBillData, $params, $response))

        return response()->json(['status'=>false,'message'=>'Eway Bill cancelled but failed to save response','data'=>[]]);

        $order->update([
            'eway_status' => 'X',
            'eway_status_message' => 'Ewaybill has been cancelled'
        ]);
        return response()->json(['status'=>true,'message'=>'Ewaybill has been cancelled','data'=>[]]);
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
    public function updateEwayBill(Request $request,MiOrder $order){

        $errors=Validator::make($request->all(),
            [
                'action'=>'required|in:update-vehicle,update-transporter,extend-validity',
                'order_id'=>'required|integer',
                'extension_reason'=>'nullable|required_if:action,extend-validity|in:natural-calamity,law-order,transshipment,accident,others',
                'extension_remarks'=>'required_if:action,extend-validity',
                'vehicle_update_reason'=>'required_if:action,update-vehicle',
                'vehicle_update_remarks'=>'required_if:action,update-vehicle',
            ])
            ->errors()
            ->toArray();

        if (count($errors)) {
           // return json_response(422, 'Invalid or Missing parameters', compact('errors'));
            return response()->json(['status'=>false,'message'=>'Invalid or Missing parameters','data'=>[]]);
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
            ->where('order_id', $order->order_id)
            ->first();

        if(!$order->eway_bill_no)
        {
            return response()->json(['status'=>false,'message'=>'EwayBill is not created','data'=>[]]);
        }

        if($request->action == 'update-vehicle'){

            $params = [

                "userGstin" => $this->company_gstn,
                "eway_bill_number" => $order->eway_bill_no,
                "vehicle_number" =>  $order->vehicle_no,
                "vehicle_type" =>  $order->vehicle_type,
                "place_of_consignor" => strtoupper($order->billFromParty->city),
                "state_of_consignor" => strtoupper($order->billFromParty->state ),
                "reason_code_for_vehicle_updation" => $this->vehicle_update_reason[$request->vehicle_update_reason]??'Others',
                "reason_for_vehicle_updation" => $request->vehicle_update_remarks,
                // "transporter_document_number" => strtoupper($data['ext_invoice_ref_no']),
                // "transporter_document_date" => date('d/m/Y', strtotime($data['invoice_date'])),
                "mode_of_transport" => $request->transportation_mode,
                "data_source" =>"erp"
            ];

            $response= $this->masterIndiaService->updateVehicleNumber($params);


        }else if($request->action == 'update-transporter'){

            $params = [

                "userGstin" =>$this->company_gstn,
                "eway_bill_number" => $order->eway_bill_no,
                "transporter_id" => $order->transporter_id // GSTN of transporter
            ];
            $response= $this->masterIndiaService->updateTransporterID($params);

        }else if($request->action == 'extend-validity'){

            $params = [

                "userGstin" => $this->company_gstn,
                "eway_bill_number" => $order->eway_bill_no,
                "place_of_consignor" => strtoupper($order->billFromParty->city),
                "pincode_of_consignor" =>$order->billFromParty->pincode,
                "state_of_consignor" => strtoupper($order->billFromParty->state),
                "remaining_distance" => 250, // to be discused it is required
                // "transporter_document_number" => strtoupper($data['ext_invoice_ref_no']),
                // "transporter_document_date" => date('d/m/Y', strtotime($data['invoice_date'])),
                "extend_validity_reason" => $this->extension_reasons[$request->extension_reason]??'Others',
                "extend_remarks" =>$request->extension_remarks,
                "from_pincode" => $order->billFromParty->pincode,
                "consignment_status" => "M", // not required for in movement status
                // "transit_type" => "Road", //Roan,Warehouse not required for consignment status M
                // "address_line1" => "Dehradun", // not required for consignment status M
                // "address_line2" => "Dehradun", // not required for consignment status M
                // "address_line3" => "Dehradun" // not required for consignment status M
            ];
            if($order->transporter_name=='SELF')
            {
                $params["vehicle_number"] = $order->vehicle_no; // $data['transporter_vehicle_number']; //to be discussed
                $params["mode_of_transport"] =$order->transportation_mode;

            }else{
                    // Need to be discussed JK
                $params["vehicle_number"] = $order->vehicle_no;
            }


            $response= $this->masterIndiaService->extendBillValidity($params);

        }else{

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
    public function getEwayBillDetails(Request $request){
        $errors=Validator::make($request->all(),
          [
              'sell_invoice_ref_no'=>'required|integer',
              'eway_service'=>'required|in:MasterIndia'
          ])
          ->errors()
          ->toArray();

        if (count($errors)) {
            return json_response(422, 'Invalid or Missing parameters', compact('errors'));
        }
        return $this->ewayBillManager->getEwayBillDetails($request->all());
    }

    /**
     * Get Api Count
     *
     * Provides api usage data
     *
     * @header Content-Type application/x-www-form-urlencoded
     * @header Authorization {API Key Here}
     *
     * @bodyParam eway_service string required Supported Values are: MasterIndia. Example: MasterIndia
     * @bodyParam account_email string required Registered email with masterindia. Example: mayank.gupta@recycledevice.com
     * @bodyParam from_date string required Date in yyyy-mm-dd format. Example: 2022-11-12
     * @bodyParam to_date string required Date in yyyy-mm-dd format. Example: 2022-12-12
     *
     * @response scenario=success {
     * "results":{
     * "apiCount":113,
     * "status":"Success",
     * "code":200
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
    public function getApiCounts(Request $request){
      $errors=Validator::make($request->all(),
          [
              'eway_service' => 'required',
              'account_email' => 'required',
              'from_date' => 'required|date_format:Y-m-d',
              'to_date' => 'required|date_format:Y-m-d',
          ])
          ->errors()
          ->toArray();

      if (count($errors)) {
          return json_response(422, 'Invalid or Missing parameters', compact('errors'));
      }

      return $this->ewayBillManager->getApiCounts($request->all());


    }

    // public function getGSTINDetails(Request $request){
    //
    //     $errors=Validator::make($request->all(),
    //         [
    //             'sell_invoice_ref_no'=>'required|integer',
    //             'eway_service'=>'required|in:MasterIndia'
    //         ])
    //         ->errors()
    //         ->toArray();
    //
    //     if (count($errors)) {
    //         return json_response(422, 'Invalid or Missing parameters', compact('errors'));
    //     }
    //
    //     return $this->ewayBillManager->getGSTINDetails($request->all());
    // }

}
