<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\UserTyping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChatPulseMessagingTest extends TestCase
{
    use RefreshDatabase;

    protected $userA;
    protected $userB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two users for messaging tests
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
    }

    /**
     * Test searching for users to start a chat.
     */
    public function test_user_can_search_others()
    {
        $response = $this->actingAs($this->userA)
            ->getJson('/api/users/search?query=Bob');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $this->userB->id,
                'name' => 'Bob Marcus',
            ]);

        // Search for non-existent user
        $response = $this->actingAs($this->userA)
            ->getJson('/api/users/search?query=UnknownPerson');

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    /**
     * Test initiating a direct conversation.
     */
    public function test_user_can_initiate_conversation()
    {
        $response = $this->actingAs($this->userA)
            ->postJson('/api/conversations/initiate', [
                'user_id' => $this->userB->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['conversation_id', 'is_new'])
            ->assertJson(['is_new' => true]);

        $conversationId = $response['conversation_id'];

        // Assert conversation created in DB and type is direct
        $this->assertDatabaseHas('conversations', [
            'id' => $conversationId,
            'type' => 'direct',
        ]);

        // Assert both users attached as members in group_user pivot
        $this->assertDatabaseHas('group_user', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userA->id,
        ]);

        $this->assertDatabaseHas('group_user', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userB->id,
        ]);

        // Try initiating again - should return the existing conversation
        $response = $this->actingAs($this->userA)
            ->postJson('/api/conversations/initiate', [
                'user_id' => $this->userB->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'conversation_id' => $conversationId,
                'is_new' => false,
            ]);
    }

    /**
     * Test sending a message in a conversation.
     */
    public function test_user_can_send_message_and_broadcasts()
    {
        Event::fake();

        // 1. Initiate conversation
        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $this->userA->id]);
        $conversation->users()->attach([
            $this->userA->id => ['role' => 'member'],
            $this->userB->id => ['role' => 'member'],
        ]);

        // 2. Send message
        $response = $this->actingAs($this->userA)
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'body' => 'Hello Bob! How are you?',
                'type' => 'text',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.body', 'Hello Bob! How are you?')
            ->assertJsonPath('message.sender_id', $this->userA->id)
            ->assertJsonPath('message.receiver_id', $this->userB->id);

        $messageId = $response['message']['id'];

        // Assert message saved in database
        $this->assertDatabaseHas('messages', [
            'id' => $messageId,
            'body' => 'Hello Bob! How are you?',
            'sender_id' => $this->userA->id,
            'receiver_id' => $this->userB->id,
            'group_id' => $conversation->id,
            'is_read' => false,
        ]);

        // Assert MessageSent event was broadcasted to others
        Event::assertDispatched(MessageSent::class, function ($event) use ($messageId) {
            return $event->message->id === $messageId;
        });
    }

    /**
     * Test fetching messages and marking them as read.
     */
    public function test_user_can_fetch_messages_and_marks_unread_as_read()
    {
        Event::fake();

        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $this->userA->id]);
        $conversation->users()->attach([
            $this->userA->id => ['role' => 'member'],
            $this->userB->id => ['role' => 'member'],
        ]);

        // Create an unread incoming message sent by User B to User A
        $message = Message::create([
            'sender_id' => $this->userB->id,
            'receiver_id' => $this->userA->id,
            'group_id' => $conversation->id,
            'body' => 'Hi Alice!',
            'type' => 'text',
            'is_read' => false,
        ]);

        // Fetch messages acting as User A
        $response = $this->actingAs($this->userA)
            ->getJson("/api/conversations/{$conversation->id}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure(['messages'])
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', 'Hi Alice!');

        // Assert message is now marked as read in database
        $message->refresh();
        $this->assertTrue($message->is_read);
        $this->assertNotNull($message->read_at);

        // Assert MessageRead receipt event was broadcasted to others
        Event::assertDispatched(MessageRead::class, function ($event) use ($conversation, $message) {
            return $event->conversationId === $conversation->id && 
                   $event->readerId === $this->userA->id;
        });
    }

    /**
     * Test broadcasting typing state.
     */
    public function test_user_can_broadcast_typing_state()
    {
        Event::fake();

        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $this->userA->id]);
        $conversation->users()->attach([
            $this->userA->id => ['role' => 'member'],
            $this->userB->id => ['role' => 'member'],
        ]);

        $response = $this->actingAs($this->userA)
            ->postJson("/api/conversations/{$conversation->id}/typing", [
                'is_typing' => true,
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        Event::assertDispatched(UserTyping::class, function ($event) use ($conversation) {
            return $event->conversationId === $conversation->id && 
                   $event->userId === $this->userA->id &&
                   $event->isTyping === true;
        });
    }

    /**
     * Test conversation listing endpoint returns correct snippets and unread badge.
     */
    public function test_get_conversations_endpoint()
    {
        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $this->userA->id]);
        $conversation->users()->attach([
            $this->userA->id => ['role' => 'member'],
            $this->userB->id => ['role' => 'member'],
        ]);

        // Add a message
        Message::create([
            'sender_id' => $this->userB->id,
            'receiver_id' => $this->userA->id,
            'group_id' => $conversation->id,
            'body' => 'Snippet text',
            'type' => 'text',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->userA)
            ->getJson('/api/conversations');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $conversation->id,
                'name' => 'Bob Marcus', // Direct chat returns other participant's name
                'unread_count' => 1,
            ])
            ->assertJsonPath('0.latest_message.body', 'Snippet text');
    }

    /**
     * Test mentioning @chugli bot in conversation returns ephemeral gossip summary.
     */
    public function test_chugli_bot_mention_returns_ephemeral_summary()
    {
        Event::fake();

        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $this->userA->id]);
        $conversation->users()->attach([
            $this->userA->id => ['role' => 'member'],
            $this->userB->id => ['role' => 'member'],
        ]);

        // Send a few standard messages to summarize
        Message::create([
            'sender_id' => $this->userB->id,
            'receiver_id' => $this->userA->id,
            'group_id' => $conversation->id,
            'body' => 'Did you watch the cricket match yesterday?',
            'type' => 'text',
        ]);

        Message::create([
            'sender_id' => $this->userA->id,
            'receiver_id' => $this->userB->id,
            'group_id' => $conversation->id,
            'body' => 'Yes! RCB played incredibly well.',
            'type' => 'text',
        ]);

        // Post mention to @chugli
        $response = $this->actingAs($this->userA)
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'body' => '@chugli what are we talking about?',
                'type' => 'text',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_ephemeral', true)
            ->assertJsonStructure(['user_message', 'bot_message']);

        // Assert user trigger and bot summary response are NOT saved in database messages table
        $this->assertDatabaseMissing('messages', [
            'body' => '@chugli what are we talking about?',
        ]);

        $this->assertDatabaseMissing('messages', [
            'sender_id' => 'chugli',
        ]);

        // Assert no event was broadcasted (keeps it strictly private/ephemeral)
        Event::assertNotDispatched(MessageSent::class);
    }
}
