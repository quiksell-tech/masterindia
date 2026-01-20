<?php

namespace App\Http\Controllers\Services;

use App\Models\Admin\MiCreditnoteItem;
use App\Models\Admin\MiOrder;
use App\Http\Controllers\Controller;
use App\Models\Admin\MiOrderItem;
use App\Models\MasterIndiaEInvoiceTransaction;
use App\Models\MiCreditnoteTransaction;
use App\Models\SystemParameter;
use App\Services\EInvoice\MasterIndiaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;


class EInvoiceController extends Controller
{
    protected $masterIndiaService;
    protected $masterIndiaEInvoiceTransaction;
   // protected $company_gstn = '05AAAPG7885R002'; by IShan
    protected $company_gstn ;
    protected $EINVOICE_COMPANY_GSTN;

    public function __construct(MasterIndiaService $masterIndiaService, MasterIndiaEInvoiceTransaction $masterIndiaEInvoiceTransaction)
    {

        $this->masterIndiaService = $masterIndiaService;
        $this->masterIndiaEInvoiceTransaction = $masterIndiaEInvoiceTransaction;
        $sysParameter=SystemParameter::getSystemParametersByName('EINVOICE_TEST_USER_GSTN');
        $this->company_gstn=$sysParameter['EINVOICE_TEST_USER_GSTN'];
        $sysParameter=SystemParameter::getSystemParametersByName('EINVOICE_COMPANY_GSTN');
        $this->EINVOICE_COMPANY_GSTN=$sysParameter['EINVOICE_COMPANY_GSTN'];

    }

    public function getEInvoice(Request $request, MiOrder $miOrder)
    {

        $order = MiOrder::where('order_id', $miOrder->order_id)->get();
        if($order->isEmpty()){
            return response()->json(['status' => 'error', 'message' => 'Order is not found', 'data' => []]);
        }
        $params = [

            "gstin" => $this->company_gstn,
            "irn" => $order->irn_no,
        ];
        $data=['order_invoice_number'=>$order->order_invoice_number];
        $response = $this->masterIndiaService->getEInvoice($data,$params);
    }

