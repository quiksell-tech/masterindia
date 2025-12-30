<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Auth\MiAdminUser;

class AdminAuthController extends Controller
{
    /**
     * Show login page
     */
    public function loginPage()
    {
        if (session()->has('admin_id')) {
            return redirect('/dashboard');
        }

        return view('auth.login');
    }

    /**
     * Send OTP (AJAX)
     */
    public function sendOtp(Request $request)
    {

        $request->validate([
            'phone' => 'required|digits:10'
        ]);

        // Check if user exists
        $user = MiAdminUser::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Phone number not registered'
            ], 422);
        }

        // Generate OTP
        $otp = 111111; // For testing; use random_int(100000, 999999) in production

        // Update OTP and expiry
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
            'is_active' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'OTP sent successfully'
        ]);
    }


    /**
     * Verify OTP (AJAX)
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|digits:10',
            'otp'   => 'required|digits:6'
        ]);

        $user = MiAdminUser::where('phone', $request->phone)
            ->where('otp', $request->otp)
            ->where('otp_expires_at', '>=', now())
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired OTP'
            ], 422);
        }

        $user->update([
            'otp' => null,
            'last_login_at' => now(),
        ]);

        session([
            'admin_id' => $user->admin_id
        ]);

        return response()->json([
            'status' => true,
            'redirect' => url('/dashboard')
        ]);
    }

    /**
     * Logout
     */
    public function logout()
    {
        session()->forget('admin_id');
        session()->invalidate();

        return redirect('/');
    }
}
