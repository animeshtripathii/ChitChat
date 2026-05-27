<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Conversations Table
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('direct'); // 'direct', 'group', 'channel'
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('visibility')->default('public'); // 'public', 'private'
            $table->string('who_can_send_messages')->default('everyone'); // 'everyone', 'admins'
            $table->boolean('member_visibility')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // Group_User Pivot Table
        Schema::create('group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role')->default('member'); // 'owner', 'admin', 'member'
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
        });

        // Channel_User Pivot Table
        Schema::create('channel_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role')->default('member'); // 'owner', 'admin', 'member'
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
        });

        // Messages Table
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained('conversations')->onDelete('cascade');
            $table->foreignId('channel_id')->nullable()->constrained('conversations')->onDelete('cascade');
            $table->foreignId('parent_message_id')->nullable()->constrained('messages')->onDelete('cascade');
            $table->text('body');
            $table->string('type')->default('text'); // 'text', 'media', 'audio'
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            // compatibility column
            $table->string('status')->default('sent'); // 'sent', 'delivered', 'read'
            $table->string('caption')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        // AI Settings Table
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->boolean('is_auto_reply_enabled')->default(false);
            $table->text('prompt_behavior')->nullable();
            $table->string('tone')->default('Professional');
            $table->string('summary_frequency')->default('daily');
            $table->timestamps();
        });

        // Reactions Table (for compatibility and feature support)
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('emoji');
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
        });

        // User Settings Table (for compatibility and privacy control)
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('privacy_last_seen')->default('everyone');
            $table->string('privacy_profile_photo')->default('everyone');
            $table->string('privacy_about')->default('everyone');
            $table->string('privacy_status_updates')->default('everyone');
            $table->boolean('read_receipts')->default(true);
            $table->boolean('security_notifications')->default(false);
            $table->boolean('two_factor_enabled')->default(false);
            $table->boolean('notification_push')->default(true);
            $table->boolean('notification_sounds')->default(true);
            $table->boolean('notification_previews')->default(true);
            $table->timestamps();
        });

        // Blocks Table (for compatibility)
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('blocked_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'blocked_user_id']);
        });

        // Statuses Table (for compatibility)
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type')->default('text'); // 'text', 'media'
            $table->text('content'); // text or media URL
            $table->string('caption')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('reactions');
        Schema::dropIfExists('ai_settings');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('channel_user');
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('conversations');
    }
};
