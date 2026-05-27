<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChannelController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\StatusController;
use App\Http\Controllers\Api\V1\AdminController;

Route::prefix('v1')->group(function () {
    // ----------------------------------------------------
    // Authentication & Onboarding Endpoints
    // ----------------------------------------------------
    Route::post('/auth/register-phone', [AuthController::class, 'registerPhone']);
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOTP']);
    Route::post('/auth/guest-login', [AuthController::class, 'guestLogin']);
    Route::post('/auth/email-register', [AuthController::class, 'emailRegister']);
    Route::post('/auth/email-login', [AuthController::class, 'emailLogin']);

    // ----------------------------------------------------
    // Protected Endpoints (Requires Sanctum Token)
    // ----------------------------------------------------
    Route::middleware(['auth:sanctum', 'banned.block'])->group(function () {
        Route::post('/auth/profile-setup', [AuthController::class, 'profileSetup']);

        // Channel Chat Management
        Route::get('/chats', [ChannelController::class, 'index']);
        Route::post('/chats/initiate', [ChannelController::class, 'initiate']);
        Route::get('/chats/{id}/messages', [ChannelController::class, 'messages']);

        // Messaging Engine
        Route::post('/messages/send', [MessageController::class, 'send']);
        Route::put('/messages/{id}/status', [MessageController::class, 'updateStatus']);
        Route::delete('/messages/{id}', [MessageController::class, 'destroy']);
        Route::post('/messages/{id}/react', [MessageController::class, 'react']);
        Route::post('/messages/{id}/forward', [MessageController::class, 'forward']);
        Route::post('/messages/{id}/ai-reply', [MessageController::class, 'aiReply']);

        // WhatsApp-Style Settings & Privacy Control
        Route::get('/settings/privacy', [SettingsController::class, 'getPrivacy']);
        Route::put('/settings/privacy', [SettingsController::class, 'updatePrivacy']);
        Route::post('/settings/block', [SettingsController::class, 'block']);
        Route::delete('/settings/block/{userId}', [SettingsController::class, 'unblock']);
        Route::put('/settings/account/two-factor', [SettingsController::class, 'toggleTwoFactor']);

        // Group Chats & Channels Management
        Route::post('/groups/create', [GroupController::class, 'create']);
        Route::post('/groups/{id}/members', [GroupController::class, 'addMembers']);
        Route::delete('/groups/{id}/members/{userId}', [GroupController::class, 'kickMember']);
        Route::put('/groups/{id}/permissions', [GroupController::class, 'updatePermissions']);

        // Status / Stories Updates
        Route::post('/status', [StatusController::class, 'store']);
        Route::get('/status', [StatusController::class, 'index']);

        // Admin Panel Endpoints
        Route::get('/admin/users', [AdminController::class, 'users']);
        Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);
        Route::get('/admin/channels', [AdminController::class, 'channels']);
        Route::delete('/admin/channels/{id}', [AdminController::class, 'deleteChannel']);
    });
});
