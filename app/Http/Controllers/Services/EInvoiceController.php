<?php

namespace App\Http\Controllers\Services;

use App\Models\Admin\MiOrder;
use App\Http\Controllers\Controller;
use App\Models\Admin\MiOrderItem;
use App\Models\MasterIndiaEInvoiceTransaction;
use App\Services\EInvoice\MasterIndiaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;


class EInvoiceController extends Controller
{
    protected $masterIndiaService;
    protected $masterIndiaEInvoiceTransaction;
    protected $company_gstn = '05AAABB0639G1Z8';

    public function __construct(MasterIndiaService $masterIndiaService, MasterIndiaEInvoiceTransaction $masterIndiaEInvoiceTransaction)
    {

        $this->masterIndiaService = $masterIndiaService;
        $this->masterIndiaEInvoiceTransaction = $masterIndiaEInvoiceTransaction;

    }

    public function getEInvoice(Request $request, MiOrder $miOrder)
    {

        $order = MiOrder::where('order_id', $miOrder->order_id)->get();
        if($order->isEmpty()){
            return response()->json(['status' => false, 'message' => 'Order is not found', 'data' => []]);
        }
        $params = [

            "gstin" => $this->company_gstn,
            "irn" => $order->irn_no,
        ];
        $data=['order_invoice_number'=>$order->order_invoice_number];
        $response = $this->masterIndiaService->getEInvoice($data,$params);
    }

