<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChatPulseGroupsChannelsTest extends TestCase
{
    use RefreshDatabase;

    protected $userA;
    protected $userB;
    protected $userC;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::create([
            'name' => 'Alice Freeman',
            'email' => 'alice@chatpulse.com',
            'phone' => '1111111111',
            'password' => Hash::make('password123'),
        ]);

        $this->userB = User::create([
            'name' => 'Bob Marcus',
            'email' => 'bob@chatpulse.com',
            'phone' => '2222222222',
            'password' => Hash::make('password123'),
        ]);

        $this->userC = User::create([
            'name' => 'Charlie Smith',
            'email' => 'charlie@chatpulse.com',
            'phone' => '3333333333',
            'password' => Hash::make('password123'),
        ]);
    }

    /**
     * Test searching users with empty query returns all other users.
     */
    public function test_search_users_with_empty_query()
    {
        $response = $this->actingAs($this->userA)
            ->getJson('/api/users/search');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $this->userB->id])
            ->assertJsonFragment(['id' => $this->userC->id]);
    }

    /**
     * Test creating a group successfully.
     * Private groups: members receive invitations and join only after accepting.
     */
    public function test_create_group_successfully()
    {
        $response = $this->actingAs($this->userA)
            ->postJson('/api/groups', [
                'name' => 'Dev Team Sync',
                'description' => 'Coordination group',
                'visibility' => 'private',
                'members' => [$this->userB->id, $this->userC->id]
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['conversation_id']);

        $conversationId = $response['conversation_id'];

        $this->assertDatabaseHas('conversations', [
            'id' => $conversationId,
            'type' => 'group',
            'name' => 'Dev Team Sync',
            'description' => 'Coordination group',
            'visibility' => 'private',
            'created_by' => $this->userA->id
        ]);

        // Alice (creator) is immediately added as owner
        $this->assertDatabaseHas('group_user', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userA->id,
            'role' => 'owner'
        ]);

        // For private groups: Bob and Charlie receive PENDING INVITATIONS (not directly added)
        $this->assertDatabaseHas('conversation_invitations', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userB->id,
            'invited_by' => $this->userA->id,
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('conversation_invitations', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userC->id,
            'invited_by' => $this->userA->id,
            'status' => 'pending'
        ]);

        // Bob and Charlie should NOT be in group_user yet (pending invitation)
        $this->assertDatabaseMissing('group_user', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userB->id,
        ]);
        $this->assertDatabaseMissing('group_user', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userC->id,
        ]);
    }

    /**
     * Test creating a PUBLIC group directly adds all members.
     */
    public function test_create_public_group_adds_members_directly()
    {
        $response = $this->actingAs($this->userA)
            ->postJson('/api/groups', [
                'name' => 'Open Dev Group',
                'description' => 'Public group',
                'visibility' => 'public',
                'members' => [$this->userB->id, $this->userC->id]
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $conversationId = $response['conversation_id'];

        // Alice is owner
        $this->assertDatabaseHas('group_user', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userA->id,
            'role' => 'owner'
        ]);

        // Bob and Charlie are directly added as members (public group)
        $this->assertDatabaseHas('group_user', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userB->id,
            'role' => 'member'
        ]);

        $this->assertDatabaseHas('group_user', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userC->id,
            'role' => 'member'
        ]);
    }

    /**
     * Test creating a channel successfully.
     */
    public function test_create_channel_successfully()
    {
        $response = $this->actingAs($this->userA)
            ->postJson('/api/channels', [
                'name' => 'Marketing Announcements',
                'description' => 'Broadcast updates',
                'visibility' => 'public',
                'who_can_send_messages' => 'admins',
                'member_visibility' => 'false'
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['conversation_id']);

        $conversationId = $response['conversation_id'];

        $this->assertDatabaseHas('conversations', [
            'id' => $conversationId,
            'type' => 'channel',
            'name' => 'Marketing Announcements',
            'description' => 'Broadcast updates',
            'visibility' => 'public',
            'who_can_send_messages' => 'admins',
            'member_visibility' => false,
            'created_by' => $this->userA->id
        ]);

        // Alice is owner in channel_user pivot
        $this->assertDatabaseHas('channel_user', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userA->id,
            'role' => 'owner'
        ]);
    }

    /**
     * Test channel message broadcasting permissions.
     */
    public function test_channel_broadcast_messaging_permissions()
    {
        // 1. Create admin-only channel by Alice
        $channel = Conversation::create([
            'type' => 'channel',
            'name' => 'News Feed',
            'who_can_send_messages' => 'admins',
            'created_by' => $this->userA->id
        ]);
        $channel->channelUsers()->attach($this->userA->id, ['role' => 'owner']);
        $channel->channelUsers()->attach($this->userB->id, ['role' => 'member']);

        // 2. Alice sends a message (Allowed)
        $response = $this->actingAs($this->userA)
            ->postJson("/api/conversations/{$channel->id}/messages", [
                'body' => 'Welcome to the channel!',
                'type' => 'text'
            ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('messages', [
            'body' => 'Welcome to the channel!',
            'channel_id' => $channel->id,
            'sender_id' => $this->userA->id
        ]);

        // 3. Bob attempts to send a message (Forbidden)
        $response = $this->actingAs($this->userB)
            ->postJson("/api/conversations/{$channel->id}/messages", [
                'body' => 'Can I post here?',
                'type' => 'text'
            ]);
        $response->assertStatus(403)
            ->assertJsonPath('error', 'Only admins can send messages in this channel.');
    }

    /**
     * Test retrieving mixed conversations endpoint.
     */
    public function test_mixed_conversations_listing()
    {
        // Direct conversation (Alice <-> Bob)
        $direct = Conversation::create(['type' => 'direct', 'created_by' => $this->userA->id]);
        $direct->users()->attach([$this->userA->id => ['role' => 'member'], $this->userB->id => ['role' => 'member']]);

        // Group conversation (Alice, Bob, Charlie)
        $group = Conversation::create(['type' => 'group', 'name' => 'Project Alpha', 'created_by' => $this->userA->id]);
        $group->users()->attach([
            $this->userA->id => ['role' => 'owner'],
            $this->userB->id => ['role' => 'member'],
            $this->userC->id => ['role' => 'member']
        ]);

        // Channel conversation (Alice, Bob)
        $channel = Conversation::create(['type' => 'channel', 'name' => 'Public Broadcast', 'created_by' => $this->userA->id]);
        $channel->channelUsers()->attach([
            $this->userA->id => ['role' => 'owner'],
            $this->userB->id => ['role' => 'member']
        ]);

        // Add some messages to direct chat
        Message::create([
            'sender_id' => $this->userB->id,
            'receiver_id' => $this->userA->id,
            'group_id' => $direct->id,
            'body' => 'Hello Alice',
            'is_read' => false
        ]);

        // Add some messages to channel
        Message::create([
            'sender_id' => $this->userA->id,
            'channel_id' => $channel->id,
            'body' => 'Broadcast message',
            'is_read' => false
        ]);

        // Alice fetches conversations
        $response = $this->actingAs($this->userA)
            ->getJson('/api/conversations');

        $response->assertStatus(200)
            ->assertJsonCount(3);

        // Verify direct chat details
        $response->assertJsonFragment([
            'id' => $direct->id,
            'type' => 'direct',
            'name' => 'Bob Marcus',
            'unread_count' => 1
        ]);

        // Verify group details
        $response->assertJsonFragment([
            'id' => $group->id,
            'type' => 'group',
            'name' => 'Project Alpha',
            'unread_count' => 0
        ]);

        // Verify channel details
        $response->assertJsonFragment([
            'id' => $channel->id,
            'type' => 'channel',
            'name' => 'Public Broadcast',
            'unread_count' => 0
        ]);
    }
}
