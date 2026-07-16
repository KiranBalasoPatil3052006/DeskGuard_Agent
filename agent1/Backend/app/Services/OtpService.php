<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OtpService
{
    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 10;

    public function generate(string $mobileNumber): array
    {
        try {
            DB::beginTransaction();

            OtpCode::forMobile($mobileNumber)
                ->where('is_used', false)
                ->update(['is_used' => true, 'used_at' => now()]);

            $otp = (string) random_int(100000, 999999);
            $expiresAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

            OtpCode::create([
                'mobile_number' => $mobileNumber,
                'otp' => $otp,
                'expires_at' => $expiresAt,
            ]);

            DB::commit();

            Log::info('OTP generated', ['mobile' => $mobileNumber]);

            return [
                'otp' => $otp,
                'expires_at' => $expiresAt->toIso8601String(),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('OtpService::generate failed', [
                'mobile' => $mobileNumber,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function verify(string $mobileNumber, string $otp): ?OtpCode
    {
        $otpRecord = OtpCode::forMobile($mobileNumber)
            ->valid()
            ->where('otp', $otp)
            ->latest()
            ->first();

        if (!$otpRecord) {
            Log::warning('OTP verification failed', ['mobile' => $mobileNumber]);
            return null;
        }

        $otpRecord->update([
            'is_used' => true,
            'used_at' => now(),
        ]);

        Log::info('OTP verified', ['mobile' => $mobileNumber]);

        return $otpRecord;
    }

    public function findOrCreateUser(string $mobileNumber): User
    {
        $user = User::where('mobile_number', $mobileNumber)->first();

        if (!$user) {
            $user = User::create([
                'mobile_number' => $mobileNumber,
                'name' => null,
                'email' => null,
                'password' => null,
                'is_verified' => true,
                'is_active' => true,
            ]);

            Log::info('User auto-created after OTP verification', [
                'mobile' => $mobileNumber,
                'user_id' => $user->id,
            ]);
        }

        if (!$user->is_verified) {
            $user->update(['is_verified' => true]);
        }

        return $user;
    }

    public function issueToken(User $user): string
    {
        return $user->createToken('agent-mobile-token')->plainTextToken;
    }
}
