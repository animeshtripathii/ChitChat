<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StatusResource;
use App\Models\Status;
use App\Models\User;
use App\Services\MediaUploadService;
use App\Services\PrivacyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class StatusController extends Controller
{
    protected $mediaUploadService;
    protected $privacyService;

    public function __construct(MediaUploadService $mediaUploadService, PrivacyService $privacyService)
    {
        $this->mediaUploadService = $mediaUploadService;
        $this->privacyService = $privacyService;
    }

    /**
     * Upload a new text or media status update valid for 24 hours.
     * POST /api/v1/status
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:text,media',
            'content' => 'required_without:file|nullable|string|max:1000',
            'file' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:10240', // 10MB limit
            'caption' => 'nullable|string|max:200',
        ]);

        $user = $request->user();

        try {
            $type = $request->type;
            $content = $request->content;

            if ($request->hasFile('file')) {
                $type = 'media';
                $content = $this->mediaUploadService->upload($request->file('file'), 'status_updates');
            }

            $status = Status::create([
                'user_id' => $user->id,
                'type' => $type,
                'content' => $content,
                'caption' => $request->caption,
                'expires_at' => now()->addHours(24),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status update shared successfully (valid for 24 hours).',
                'status' => new StatusResource($status->load('user')),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to share status update.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch active status updates from the user's contacts.
     * GET /api/v1/status
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            // Fetch contacts (users sharing mutual active chats, not blocked)
            $contactIds = User::where('id', '!=', $user->id)
                ->whereHas('chats', function ($q) use ($user) {
                    $q->whereHas('users', function ($innerQ) use ($user) {
                        $innerQ->where('users.id', $user->id);
                    });
                })
                ->get()
                ->filter(fn($c) => !$this->privacyService->isBlocked($user->id, $c->id))
                ->pluck('id');

            // Include self status updates as well in the feed if desired,
            // or exclusively contacts. WhatsApp shows self at top and contacts below.
            // Let's retrieve active contacts updates.
            $statuses = Status::whereIn('user_id', $contactIds)
                ->where('expires_at', '>', now())
                ->with('user')
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'statuses' => StatusResource::collection($statuses),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch status updates.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
