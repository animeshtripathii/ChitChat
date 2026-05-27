<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\AILog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudinaryMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and a conversation
        $this->user = User::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
            'password' => bcrypt('password123'),
        ]);

        $this->conversation = Conversation::create([
            'type' => 'group',
            'name' => 'Engineering Room',
            'created_by' => $this->user->id,
        ]);

        $this->conversation->users()->attach($this->user->id, ['role' => 'member']);
    }

    /**
     * Test sending an image file attachment.
     */
    public function test_user_can_send_image_attachment()
    {
        Storage::fake('public');

        // Create a fake image without using the GD extension
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->user)
            ->postJson("/api/conversations/{$this->conversation->id}/messages", [
                'file' => $file,
                'caption' => 'A beautiful sunset image',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        // Check if message was saved in database with image type
        $this->assertDatabaseHas('messages', [
            'group_id' => $this->conversation->id,
            'sender_id' => $this->user->id,
            'type' => 'image',
            'caption' => 'A beautiful sunset image',
        ]);

        $message = Message::where('group_id', $this->conversation->id)->first();
        $this->assertStringContainsString('/storage/uploads/image/', $message->body);
    }

    /**
     * Test sending a PDF document attachment.
     */
    public function test_user_can_send_pdf_attachment()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('project_proposal.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->postJson("/api/conversations/{$this->conversation->id}/messages", [
                'file' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        // Check if message was saved in database with document type and original filename as default caption
        $this->assertDatabaseHas('messages', [
            'group_id' => $this->conversation->id,
            'sender_id' => $this->user->id,
            'type' => 'document',
            'caption' => 'project_proposal.pdf',
        ]);

        $message = Message::where('group_id', $this->conversation->id)->first();
        $this->assertStringContainsString('/storage/uploads/document/', $message->body);
    }

    /**
     * Test getting AI performance and cost telemetry aggregates.
     */
    public function test_admin_can_retrieve_dynamic_ai_stats_and_chart()
    {
        // Create an admin user with the required system administrator email
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'tripathianimesh38@gmail.com',
            'phone' => '1112223333',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        // Access stats - should auto-seed since count is 0
        $response = $this->actingAs($admin)
            ->getJson('/api/admin/ai-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'metrics' => [
                    'total_requests',
                    'avg_latency',
                    'total_cost',
                    'budget_utilization',
                    'spent_budget',
                    'limit_budget',
                    'real_total_users',
                ],
                'cost_breakdown',
                'recent_generations',
                'chart'
            ]);

        // Assert 8 records seeded successfully
        $this->assertEquals(8, AILog::count());
        $this->assertCount(8, $response['chart']);
    }
}
