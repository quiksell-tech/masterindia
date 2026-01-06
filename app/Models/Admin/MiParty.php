<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class MiParty extends Model
{
    protected $table = 'mi_party';
    protected $primaryKey = 'party_id';

    protected $fillable = [
        'company_id',
        'party_gstn',
        'party_trade_name',
        'party_legal_name',
        'contact_name',
        'phone',
        'email',
        'name',
        'is_active'
    ];
    public function company()
    {
        return $this->belongsTo(MiCompany::class, 'company_id');
    }
}
