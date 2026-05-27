<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChatPulseAdminConsoleTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $normalUser;
    protected $moderatorUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin User (tripathianimesh38@gmail.com)
        $this->adminUser = User::create([
            'name' => 'Admin Root',
            'email' => 'tripathianimesh38@gmail.com',
            'phone' => '9999999999',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'online',
        ]);

        // Normal User
        $this->normalUser = User::create([
            'name' => 'Luna Freeman',
            'email' => 'luna@chatpulse.com',
            'phone' => '1111111111',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'status' => 'offline',
        ]);

        // Moderator User
        $this->moderatorUser = User::create([
            'name' => 'Alice Walker',
            'email' => 'alice.w@example.com',
            'phone' => '5555555555',
            'password' => Hash::make('password123'),
            'role' => 'moderator',
            'status' => 'online',
        ]);
    }

    /**
     * Test guest is redirected to login when trying to access admin console.
     */
    public function test_guest_is_redirected_from_admin()
    {
        $response = $this->get('/admin');
        $response->assertStatus(302); // Redirect to login
    }

    /**
     * Test non-admin user (e.g. Luna) receives 403 Forbidden.
     */
    public function test_non_admin_cannot_access_admin_console()
    {
        $response = $this->actingAs($this->normalUser)
            ->get('/admin');

        $response->assertStatus(403);
    }

    /**
     * Test admin user (tripathianimesh38@gmail.com) can access admin console.
     */
    public function test_authorized_admin_can_access_admin_console()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/admin');

        $response->assertStatus(200)
            ->assertViewIs('admin')
            ->assertSee('Admin Console')
            ->assertSee('tripathianimesh38@gmail.com');
    }

    /**
     * Test user listings endpoint with filter checks.
     */
    public function test_admin_can_fetch_users_with_filters()
    {
        // 1. Fetch all users
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonFragment(['name' => 'Admin Root'])
            ->assertJsonFragment(['name' => 'Luna Freeman'])
            ->assertJsonFragment(['name' => 'Alice Walker']);

        // 2. Filter by status (online)
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/users?status=online');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => 'Admin Root'])
            ->assertJsonFragment(['name' => 'Alice Walker'])
            ->assertJsonMissing(['name' => 'Luna Freeman']);

        // 3. Filter by role (moderator)
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/users?role=moderator');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Alice Walker'])
            ->assertJsonMissing(['name' => 'Admin Root']);
    }

    /**
     * Test toggle status of users (ban/unban).
     */
    public function test_admin_can_ban_and_unban_user()
    {
        // Ban user
        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/admin/users/{$this->normalUser->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.status', 'banned');

        $this->assertDatabaseHas('users', [
            'id' => $this->normalUser->id,
            'status' => 'banned'
        ]);

        // Unban user
        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/admin/users/{$this->normalUser->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.status', 'offline');

        $this->assertDatabaseHas('users', [
            'id' => $this->normalUser->id,
            'status' => 'offline'
        ]);
    }

    /**
     * Test admin cannot ban themselves or the main root email.
     */
    public function test_admin_cannot_ban_root_admin()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/admin/users/{$this->adminUser->id}/toggle-status");

        $response->assertStatus(400)
            ->assertJsonPath('error', 'Cannot moderate the system root admin.');

        $this->assertDatabaseHas('users', [
            'id' => $this->adminUser->id,
            'status' => 'online'
        ]);
    }

    /**
     * Test fetching AI stats successfully.
     */
    public function test_admin_can_fetch_ai_stats()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/ai-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'metrics' => [
                    'total_requests',
                    'avg_latency',
                    'total_cost',
                    'budget_utilization',
                ],
                'cost_breakdown',
                'recent_generations',
            ]);
    }
}
