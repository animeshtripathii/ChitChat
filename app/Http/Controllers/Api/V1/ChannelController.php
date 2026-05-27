<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChannelResource;
use App\Models\Channel;
use App\Services\ChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ChannelController extends Controller
{
    protected $channelService;

    public function __construct(ChannelService $channelService)
    {
        $this->channelService = $channelService;
    }

    /**
     * Retrieve all active individual and group channels.
     * GET /api/v1/chats
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $channels = $this->channelService->getUserChannels($request->user());
            return response()->json([
                'success' => true,
                'chats' => ChannelResource::collection($channels),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch channels list.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a chat with an identifier (phone or email).
     * POST /api/v1/chats/initiate
     */
    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        try {
            $channel = $this->channelService->initiateChat($request->user(), $request->phone_number);
            return response()->json([
                'success' => true,
                'message' => 'Conversation initiated successfully.',
                'chat' => new ChannelResource($channel->load(['users', 'latestMessage'])),
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
     * Retrieve chronological message history for a specific channel.
     * GET /api/v1/chats/{id}/messages
     */
    public function messages(Request $request, int $id): JsonResponse
    {
        try {
            $channel = Channel::findOrFail($id);
            
            // Enforce channel participation (bypass for temp guest or admin role)
            $isMember = $channel->users->contains('id', $request->user()->id);
            if (!$isMember && $request->user()->role !== 'temp' && $request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this conversation\'s history.',
                ], 403);
            }

            // Retrieve messages chronologically including trashed (soft-deleted) items
            $messages = $channel->messages()
                ->with(['sender', 'parent'])
                ->withTrashed()
                ->oldest()
                ->get();

            return response()->json([
                'success' => true,
                'messages' => \App\Http\Resources\MessageResource::collection($messages),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve message history.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
