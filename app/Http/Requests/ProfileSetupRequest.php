<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string|max:50|unique:users,username,' . $this->user()->id,
            'bio' => 'nullable|string|max:160',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB limit
        ];
    }
}
