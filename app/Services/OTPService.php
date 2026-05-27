<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OTPService
{
    protected $smsService;

    public function __construct(SmsServiceInterface $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Generate a 6-digit random OTP, cache it for 5 minutes, and dispatch via active driver.
     */
    public function generateOTP(string $phoneNumber): string
    {
        $driver = config('sms.driver', 'local');
        $whitelist = config('sms.sandbox.whitelist', []);

        // If sandbox driver is active and number is in testing whitelist, use placeholder
        if ($driver === 'sandbox' && in_array($phoneNumber, $whitelist)) {
            $otp = config('sms.sandbox.placeholder_otp', '123456');
            Log::info("SMS_DRIVER=sandbox | Whitelisted testing phone number {$phoneNumber} assigned placeholder OTP {$otp}");
        } else {
            $otp = (string) rand(100000, 999999);
        }

        // Cache the OTP under the phone number key with a 5-minute (300 seconds) expiration in Redis
        Cache::put('otp_' . $phoneNumber, $otp, 300);

        // Send OTP using the bounded SMS service implementation
        $this->smsService->sendSms($phoneNumber, "Your verification code is: {$otp}. It is valid for 5 minutes.");

        return $otp;
    }

    /**
     * Verify the provided OTP against the cached value.
     */
    public function verifyOTP(string $phoneNumber, string $code): bool
    {
        $driver = config('sms.driver', 'local');
        $whitelist = config('sms.sandbox.whitelist', []);

        // Whitelisted numbers can automatically bypass via the placeholder code under sandbox mode
        if ($driver === 'sandbox' && in_array($phoneNumber, $whitelist)) {
            if ($code === config('sms.sandbox.placeholder_otp', '123456')) {
                return true;
            }
        }

        // Developer bypass under local driver for convenience
        if ($driver === 'local' && $code === '123456') {
            return true;
        }

        $cachedOtp = Cache::get('otp_' . $phoneNumber);

        if ($cachedOtp && $cachedOtp === $code) {
            Cache::forget('otp_' . $phoneNumber);
            return true;
        }

        return false;
    }
}
