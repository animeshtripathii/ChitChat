<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterPhoneRequest;
use App\Http\Requests\VerifyOTPRequest;
use App\Http\Requests\ProfileSetupRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\OTPService;
use App\Services\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class AuthController extends Controller
{
    protected $otpService;
    protected $mediaUploadService;

    public function __construct(OTPService $otpService, MediaUploadService $mediaUploadService)
    {
        $this->otpService = $otpService;
        $this->mediaUploadService = $mediaUploadService;
    }

    /**
     * Temporary Guest Login.
     * POST /api/v1/auth/guest-login
     */
    public function guestLogin(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|min:3|max:20',
            'email' => 'required|email|max:100',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // To support repeated sandbox tests, we find or create the guest user.
                // However, we want to allow guests to login.
                $user = User::where('email', $request->email)->first();

                if (!$user) {
                    $user = User::create([
                        'username' => $request->username,
                        'email' => $request->email,
                        'role' => 'temp',
                        'status' => 'online',
                        'last_seen_at' => now(),
                    ]);

                    // Auto-provision default privacy settings
                    UserSetting::create([
                        'user_id' => $user->id,
                        'privacy_last_seen' => 'everyone',
                        'privacy_profile_photo' => 'everyone',
                        'privacy_about' => 'everyone',
                        'privacy_status_updates' => 'everyone',
                        'read_receipts' => true,
                        'security_notifications' => false,
                        'two_factor_enabled' => false,
                    ]);
                } else {
                    $user->status = 'online';
                    $user->last_seen_at = now();
                    $user->save();
                }

                $token = $user->createToken('guest_access_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Logged in as temporary Guest. This account expires in 1 hour.',
                    'token' => $token,
                    'user' => new UserResource($user),
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Guest login failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Persistent Email & Password Registration.
     * POST /api/v1/auth/email-register
     */
    public function emailRegister(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|min:3|max:20',
            'email' => 'required|email|unique:users,email|max:100',
            'password' => 'required|string|min:6',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = User::create([
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'registered',
                    'status' => 'online',
                    'last_seen_at' => now(),
                ]);

                // Auto-provision default privacy settings
                UserSetting::create([
                    'user_id' => $user->id,
                    'privacy_last_seen' => 'everyone',
                    'privacy_profile_photo' => 'everyone',
                    'privacy_about' => 'everyone',
                    'privacy_status_updates' => 'everyone',
                    'read_receipts' => true,
                    'security_notifications' => false,
                    'two_factor_enabled' => false,
                ]);

                $token = $user->createToken('registered_access_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Account registered successfully.',
                    'token' => $token,
                    'user' => new UserResource($user),
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Account registration failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Email & Password Login (Registered & Admin users).
     * POST /api/v1/auth/email-login
     */
    public function emailLogin(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password credentials.',
                ], 401);
            }

            // Update user status
            $user->status = 'online';
            $user->last_seen_at = now();
            $user->save();

            $token = $user->createToken('email_access_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully.',
                'token' => $token,
                'user' => new UserResource($user),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalizes user onboarding by updating profile information.
     * POST /api/v1/auth/profile-setup
     */
    public function profileSetup(ProfileSetupRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            $data = [
                'username' => $request->username,
                'bio' => $request->input('bio', $user->bio),
            ];

            if ($request->hasFile('profile_picture')) {
                $data['profile_picture_url'] = $this->mediaUploadService->upload(
                    $request->file('profile_picture'),
                    'profiles'
                );
            }

            $user->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Profile configured successfully.',
                'user' => new UserResource($user),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to finalize profile setup.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
