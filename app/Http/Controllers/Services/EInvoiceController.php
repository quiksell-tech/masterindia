<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Services\EInvoice\EInvoiceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EInvoiceController extends Controller
{
    public function __construct(EInvoiceManager $EInvoiceManager){
        $this->EInvoiceManager = $EInvoiceManager;
    }


    public function generateEInvoice(Request $request){

      $errors=Validator::make($request->all(),
          [
              'sell_invoice_ref_no'=>'required|integer',
              'einvoice_service' => 'required',
          ],
          [
              'sell_invoice_ref_no.*'=>'Sell invoice reference number is required',
          ])
          ->errors()
          ->toArray();

      if (count($errors)) {
          return json_response(422, 'Invalid or Missing parameters', compact('errors'));
      }
      return $this->EInvoiceManager->generateEInvoice($request->all());

    }

    public function generateCreditNote(Request $request){

        $errors=Validator::make($request->all(),
            [
                'credit_note_ref_no'=>'required',
                'einvoice_service' => 'required',
            ],
            [
                'credit_note_ref_no.*'=>'Credit note reference number is required',
            ])
            ->errors()
            ->toArray();

        if (count($errors)) {
            return json_response(422, 'Invalid or Missing parameters', compact('errors'));
        }
        return $this->EInvoiceManager->generateCreditNote($request->all());

    }

    public function cancelEInvoice(Request $request){
      $errors=Validator::make($request->all(),
          [
              'sell_invoice_ref_no'=>'required|integer',
              'cancel_reason' => 'required|in:incorrect-details,duplicate',
              'cancel_remarks' => 'required',
              'einvoice_service' => 'required',
          ],
          [
              'sell_invoice_ref_no.*'=>'Sell invoice reference number is required',
          ])
          ->errors()
          ->toArray();

      if (count($errors)) {
          return json_response(422, 'Invalid or Missing parameters', compact('errors'));
      }
      return $this->EInvoiceManager->cancelEInvoice($request->all());
    }

    public function getEInvoice(Request $request){
      $errors=Validator::make($request->all(),
          [
              'sell_invoice_ref_no'=>'required|integer',
              'einvoice_service' => 'required',
          ],
          [
              'sell_invoice_ref_no.*'=>'Sell invoice reference number is required',
          ])
          ->errors()
          ->toArray();

      if (count($errors)) {
          return json_response(422, 'Invalid or Missing parameters', compact('errors'));
      }
      return $this->EInvoiceManager->getEInvoice($request->all());
    }

    public function getGSTINDetails(Request $request){
      $errors=Validator::make($request->all(),
          [
              'gstin_number'=>'required',
              'einvoice_service' => 'required',
          ],
          [
              'gstin_number.*'=>'GSTIN number is required',
          ])
          ->errors()
          ->toArray();

      if (count($errors)) {
          return json_response(422, 'Invalid or Missing parameters', compact('errors'));
      }
      return $this->EInvoiceManager->getGSTINDetails($request->all());
    }

    public function syncGSTINDetails(Request $request){
      $errors=Validator::make($request->all(),
          [
              'gstin_number'=>'required',
              'einvoice_service' => 'required',
          ],
          [
              'gstin_number.*'=>'GSTIN number is required',
          ])
          ->errors()
          ->toArray();

      if (count($errors)) {
          return json_response(422, 'Invalid or Missing parameters', compact('errors'));
      }
      return $this->EInvoiceManager->syncGSTINDetails($request->all());
    }

    public function getApiCounts(Request $request){
      $errors=Validator::make($request->all(),
          [
              'einvoice_service' => 'required',
              'account_email' => 'required',
              'from_date' => 'required|date_format:Y-m-d',
              'to_date' => 'required|date_format:Y-m-d',
          ])
          ->errors()
          ->toArray();

      if (count($errors)) {
          return json_response(422, 'Invalid or Missing parameters', compact('errors'));
      }

      return $this->EInvoiceManager->getApiCounts($request->all());


    }

    // public function generateBulkEInvoice(Request $request){
    //   $errors=Validator::make($request->all(),
    //       [
    //           'sell_invoice_ref_no'=>'required|integer',
    //           'einvoice_service' => 'required',
    //       ],
    //       [
    //           'sell_invoice_ref_no.*'=>'Sell invoice reference number is required',
    //       ])
    //       ->errors()
    //       ->toArray();
    //
    //   if (count($errors)) {
    //       return json_response(422, 'Invalid or Missing parameters', compact('errors'));
    //   }
    //   return $this->EInvoiceManager->generateBulkEInvoice(null);
    // }

    /**
    * Apis related to eway bill using einvoice IRL starts from here
    * Not Being used for the time
    *
    */

    // public function generateEwayBillByIRN(Request $request){
    //   $errors=Validator::make($request->all(),
    //       [
    //           'sell_invoice_ref_no'=>'required|integer',
    //           'einvoice_service' => 'required',
    //       ],
    //       [
    //           'sell_invoice_ref_no.*'=>'Sell invoice reference number is required',
    //       ])
    //       ->errors()
    //       ->toArray();
    //
    //   if (count($errors)) {
    //       return json_response(422, 'Invalid or Missing parameters', compact('errors'));
    //   }
    //   return $this->EInvoiceManager->generateEwayBillByIRN($request->all());
    // }
    //
    // public function getEwayBillDetailsyIRN(Request $request){
    //   $errors=Validator::make($request->all(),
    //       [
    //           'sell_invoice_ref_no'=>'required|integer',
    //           'einvoice_service' => 'required',
    //       ],
    //       [
    //           'sell_invoice_ref_no.*'=>'Sell invoice reference number is required',
    //       ])
    //       ->errors()
    //       ->toArray();
    //
    //   if (count($errors)) {
    //       return json_response(422, 'Invalid or Missing parameters', compact('errors'));
    //   }
    //   return $this->EInvoiceManager->getEwayBillDetailsyIRN($request->all());
    // }
    //
    // public function cancelEwayBillByIRN(Request $request){
    //   $errors=Validator::make($request->all(),
    //       [
    //           'sell_invoice_ref_no'=>'required|integer',
    //           'einvoice_service' => 'required',
    //       ],
    //       [
    //           'sell_invoice_ref_no.*'=>'Sell invoice reference number is required',
    //       ])
    //       ->errors()
    //       ->toArray();
    //
    //   if (count($errors)) {
    //       return json_response(422, 'Invalid or Missing parameters', compact('errors'));
    //   }
    //   return $this->EInvoiceManager->cancelEwayBillByIRN($request->all());
    // }



}
