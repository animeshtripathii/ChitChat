<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_name' => 'required|string|min:3|max:50',
            'group_description' => 'nullable|string|max:250',
            'group_icon' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'required|exists:users,id',
        ];
    }
}
