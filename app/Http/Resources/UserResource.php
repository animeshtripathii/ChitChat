<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->when($request->user() && ($request->user()->id === $this->id || $request->user()->role === 'admin'), $this->email),
            'role' => $this->role,
            'phone_number' => $this->phone_number,
            'country_code' => $this->country_code,
            'username' => $this->username,
            'bio' => $this->bio,
            'profile_picture_url' => $this->profile_picture_url,
            'status' => $this->status,
            'last_seen_at' => $this->last_seen_at ? $this->last_seen_at->toIso8601String() : null,
            'public_key' => $this->public_key,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
