<?php

namespace App\Http\Controllers\Services;

use App\Services\EwayBill\EwayBillManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class EwayBillController extends Controller
{

    public function __construct(EwayBillManager $ewayBillManager){

        $this->ewayBillManager=$ewayBillManager;
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
    public function generateEwayBill(Request $request){

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

        return $this->ewayBillManager->generateEwayBill($request->all());
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
    public function cancelEwayBill(Request $request){

      $errors=Validator::make($request->all(),
          [
              'sell_invoice_ref_no'=>'required|integer',
              'cancel_reason' => 'required|in:duplicate,order-cancelled,incorrect-details,others',
              'cancel_remarks' => 'required',
              'eway_service'=>'required|in:MasterIndia'
          ])
          ->errors()
          ->toArray();

      if (count($errors)) {
          return json_response(422, 'Invalid or Missing parameters', compact('errors'));
      }

      return $this->ewayBillManager->cancelEwayBill($request->all());

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
    public function updateEwayBill(Request $request){
        $errors=Validator::make($request->all(),
            [
                'action'=>'required|in:update-vehicle,update-transporter,extend-validity',
                'sell_invoice_ref_no'=>'required|integer',
                'eway_service'=>'required|in:MasterIndia',
                'extension_reason'=>'nullable|required_if:action,extend-validity|in:natural-calamity,law-order,transshipment,accident,others',
                'extension_remarks'=>'required_if:action,extend-validity',
                'vehicle_update_reason'=>'required_if:action,update-vehicle',
                'vehicle_update_remarks'=>'required_if:action,update-vehicle',
            ])
            ->errors()
            ->toArray();

        if (count($errors)) {
            return json_response(422, 'Invalid or Missing parameters', compact('errors'));
        }

        return $this->ewayBillManager->updateEwayBill($request->all());
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
