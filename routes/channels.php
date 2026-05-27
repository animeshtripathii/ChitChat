<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Channel;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User Specific Channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ChatRoom Private Channel (for direct, group, and channel rooms)
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    if ($user->role === 'admin') {
        return true;
    }
    
    $conversation = \App\Models\Conversation::find($conversationId);
    if (!$conversation) {
        return false;
    }

    return $conversation->users()->where('users.id', $user->id)->exists() ||
           $conversation->channelUsers()->where('users.id', $user->id)->exists();
});

// Chat Room Specific Channel (Compatibility)
Broadcast::channel('channel.{channelId}', function ($user, $channelId) {
    if ($user->role === 'temp' || $user->role === 'admin') {
        return true;
    }
    $channel = \App\Models\Channel::with('users')->find($channelId);
    return $channel && $channel->users->contains('id', $user->id);
});

// General Presence Channel for Online Tracking
Broadcast::channel('presence.chat', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar,
        'status' => $user->status,
    ];
});
