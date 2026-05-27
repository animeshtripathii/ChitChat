<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\ChannelResource;
use App\Models\User;
use App\Models\Channel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class AdminController extends Controller
{
    /**
     * Verify that the authenticated user is an admin.
     */
    protected function verifyAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized. Admin panel access restricted.');
        }
    }

    /**
     * Get all registered and guest users.
     * GET /api/v1/admin/users
     */
    public function users(Request $request): JsonResponse
    {
        $this->verifyAdmin($request);

        try {
            $users = User::orderBy('id', 'desc')->get();
            return response()->json([
                'success' => true,
                'users' => UserResource::collection($users),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users list.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete user account permanently.
     * DELETE /api/v1/admin/users/{id}
     */
    public function deleteUser(Request $request, int $id): JsonResponse
    {
        $this->verifyAdmin($request);

        try {
            $user = User::findOrFail($id);

            if ($user->id === $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own admin account.',
                ], 400);
            }

            // Permanently delete user (database cascades automatically)
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User account and associated playroom data permanently purged.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to purge user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all active group rooms and broadcast channels.
     * GET /api/v1/admin/channels
     */
    public function channels(Request $request): JsonResponse
    {
        $this->verifyAdmin($request);

        try {
            $channels = Channel::where('type', 'group')
                ->orWhere('type', 'channel')
                ->with(['groupMetadata', 'users'])
                ->get();

            return response()->json([
                'success' => true,
                'channels' => ChannelResource::collection($channels),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch rooms list.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete channel permanently.
     * DELETE /api/v1/admin/channels/{id}
     */
    public function deleteChannel(Request $request, int $id): JsonResponse
    {
        $this->verifyAdmin($request);

        try {
            $channel = Channel::findOrFail($id);
            $channel->delete();

            return response()->json([
                'success' => true,
                'message' => 'Chat room/channel permanently deleted.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete chat room.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
