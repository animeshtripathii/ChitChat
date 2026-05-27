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

class ChatPulseAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test guest-only and auth-only middleware behavior.
     */
    public function test_auth_middleware_redirects_correctly()
    {
        // Unauthenticated user trying to access dashboard is redirected to login
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        // Unauthenticated user trying to access profile setup is redirected to login
        $response = $this->get('/profile/setup');
        $response->assertRedirect('/login');

        // Create a user and log them in
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@chatpulse.com',
            'phone' => '1234567890',
            'password' => Hash::make('password123'),
        ]);

        // Authenticated user trying to access login is redirected to dashboard
        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/dashboard');

        // Authenticated user trying to access register is redirected to dashboard
        $response = $this->actingAs($user)->get('/register');
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test user registration and auto-provisioning.
     */
    public function test_user_can_register_successfully()
    {
        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@chatpulse.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Registered user is redirected to profile setup
        $response->assertRedirect(route('profile.setup'));

        // Assert user created in database
        $this->assertDatabaseHas('users', [
            'name' => 'Jane Doe',
            'email' => 'jane@chatpulse.com',
            'phone' => null,
            'role' => 'user',
        ]);

        $user = User::where('email', 'jane@chatpulse.com')->first();
        $this->assertNotNull($user);

        // Assert settings and AI settings were auto-provisioned
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('ai_settings', [
            'user_id' => $user->id,
            'is_auto_reply_enabled' => false,
        ]);

        // Assert user is authenticated
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test user registration with phone number.
     */
    public function test_user_can_register_successfully_with_phone()
    {
        $response = $this->post('/register', [
            'name' => 'Bob Phone',
            'email' => '1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Registered user is redirected to profile setup
        $response->assertRedirect(route('profile.setup'));

        // Assert user created in database with phone number
        $this->assertDatabaseHas('users', [
            'name' => 'Bob Phone',
            'email' => null,
            'phone' => '1234567890',
            'role' => 'user',
        ]);

        $user = User::where('phone', '1234567890')->first();
        $this->assertNotNull($user);

        // Assert settings and AI settings were auto-provisioned
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('ai_settings', [
            'user_id' => $user->id,
        ]);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test unique field verification API endpoint.
     */
    public function test_check_unique_endpoint()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@chatpulse.com',
            'phone' => '1234567890',
            'password' => Hash::make('password123'),
        ]);

        // 1. Guest Checks (no logged in user)
        // Check unique for email that is taken
        $response = $this->getJson('/api/check-unique?field=email&value=john@chatpulse.com');
        $response->assertStatus(200)->assertJson(['available' => false]);

        // Check unique for phone that is taken
        $response = $this->getJson('/api/check-unique?field=phone&value=1234567890');
        $response->assertStatus(200)->assertJson(['available' => false]);

        // Check unique for email that is available
        $response = $this->getJson('/api/check-unique?field=email&value=newemail@chatpulse.com');
        $response->assertStatus(200)->assertJson(['available' => true]);

        // Check unique for phone that is available
        $response = $this->getJson('/api/check-unique?field=phone&value=9999999999');
        $response->assertStatus(200)->assertJson(['available' => true]);

        // 2. Authenticated Check (acting as the user, checking their own field - should ignore self)
        $response = $this->actingAs($user)->getJson('/api/check-unique?field=email&value=john@chatpulse.com');
        $response->assertStatus(200)->assertJson(['available' => true]);
    }

    /**
     * Test login functionality.
     */
    public function test_user_can_login_with_email_or_phone()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@chatpulse.com',
            'phone' => '1234567890',
            'password' => Hash::make('password123'),
        ]);

        // Test login with email
        $response = $this->post('/login', [
            'login' => 'john@chatpulse.com',
            'password' => 'password123',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // Logout
        $this->post('/logout');

        // Test login with phone
        $response = $this->post('/login', [
            'login' => '1234567890',
            'password' => 'password123',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test onboarding profile setup.
     */
    public function test_user_can_setup_profile_with_avatar()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@chatpulse.com',
            'phone' => '1234567890',
            'password' => Hash::make('password123'),
        ]);

        $file = UploadedFile::fake()->create('avatar.png', 100, 'image/png');

        $response = $this->actingAs($user)->post('/profile/setup', [
            'name' => 'John New Name',
            'status_message' => 'Feeling great!',
            'avatar' => $file,
        ]);

        $response->assertRedirect('/dashboard');

        // Refresh user from DB
        $user->refresh();

        $this->assertEquals('John New Name', $user->name);
        $this->assertEquals('Feeling great!', $user->status_message);
        $this->assertNotNull($user->avatar);

        // Verify image stored
        $storedPath = str_replace(asset('storage/'), '', $user->avatar);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($storedPath);
    }

    /**
     * Test logout action.
     */
    public function test_user_can_logout()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@chatpulse.com',
            'phone' => '1234567890',
            'password' => Hash::make('password123'),
            'status' => 'online',
        ]);

        $response = $this->actingAs($user)->post('/logout');
        $response->assertRedirect('/login');

        $this->assertGuest();
        
        $user->refresh();
        $this->assertEquals('offline', $user->status);
    }
}
