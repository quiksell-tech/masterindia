<?php

namespace App\Services\EInvoice;

interface EinvoiceService
{

    public function generateEInvoice($data);

    public function cancelEInvoice($data);

    public function getEInvoice($data);

    public function getGSTINDetails($data);

    public function syncGSTINDetails($data);

    // public function generateBulkEInvoice($data);
    //
    // public function generateEwayBillByIRN($data);
    //
    // public function getEwayBillDetailsyIRN($data);
    //
    // public function cancelEwayBillByIRN($data);

}
