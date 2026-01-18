<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemParameter extends Model
{
    protected $table = 'system_parameters';

    protected $primaryKey = 'system_parameter_id';

    protected $fillable = [
        'sysprm_provider',
        'sysprm_name',
        'sysprm_value',
        'current_flag',
        'created_at',
        'updated_at',
    ];

    public static function getSystemParametersByName(string $name)
    {
        return self::query()
            ->where('sysprm_name', $name)
            ->where('current_flag', 'Y')
            ->pluck('sysprm_value', 'sysprm_name');
    }

}
