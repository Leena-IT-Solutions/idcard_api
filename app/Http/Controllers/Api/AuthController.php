<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

        $user = User::where($loginField, $request->login)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('roles'),
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user()->load('roles'));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get registration configuration (e.g. WhatsApp OTP requirement).
     */
    public function registrationConfig()
    {
        $otpService = new \App\Services\WhatsAppOtpService();
        return response()->json([
            'whatsapp_otp_required' => $otpService->isConfigured(),
        ]);
    }

    /**
     * Send WhatsApp OTP for registration verification.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|max:20',
        ]);

        $otpService = new \App\Services\WhatsAppOtpService();
        $result = $otpService->sendOtp($request->mobile);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'expires_in_seconds' => $result['expires_in_seconds'] ?? 300,
        ]);
    }

    /**
     * Verify WhatsApp OTP without registering.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|max:20',
            'otp' => 'required|string|min:4|max:10',
        ]);

        $otpService = new \App\Services\WhatsAppOtpService();
        $isValid = $otpService->verifyOtp($request->mobile, $request->otp, false);

        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired WhatsApp OTP.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp OTP verified successfully.',
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'mobile' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'otp' => 'nullable|string',
        ]);

        $otpService = new \App\Services\WhatsAppOtpService();

        // If WhatsApp OTP is enforced and active in Settings, verify OTP or pre-verified flag
        if ($otpService->isConfigured()) {
            $isVerified = false;

            if (!empty($request->otp)) {
                $isVerified = $otpService->verifyOtp($request->mobile, $request->otp, true);
            } elseif ($otpService->isMobileVerified($request->mobile)) {
                $isVerified = true;
            }

            if (!$isVerified) {
                return response()->json([
                    'message' => 'WhatsApp OTP verification is required to complete registration.',
                    'errors' => ['otp' => ['The WhatsApp OTP is invalid, expired, or missing.']],
                ], 422);
            }

            $otpService->clearVerification($request->mobile);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
        ]);

        // Only assign the Parent role
        $user->assignRole('parent');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('roles'),
        ], 201);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = \Illuminate\Support\Facades\Password::sendResetLink(
            $request->only('email')
        );

        return $status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 400);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'mobile' => 'required|string|max:20|unique:users,mobile,' . $user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->load('roles')
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password updated successfully']);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }
}
