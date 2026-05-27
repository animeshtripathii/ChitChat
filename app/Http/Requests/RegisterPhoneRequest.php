<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone_number' => [
                'required',
                'string',
                'regex:/^\d{4,14}$/',
            ],
            'country_code' => [
                'required',
                'string',
                'regex:/^\+[1-9]\d{0,4}$/',
            ],
        ];
    }

    /**
     * Enforce strict E.164 international phone formatting on the combined country code and local number.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $combined = $this->input('country_code') . $this->input('phone_number');
            if (!preg_match('/^\+[1-9]\d{1,14}$/', $combined)) {
                $validator->errors()->add('phone_number', 'The combined country code and phone number must form a valid E.164 international phone format (e.g., +15551234567).');
            }
        });
    }
}
