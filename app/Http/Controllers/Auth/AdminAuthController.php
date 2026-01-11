<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\MiAdminUser;
use App\Services\Msg91Service;
use Illuminate\Http\Request;

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
    public function sendOtp(Request $request,Msg91Service $msg91)
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
        $otp = 224444; // For testing; for develoment

        if (app()->isProduction())
        {
            $otp= random_int(100000, 999999);
            $mobile = '91' . $user->phone;
            $result = $msg91->sendOtpNew($mobile, $otp);
            var_dump($result);
            if (!$result['success']) {
                return response()->json([
                    'status' => false,
                    'message' => 'OTP failed',
                    'error' => $result['message']
                ], 500);
            }
        }


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
