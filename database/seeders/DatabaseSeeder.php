<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSetting;
use App\Models\AISetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Luna
        $luna = User::create([
            'name' => 'Luna',
            'email' => 'luna@chatpulse.com',
            'phone' => '1111111111',
            'avatar' => 'https://api.dicebear.com/7.x/pixel-art/svg?seed=luna',
            'status_message' => 'Drawing stars ✨',
            'role' => 'user',
            'password' => Hash::make('password123'),
            'status' => 'online',
            'last_seen_at' => now(),
            // Keep old properties filled for backward compatibility
            'username' => 'Luna',
            'phone_number' => '1111111111',
            'profile_picture_url' => 'https://api.dicebear.com/7.x/pixel-art/svg?seed=luna',
            'bio' => 'Drawing stars ✨',
        ]);

        UserSetting::create([
            'user_id' => $luna->id,
            'privacy_last_seen' => 'everyone',
            'privacy_profile_photo' => 'everyone',
            'privacy_about' => 'everyone',
            'privacy_status_updates' => 'everyone',
            'read_receipts' => true,
        ]);

        AISetting::create([
            'user_id' => $luna->id,
            'is_auto_reply_enabled' => false,
            'prompt_behavior' => 'Helpful AI responder',
            'summary_frequency' => 'daily'
        ]);

        // Seed Leo
        $leo = User::create([
            'name' => 'Leo',
            'email' => 'leo@chatpulse.com',
            'phone' => '2222222222',
            'avatar' => 'https://api.dicebear.com/7.x/pixel-art/svg?seed=leo',
            'status_message' => 'Love dinosaurs 🦖',
            'role' => 'user',
            'password' => Hash::make('password123'),
            'status' => 'online',
            'last_seen_at' => now(),
            // Keep old properties filled for backward compatibility
            'username' => 'Leo',
            'phone_number' => '2222222222',
            'profile_picture_url' => 'https://api.dicebear.com/7.x/pixel-art/svg?seed=leo',
            'bio' => 'Love dinosaurs 🦖',
        ]);

        UserSetting::create([
            'user_id' => $leo->id,
            'privacy_last_seen' => 'everyone',
            'privacy_profile_photo' => 'everyone',
            'privacy_about' => 'everyone',
            'privacy_status_updates' => 'everyone',
            'read_receipts' => true,
        ]);

        AISetting::create([
            'user_id' => $leo->id,
            'is_auto_reply_enabled' => false,
            'prompt_behavior' => 'Sassy Dino expert',
            'summary_frequency' => 'daily'
        ]);

        // Seed Admin (tripathianimesh38@gmail.com)
        $admin = User::create([
            'name' => 'Admin Root',
            'email' => 'tripathianimesh38@gmail.com',
            'phone' => '9999999999',
            'avatar' => 'https://api.dicebear.com/7.x/pixel-art/svg?seed=admin',
            'status_message' => 'Clubhouse Overseer 🦉',
            'role' => 'admin',
            'password' => Hash::make('password123'),
            'status' => 'online',
            'last_seen_at' => now(),
            // Keep old properties filled for backward compatibility
            'username' => 'tripathianimesh38',
            'phone_number' => '9999999999',
            'profile_picture_url' => 'https://api.dicebear.com/7.x/pixel-art/svg?seed=admin',
            'bio' => 'Clubhouse Overseer 🦉',
        ]);

        UserSetting::create([
            'user_id' => $admin->id,
            'privacy_last_seen' => 'everyone',
            'privacy_profile_photo' => 'everyone',
            'privacy_about' => 'everyone',
            'privacy_status_updates' => 'everyone',
            'read_receipts' => true,
        ]);

        AISetting::create([
            'user_id' => $admin->id,
            'is_auto_reply_enabled' => false,
            'prompt_behavior' => 'System Auditor',
            'summary_frequency' => 'daily'
        ]);

        // Seed BK (Banned User)
        $bk = User::create([
            'name' => 'Bad Karma',
            'email' => 'bk98@spammail.com',
            'phone' => '4444444444',
            'avatar' => 'https://api.dicebear.com/7.x/pixel-art/svg?seed=bk',
            'status_message' => 'Send spam here 🚫',
            'role' => 'user',
            'password' => Hash::make('password123'),
            'status' => 'banned',
            'last_seen_at' => now()->subWeeks(2),
            'username' => 'badkarma',
            'phone_number' => '4444444444',
            'profile_picture_url' => 'https://api.dicebear.com/7.x/pixel-art/svg?seed=bk',
            'bio' => 'Send spam here 🚫',
        ]);

        UserSetting::create([
            'user_id' => $bk->id,
            'privacy_last_seen' => 'everyone',
            'privacy_profile_photo' => 'everyone',
            'privacy_about' => 'everyone',
            'privacy_status_updates' => 'everyone',
            'read_receipts' => true,
        ]);

        AISetting::create([
            'user_id' => $bk->id,
            'is_auto_reply_enabled' => false,
            'prompt_behavior' => 'Spam responder',
            'summary_frequency' => 'daily'
        ]);

        // Seed AW (Moderator User)
        $aw = User::create([
            'name' => 'Alice Walker',
            'email' => 'alice.w@example.com',
            'phone' => '5555555555',
            'avatar' => 'https://api.dicebear.com/7.x/pixel-art/svg?seed=alice_w',
            'status_message' => 'Keeping it clean 🧼',
            'role' => 'moderator',
            'password' => Hash::make('password123'),
            'status' => 'online',
            'last_seen_at' => now(),
            'username' => 'alicewalker',
            'phone_number' => '5555555555',
            'profile_picture_url' => 'https://api.dicebear.com/7.x/pixel-art/svg?seed=alice_w',
            'bio' => 'Keeping it clean 🧼',
        ]);

        UserSetting::create([
            'user_id' => $aw->id,
            'privacy_last_seen' => 'everyone',
            'privacy_profile_photo' => 'everyone',
            'privacy_about' => 'everyone',
            'privacy_status_updates' => 'everyone',
            'read_receipts' => true,
        ]);

        AISetting::create([
            'user_id' => $aw->id,
            'is_auto_reply_enabled' => false,
            'prompt_behavior' => 'Moderator responder',
            'summary_frequency' => 'daily'
        ]);
    }
}
