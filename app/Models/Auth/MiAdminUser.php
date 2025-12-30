<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;

class MiAdminUser extends Model
{
    protected $table = 'mi_admin_user';
    protected $primaryKey = 'admin_id';
    protected $fillable = [
        'phone',
        'name',
        'otp',
        'otp_expires_at',
        'is_active',
        'last_login_at',
    ];



    public $timestamps = true;
}
