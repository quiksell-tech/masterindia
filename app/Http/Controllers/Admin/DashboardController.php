<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auth\MiAdminUser;

class DashboardController extends Controller
{
    /**
     * Show dashboard page
     */
    public function index()
    {
        // Middleware already ensures admin is logged in
        // You can fetch admin info if needed
        $admin = MiAdminUser::find(session('admin_id'));

        // Pass data to dashboard
        return view('dashboard', compact('admin'));
    }
}