    public function generateCreditNote(Request $request,  $order_id)
    {
        die('ok');
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

        if (empty($items)) {
            return response()->json(['status' => false, 'message' => 'Order Items Are not added', 'data' => []]);
        }

        if (empty($order)) {
            return response()->json(['status' => false, 'message' => 'Order is not found', 'data' => []]);
        }
        if(empty($order->billFromAddress->address_id))
        {
            return response()->json(['status' => false, 'message' => 'please update bill FROM Address', 'data' => []]);
        }
        if(empty($order->billToAddress->address_id))
        {
            return response()->json(['status' => false, 'message' => 'please Update bill TO Address', 'data' => []]);
        }
        if(!empty($order->irn_no) && $order->irn_status=='C' )
        {
            return response()->json(['status' => false, 'message' => 'IRN Already Created', 'data' => []]);
        }
        if(!empty($order->eway_bill_no) && $order->eway_status!='C' )
        {
            return response()->json(['status' => false, 'message' => 'Eway Bill Is Not Created', 'data' => []]);
        }
        // validate GSTN
        if (!empty($order->billToParty->party_gstn)) {

            if ($order->supply_type == 'outward') {
                $valid = $this->masterIndiaService->getGSTINDetailsNew([
                    'buyer_gstin' => $order->billToParty->party_gstn,
                    'sell_invoice_ref_no' => $order->order_invoice_number,
                    'company_gstin' => $this->company_gstn,
                ]);

            } else {

                $valid = $this->masterIndiaService->getGSTINDetailsNew([
                    'buyer_gstin' => $order->billFromParty->party_gstn,
                    'sell_invoice_ref_no' => $this->company_gstn,
                    'company_gstin' => $this->company_gstn,
                ]);
            }


            if ($valid instanceof Response) {
                // update psos for error

                $order->update([
                    'irn_status' => 'E',
                    'irn_status_message' => json_decode($valid->getContent(), true)['message'] ?? ''
                ]);
                return response()->json(['status' => false, 'message' => $valid->getContent(), true['message'] ?? '', 'data' => []]);
            }

            if ($valid['gstin_status'] != 'active') {

                // set error to skip this record for batch process

                $order->update([
                    'irn_status' => 'E',
                    'irn_status_message' => 'GSTIN not active: ' . ($valid['gstin_status'] ?? 'unknown')
                ]);
                return response()->json(['status' => false, 'message' => 'GSTIN not active: ' . ($valid['gstin_status'] ?? 'unknown'), 'data' => []]);

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
                "product_description" => $item->item_name,
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

            "user_gstin" => $this->company_gstn,
            "data_source" => "erp",
            "transaction_details" => [
                "supply_type" => "B2B",
                "charge_type" => "N",
                "igst_on_intra" => "N",
                "ecommerce_gstin" => ""
            ],
            "document_details" => [
                "document_type" => "CRN",
                "document_number" => strtoupper($order->order_invoice_number),
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

        if (!empty($order->dispatchFromAddress->address_id)) {
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
        $data=['order_invoice_number'=>$order->order_invoice_number];

        $response = $this->masterIndiaService->generateCreditNote($data,$params);

    }

    public function cancelEInvoice(Request $request, $order_id)
    {
        die('dsdsds');
        $order = MiOrder::where('order_id', $order_id)->get();
        if (empty($order)) {
            return response()->json(['status' => false, 'message' => 'Order is not found', 'data' => []]);
        }
        $params = [

            "user_gstin" => $this->company_gstn,
            "irn" => $order->irn_no,
            "cancel_reason" => $request->cancellation_reasons,
            "cancel_remarks" => $request->cancel_remarks ?? 'Wrong Entry'
        ];
        $data=['order_invoice_number'=>$order->order_invoice_number];
        $response = $this->masterIndiaService->cancelEInvoice($data,$params);

        $isUpdated = $this->masterIndiaEInvoiceTransaction->update(['order_id' => $order->order_id], [
            'invoice_status' => 'Cancelled',
            'cancellation_reason' => $params["cancel_reason"],
            'cancellation_remarks' => $params['cancel_remarks'],
            'status_received' => 'CNL'
        ]);

        if ($isUpdated) {
            $order->update([
                'irn_status' => 'X',
                'irn_status_message' => 'Einvoice has been cancelled ' . ($response['display_message'] ?? '')
            ]);
            return response()->json(['status' => true, 'message' => 'Einvoice has been cancelled at', 'data' => []]);
        }

        return response()->json(['status' => false, 'message' => 'Invoice cancelled but failed to save response', 'data' => []]);


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

        if (empty($items)) {
            return response()->json(['status' => false, 'message' => 'Order Items Are not added', 'data' => []]);
        }

        if (empty($order)) {
            return response()->json(['status' => false, 'message' => 'Order is not found', 'data' => []]);
        }
        if(empty($order->billFromAddress->address_id))
        {
            return response()->json(['status' => false, 'message' => 'please update bill FROM Address', 'data' => []]);
        }
        if(empty($order->billToAddress->address_id))
        {
            return response()->json(['status' => false, 'message' => 'please Update bill TO Address', 'data' => []]);
        }
        if(!empty($order->irn_no) && $order->irn_status=='C' )
        {
            return response()->json(['status' => false, 'message' => 'IRN Already Created', 'data' => []]);
        }
        if(!empty($order->eway_bill_no) && $order->eway_status!='C' )
        {
            return response()->json(['status' => false, 'message' => 'Eway Bill Is Not Created', 'data' => []]);
        }
        // validate GSTN
        if (!empty($order->billToParty->party_gstn)) {

            if ($order->supply_type == 'outward') {
                $valid = $this->masterIndiaService->getGSTINDetailsNew([
                    'buyer_gstin' => $order->billToParty->party_gstn,
                    'sell_invoice_ref_no' => $order->order_invoice_number,
                    'company_gstin' => $this->company_gstn,
                ]);

            } else {

                $valid = $this->masterIndiaService->getGSTINDetailsNew([
                    'buyer_gstin' => $order->billFromParty->party_gstn,
                    'sell_invoice_ref_no' => $this->company_gstn,
                    'company_gstin' => $this->company_gstn,
                ]);
            }


            if ($valid instanceof Response) {
                // update psos for error

                $order->update([
                    'irn_status' => 'E',
                    'irn_status_message' => json_decode($valid->getContent(), true)['message'] ?? ''
                ]);
                return response()->json(['status' => false, 'message' => $valid->getContent(), true['message'] ?? '', 'data' => []]);
            }

            if ($valid['gstin_status'] != 'active') {

                // set error to skip this record for batch process

                $order->update([
                    'irn_status' => 'E',
                    'irn_status_message' => 'GSTIN not active: ' . ($valid['gstin_status'] ?? 'unknown')
                ]);
                return response()->json(['status' => false, 'message' => 'GSTIN not active: ' . ($valid['gstin_status'] ?? 'unknown'), 'data' => []]);

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
                "product_description" => $item->item_name,
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

            "user_gstin" => '09AAAPG7885R002',//$this->company_gstn,
            "data_source" => "erp",
            "transaction_details" => [
                "supply_type" => "B2B",
                "charge_type" => "N",
                "igst_on_intra" => "N",
                "ecommerce_gstin" => ""
            ],
            "document_details" => [
                "document_type" => "INV",
                "document_number" => strtoupper($order->order_invoice_number),
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
//        die;
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
            return response()->json(['status' => false, 'message' => $message, 'data' => []]);
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
                'irn_status' => 'C',
                'irn_status_message' => 'E-invoice has been created ' . ($response['display_message'] ?? '')
            ]);

            return response()->json(['status' => true, 'message' => 'E-Invoice has been created :' . ($response['display_message'] ?? ''), 'data' => []]);
        }

        return response()->json(['status' => false, 'message' => 'E-Invoice created but failed to save response', 'data' => []]);
    }
}
