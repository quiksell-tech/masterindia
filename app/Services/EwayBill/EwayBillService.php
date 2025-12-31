<?php

namespace App\Services\EwayBill;

interface EwayBillService
{

    public function generateEwayBill($data);


    public function updateEwayBill($data);


    public function cancelEwayBill($data);


    public function getEwayBillDetails($data);


    public function getGSTINDetails($data);

}
