<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use App\Models\AISetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatPulseSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user with default settings provisioned
        $this->user = User::create([
            'name' => 'Alex Morgan',
            'email' => 'alex@chatpulse.com',
            'phone' => '1234567890',
            'password' => Hash::make('password123'),
        ]);

        UserSetting::create([
            'user_id' => $this->user->id,
            'privacy_last_seen' => 'everyone',
            'privacy_profile_photo' => 'everyone',
            'read_receipts' => true,
            'notification_push' => true,
            'notification_sounds' => true,
            'notification_previews' => true,
        ]);

        AISetting::create([
            'user_id' => $this->user->id,
            'is_auto_reply_enabled' => false,
            'prompt_behavior' => 'Helpful assistant',
            'tone' => 'Professional',
            'summary_frequency' => 'daily',
        ]);
    }

    /**
     * Test guest access restrictions.
     */
    public function test_guest_cannot_access_settings_api()
    {
        $this->getJson('/api/settings')->assertStatus(401);
        $this->postJson('/api/settings', [])->assertStatus(401);
        $this->postJson('/api/settings/password', [])->assertStatus(401);
    }

    /**
     * Test fetching settings.
     */
    public function test_user_can_fetch_settings()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJson([
                'profile' => [
                    'name' => 'Alex Morgan',
                    'email' => 'alex@chatpulse.com',
                    'phone' => '1234567890',
                ],
                'privacy' => [
                    'privacy_last_seen' => 'everyone',
                    'privacy_profile_photo' => 'everyone',
                    'read_receipts' => true,
                ],
                'notifications' => [
                    'notification_push' => true,
                    'notification_sounds' => true,
                    'notification_previews' => true,
                ],
                'ai' => [
                    'is_auto_reply_enabled' => false,
                    'tone' => 'Professional',
                    'prompt_behavior' => 'Helpful assistant',
                    'summary_frequency' => 'daily',
                ]
            ]);
    }

    /**
     * Test updating profile information, privacy and notification settings.
     */
    public function test_user_can_update_profile_and_preferences()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/settings', [
                'name' => 'Alex Morgan Updated',
                'status_message' => 'Busy Coding',
                'email' => 'alex.new@chatpulse.com',
                'phone' => '0987654321',
                'privacy_last_seen' => 'contacts',
                'privacy_profile_photo' => 'nobody',
                'read_receipts' => false,
                'notification_push' => false,
                'notification_sounds' => false,
                'notification_previews' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Assert database updates on user
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Alex Morgan Updated',
            'status_message' => 'Busy Coding',
            'email' => 'alex.new@chatpulse.com',
            'phone' => '0987654321',
        ]);

        // Assert database updates on user settings
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $this->user->id,
            'privacy_last_seen' => 'contacts',
            'privacy_profile_photo' => 'nobody',
            'read_receipts' => false,
            'notification_push' => false,
            'notification_sounds' => false,
            'notification_previews' => false,
        ]);
    }

    /**
     * Test updating AI assistant settings.
     */
    public function test_user_can_update_ai_settings()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/settings', [
                'is_auto_reply_enabled' => true,
                'tone' => 'Casual',
                'prompt_behavior' => 'Sarcastic bot',
                'summary_frequency' => 'Every 10 messages',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Assert database updates
        $this->assertDatabaseHas('ai_settings', [
            'user_id' => $this->user->id,
            'is_auto_reply_enabled' => true,
            'tone' => 'Casual',
            'prompt_behavior' => 'Sarcastic bot',
            'summary_frequency' => 'Every 10 messages',
        ]);
    }

    /**
     * Test updating profile photo avatar.
     */
    public function test_user_can_upload_avatar()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->user)
            ->postJson('/api/settings', [
                'name' => 'Alex Morgan',
                'avatar' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->user->refresh();
        $this->assertNotNull($this->user->avatar);
        $this->assertStringContainsString('avatars/', $this->user->avatar);
    }

    /**
     * Test changing user password.
     */
    public function test_user_can_change_password()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/settings/password', [
                'current_password' => 'password123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    /**
     * Test changing password with incorrect current password fails.
     */
    public function test_change_password_fails_with_incorrect_current()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/settings/password', [
                'current_password' => 'wrongpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}
