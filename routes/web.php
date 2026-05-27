<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;

// Root redirect
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Guest-only authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated-only routes
Route::middleware(['auth', 'banned.block'])->group(function () {
    Route::get('/profile/setup', [AuthController::class, 'showProfileSetup'])->name('profile.setup');
    Route::post('/profile/setup', [AuthController::class, 'saveProfileSetup']);
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Main Chat Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Dashboard APIs
    Route::get('/api/conversations', [DashboardController::class, 'getConversations'])->name('api.conversations');
    Route::get('/api/users/search', [DashboardController::class, 'searchUsers'])->name('api.users.search');
    Route::post('/api/conversations/initiate', [DashboardController::class, 'initiateConversation'])->name('api.conversations.initiate');
    Route::get('/api/conversations/{conversation}/messages', [DashboardController::class, 'getMessages'])->name('api.conversations.messages');
    Route::post('/api/conversations/{conversation}/messages', [DashboardController::class, 'sendMessage'])->name('api.conversations.send');
    Route::post('/api/messages/{message}/react', [DashboardController::class, 'reactToMessage'])->name('api.messages.react');
    Route::delete('/api/messages/{message}', [DashboardController::class, 'deleteMessage'])->name('api.messages.delete');
    Route::post('/api/messages/{message}/forward', [DashboardController::class, 'forwardMessage'])->name('api.messages.forward');
    Route::post('/api/messages/{message}/pin', [DashboardController::class, 'pinMessage'])->name('api.messages.pin');
    Route::post('/api/conversations/{conversation}/typing', [DashboardController::class, 'typing'])->name('api.conversations.typing');
    Route::post('/api/groups', [DashboardController::class, 'createGroup'])->name('api.groups.create');
    Route::post('/api/channels', [DashboardController::class, 'createChannel'])->name('api.channels.create');
    Route::post('/api/channels/{conversation}/join', [DashboardController::class, 'joinChannel'])->name('api.channels.join');
    Route::get('/api/conversations/{conversation}/members', [DashboardController::class, 'getConversationMembers'])->name('api.conversations.members');
    
    // User & AI Settings APIs
    Route::get('/api/settings', [DashboardController::class, 'getSettings'])->name('api.settings.get');
    Route::post('/api/settings', [DashboardController::class, 'saveSettings'])->name('api.settings.save');
    Route::post('/api/settings/password', [DashboardController::class, 'changePassword'])->name('api.settings.password');
    
    // AI Smart Reply API for Web Interface
    Route::post('/api/v1/messages/{id}/ai-reply', [\App\Http\Controllers\Api\V1\MessageController::class, 'aiReply'])->name('api.messages.ai-reply');

    // Invitations APIs
    Route::get('/api/invitations', [DashboardController::class, 'getInvitations'])->name('api.invitations.get');
    Route::post('/api/invitations/{invitation}/respond', [DashboardController::class, 'respondToInvitation'])->name('api.invitations.respond');
    Route::post('/api/conversations/{conversation}/invite', [DashboardController::class, 'inviteMember'])->name('api.conversations.invite');

    // Admin Console routes
    Route::middleware('admin.access')->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
        Route::get('/api/admin/users', [AdminController::class, 'getUsers'])->name('api.admin.users');
        Route::post('/api/admin/users/{user}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('api.admin.users.toggle');
        Route::get('/api/admin/ai-stats', [AdminController::class, 'getAIStats'])->name('api.admin.ai-stats');
        
        // Groups & Channels Moderation routes
        Route::get('/api/admin/conversations', [AdminController::class, 'getConversations'])->name('api.admin.conversations');
        Route::post('/api/admin/conversations/{conversation}/toggle-ban', [AdminController::class, 'toggleConversationStatus'])->name('api.admin.conversations.toggle');
        Route::delete('/api/admin/conversations/{conversation}', [AdminController::class, 'deleteConversation'])->name('api.admin.conversations.delete');
    });
});

// Public API checks (accessible by guests and auth users)
Route::get('/api/check-unique', [AuthController::class, 'checkUnique'])->name('api.check-unique');

Route::get('/api/ai-diagnostic', function() {
    $apiKey = config('services.gemini.api_key');
    if (!$apiKey) {
        return response()->json([
            'status' => 'error',
            'message' => 'API Key is null or empty. Please check your GEMINI_API_KEY environment variable on Render.',
            'raw_config_value' => config('services.gemini.api_key'),
            'env_value_directly' => env('GEMINI_API_KEY'),
        ]);
    }
    
    // Attempt to make a simple request to Gemini API
    $prompt = "Hello, write 'hello' back.";
    $startTime = microtime(true);
    
    try {
        $response = \Illuminate\Support\Facades\Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);
        
        $duration = microtime(true) - $startTime;
        
        if ($response->successful()) {
            return response()->json([
                'status' => 'success',
                'duration_seconds' => $duration,
                'response_data' => $response->json(),
            ]);
        } else {
            return response()->json([
                'status' => 'api_error',
                'status_code' => $response->status(),
                'duration_seconds' => $duration,
                'response_body' => $response->body(),
                'hint' => 'If the error is about model not found, we may need to adjust the model name (e.g. gemini-2.5-flash or gemini-1.5-flash).'
            ]);
        }
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'exception',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
});
