<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Events\MessageDeleted;
use App\Models\Message;
use App\Services\AIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ModerateMessageListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        // Skip if message was already moderated or soft-deleted or is not text
        if ($message->is_moderated || $message->type !== 'text' || $message->trashed()) {
            return;
        }

        Log::info("[ModerateMessageListener] Moderating message ID {$message->id} in background queue.");

        // Call Gemini AI moderation service
        if ($this->aiService->isUnsafe($message->body)) {
            Log::info("[ModerateMessageListener] Message ID {$message->id} flagged as unsafe. Retracting...");

            // Set as moderated and soft delete
            $message->update([
                'is_moderated' => true,
            ]);
            $message->delete();

            // Broadcast deletion event
            broadcast(new MessageDeleted($message->id, $message->channel_id, true))->toOthers();
        }
    }
}
