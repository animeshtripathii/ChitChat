<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReactionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $channelId;
    public $reactions;

    public function __construct(int $messageId, int $channelId, $reactions)
    {
        $this->messageId = $messageId;
        $this->channelId = $channelId;
        $this->reactions = $reactions;
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
        return 'ReactionUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'channel_id' => $this->channelId,
            'reactions' => $this->reactions,
        ];
    }
}
