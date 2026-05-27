<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Services\MessageService;
use App\Services\AIService;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class MessageController extends Controller
{
    protected $messageService;
    protected $aiService;

    public function __construct(MessageService $messageService, AIService $aiService)
    {
        $this->messageService = $messageService;
        $this->aiService = $aiService;
    }

    /**
     * Send a text or media message.
     * POST /api/v1/messages/send
     */
    public function send(SendMessageRequest $request): JsonResponse
    {
        try {
            $message = $this->messageService->sendMessage(
                $request->user(),
                $request->channel_id,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Message transmitted successfully.',
                'data' => new MessageResource($message->load(['sender', 'parent'])),
            ], 201);
        } catch (Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 500 ? $e->getCode() : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }
    }

    /**
     * Update message status (delivered / read).
     * PUT /api/v1/messages/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:delivered,read',
        ]);

        try {
            $message = $this->messageService->updateStatus(
                $request->user(),
                $id,
                $request->status
            );

            return response()->json([
                'success' => true,
                'message' => 'Message status synchronized successfully.',
                'data' => new MessageResource($message->load(['sender', 'parent'])),
            ]);
        } catch (Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 500 ? $e->getCode() : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }
    }

    /**
     * Soft delete message ("Delete for Everyone").
     * DELETE /api/v1/messages/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $this->messageService->deleteMessage($request->user(), $id);
            
            // Broadcast deletion event
            $message = Message::withTrashed()->findOrFail($id);
            broadcast(new \App\Events\MessageDeleted($id, $message->channel_id, false))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Message retracted successfully for all users.',
            ]);
        } catch (Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 500 ? $e->getCode() : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }
    }

    /**
     * Toggle a reaction on a message.
     * POST /api/v1/messages/{id}/react
     */
    public function react(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'emoji' => 'required|string',
        ]);

        try {
            $reactions = $this->messageService->toggleReaction(
                $request->user(),
                $id,
                $request->emoji
            );

            return response()->json([
                'success' => true,
                'message' => 'Reaction toggled successfully.',
                'reactions' => $reactions,
            ]);
        } catch (Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 500 ? $e->getCode() : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }
    }

    /**
     * Forward a message to another channel.
     * POST /api/v1/messages/{id}/forward
     */
    public function forward(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'channel_id' => 'nullable|exists:channels,id',
            'conversation_id' => 'nullable|exists:channels,id',
        ]);

        // Accept either channel_id or conversation_id from the frontend
        $targetChannelId = $request->channel_id ?? $request->conversation_id;
        if (!$targetChannelId) {
            return response()->json(['success' => false, 'message' => 'A target conversation is required.'], 422);
        }


        try {
            $message = $this->messageService->forwardMessage(
                $request->user(),
                $id,
                $targetChannelId
            );

            return response()->json([
                'success' => true,
                'message' => 'Message forwarded successfully.',
                'data' => new MessageResource($message->load(['sender', 'parent'])),
            ]);
        } catch (Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 500 ? $e->getCode() : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }
    }

    /**
     * Ask AI Bot to Reply to a message.
     * POST /api/v1/messages/{id}/ai-reply
     */
    public function aiReply(Request $request, int $id): JsonResponse
    {
        try {
            $message = Message::findOrFail($id);
            
            // Check if message is a text message
            if ($message->type !== 'text') {
                return response()->json([
                    'success' => false,
                    'message' => 'AI can only reply to text messages.',
                ], 400);
            }

            // Generate reply using Gemini AIService, passing user to personalize tone/instructions
            $replyText = $this->aiService->generateSmartReply($message->body, $request->user());

            return response()->json([
                'success' => true,
                'reply' => $replyText,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI failed to generate a reply.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
