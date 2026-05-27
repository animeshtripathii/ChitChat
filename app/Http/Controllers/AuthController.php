<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSetting;
use App\Models\AISetting;
use App\Services\MediaUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    protected $mediaUploadService;

    public function __construct(MediaUploadService $mediaUploadService)
    {
        $this->mediaUploadService = $mediaUploadService;
    }

    /**
     * Show the login page.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle authentication login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string', // Can be email or phone number
            'password' => 'required|string',
        ]);

        $login = $request->input('login');
        $password = $request->input('password');

        // Check if login is email or phone
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $login)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return back()->withErrors([
                'login' => 'The provided credentials do not match our records.',
            ])->withInput($request->only('login'));
        }

        if ($user->status === 'banned') {
            return back()->withErrors([
                'login' => 'Your account has been banned by the administrator.',
            ])->withInput($request->only('login'));
        }

        Auth::login($user, $request->has('remember'));

        // Update online status
        $user->update([
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        // Redirect to dashboard (or profile setup if name is missing)
        if (empty($user->name)) {
            return redirect()->route('profile.setup');
        }

        return redirect()->intended('/dashboard');
    }

    /**
     * Show registration page.
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Handle user registration.
     */
    public function register(Request $request)
    {
        $input = $request->input('email');
        $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);

        $rules = [
            'name' => 'required|string|max:50',
            'password' => 'required|string|min:6|confirmed',
        ];

        if ($isEmail) {
            $rules['email'] = 'required|string|email|max:100|unique:users,email';
        } else {
            $rules['email'] = 'required|string|min:8|max:20|unique:users,phone';
        }

        $request->validate($rules);

        $user = User::create([
            'name' => $request->name,
            'email' => $isEmail ? $input : null,
            'phone' => !$isEmail ? $input : null,
            'role' => 'user',
            'password' => Hash::make($request->password),
            'status' => 'online',
            'last_seen_at' => now(),
            // compatibility columns
            'username' => $request->name,
            'phone_number' => !$isEmail ? $input : null,
        ]);

        // Auto-provision default privacy settings
        UserSetting::create([
            'user_id' => $user->id,
            'privacy_last_seen' => 'everyone',
            'privacy_profile_photo' => 'everyone',
            'privacy_about' => 'everyone',
            'privacy_status_updates' => 'everyone',
            'read_receipts' => true,
        ]);

        // Auto-provision AI Co-pilot settings
        AISetting::create([
            'user_id' => $user->id,
            'is_auto_reply_enabled' => false,
            'prompt_behavior' => 'Helpful AI assistant',
            'summary_frequency' => 'daily',
        ]);

        Auth::login($user);

        // Redirect straight to profile setup to upload picture and set custom status
        return redirect()->route('profile.setup');
    }

    /**
     * Show profile onboarding/setup screen.
     */
    public function showProfileSetup()
    {
        $user = Auth::user();
        return view('auth.profile-setup', compact('user'));
    }

    /**
     * Save onboarding profile updates.
     */
    public function saveProfileSetup(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:50',
            'status_message' => 'nullable|string|max:150',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // Max 5MB
        ]);

        $data = [
            'name' => $request->name,
            'username' => $request->name, // compatibility
            'status_message' => $request->status_message,
            'bio' => $request->status_message, // compatibility
        ];

        if ($request->hasFile('avatar')) {
            $avatarUrl = $this->mediaUploadService->upload($request->file('avatar'), 'avatars');
            $data['avatar'] = $avatarUrl;
            $data['profile_picture_url'] = $avatarUrl; // compatibility
        }

        $user->update($data);

        return redirect()->route('dashboard')->with('success', 'Profile configured successfully!');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->update([
                'status' => 'offline',
                'last_seen_at' => now(),
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * API check to see if email or phone is unique.
     * Used for real-time validation via Alpine.js.
     */
    public function checkUnique(Request $request)
    {
        $field = $request->query('field');
        $value = $request->query('value');

        if (!in_array($field, ['email', 'phone'])) {
            return response()->json(['valid' => false, 'error' => 'Invalid field.'], 400);
        }

        $exists = User::where($field, $value)
            ->when(Auth::check(), function ($query) {
                // Ignore current user in checks (for edit profile context)
                return $query->where('id', '!=', Auth::id());
            })
            ->exists();

        return response()->json([
            'available' => !$exists
        ]);
    }
}
