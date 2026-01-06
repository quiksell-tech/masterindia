<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class MiCompanyAddress extends Model
{
    protected $table = 'mi_company_addresses';
    protected $primaryKey = 'address_id';

    protected $fillable = [
        'company_id',
        'address_type',
        'address_line',
        'party_id',
        'city',
        'state',
        'state_code',
        'pincode',
        'pincode_id',
        'is_active',
    ];

    public function company()
    {
        return $this->belongsTo(MiCompany::class, 'company_id');
    }
    public function party()
    {
        return $this->belongsTo(MiParty::class, 'party_id');
    }

    public function pincodeMaster()
    {
        return $this->belongsTo(MiPincodeMaster::class, 'pincode_id');
    }
}
