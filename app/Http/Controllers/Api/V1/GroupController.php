<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGroupRequest;
use App\Http\Resources\ChannelResource;
use App\Services\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class GroupController extends Controller
{
    protected $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    /**
     * Create group or broadcast channel.
     * POST /api/v1/groups/create
     */
    public function create(CreateGroupRequest $request): JsonResponse
    {
        try {
            $type = $request->input('type', 'group'); // 'group' or 'channel'

            $channel = $this->groupService->createGroup(
                $request->user(),
                $request->group_name,
                $request->group_description,
                $request->file('group_icon'),
                $request->member_ids ?? [],
                $type
            );

            return response()->json([
                'success' => true,
                'message' => ($type === 'channel' ? 'Broadcast channel' : 'Group') . ' created successfully.',
                'chat' => new ChannelResource($channel),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create room.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add members to group (Admin only).
     * POST /api/v1/groups/{id}/members
     */
    public function addMembers(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'required|exists:users,id',
        ]);

        try {
            $this->groupService->addMembers(
                $request->user(),
                $id,
                $request->member_ids
            );

            return response()->json([
                'success' => true,
                'message' => 'Group members added successfully.',
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
     * Remove a member from the group (Admin only).
     * DELETE /api/v1/groups/{id}/members/{userId}
     */
    public function kickMember(Request $request, int $id, int $userId): JsonResponse
    {
        try {
            $this->groupService->kickMember(
                $request->user(),
                $id,
                $userId
            );

            return response()->json([
                'success' => true,
                'message' => 'Member removed from group successfully.',
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
     * Toggle group settings/permissions (Admin only).
     * PUT /api/v1/groups/{id}/permissions
     */
    public function updatePermissions(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'restrict_adjust_settings' => 'nullable|boolean',
            'restrict_send_messages' => 'nullable|boolean',
        ]);

        try {
            $metadata = $this->groupService->updatePermissions(
                $request->user(),
                $id,
                $request->only(['restrict_adjust_settings', 'restrict_send_messages'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Group administration permissions adjusted successfully.',
                'permissions' => [
                    'restrict_adjust_settings' => (bool) $metadata->restrict_adjust_settings,
                    'restrict_send_messages' => (bool) $metadata->restrict_send_messages,
                ],
            ]);
        } catch (Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() <= 500 ? $e->getCode() : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }
    }
}
