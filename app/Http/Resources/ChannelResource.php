<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type, // 'individual', 'group', 'channel'
            'unread_count' => $this->unread_count ?? 0,
            'users' => UserResource::collection($this->whenLoaded('users')),
            'latest_message' => new MessageResource($this->whenLoaded('latestMessage')),
            'group_metadata' => $this->when($this->type === 'group' || $this->type === 'channel', function () {
                return [
                    'group_name' => $this->groupMetadata?->group_name,
                    'group_description' => $this->groupMetadata?->group_description,
                    'group_icon_url' => $this->groupMetadata?->group_icon_url,
                    'restrict_adjust_settings' => (bool) $this->groupMetadata?->restrict_adjust_settings,
                    'restrict_send_messages' => (bool) $this->groupMetadata?->restrict_send_messages,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
