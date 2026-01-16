<?php

namespace App\Models;


use App\Models\Admin\MiCreditnoteItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class MiCreditnoteTransaction extends Model
{
    protected $table = 'masterindia_creditnote_transactions';

    protected $primaryKey = 'creditnote_id';

    public $timestamps = true;

    protected $fillable = [
        'creditnote_invoice_no',
        'order_invoice_number',
        'order_id',
        'credit_note_status',
        'credit_note_status_message',
        'credit_note_date',
        'gst_invoice_no',
        'return_type',
        'return_date',
        'einvoice_no',
        'financial_year',
        'sequence_no',
        'creditnote_pdf_url',
        'updated_at',
        'ack_no',
        'creditnote_irn_no',
        'qrcode_url',
        'einvoice_pdf_url',
        'status_received',
        'alert_message',
        'request_id',
    ];
    protected $casts = [
        'return_date' => 'date',
        'credit_note_date' => 'date',
    ];
    public function items()
    {
        return $this->hasMany(
            MiCreditnoteItem::class,
            'creditnote_id',
            'creditnote_id'
        );
    }
    public function getTotalSaleValueAttribute($value)
    {
        $totalSaleValue = 0;
        $totalTax       = 0;
        $totalAfterTax  = 0;
        foreach ($this->items as $item) {

            $taxableAmount = $item->total_item_quantity * $item->price_per_unit;
            $taxAmount     = ($taxableAmount * $item->tax_percentage) / 100;
            $afterTax      = $taxableAmount + $taxAmount;

            $totalSaleValue += $taxableAmount;
            $totalTax       += $taxAmount;
            $totalAfterTax  += $afterTax;
        }
        return $totalSaleValue;
    }
    public function getTotalTaxAttribute($value)
    {
        $totalSaleValue = 0;
        $totalTax       = 0;
        $totalAfterTax  = 0;
        foreach ($this->items as $item) {

            $taxableAmount = $item->total_item_quantity * $item->price_per_unit;
            $taxAmount     = ($taxableAmount * $item->tax_percentage) / 100;
            $afterTax      = $taxableAmount + $taxAmount;

            $totalSaleValue += $taxableAmount;
            $totalTax       += $taxAmount;
            $totalAfterTax  += $afterTax;
        }
        return $totalTax;
    }

    public function getTotalAfterTaxAttribute($value)
    {
        $totalSaleValue = 0;
        $totalTax       = 0;
        $totalAfterTax  = 0;
        foreach ($this->items as $item) {

            $taxableAmount = $item->total_item_quantity * $item->price_per_unit;
            $taxAmount     = ($taxableAmount * $item->tax_percentage) / 100;
            $afterTax      = $taxableAmount + $taxAmount;

            $totalSaleValue += $taxableAmount;
            $totalTax       += $taxAmount;
            $totalAfterTax  += $afterTax;
        }
        return $totalAfterTax;
    }
    public static function generateInvoiceNumber(string $prefix = 'HREW'): array
    {
        $fy = self::getFinancialYearCode();

        $lastSequence = self::where('financial_year', $fy)
            ->max('creditnote_invoice_no');
        $lastSequence=intval($lastSequence);
        $nextSequence = $lastSequence ? $lastSequence + 1 : 1;

        $invoiceNo = sprintf(
            '%s-%s-%05d',
            $prefix,
            $fy,
            $nextSequence
        );

        return [
            'invoice_no'     => $invoiceNo,
            'financial_year' => $fy,
            'sequence_no'    => $nextSequence,
        ];
    }
    public static function getFinancialYearCode($date = null): string
    {
        $date = $date ? Carbon::parse($date) : now();

        $year  = $date->year;
        $month = $date->month;

        if ($month >= 4) {
            return substr($year, -2) . substr($year + 1, -2);
        }

        return substr($year - 1, -2) . substr($year, -2);
    }
}
