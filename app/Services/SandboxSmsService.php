<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SandboxSmsService implements SmsServiceInterface
{
    /**
     * Sends SMS to phone number, falling back to Twilio if API keys are set.
     */
    public function sendSms(string $phoneNumber, string $message): bool
    {
        $sid = config('sms.twilio.sid');
        $token = config('sms.twilio.token');
        $from = config('sms.twilio.from');

        if ($sid && $token && $from) {
            try {
                $response = Http::withBasicAuth($sid, $token)
                    ->asForm()
                    ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                        'To' => $phoneNumber,
                        'From' => $from,
                        'Body' => $message,
                    ]);

                if ($response->successful()) {
                    Log::info("SMS_DRIVER=sandbox | Twilio Send Success to {$phoneNumber}");
                    return true;
                }

                Log::error("SMS_DRIVER=sandbox | Twilio Send Failed: " . $response->body());
            } catch (\Exception $e) {
                Log::error("SMS_DRIVER=sandbox | Twilio Exception: " . $e->getMessage());
            }
        }

        // Fallback log if sandbox is active without credentials configured yet
        Log::warning("SMS_DRIVER=sandbox | Twilio credentials unconfigured. Falling back to log: TO: {$phoneNumber} | MESSAGE: {$message}");
        return true;
    }
}
