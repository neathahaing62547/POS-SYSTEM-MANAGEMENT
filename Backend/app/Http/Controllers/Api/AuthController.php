<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $key = strtolower($validated['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Too many login attempts. Please try again later.'
            ], 429);
        }
        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password_hash)) {
            RateLimiter::hit($key, 300);
            return response()->json([
                'message' => 'Invalid email or password.'
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account is not active.'
            ], 403);
        }
     $otp = random_int(100000, 999999);

        Otp::where('user_id', $user->id)
            ->where('type', 'login')
            ->delete();
        Otp::create([
            'user_id' => $user->id,
            'otp' => Hash::make((string) $otp),
            'type' => 'login',
            'expires_at' => now()->addMinutes(5),
        ]);
        Mail::to($user->email)->send(
            new OtpMail((string) $otp, 'login')
        );
        RateLimiter::clear($key);
        return response()->json([
            'message' => 'OTP sent to your email.',
            'user_id' => $user->id,
        ]);
    }

    public function verifyLoginOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'otp' => ['required', 'digits:6'],
        ]);
        $otp = Otp::where('user_id', $validated['user_id'])
            ->where('type', 'login')
            ->whereNull('verified_at')
            ->latest()
            ->first();
        if (!$otp) {
            return response()->json([
                'message' => 'OTP not found.'
            ], 404);
        }
        if (now()->greaterThan($otp->expires_at)) {
            return response()->json([
                'message' => 'OTP has expired.'
            ], 422);
        }
        if (!Hash::check($validated['otp'], $otp->otp)) {
            return response()->json([
                'message' => 'Invalid OTP.'
            ], 422);
        }
        $otp->update([
            'verified_at' => now(),
        ]);
        $user = User::find($validated['user_id']);

        $user->update([
            'last_login_at' => now(),
        ]);
        $token = $user->createToken('api-token-01384dsjhfsaff-dfashfjdsadskjfhf')->plainTextToken;
        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user->load('roles'),
        ]);
    }
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful.'
        ]);
    }
    public function me(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load('roles')
        );
    }
}
