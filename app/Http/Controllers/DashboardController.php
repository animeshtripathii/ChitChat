<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ConversationInvitation;
use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\UserTyping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $mediaUploadService;

    public function __construct(\App\Services\MediaUploadService $mediaUploadService)
    {
        $this->mediaUploadService = $mediaUploadService;
    }

    /**
     * Get list of conversations for the authenticated user.
     */
    public function getConversations()
    {
        $user = Auth::user();

        // Get group and direct conversations (user must be a member)
        // Eager-load 'settings' on users so getPublicProfile() is N+1-safe
        $groups = $user->groupConversations()
            ->with(['users.settings', 'latestMessage'])
            ->get();

        // Filter out banned groups/channels where the current user is NOT the creator
        $groups = $groups->filter(function ($conv) use ($user) {
            if ($conv->status === 'banned' && $conv->created_by !== $user->id) {
                return false;
            }
            return true;
        });

        // Get channels:
        // - ALL public channels are visible to all users (they can discover & join)
        // - Private channels: only show if user is a member
        $memberChannelIds = $user->channelConversations()->pluck('conversations.id');

        $channels = Conversation::where('type', 'channel')
            ->where(function ($q) use ($memberChannelIds) {
                $q->where('visibility', 'public')
                  ->orWhereIn('id', $memberChannelIds);
            })
            ->with(['channelUsers.settings', 'latestChannelMessage'])
            ->get()
            ->filter(function ($conv) use ($user) {
                if ($conv->status === 'banned' && $conv->created_by !== $user->id) {
                    return false;
                }
                return true;
            });

        // Merge and map
        $conversations = $groups->concat($channels)
            ->map(function ($conversation) use ($user, $memberChannelIds) {
                $otherUser = null;
                $name      = $conversation->name;
                $icon      = $conversation->icon;

                if ($conversation->type === 'direct') {
                    $otherUser = $conversation->getOtherUser($user->id);
                    if ($otherUser) {
                        $profile = $otherUser->getPublicProfile();
                        $name    = $otherUser->name;     // name is always shown
                        $icon    = $profile['avatar'];   // respects privacy_profile_photo
                    }
                }

                $latest = $conversation->type === 'channel'
                    ? $conversation->latestChannelMessage
                    : $conversation->latestMessage;

                // Is the current user already a member of this channel?
                $isMember = $conversation->type === 'channel'
                    ? $memberChannelIds->contains($conversation->id)
                    : true;

                return [
                    'id'           => $conversation->id,
                    'type'         => $conversation->type,
                    'name'         => $name,
                    'icon'         => $icon,
                    'visibility'   => $conversation->visibility,
                    'is_member'    => $isMember,
                    'unread_count' => $isMember ? $conversation->getUnreadCount($user->id) : 0,
                    'status'       => $conversation->status ?: 'active',
                    'is_creator'   => $conversation->created_by === $user->id,
                    'other_user_banned' => $otherUser ? ($otherUser->status === 'banned') : false,
                    // Privacy-masked other_user data
                    'other_user'   => $otherUser ? $otherUser->getPublicProfile() : null,
                    'latest_message' => $latest ? [
                        'id'         => $latest->id,
                        'body'       => $latest->body,
                        'type'       => $latest->type,
                        'sender_id'  => $latest->sender_id,
                        'is_read'    => $latest->is_read,
                        'created_at' => $latest->created_at->toIso8601String(),
                        'time_ago'   => $latest->created_at->diffForHumans(),
                    ] : null,
                ];
            })
            ->sortByDesc(function ($chat) {
                return $chat['latest_message']['created_at'] ?? $chat['id'];
            })
            ->values();

        return response()->json($conversations);
    }

    /**
     * Search other registered users to start a new chat.
     * Returns privacy-masked profiles (avatar/status hidden per user settings).
     */
    public function searchUsers(Request $request)
    {
        $search      = $request->query('query');
        $currentUser = Auth::user();

        $query = User::where('id', '!=', $currentUser->id)
            ->with('settings'); // eager-load for getPublicProfile()

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->limit(20)->get()
            ->map(fn($u) => $u->getPublicProfile());

        return response()->json($users);
    }

    /**
     * Initiate a new direct conversation.
     */
    public function initiateConversation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $currentUser = Auth::user();
        $targetUserId = $request->input('user_id');

        // Check if direct conversation already exists between the two users
        $existing = Conversation::where('type', 'direct')
            ->whereHas('users', function ($query) use ($currentUser) {
                $query->where('users.id', $currentUser->id);
            })
            ->whereHas('users', function ($query) use ($targetUserId) {
                $query->where('users.id', $targetUserId);
            })
            ->first();

        if ($existing) {
            return response()->json([
                'conversation_id' => $existing->id,
                'is_new' => false,
            ]);
        }

        // Create new direct conversation
        $conversation = DB::transaction(function () use ($currentUser, $targetUserId) {
            $conversation = Conversation::create([
                'type' => 'direct',
                'created_by' => $currentUser->id,
            ]);

            // Attach both users
            $conversation->users()->attach([
                $currentUser->id => ['role' => 'member'],
                $targetUserId => ['role' => 'member'],
            ]);

            return $conversation;
        });

        return response()->json([
            'conversation_id' => $conversation->id,
            'is_new' => true,
        ]);
    }

    /**
     * Fetch messages in a conversation, marking them as read.
     */
    public function getMessages(Conversation $conversation)
    {
        $user = Auth::user();

        // Security check: ensure user can access this conversation
        if ($conversation->type === 'channel') {
            $isMember = $conversation->channelUsers()->where('users.id', $user->id)->exists();
            // Non-members can READ public channels (but not post)
            if (!$isMember && $conversation->visibility !== 'public') {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
        } else {
            if (!$conversation->users()->where('users.id', $user->id)->exists()) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
        }

        // Fetch messages sorted chronologically
        $query = $conversation->type === 'channel' ? $conversation->channelMessages() : $conversation->messages();
        $messages = $query
            ->with(['sender:id,name,avatar', 'reactions', 'parent.sender:id,name'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark incoming messages as read
        // WhatsApp rule: if the READER (current user = $user) has read_receipts=OFF,
        // we do NOT mark messages as read → the SENDER won't see blue double-ticks.
        $readerSettings     = $user->settings()->first();
        $readerReceiptsOn   = $readerSettings ? (bool) $readerSettings->read_receipts : true;

        $unreadMessages = $query
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false);

        if ($readerReceiptsOn && $unreadMessages->count() > 0) {
            $now = now();

            // Mark all unread messages as read (reader allows receipts)
            $unreadMessages->update([
                'is_read' => true,
                'read_at' => $now,
            ]);

            // Broadcast read receipts event to the sender(s)
            broadcast(new MessageRead($conversation->id, $user->id, $now))->toOthers();
        }
        // If read_receipts is OFF: messages stay is_read=false → sender sees no blue tick


        // Compute member status for channels
        $isMember = $conversation->type === 'channel'
            ? $conversation->channelUsers()->where('users.id', $user->id)->exists()
            : true;

        return response()->json([
            'is_member' => $isMember,
            'visibility' => $conversation->visibility,
            'messages' => $messages->map(function ($msg) {
                return [
                    'id'          => $msg->id,
                    'body'        => $msg->body,
                    'type'        => $msg->type,
                    'sender_id'   => $msg->sender_id,
                    'sender_name' => $msg->sender ? $msg->sender->name : null,
                    'receiver_id' => $msg->receiver_id,
                    'mentions'    => $msg->mentions ?? [],
                    'is_read'     => $msg->is_read,
                    'read_at'     => $msg->read_at ? $msg->read_at->toIso8601String() : null,
                    'created_at'  => $msg->created_at->toIso8601String(),
                    'sender'      => [
                        'id'     => $msg->sender->id,
                        'name'   => $msg->sender->name,
                        'avatar' => $msg->sender->avatar,
                    ],
                    'reactions'   => $msg->reactions->map(fn($r) => ['user_id' => $r->user_id, 'emoji' => $r->emoji])->toArray(),
                    'parent_message_id' => $msg->parent_message_id,
                    'parent'      => $msg->parent ? [
                        'id'          => $msg->parent->id,
                        'body'        => $msg->parent->body,
                        'type'        => $msg->parent->type,
                        'sender_name' => $msg->parent->sender ? $msg->parent->sender->name : 'Teammate',
                    ] : null,
                    'is_pinned'   => (bool) $msg->is_pinned,
                    'caption'     => $msg->caption,
                    'is_ephemeral' => false,
                ];
            })
        ]);
    }

    /**
     * Join a public channel (self-join without invitation).
     */
    public function joinChannel(Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->type !== 'channel') {
            return response()->json(['error' => 'This is not a channel.'], 422);
        }

        if ($conversation->visibility !== 'public') {
            return response()->json(['error' => 'You need an invitation to join this private channel.'], 403);
        }

        // Already a member?
        if ($conversation->channelUsers()->where('users.id', $user->id)->exists()) {
            return response()->json(['success' => true, 'message' => 'Already a member.']);
        }

        $conversation->channelUsers()->attach($user->id, ['role' => 'member']);

        return response()->json([
            'success' => true,
            'message' => 'You have joined the channel!',
        ]);
    }

    /**
     * Get members of a conversation for the @mention dropdown.
     */
    public function getConversationMembers(Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->type === 'channel') {
            // Must be a member OR public channel
            $isMember = $conversation->channelUsers()->where('users.id', $user->id)->exists();
            if (!$isMember && $conversation->visibility !== 'public') {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
            // Respect member_visibility setting
            if (!$isMember && !$conversation->member_visibility) {
                return response()->json(['members' => []]);
            }
            $members = $conversation->channelUsers()->get(['users.id', 'users.name', 'users.email', 'users.avatar']);
        } else {
            // Group or direct: must be member
            if (!$conversation->users()->where('users.id', $user->id)->exists()) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
            $members = $conversation->users()->get(['users.id', 'users.name', 'users.email', 'users.avatar']);
        }

        return response()->json([
            'members' => $members->map(fn($m) => [
                'id'     => $m->id,
                'name'   => $m->name,
                'email'  => $m->email,
                'avatar' => $m->avatar,
            ])
        ]);
    }


    /**
     * Send a new message to a conversation.
     * Supports @mentions with member validation and structured JSON storage.
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = Auth::user();
        $body = $request->input('body', '');

        if (is_string($body) && str_contains(strtolower($body), '@chugli')) {
            // ── Membership & permission check ────────────────────────────────
            if ($conversation->type === 'channel') {
                $member = $conversation->channelUsers()->where('users.id', $user->id)->first();
                if (!$member) {
                    return response()->json(['error' => 'Unauthorized.'], 403);
                }
                if ($conversation->who_can_send_messages === 'admins') {
                    if (!in_array($member->pivot->role, ['owner', 'admin'])) {
                        return response()->json(['error' => 'Only admins can send messages in this channel.'], 403);
                    }
                }
            } else {
                if (!$conversation->users()->where('users.id', $user->id)->exists()) {
                    return response()->json(['error' => 'Unauthorized.'], 403);
                }
            }

            // Get last 20 messages for the conversation
            $query = $conversation->type === 'channel' ? $conversation->channelMessages() : $conversation->messages();
            $messages = $query
                ->with('sender:id,name')
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get()
                ->reverse();

            // Format for AI Service
            $messagesList = [];
            foreach ($messages as $msg) {
                $messagesList[] = [
                    'sender_name' => $msg->sender ? $msg->sender->name : 'Teammate',
                    'body'        => $msg->body,
                ];
            }

            // Call AIService to generate summary
            $aiService = app(\App\Services\AIService::class);
            $summary = $aiService->generateSummary($messagesList, $user);

            $userMessage = [
                'id'          => 'user_chugli_' . microtime(true),
                'body'        => $body,
                'type'        => 'text',
                'sender_id'   => $user->id,
                'sender_name' => $user->name,
                'receiver_id' => null,
                'mentions'    => [['id' => 'chugli', 'name' => 'chugli']],
                'is_read'     => true,
                'is_ephemeral'=> true,
                'created_at'  => now()->toIso8601String(),
                'sender'      => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'avatar' => $user->avatar,
                ]
            ];

            $botMessage = [
                'id'          => 'bot_chugli_' . microtime(true),
                'body'        => $summary,
                'type'        => 'text',
                'sender_id'   => 'chugli',
                'sender_name' => 'Chugli Bot',
                'receiver_id' => $user->id,
                'mentions'    => [],
                'is_read'     => true,
                'is_ephemeral'=> true,
                'created_at'  => now()->addSecond()->toIso8601String(),
                'sender'      => [
                    'id'     => 'chugli',
                    'name'   => 'Chugli Bot',
                    'avatar' => 'https://api.dicebear.com/7.x/bottts/svg?seed=chugli',
                ]
            ];

            return response()->json([
                'success'      => true,
                'is_ephemeral' => true,
                'user_message' => $userMessage,
                'bot_message'  => $botMessage,
            ], 200);
        }

        $request->validate([
            'body'              => 'required_without:file|nullable|string|max:4000',
            'type'              => 'nullable|string|in:text,media,audio,image,video,document',
            'file'              => 'nullable|file|max:51200', // 50MB limit
            'caption'           => 'nullable|string|max:255',
            'mentions'          => 'nullable|array',
            'mentions.*'        => 'integer|exists:users,id',
            'parent_message_id' => 'nullable|integer|exists:messages,id',
        ]);

        // Handle File Attachment Upload if exists
        $body = $request->input('body', '');
        $type = $request->input('type', 'text');
        $caption = $request->input('caption', null);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mime = $file->getMimeType();

            // Resolve Type
            if (str_starts_with($mime, 'image/')) {
                $type = 'image';
            } elseif (str_starts_with($mime, 'video/')) {
                $type = 'video';
            } elseif (str_starts_with($mime, 'audio/')) {
                $type = 'audio';
            } else {
                $type = 'document';
            }

            // If caption is empty, default to original filename
            if (empty($caption)) {
                $caption = $file->getClientOriginalName();
            }

            // Upload via MediaUploadService
            $mediaUploadService = app(\App\Services\MediaUploadService::class);
            $body = $mediaUploadService->upload($file, $type);
        }

        // ── Banned check ──────────────────────────────────
        if ($conversation->status === 'banned') {
            return response()->json(['error' => 'This group/channel has been banned by the Administrator.'], 403);
        }

        if ($conversation->type === 'direct') {
            $otherUser = $conversation->getOtherUser($user->id);
            if ($otherUser && $otherUser->status === 'banned') {
                return response()->json(['error' => 'This user has been banned by the Administrator.'], 403);
            }
        }

        // ── Membership & permission check ────────────────────────────────
        if ($conversation->type === 'channel') {
            $member = $conversation->channelUsers()->where('users.id', $user->id)->first();
            if (!$member) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
            if ($conversation->who_can_send_messages === 'admins') {
                if (!in_array($member->pivot->role, ['owner', 'admin'])) {
                    return response()->json(['error' => 'Only admins can send messages in this channel.'], 403);
                }
            }
        } else {
            if (!$conversation->users()->where('users.id', $user->id)->exists()) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
        }

        // ── Validate & resolve @mentions ─────────────────────────────────
        $mentionedUsers = [];
        $mentionIds = array_unique(array_filter((array) $request->input('mentions', [])));

        if (!empty($mentionIds)) {
            // Get all member IDs for this conversation (security: only members can be mentioned)
            $memberIds = $conversation->type === 'channel'
                ? $conversation->channelUsers()->pluck('users.id')->toArray()
                : $conversation->users()->pluck('users.id')->toArray();

            $validMentionIds = array_values(array_intersect($mentionIds, $memberIds));

            if (!empty($validMentionIds)) {
                $mentionedUsers = \App\Models\User::whereIn('id', $validMentionIds)
                    ->get(['id', 'name'])
                    ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
                    ->toArray();
            }
        }

        // ── Store message ────────────────────────────────────────────────
        $otherUser = $conversation->type === 'direct' ? $conversation->getOtherUser($user->id) : null;

        $messageData = [
            'sender_id'         => $user->id,
            'receiver_id'       => $otherUser ? $otherUser->id : null,
            'body'              => $body,
            'type'              => $type,
            'caption'           => $caption,
            'mentions'          => !empty($mentionedUsers) ? $mentionedUsers : null,
            'is_read'           => false,
            'parent_message_id' => $request->input('parent_message_id', null),
        ];

        if ($conversation->type === 'channel') {
            $messageData['channel_id'] = $conversation->id;
        } else {
            $messageData['group_id'] = $conversation->id;
        }

        $message = Message::create($messageData);
        $message->load(['sender:id,name,avatar', 'reactions', 'parent.sender:id,name']);

        // ── Broadcast to others ──────────────────────────────────────────
        broadcast(new MessageSent($message))->toOthers();

        // ── AI Auto-Pilot Trigger ────────────────────────────────────────
        $otherUser = $conversation->type === 'direct' ? $conversation->getOtherUser($user->id) : null;
        if ($otherUser) {
            $otherAISettings = $otherUser->aiSettings()->first();
            if ($otherAISettings && $otherAISettings->is_auto_reply_enabled) {
                // Generate a smart reply from $otherUser's AI assistant using $otherUser's tone & instructions
                $aiService = app(\App\Services\AIService::class);
                $replyText = $aiService->generateSmartReply($message->body, $otherUser);

                // Create the automated reply message
                $replyMessage = Message::create([
                    'sender_id'   => $otherUser->id,
                    'receiver_id' => $user->id,
                    'group_id'    => $conversation->id,
                    'body'        => $replyText,
                    'type'        => 'text',
                    'is_read'     => false,
                    'status'      => 'sent',
                ]);

                $replyMessage->load('sender:id,name,avatar');
                
                // Broadcast it so the sender gets B's reply instantly in real-time
                broadcast(new MessageSent($replyMessage));
            }
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id'          => $message->id,
                'body'        => $message->body,
                'type'        => $message->type,
                'sender_id'   => $message->sender_id,
                'sender_name' => $message->sender->name,
                'receiver_id' => $message->receiver_id,
                'mentions'    => $message->mentions ?? [],
                'is_read'     => $message->is_read,
                'created_at'  => $message->created_at->toIso8601String(),
                'sender'      => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'avatar' => $user->avatar,
                ],
                'reactions'   => $message->reactions->map(fn($r) => ['user_id' => $r->user_id, 'emoji' => $r->emoji])->toArray(),
                'parent_message_id' => $message->parent_message_id,
                'parent'      => $message->parent ? [
                    'id'          => $message->parent->id,
                    'body'        => $message->parent->body,
                    'type'        => $message->parent->type,
                    'sender_name' => $message->parent->sender ? $message->parent->sender->name : 'Teammate',
                ] : null,
            ]
        ], 201);
    }

    /**
     * Create a new group conversation.
     */
    public function createGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'nullable|string|in:public,private',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'members' => 'nullable|array',
            'members.*' => 'exists:users,id',
        ]);

        $currentUser = Auth::user();
        
        $iconUrl = null;
        if ($request->hasFile('icon')) {
            $iconUrl = $this->mediaUploadService->upload($request->file('icon'), 'group_icons');
        }

        $conversation = DB::transaction(function () use ($request, $currentUser, $iconUrl) {
            $conversation = Conversation::create([
                'type' => 'group',
                'name' => $request->name,
                'description' => $request->description,
                'icon' => $iconUrl,
                'visibility' => $request->input('visibility', 'public'),
                'created_by' => $currentUser->id,
            ]);

            // Add owner/creator with role 'owner'
            $conversation->users()->attach($currentUser->id, ['role' => 'owner']);

            // Add selected members with role 'member'
            if ($request->has('members') && is_array($request->members)) {
                $memberIds = array_diff($request->members, [$currentUser->id]);
                if (!empty($memberIds)) {
                    if ($request->input('visibility', 'public') === 'private') {
                        // For private groups, create pending invitations instead of joining them directly
                        foreach ($memberIds as $id) {
                            ConversationInvitation::create([
                                'conversation_id' => $conversation->id,
                                'user_id' => $id,
                                'invited_by' => $currentUser->id,
                                'status' => 'pending',
                            ]);
                        }
                    } else {
                        // For public groups, join immediately
                        $attachData = [];
                        foreach ($memberIds as $id) {
                            $attachData[$id] = ['role' => 'member'];
                        }
                        $conversation->users()->attach($attachData);
                    }
                }
            }

            return $conversation;
        });

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully!',
            'conversation_id' => $conversation->id,
        ], 201);
    }

    /**
     * Create a new channel conversation.
     */
    public function createChannel(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'nullable|string|in:public,private',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'who_can_send_messages' => 'nullable|string|in:everyone,admins',
            'member_visibility' => 'nullable|string', // string form fields
        ]);

        $currentUser = Auth::user();

        $iconUrl = null;
        if ($request->hasFile('icon')) {
            $iconUrl = $this->mediaUploadService->upload($request->file('icon'), 'channel_icons');
        }

        $conversation = DB::transaction(function () use ($request, $currentUser, $iconUrl) {
            $conversation = Conversation::create([
                'type' => 'channel',
                'name' => $request->name,
                'description' => $request->description,
                'icon' => $iconUrl,
                'visibility' => $request->input('visibility', 'public'),
                'who_can_send_messages' => $request->input('who_can_send_messages', 'everyone'),
                'member_visibility' => filter_var($request->input('member_visibility', 'true'), FILTER_VALIDATE_BOOLEAN),
                'created_by' => $currentUser->id,
            ]);

            // Add owner/creator with role 'owner'
            $conversation->channelUsers()->attach($currentUser->id, ['role' => 'owner']);

            return $conversation;
        });

        return response()->json([
            'success' => true,
            'message' => 'Channel created successfully!',
            'conversation_id' => $conversation->id,
        ], 201);
    }

    /**
     * Broadcast typing status.
     */
    public function typing(Request $request, Conversation $conversation)
    {
        $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        $user = Auth::user();

        // Ensure user belongs to conversation
        if (!$conversation->users()->where('users.id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        broadcast(new UserTyping($conversation->id, $user->id, $user->name, $request->is_typing))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Get settings for the authenticated user.
     */
    public function getSettings()
    {
        $user = Auth::user();
        
        $settings = $user->settings ?: \App\Models\UserSetting::firstOrCreate([
            'user_id' => $user->id
        ], [
            'privacy_last_seen' => 'everyone',
            'privacy_profile_photo' => 'everyone',
            'privacy_about' => 'everyone',
            'privacy_status_updates' => 'everyone',
            'read_receipts' => true,
            'security_notifications' => false,
            'two_factor_enabled' => false,
            'notification_push' => true,
            'notification_sounds' => true,
            'notification_previews' => true,
        ]);

        $aiSettings = $user->aiSettings ?: \App\Models\AISetting::firstOrCreate([
            'user_id' => $user->id
        ], [
            'is_auto_reply_enabled' => false,
            'prompt_behavior' => 'Helpful AI assistant',
            'tone' => 'Professional',
            'summary_frequency' => 'daily',
        ]);

        return response()->json([
            'profile' => [
                'name' => $user->name,
                'status_message' => $user->status_message,
                'avatar' => $user->avatar,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'privacy' => [
                'privacy_last_seen' => $settings->privacy_last_seen,
                'privacy_profile_photo' => $settings->privacy_profile_photo,
                'read_receipts' => (bool)$settings->read_receipts,
            ],
            'notifications' => [
                'notification_push' => (bool)$settings->notification_push,
                'notification_sounds' => (bool)$settings->notification_sounds,
                'notification_previews' => (bool)$settings->notification_previews,
            ],
            'ai' => [
                'is_auto_reply_enabled' => (bool)$aiSettings->is_auto_reply_enabled,
                'tone' => $aiSettings->tone ?: 'Professional',
                'prompt_behavior' => $aiSettings->prompt_behavior,
                'summary_frequency' => $aiSettings->summary_frequency ?: 'daily',
            ]
        ]);
    }

    /**
     * Save settings for the authenticated user.
     */
    public function saveSettings(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            // Profile Information
            'name' => 'nullable|string|max:50',
            'status_message' => 'nullable|string|max:150',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,

            // Privacy settings
            'privacy_last_seen' => 'nullable|string|in:everyone,contacts,nobody,Everyone,My Contacts,Nobody',
            'privacy_profile_photo' => 'nullable|string|in:everyone,contacts,nobody,Everyone,My Contacts,Nobody',
            'read_receipts' => 'nullable|boolean',

            // Notifications
            'notification_push' => 'nullable|boolean',
            'notification_sounds' => 'nullable|boolean',
            'notification_previews' => 'nullable|boolean',

            // AI settings
            'is_auto_reply_enabled' => 'nullable|boolean',
            'tone' => 'nullable|string|in:Professional,Casual,Direct',
            'prompt_behavior' => 'nullable|string',
            'summary_frequency' => 'nullable|string',
        ]);

        // Update User Profile Info
        $userData = [];
        if ($request->has('name')) {
            $userData['name'] = $request->name;
            $userData['username'] = $request->name;
        }
        if ($request->has('status_message')) {
            $userData['status_message'] = $request->status_message;
            $userData['bio'] = $request->status_message;
        }
        if ($request->has('email')) {
            $userData['email'] = $request->email;
        }
        if ($request->has('phone')) {
            $userData['phone'] = $request->phone;
            $userData['phone_number'] = $request->phone;
        }

        if ($request->hasFile('avatar')) {
            $avatarUrl = $this->mediaUploadService->upload($request->file('avatar'), 'avatars');
            $userData['avatar'] = $avatarUrl;
            $userData['profile_picture_url'] = $avatarUrl;
        }

        if (!empty($userData)) {
            $user->update($userData);
        }

        // Update User Privacy / Notifications Settings
        $settingsData = [];
        $settingsKeys = [
            'privacy_last_seen',
            'privacy_profile_photo',
            'read_receipts',
            'notification_push',
            'notification_sounds',
            'notification_previews',
        ];

        foreach ($settingsKeys as $key) {
            if ($request->has($key)) {
                $value = $request->input($key);
                if (in_array($key, ['read_receipts', 'notification_push', 'notification_sounds', 'notification_previews'])) {
                    $settingsData[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                } else {
                    $settingsData[$key] = strtolower($value);
                }
            }
        }

        if (!empty($settingsData)) {
            $settings = $user->settings ?: new \App\Models\UserSetting(['user_id' => $user->id]);
            $settings->fill($settingsData);
            $settings->save();
        }

        // Update AI Settings
        $aiData = [];
        $aiKeys = [
            'is_auto_reply_enabled',
            'tone',
            'prompt_behavior',
            'summary_frequency',
        ];

        foreach ($aiKeys as $key) {
            if ($request->has($key)) {
                $value = $request->input($key);
                if ($key === 'is_auto_reply_enabled') {
                    $aiData[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                } else {
                    $aiData[$key] = $value;
                }
            }
        }

        if (!empty($aiData)) {
            $aiSettings = $user->aiSettings ?: new \App\Models\AISetting(['user_id' => $user->id]);
            $aiSettings->fill($aiData);
            $aiSettings->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings saved successfully!'
        ]);
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided current password does not match our records.'
            ], 422);
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully!'
        ]);
    }

    /**
     * Get pending invitations for the authenticated user.
     */
    public function getInvitations()
    {
        $user = Auth::user();
        $invitations = ConversationInvitation::where('user_id', $user->id)
            ->where('status', 'pending')
            ->with(['conversation', 'inviter'])
            ->get()
            ->map(function ($invite) {
                return [
                    'id' => $invite->id,
                    'conversation_id' => $invite->conversation_id,
                    'conversation' => [
                        'name' => $invite->conversation->name,
                        'description' => $invite->conversation->description,
                        'icon' => $invite->conversation->icon,
                        'type' => $invite->conversation->type,
                    ],
                    'inviter' => [
                        'name' => $invite->inviter->name,
                        'avatar' => $invite->inviter->avatar,
                    ],
                    'created_at' => $invite->created_at->toIso8601String(),
                    'time_ago' => $invite->created_at->diffForHumans(),
                ];
            });

        return response()->json($invitations);
    }

    /**
     * Respond to a group/channel invitation.
     */
    public function respondToInvitation(Request $request, ConversationInvitation $invitation)
    {
        $request->validate([
            'response' => 'required|string|in:accept,decline',
        ]);

        $user = Auth::user();

        // Ensure invitation belongs to the current user
        if ($invitation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        if ($request->response === 'accept') {
            DB::transaction(function () use ($invitation, $user) {
                $conversation = $invitation->conversation;
                if ($conversation->type === 'channel') {
                    // Check if already in channel_user
                    if (!$conversation->channelUsers()->where('users.id', $user->id)->exists()) {
                        $conversation->channelUsers()->attach($user->id, ['role' => 'member']);
                    }
                } else {
                    // Check if already in group_user
                    if (!$conversation->users()->where('users.id', $user->id)->exists()) {
                        $conversation->users()->attach($user->id, ['role' => 'member']);
                    }
                }
                $invitation->update(['status' => 'accepted']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Invitation accepted successfully!',
            ]);
        } else {
            $invitation->update(['status' => 'declined']);

            return response()->json([
                'success' => true,
                'message' => 'Invitation declined successfully!',
            ]);
        }
    }

    /**
     * Invite a member to a private conversation.
     */
    public function inviteMember(Request $request, Conversation $conversation)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $currentUser = Auth::user();
        $targetUserId = $request->user_id;

        // Check if caller is member of this conversation
        $isMember = $conversation->type === 'channel'
            ? $conversation->channelUsers()->where('users.id', $currentUser->id)->exists()
            : $conversation->users()->where('users.id', $currentUser->id)->exists();

        if (!$isMember) {
            return response()->json(['error' => 'Unauthorized. You must be a member to invite others.'], 403);
        }

        // Check if target user is already a member
        $alreadyMember = $conversation->type === 'channel'
            ? $conversation->channelUsers()->where('users.id', $targetUserId)->exists()
            : $conversation->users()->where('users.id', $targetUserId)->exists();

        if ($alreadyMember) {
            return response()->json(['error' => 'User is already a member of this conversation.'], 422);
        }

        // Check if invitation already exists
        $existingInvite = ConversationInvitation::where('conversation_id', $conversation->id)
            ->where('user_id', $targetUserId)
            ->where('status', 'pending')
            ->first();

        if ($existingInvite) {
            return response()->json(['error' => 'An invitation has already been sent to this user.'], 422);
        }

        $invitation = ConversationInvitation::create([
            'conversation_id' => $conversation->id,
            'user_id' => $targetUserId,
            'invited_by' => $currentUser->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent successfully!',
            'invitation_id' => $invitation->id,
        ]);
    }

    /**
     * React to a message (toggle standard emoji).
     */
    public function reactToMessage(Request $request, Message $message)
    {
        $request->validate(['emoji' => 'required|string']);
        $user = Auth::user();
        
        $reaction = \App\Models\Reaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->first();

        if ($reaction) {
            if ($reaction->emoji === $request->emoji) {
                $reaction->delete();
            } else {
                $reaction->update(['emoji' => $request->emoji]);
            }
        } else {
            \App\Models\Reaction::create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'emoji' => $request->emoji
            ]);
        }

        $reactions = \App\Models\Reaction::where('message_id', $message->id)
            ->get(['emoji', 'user_id'])
            ->toArray();

        $conversationId = $message->group_id ?? $message->channel_id;
        if ($conversationId) {
            broadcast(new \App\Events\ReactionUpdated($message->id, $conversationId, $reactions))->toOthers();
        }

        return response()->json([
            'success' => true,
            'reactions' => $reactions
        ]);
    }

    /**
     * Delete a message (soft delete/Delete for Everyone).
     */
    public function deleteMessage(Request $request, Message $message)
    {
        $user = Auth::user();

        if ($message->sender_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $conversationId = $message->group_id ?? $message->channel_id;
        $messageId = $message->id;

        $message->delete();

        if ($conversationId) {
            broadcast(new \App\Events\MessageDeleted($messageId, $conversationId, false))->toOthers();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Forward a message to another conversation.
     */
    public function forwardMessage(Request $request, Message $message)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id'
        ]);

        $user = Auth::user();
        $targetConv = Conversation::findOrFail($request->conversation_id);

        // Security check: user must be a member of the target conversation
        if ($targetConv->type === 'channel') {
            $isMember = $targetConv->channelUsers()->where('users.id', $user->id)->exists();
            if (!$isMember) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
        } else {
            if (!$targetConv->users()->where('users.id', $user->id)->exists()) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
        }

        // Create the forwarded message copy in the target conversation
        $otherUser = $targetConv->type === 'direct' ? $targetConv->getOtherUser($user->id) : null;

        $messageData = [
            'sender_id'   => $user->id,
            'receiver_id' => $otherUser ? $otherUser->id : null,
            'body'        => $message->body,
            'type'        => $message->type,
            'caption'     => $message->caption ? "Forwarded: " . $message->caption : "Forwarded Attachment",
            'is_read'     => false,
        ];

        if ($targetConv->type === 'channel') {
            $messageData['channel_id'] = $targetConv->id;
        } else {
            $messageData['group_id'] = $targetConv->id;
        }

        $forwarded = Message::create($messageData);
        $forwarded->load('sender:id,name,avatar');

        return response()->json([
            'success' => true,
            'message' => [
                'id'          => $forwarded->id,
                'body'        => $forwarded->body,
                'type'        => $forwarded->type,
                'sender_id'   => $forwarded->sender_id,
                'sender_name' => $forwarded->sender->name,
                'receiver_id' => $forwarded->receiver_id,
                'mentions'    => [],
                'is_read'     => $forwarded->is_read,
                'created_at'  => $forwarded->created_at->toIso8601String(),
                'sender'      => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'avatar' => $user->avatar,
                ],
                'reactions'   => [],
                'parent_message_id' => null,
                'parent'      => null
            ]
        ], 201);
    }
    /**
     * Pin or unpin a message.
     */
    public function pinMessage(Request $request, Message $message)
    {
        $user = Auth::user();

        // Only sender, group admins, or system admins can pin
        $conversationId = $message->group_id ?? $message->channel_id;
        $conversation = Conversation::find($conversationId);

        $isAdmin = $user->role === 'admin';
        $isCreator = $conversation && $conversation->created_by === $user->id;
        $isSender = $message->sender_id === $user->id;

        if (!$isAdmin && !$isCreator && !$isSender) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $message->update(['is_pinned' => !$message->is_pinned]);

        return response()->json([
            'success'    => true,
            'is_pinned'  => (bool) $message->is_pinned,
            'message_id' => $message->id,
        ]);
    }
}
