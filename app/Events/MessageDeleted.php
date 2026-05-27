<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $channelId;
    public $isModerated;

    public function __construct(int $messageId, int $channelId, bool $isModerated = false)
    {
        $this->messageId = $messageId;
        $this->channelId = $channelId;
        $this->isModerated = $isModerated;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel.' . $this->channelId),
            new PrivateChannel('chat.' . $this->channelId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageDeleted';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'channel_id' => $this->channelId,
            'is_moderated' => $this->isModerated,
            'body' => $this->isModerated ? 'This message was automatically removed by the Moderator Bot.' : 'This message was deleted.',
        ];
    }
}
