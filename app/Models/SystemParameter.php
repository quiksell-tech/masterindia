<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemParameter extends Model
{
    protected $table = 'system_parameters';

    protected $primaryKey = 'system_parameter_id';

    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * Laravel timestamps are disabled
     * because table uses custom timestamp column
     */
    public $timestamps = false;

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'sysprm_provider',
        'sysprm_name',
        'sysprm_value',
        'current_flag',
        'last_updated_by',
        'last_updated_on',
    ];
    public static function getSystemParametersByProviderKV(string $provider)
    {
        return self::query()
            ->where('sysprm_provider', $provider)
            ->where('current_flag', 'Y')
            ->pluck('sysprm_value', 'sysprm_name');
    }

}
