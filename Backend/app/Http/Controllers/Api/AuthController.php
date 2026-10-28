<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\PasswordRecoveryOtp;
use App\Models\Otp;
use App\Models\User;
use App\Services\OtpEmailRecoverService;
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
        $token = $user->createToken('api-token-01384dsjhfsaff-dfashfjdsadskjfhf-natha-sakfjsdadhjfg
        asxdsfafdsadfsadfsafsdafsdfdsafsdafdsafsdafdsfdsafsafsd')->plainTextToken;
        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user->load('roles.permissions'),
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
            $request->user()->load('roles.permissions')
        );
    }
    public function recoveremail(Request $request, OtpEmailRecoverService $otpEmailRecoverService)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email not found.',
            ], 404);
        }
        $otp = rand(100000, 999999);
        PasswordRecoveryOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp' => Hash::make((string)$otp),
            'type' => 'recovery otp',
            'expires_at' => now()->addMinutes(5),
        ]);
        $otpEmailRecoverService->send(
            $user->email,
            $otp
        );
        return response()->json([
            'message' => 'Recovery OTP sent successfully.',
        ]);
    }
    public function verifyRecoverOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);
        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return response()->json([
                'message' => 'Email not found in users.'
            ], 404);
        }
        $recovery = PasswordRecoveryOtp::where('user_id', $user->id)
            ->where('email', $validated['email'])
            ->where('type', 'recovery otp')
            ->whereNull('verified_at')
            ->latest()
            ->first();
        if (!$recovery) {
            return response()->json([
                'message' => 'Email does not match recovery record.'
            ], 404);
        }
        if (now()->greaterThan($recovery->expires_at)) {
            return response()->json([
                'message' => 'OTP has expired.'
            ], 422);
        }
        if (!Hash::check($validated['otp'], $recovery->otp)) {
            return response()->json([
                'message' => 'OTP does not match.'
            ], 422);
        }
        $recovery->update([
            'verified_at' => now(),
        ]);
        return response()->json([
            'message' => 'OTP verified successfully.',
            'user_id' => $user->id,
        ]);
    }
    public function ChangePassword(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);
        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'Email not found.'], 404);
        }
        $recovery = PasswordRecoveryOtp::where('user_id', $user->id)
            ->where('email', $validated['email'])
            ->where('type', 'recovery otp')
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes(10))
            ->latest()
            ->first();
        if (!$recovery) {
            return response()->json([
                'message' => 'Verification expired. Please request a new code.'
            ], 403);
        }
        $user->update([
            'password_hash' =>  Hash::make($validated['password']),
        ]);
        PasswordRecoveryOtp::where('user_id' ,$user->id)->delete();
        return response()->json(['message' => 'Password changed successfully.']);
    }
}
