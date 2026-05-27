<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_id' => 'required|exists:channels,id',
            'body' => 'required_without:file|nullable|string',
            'file' => 'nullable|file|max:25600', // 25MB max
            'caption' => 'nullable|string|max:1000',
            'parent_message_id' => 'nullable|exists:messages,id',
        ];
    }
}
