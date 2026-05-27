<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePrivacyRequest;
use App\Models\Block;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Exception;

class SettingsController extends Controller
{
    /**
     * Fetch user privacy settings.
     * GET /api/v1/settings/privacy
     */
    public function getPrivacy(Request $request): JsonResponse
    {
        $settings = $request->user()->settings()->firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'privacy_last_seen' => 'everyone',
                'privacy_profile_photo' => 'everyone',
                'privacy_about' => 'everyone',
                'privacy_status_updates' => 'everyone',
                'read_receipts' => true,
                'security_notifications' => false,
                'two_factor_enabled' => false,
            ]
        );

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    /**
     * Update privacy configurations.
     * PUT /api/v1/settings/privacy
     */
    public function updatePrivacy(UpdatePrivacyRequest $request): JsonResponse
    {
        $user = $request->user();
        $settings = $user->settings()->firstOrCreate(['user_id' => $user->id]);

        $settings->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Privacy configurations synchronized successfully.',
            'settings' => $settings,
        ]);
    }

    /**
     * Block a contact.
     * POST /api/v1/settings/block
     */
    public function block(Request $request): JsonResponse
    {
        $request->validate([
            'blocked_user_id' => 'required|exists:users,id',
        ]);

        $userId = $request->user()->id;
        $blockedUserId = $request->blocked_user_id;

        if ($userId === (int) $blockedUserId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot block yourself.',
            ], 400);
        }

        try {
            Block::firstOrCreate([
                'user_id' => $userId,
                'blocked_user_id' => $blockedUserId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User blocked successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to block user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unblock a contact.
     * DELETE /api/v1/settings/block/{userId}
     */
    public function unblock(Request $request, int $userId): JsonResponse
    {
        $currentUserId = $request->user()->id;

        try {
            $block = Block::where('user_id', $currentUserId)
                ->where('blocked_user_id', $userId)
                ->first();

            if (!$block) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user is not currently blocked.',
                ], 404);
            }

            $block->delete();

            return response()->json([
                'success' => true,
                'message' => 'User unblocked successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unblock user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle or configure account 6-digit master PIN.
     * PUT /api/v1/settings/account/two-factor
     */
    public function toggleTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'enable' => 'required|boolean',
            'pin' => 'required_if:enable,true|nullable|string|size:6|regex:/^[0-9]+$/',
        ]);

        $user = $request->user();
        $settings = $user->settings()->firstOrCreate(['user_id' => $user->id]);

        try {
            if ($request->enable) {
                $user->two_factor_pin = Hash::make($request->pin);
                $user->save();

                $settings->two_factor_enabled = true;
                $settings->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Two-factor account protection PIN enabled successfully.',
                ]);
            } else {
                $user->two_factor_pin = null;
                $user->save();

                $settings->two_factor_enabled = false;
                $settings->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Two-factor account protection PIN disabled successfully.',
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update two-factor setting.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
