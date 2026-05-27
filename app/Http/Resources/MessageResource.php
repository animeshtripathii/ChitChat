<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isDeleted = $this->trashed();

        return [
            'id' => $this->id,
            'channel_id' => $this->channel_id,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'sender_id' => $this->sender_id,
            'type' => $isDeleted ? 'text' : $this->type,
            'body' => $isDeleted ? ($this->is_moderated ? 'This message was automatically removed by the Moderator Bot.' : 'This message was deleted.') : $this->body,
            'caption' => $isDeleted ? null : $this->caption,
            'status' => $this->status, // 'sent', 'delivered', 'read'
            'parent_message_id' => $this->parent_message_id,
            'parent_message' => new MessageResource($this->whenLoaded('parent')),
            'is_deleted' => $isDeleted,
            'is_moderated' => $this->is_moderated,
            'reactions' => $this->reactions()->with('user')->get()->map(function ($r) {
                return [
                    'emoji' => $r->emoji,
                    'user_id' => $r->user_id,
                    'username' => $r->user->username
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
