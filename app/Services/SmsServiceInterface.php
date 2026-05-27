<?php

namespace App\Services;

interface SmsServiceInterface
{
    /**
     * Send an SMS verification message to a phone number.
     *
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    public function sendSms(string $phoneNumber, string $message): bool;
}
