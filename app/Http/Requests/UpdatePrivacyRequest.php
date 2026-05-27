<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrivacyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'privacy_last_seen' => 'nullable|string|in:everyone,contacts,nobody',
            'privacy_profile_photo' => 'nullable|string|in:everyone,contacts,nobody',
            'privacy_about' => 'nullable|string|in:everyone,contacts,nobody',
            'privacy_status_updates' => 'nullable|string|in:everyone,contacts,nobody',
            'read_receipts' => 'nullable|boolean',
            'security_notifications' => 'nullable|boolean',
        ];
    }
}