    public function generateCreditNote(Request $request,  $creditnoteId)
    {
        $creditnote = MiCreditnoteTransaction::with('items')
            ->where('creditnote_id', $creditnoteId)
            ->first();

        if(!$creditnote){

          return response()->json(['status' => 'error', 'message' => 'Credit Note Order Not Found', 'data' => []]);
        }
        if (empty($creditnote->items)) {

            return response()->json(['status' => 'error', 'message' => 'Credit Note Items Are not added', 'data' => []]);
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
            ->where('order_id', $creditnote->order_id)
            ->first();



        if (empty($order)) {
            return response()->json(['status' => 'error', 'message' => 'Related order  is not found', 'data' => []]);
        }

        if(empty($order->billFromAddress->address_id))
        {
            return response()->json(['status' => 'error', 'message' => 'please update bill FROM Address', 'data' => []]);
        }
        if(empty($order->billToAddress->address_id))
        {
            return response()->json(['status' => 'error', 'message' => 'please Update bill TO Address', 'data' => []]);
        }

        if(empty($order->irn_no)  )
        {
            return response()->json(['status' => 'error', 'message' => 'IRN Not  Created', 'data' => []]);
        }
        if (empty($order->billFromParty->party_id)) {

            return response()->json(['status' => 'error', 'message' => 'Bill From Party Not Found', 'data' => []]);
        }
        if(empty($order->billToParty->party_id))
        {
            return response()->json(['status' => 'error', 'message' => 'Bill to Party Not Found', 'data' => []]);
        }
        // validate GSTN
        if (!empty($order->billToParty->party_gstn)) {

            if ($order->supply_type == 'outward') {
                $valid = $this->masterIndiaService->getGSTINDetailsNew([
                    'buyer_gstin' => $order->billToParty->party_gstn,
                    'sell_invoice_ref_no' => $order->order_invoice_number,
                  //  'company_gstin' => $this->company_gstn,
                    'company_gstin' => $this->EINVOICE_COMPANY_GSTN,
                ]);

            } else {

                $valid = $this->masterIndiaService->getGSTINDetailsNew([
                    'buyer_gstin' => $order->billFromParty->party_gstn,
                    'sell_invoice_ref_no' => $this->company_gstn,
                   // 'company_gstin' => $this->company_gstn,
                    'company_gstin' => $this->EINVOICE_COMPANY_GSTN,
                ]);
            }


            if ($valid instanceof Response) {
                // update psos for error
                $message=json_decode($valid->getContent(), true)['message'] ?? '';
                $creditnote->update([
                    'credit_note_status' => 'E',
                    'credit_note_status_message' => $message,
                ]);
                return response()->json(['status' => 'error', 'message' => $message, 'data' => []]);
            }

            if ($valid['gstin_status'] != 'active') {

                // set error to skip this record for batch process

                $order->update([
                    'credit_note_status' => 'E',
                    'credit_note_status_message' => 'GSTIN not active: ' . ($valid['gstin_status'] ?? 'unknown')
                ]);
                return response()->json(['status' => 'error', 'message' => 'GSTIN not active: ' . ($valid['gstin_status'] ?? 'unknown'), 'data' => []]);

            }

        }

        if ($order->billFromAddress->state_code == $order->billToAddress->state_code) {

            $total_igst_value = 0;
            $total_sgst_value = $creditnote->total_tax / 2;
            $total_cgst_value = $creditnote->total_tax / 2;
        } else {
            $total_igst_value = $creditnote->total_tax;
            $total_sgst_value = 0;
            $total_cgst_value = 0;
        }


        $items_list = [];
        $i = 1;

        foreach ($creditnote->items as $item) {


            $taxableAmount = $item->total_item_quantity * $item->price_per_unit;
            $taxAmount = ($taxableAmount * $item->tax_percentage) / 100;
            $afterTaxValue = $taxableAmount + $taxAmount;


            if ($order->billFromAddress->state_code == $order->billToParty->state_code) {

                $igst_value = 0;
                $cgst_value = $taxAmount / 2;
                $sgst_value = $taxAmount / 2;

            } else {
                $sgst_value = 0;
                $cgst_value = 0;
                $igst_value = $taxAmount;
            }

            $items_list[] = [
                "item_serial_number" => $i++,
                "product_description" => $item->item_name.' '.$item->item_code,
                "is_service" => 'N',
                "hsn_code" => $item->hsn_code,
                "bar_code" => '',
                "quantity" => $item->total_item_quantity,
                // "free_quantity" => 0,
                "unit" => $item->item_unit,
                "unit_price" => round($item->price_per_unit, 2),
                "total_amount" => round($taxableAmount, 2),
                // "pre_tax_value" => 0,
                "discount" => 0,
                "other_charge" => 0,
                "assessable_value" => round($taxableAmount, 2),
                "gst_rate" => $item->tax_percentage,
                "igst_amount" => round($igst_value, 2),
                "cgst_amount" => round($cgst_value, 2),
                "sgst_amount" => round($sgst_value, 2),

                "total_item_value" => round($afterTaxValue, 2),

            ];
        }

        $params = [

            "user_gstin" =>$this->company_gstn,
            "data_source" => "erp",
            "transaction_details" => [
                "supply_type" => "B2B",
                "charge_type" => "N",
                "igst_on_intra" => "N",
                "ecommerce_gstin" => ""
            ],
            "document_details" => [
                "document_type" => "CRN",
                "document_number" => strtoupper($creditnote->creditnote_invoice_no), // neeed to change from auto Or given
                "document_date" => date('d/m/Y', strtotime($creditnote->credit_note_date))
            ],
            "seller_details" => [
                "gstin" => $order->billFromParty->party_gstn,
                "legal_name" => $order->billFromParty->party_legal_name,
                // "trade_name" => "MastersIndia UP",
                "address1" => $order->billFromAddress->address_line,
                "address2" => '',
                // "address2" => "Vila",
                "location" => strtoupper($order->billFromAddress->city),
                "pincode" => $order->billFromAddress->pincode,
                "state_code" => strtoupper($order->billFromAddress->state_code),
                "phone_number" => $order->billFromAddress->phone,
                // "email" => ""
            ],
            "buyer_details" => [
                "gstin" => $order->billToParty->party_gstn,
                "legal_name" => $order->billToParty->party_legal_name,
                "trade_name" => $order->billToParty->party_trade_name,
                "address1" => $order->billToAddress->address_line,
                "address2" => '',
                "location" => strtoupper($order->billToAddress->city),
                "pincode" => $order->billToAddress->pincode,
                "place_of_supply" => $order->billToAddress->state_code,
                "state_code" => strtoupper($order->billToAddress->state_code),
                "phone_number" => $order->billToAddress->phone,
                // "email" => ""
            ],
            "reference_details" => [

                "preceding_document_details" => [[
                    "reference_of_original_invoice" => strtoupper($order->order_invoice_number),
                    "preceding_invoice_date" => date('d/m/Y', strtotime($order->order_invoice_date)),
                    // "other_reference" => "2334"
                ]],

            ],

            "value_details" => [
                "total_assessable_value" => round($creditnote->total_sale_value, 2),
                "total_cgst_value" => round($total_cgst_value, 2),
                "total_sgst_value" => round($total_sgst_value, 2),
                "total_igst_value" => round($total_igst_value, 2),
                "total_invoice_value" => round($creditnote->total_after_tax, 2),

            ],
            "item_list" => $items_list
        ];

        if (!empty($order->dispatchFromAddress->address_id)) {
            $params["dispatch_details"] = [
                "company_name" => $order->dispatchFromParty->party_legal_name,
                "address1" => $order->dispatchFromAddress->address_line,
                "address2" => '',
                // "address2" => "Vila",
                "location" => strtoupper($order->dispatchFromAddress->city),
                "pincode" => $order->dispatchFromAddress->pincode,
                "state_code" => strtoupper($order->dispatchFromAddress->state_code),
            ];

        } else {
            $params["dispatch_details"] = [
                "company_name" => $order->billFromParty->party_legal_name,
                "address1" => $order->billFromAddress->address_line,
                "address2" => '',
                // "address2" => "Vila",
                "location" => strtoupper($order->billFromAddress->city),
                "pincode" => $order->billFromAddress->pincode,
                "state_code" => strtoupper($order->billFromAddress->state_code),
            ];
        }

        if (!empty($order->shipToAddress->address_id)) {
            $params["ship_details"] = [
                // "gstin" => "05AAAPG7885R002",
                "legal_name" => $order->shipToParty->party_legal_name,
                "trade_name" => $order->shipToParty->party_trade_name,
                "address1" => $order->shipToAddress->address_line,
                "address2" => '',
                "location" => strtoupper($order->shipToAddress->city),
                "pincode" => $order->shipToAddress->pincode,
                "state_code" => strtoupper($order->shipToAddress->state_code)
            ];
        } else {

            $params["ship_details"] = [
                // "gstin" => "05AAAPG7885R002",
                "legal_name" => $order->billToParty->party_legal_name,
                "trade_name" => $order->billToParty->party_trade_name,
                "address1" => $order->billToAddress->address_line,
                "address2" => '',
                "location" => strtoupper($order->billToAddress->city),
                "pincode" => $order->billToAddress->pincode,
                "state_code" => strtoupper($order->billToAddress->state_code)
            ];
        }

        $data=['creditnote_invoice_no'=>$creditnote->creditnote_invoice_no];

        $response = $this->masterIndiaService->generateCreditNote($data,$params);

        if ($response instanceof Response) {
            //update psos for failure
            $message=json_decode($response->getContent(), true)['message'] ?? '';
            $creditnote->update(
                [
                    'credit_note_status' => 'E',
                    'credit_note_status_message' => $message,
                ]);
            return response()->json(['status' => 'error', 'message' => $message, 'data' => []]);
        }

        $updateData=[

            'ack_no' => $response['message']['AckNo'],
            'ack_date' => $response['message']['AckDt'],
            'creditnote_irn_no' => $response['message']['Irn'],
            'qrcode_url' => $response['message']['QRCodeUrl'],
            'creditnote_pdf_url' => $response['message']['EinvoicePdf'],
            'status_received' => $response['message']['Status'],
            'alert_message' => $response['message']['alert'],
            'request_id' => $response['requestId'],
            'credit_note_status_message' => 'Credit note has been created',
            'credit_note_status' => 'C'
        ];

        $isRecordUpdated=$creditnote->update($updateData);
        if ($isRecordUpdated) {

            return response()->json(['status' => 'success', 'message' => 'Credit note has been created :' . ($response['display_message'] ?? ''), 'data' => []]);
        }

        return response()->json(['status' => 'error', 'message' => 'Credit Note created but failed to save response', 'data' => []]);

    }

    public function cancelEInvoice(Request $request, $order_id)
    {
        if(empty($request->cancel_reason))
        {
            return response()->json(['status' => 'error', 'message' => 'Select cancel reason', 'data' => []]);
        }
        $order = MiOrder::where('order_id', $order_id)->first();
        if (empty($order)) {
            return response()->json(['status' => 'error', 'message' => 'Order is not found', 'data' => []]);
        }

        $params = [

            "user_gstin" =>$this->company_gstn,
            "irn" => $order->irn_no,
            "cancel_reason" => $request->cancel_reason,
            "cancel_remarks" => $request->cancel_remarks ?? 'Wrong Entry'
        ];

        $data=['order_invoice_number'=>$order->order_invoice_number];
        $response = $this->masterIndiaService->cancelEInvoice($data,$params);
        if ($response instanceof Response) {
            //update psos for failure
            $message=json_decode($response->getContent(), true)['message'] ?? '';
            $order->update(
                [
                    'credit_note_status' => 'E',
                    'credit_note_status_message' => $message,
                ]);
            return response()->json(['status' => 'error', 'message' => $message, 'data' => []]);
        }
        $order->update([
            'irn_status' => 'X',
            'irn_status_message' => 'E-invoice has been cancelled ' . ($response['display_message'] ?? '')
        ]);
        return response()->json(['status' => 'success', 'message' => 'E-invoice has been cancelled', 'data' => []]);

    }

    public function cancelCreditNote(Request $request, $creditnoteId)
    {
        if(empty($request->cancel_reason))
        {
            return response()->json(['status' => 'error', 'message' => 'Select cancel reason', 'data' => []]);
        }
        $order = MiCreditnoteItem::where('order_id', $creditnoteId)->first();
        if (empty($order)) {

            return response()->json(['status' => 'error', 'message' => 'Credit note is not found', 'data' => []]);
        }

        $params = [

            "user_gstin" =>$this->company_gstn,
            "irn" => $order->irn_no,
            "cancel_reason" => $request->cancel_reason,
            "cancel_remarks" => $request->cancel_remarks ?? 'Wrong Entry'
        ];

        $data=['order_invoice_number'=>$order->creditnote_invoice_no];
        $response = $this->masterIndiaService->cancelEInvoice($data,$params);
        if ($response instanceof Response) {
            //update psos for failure
            $message=json_decode($response->getContent(), true)['message'] ?? '';
            $order->update(
                [
                    'credit_note_status' => 'E',
                    'credit_note_status_message' => $message,
                ]);
            return response()->json(['status' => 'error', 'message' => $message, 'data' => []]);
        }
        $order->update([
            'credit_note_status' => 'X',
            'credit_note_status_message' => 'E-invoice has been cancelled ' . ($response['display_message'] ?? '')
        ]);
        return response()->json(['status' => 'success', 'message' => 'Credit Note has been cancelled', 'data' => []]);

    }

    public function generateEInvoice(Request $request, $order_id)
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

        if(($order->irn_status=='C' || $order->irn_status=='X') && $order->eway_status=='C'){

            return response()->json(['status' => 'error', 'message' => 'Please cancel Eway first than Create E-invoice', 'data' => []]);
        }
        $invoiceNumberForApi = $order->order_invoice_number;

        if ($order->irn_status == 'X') {
            $invoiceNumberForApi .= 'A';
        }

        if (empty($items)) {
            return response()->json(['status' => 'error', 'message' => 'Order Items Are not added', 'data' => []]);
        }

        if (empty($order)) {
            return response()->json(['status' => 'error', 'message' => 'Order is not found', 'data' => []]);
        }
        if(empty($order->billFromAddress->address_id))
        {
            return response()->json(['status' => 'error', 'message' => 'please update bill FROM Address', 'data' => []]);
        }
        if(empty($order->billToAddress->address_id))
        {
            return response()->json(['status' => 'error', 'message' => 'please Update bill TO Address', 'data' => []]);
        }
        if( $order->irn_status=='C' )
        {
            return response()->json(['status' => 'error', 'message' => 'IRN Already Created', 'data' => []]);
        }

        // validate GSTN
        if (!empty($order->billToParty->party_gstn)) {

            if ($order->supply_type == 'outward') {
                $valid = $this->masterIndiaService->getGSTINDetailsNew([
                    'buyer_gstin' => $order->billToParty->party_gstn,
                    'sell_invoice_ref_no' => $order->order_invoice_number,
                    'company_gstin' => $this->EINVOICE_COMPANY_GSTN,
                ]);

            } else {

                $valid = $this->masterIndiaService->getGSTINDetailsNew([
                    'buyer_gstin' => $order->billFromParty->party_gstn,
                    'sell_invoice_ref_no' => $this->company_gstn,
                    'company_gstin' => $this->EINVOICE_COMPANY_GSTN,
                ]);
            }


            if ($valid instanceof Response) {
                // update psos for error
                    $message=json_decode($valid->getContent(), true)['message'] ?? '';
                $order->update([
                    'irn_status' => 'E',
                    'irn_status_message' =>$message
                ]);
                return response()->json(['status' => 'error', 'message' =>$message, 'data' => []]);
            }

            if ($valid['gstin_status'] != 'active') {

                // set error to skip this record for batch process

                $order->update([
                    'irn_status' => 'E',
                    'irn_status_message' => 'GSTIN not active: ' . ($valid['gstin_status'] ?? 'unknown')
                ]);
                return response()->json(['status' => 'error', 'message' => 'GSTIN not active: ' . ($valid['gstin_status'] ?? 'unknown'), 'data' => []]);

            }

        }

        if ($order->billFromAddress->state_code == $order->billToParty->state_code) {

            $total_igst_value = 0;
            $total_sgst_value = $order->total_tax / 2;
            $total_cgst_value = $order->total_tax / 2;
        } else {
            $total_igst_value = $order->total_tax;
            $total_sgst_value = 0;
            $total_cgst_value = 0;
        }


        $items_list = [];
        $i = 1;

        foreach ($items as $item) {

            $taxableAmount = $item->total_item_quantity * $item->price_per_unit;
            $taxAmount = ($taxableAmount * $item->tax_percentage) / 100;
            $afterTaxValue = $taxableAmount + $taxAmount;

            if ($order->billFromAddress->state_code == $order->billToParty->state_code) {

                $igst_value = 0;
                $cgst_value = $taxAmount / 2;
                $sgst_value = $taxAmount / 2;

            } else {
                $sgst_value = 0;
                $cgst_value = 0;
                $igst_value = $taxAmount;
            }

            $items_list[] = [
                "item_serial_number" => $i++,
                "product_description" =>  $item->item_name.' '.$item->item_name,
                "is_service" => 'N',
                "hsn_code" => $item->hsn_code,
                "bar_code" => '',
                "quantity" => $item->total_item_quantity,
                // "free_quantity" => 0,
                "unit" => $item->item_unit,
                "unit_price" => round($item->price_per_unit, 2),
                "total_amount" => round($taxableAmount, 2),
                // "pre_tax_value" => 0,
                "discount" => 0,
                "other_charge" => 0,
                "assessable_value" => round($taxableAmount, 2),
                "gst_rate" => $item->tax_percentage,
                "igst_amount" => round($igst_value, 2),
                "cgst_amount" => round($cgst_value, 2),
                "sgst_amount" => round($sgst_value, 2),

                "total_item_value" => round($afterTaxValue, 2),

            ];
        }

        $params = [

            "user_gstin" =>$this->company_gstn,
            "data_source" => "erp",
            "transaction_details" => [
                "supply_type" => "B2B",
                "charge_type" => "N",
                "igst_on_intra" => "N",
                "ecommerce_gstin" => ""
            ],
            "document_details" => [
                "document_type" => "INV",
                "document_number" => strtoupper($invoiceNumberForApi),
                "document_date" => date('d/m/Y', strtotime($order->order_invoice_date))
            ],
            "seller_details" => [
                "gstin" => $order->billFromParty->party_gstn,
                "legal_name" => $order->billFromParty->party_legal_name,
                // "trade_name" => "MastersIndia UP",
                "address1" => $order->billFromAddress->address_line,
                "address2" => '',
                // "address2" => "Vila",
                "location" => strtoupper($order->billFromAddress->city),
                "pincode" => $order->billFromAddress->pincode,
                "state_code" => strtoupper($order->billFromAddress->state_code),
                "phone_number" => $order->billFromParty->phone,
                // "email" => ""
            ],
            "buyer_details" => [
                "gstin" => $order->billToParty->party_gstn,
                "legal_name" => $order->billToParty->party_legal_name,
                "trade_name" => $order->billToParty->party_trade_name,
                "address1" => $order->billToAddress->address_line,
                "address2" => '',
                "location" => strtoupper($order->billToAddress->city),
                "pincode" => $order->billToAddress->pincode,
                "place_of_supply" => $order->billToAddress->state_code,
                "state_code" => strtoupper($order->billToAddress->state_code),
                "phone_number" => $order->billToParty->phone,
                // "email" => ""
            ],
            "reference_details" => [

                "preceding_document_details" => [[
                    "reference_of_original_invoice" => strtoupper($order->order_invoice_number),
                    "preceding_invoice_date" => date('d/m/Y', strtotime($order->order_invoice_date)),
                    // "other_reference" => "2334"
                ]],

            ],

            "value_details" => [
                "total_assessable_value" => round($order->total_sale_value, 2),
                "total_cgst_value" => round($total_cgst_value, 2),
                "total_sgst_value" => round($total_sgst_value, 2),
                "total_igst_value" => round($total_igst_value, 2),

                "total_invoice_value" => round($order->total_after_tax, 2),

            ],
            "item_list" => $items_list
        ];

        if (!empty($order->dispatchFromAddress->address_id)) {
            $params["dispatch_details"] = [
                "company_name" => $order->dispatchFromParty->party_legal_name,
                "address1" => $order->dispatchFromAddress->address_line,
                "address2" => '',
                // "address2" => "Vila",
                "location" => strtoupper($order->dispatchFromAddress->city),
                "pincode" => $order->dispatchFromAddress->pincode,
                "state_code" => strtoupper($order->dispatchFromAddress->state_code),
            ];

        } else {
            $params["dispatch_details"] = [
                "company_name" => $order->billFromParty->party_legal_name,
                "address1" => $order->billFromAddress->address_line,
                "address2" => '',
                // "address2" => "Vila",
                "location" => strtoupper($order->billFromAddress->city),
                "pincode" => $order->billFromAddress->pincode,
                "state_code" => strtoupper($order->billFromAddress->state_code),
            ];
        }

        if (!empty($order->shipToAddress->address_id)) {
            $params["ship_details"] = [
                // "gstin" => "05AAAPG7885R002",
                "legal_name" => $order->shipToParty->party_legal_name,
                "trade_name" => $order->shipToParty->party_trade_name,
                "address1" => $order->shipToAddress->address_line,
                "address2" => '',
                "location" => strtoupper($order->shipToAddress->city),
                "pincode" => $order->shipToAddress->pincode,
                "state_code" => strtoupper($order->shipToAddress->state_code)
            ];
        } else {

            $params["ship_details"] = [
                // "gstin" => "05AAAPG7885R002",
                "legal_name" => $order->billToParty->party_legal_name,
                "trade_name" => $order->billToParty->party_trade_name,
                "address1" => $order->billToAddress->address_line,
                "address2" => '',
                "location" => strtoupper($order->billToAddress->city),
                "pincode" => $order->billToAddress->pincode,
                "state_code" => strtoupper($order->billToAddress->state_code)
            ];
        }
//        print_r($params);
       // die;
        $data=['order_invoice_number'=>$order->order_invoice_number];
        $response = $this->masterIndiaService->generateEInvoice($data,$params);

        if ($response instanceof Response) {
            //update psos for failure
            $message=json_decode($response->getContent(), true)['message'] ?? '';
            $order->update(
                [
                    'irn_status' => 'E',
                    'irn_status_message' => $message,
                ]);
            return response()->json(['status' => 'error', 'message' => $message, 'data' => []]);
        }


        $isRecordCreated = $this->masterIndiaEInvoiceTransaction->create([
            'order_id' => $order->order_id,
            'ack_no' => $response['message']['AckNo'],
            'ack_date' => $response['message']['AckDt'],
            'irn_no' => $response['message']['Irn'],
            'qrcode_url' => $response['message']['QRCodeUrl'],
            'einvoice_pdf_url' => $response['message']['EinvoicePdf'],
            'status_received' => $response['message']['Status'],
            'alert_message' => $response['message']['alert'],
            'request_id' => $response['requestId'],
            'invoice_status' => 'Created'
        ]);

        if ($isRecordCreated) {

            $order->update([
                'irn_no' => $response['message']['Irn'],
                'einvoice_pdf_url' => $response['message']['EinvoicePdf'],
                'irn_status' => 'C',
                'irn_status_message' => 'E-invoice has been created ' . ($response['display_message'] ?? ''),
                'order_invoice_number'=>$invoiceNumberForApi,
            ]);

            return response()->json(['status' => 'success', 'message' => 'E-Invoice has been created :' . ($response['display_message'] ?? ''), 'data' => []]);
        }

        return response()->json(['status' => 'error', 'message' => 'E-Invoice created but failed to save response', 'data' => []]);
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

    public function insertCreditNoteData(Request $request, $order_id)
    {
        // fetch items from order table then show
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
        if(!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order Not found', 'data' => []]);
        }
        if(empty($items)) {
            return response()->json(['status' => 'error', 'message' => 'Items are not added to order ', 'data' => []]);
        }

        if(empty($order->billFromParty->party_id))
        {
            return response()->json(['status' => 'error', 'message' => 'please update order FROM Party', 'data' => []]);
        }
        if(empty($order->billToParty->party_id))
        {
            return response()->json(['status' => 'error', 'message' => 'please update order Bill to Party', 'data' => []]);
        }
        if(empty($order->billFromAddress->address_id))
        {
            return response()->json(['status' => 'error', 'message' => 'please update bill FROM Address', 'data' => []]);
        }
        if(empty($order->billToAddress->address_id))
        {
            return response()->json(['status' => 'error', 'message' => 'please Update bill TO Address', 'data' => []]);
        }

        /** 2. Get or create credit note */
        $creditnote = MiCreditnoteTransaction::where('order_id', $order_id)->first();

        if (!$creditnote) {

            $creditnoteInvoice = MiCreditnoteTransaction::generateInvoiceNumber('CREW');

            $creditnote = MiCreditnoteTransaction::create([
                'creditnote_invoice_no'  => $creditnoteInvoice['invoice_no'],
                'financial_year'  => $creditnoteInvoice['financial_year'],
                'sequence_no'  => $creditnoteInvoice['sequence_no'],
                'order_id'            => $order_id,
                'order_invoice_number' => $order->order_invoice_number,
                'credit_note_status'  => 'N',
                'return_type'  => 'SALES_RETURN',
                'credit_note_date'    => now(),
            ]);
        }

        /** 3. Delete existing credit note items */
        MiCreditnoteItem::where('creditnote_id', $creditnote->creditnote_id)->delete();

        /** 4. Insert items from order items */
        $creditnoteItems = [];

        foreach ($items as $item) {
            $creditnoteItems[] = [
                'creditnote_id'        => $creditnote->creditnote_id,
                'item_id'              => $item->item_id,
                'item_name'            => $item->item_name,
                'item_description'     => $item->item_description,
                'item_code'            => $item->item_code,
                'hsn_code'             => $item->hsn_code,
                'item_unit'            => $item->item_unit,
                'total_item_quantity'  => $item->total_item_quantity,
                'price_per_unit'       => $item->price_per_unit,
                'tax_percentage'       => $item->tax_percentage,
                'taxable_amount'       => $item->taxable_amount,
                'after_tax_value'      => $item->after_tax_value,
                'created_at'           => now(),
                'updated_at'           => now(),
            ];
        }

        MiCreditnoteItem::insert($creditnoteItems);
        return response()->json(['status' => 'success', 'message' => 'CreditNote data has been inserted', 'data' => ['creditnote_id'=>$creditnote->creditnote_id] ]);
    }
}
