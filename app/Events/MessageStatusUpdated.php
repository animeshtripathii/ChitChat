<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel.' . $this->message->channel_id),
        ];
    }

    /**
     * Define custom broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'MessageStatusUpdated';
    }

    /**
     * Custom payload returning the updated status data.
     */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'channel_id' => $this->message->channel_id,
            'status' => $this->message->status,
            'updated_at' => $this->message->updated_at->toIso8601String(),
        ];
    }
}
