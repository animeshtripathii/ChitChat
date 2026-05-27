<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LocalSmsService implements SmsServiceInterface
{
    /**
     * Write SMS verification code message to local laravel.log file.
     */
    public function sendSms(string $phoneNumber, string $message): bool
    {
        Log::info("SMS_DRIVER=local | TO: {$phoneNumber} | MESSAGE: {$message}");
        return true;
    }
}
