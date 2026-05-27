# 🗄️ Database Migrations & Schema Architecture

This document details the database migrations and schema architecture of ChitChat, including all database tables and their structural relationships.

---

## 📂 Migrations Index

ChitChat contains exactly **10 migration files** that build and configure the database schema:

| File | Tables Created / Modified | Purpose |
|---|---|---|
| `0001_01_01_000000_create_users_table.php` | `users`, `password_reset_tokens`, `sessions` | Core authentication tables for user login and sessions. |
| `0001_01_01_000001_create_cache_table.php` | `cache`, `cache_locks` | Framework cache management and atomic locks. |
| `0001_01_01_000002_create_jobs_table.php` | `jobs`, `job_batches`, `failed_jobs` | Laravel Queue management for background jobs. |
| `2026_05_23_135741_create_personal_access_tokens_table.php` | `personal_access_tokens` | Laravel Sanctum API authentication tokens. |
| `2026_05_23_140000_create_whatsapp_tables.php` | `conversations`, `group_user`, `channel_user`, `messages`, `ai_settings`, `reactions`, `user_settings`, `blocks`, `statuses` | Core messaging, settings, presence, and relationship tables. |
| `2026_05_26_160000_create_conversation_invitations_table.php` | `conversation_invitations` | Invitation management for joining groups/channels. |
| `2026_05_26_171047_add_mentions_to_messages_table.php` | Modifies `messages` | Adds JSON `mentions` support to messages. |
| `2026_05_26_180246_create_ai_logs_table.php` | `ai_logs` | Audit trail logs of Gemini AI usage and cost analytics. |
| `2026_05_27_070803_add_is_pinned_to_messages_table.php` | Modifies `messages` | Adds `is_pinned` column for pinned message logic. |
| `2026_05_27_120000_add_status_to_conversations_table.php` | Modifies `conversations` | Adds `status` column to conversation status tracking. |

---

## 🗃️ Database Tables Breakdown (20 Tables)

### 🧑‍💻 Identity & Authentication
1. **`users`**
   - **Fields**: `id`, `name`, `email`, `phone`, `password`, `bio`, `status_card`, `avatar`, `role` (user/admin), `banned_at`, `last_seen_at`, `remember_token`, `timestamps`
   - **Purpose**: Core user identity storage.
2. **`password_reset_tokens`**
   - **Fields**: `email`, `token`, `created_at`
   - **Purpose**: Tokens used to verify password reset requests.
3. **`sessions`**
   - **Fields**: `id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`
   - **Purpose**: PHP/Laravel web session storage.
4. **`personal_access_tokens`**
   - **Fields**: `id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `timestamps`
   - **Purpose**: Laravel Sanctum tokens for API authorization.

### 💬 Messaging, Groups & Channels
5. **`conversations`**
   - **Fields**: `id`, `type` (direct/group/channel), `name`, `description`, `icon`, `visibility` (public/private), `who_can_send_messages` (everyone/admins), `member_visibility`, `created_by`, `status`, `timestamps`
   - **Purpose**: Chat session descriptors.
6. **`group_user`**
   - **Fields**: `id`, `conversation_id`, `user_id`, `role` (owner/admin/member), `timestamps`
   - **Unique Key**: `conversation_id` + `user_id`
   - **Purpose**: Map group members and their operational roles.
7. **`channel_user`**
   - **Fields**: `id`, `conversation_id`, `user_id`, `role` (owner/admin/member), `timestamps`
   - **Unique Key**: `conversation_id` + `user_id`
   - **Purpose**: Map broadcast channel subscribers.
8. **`messages`**
   - **Fields**: `id`, `sender_id`, `receiver_id`, `group_id`, `channel_id`, `parent_message_id`, `body`, `type` (text/media/audio), `is_read`, `read_at`, `status` (sent/delivered/read), `caption`, `mentions` (JSON), `is_pinned`, `timestamps`, `deleted_at` (soft deletes)
   - **Purpose**: Houses all message payloads, reply mappings, pins, and soft-delete statuses.
9. **`conversation_invitations`**
   - **Fields**: `id`, `conversation_id`, `inviter_id`, `invitee_id`, `status` (pending/accepted/declined), `token`, `expires_at`, `timestamps`
   - **Purpose**: Tracks invitation states for joining restricted groups or channels.

### 🛠️ User Settings, Blocks & Stories
10. **`user_settings`**
    - **Fields**: `id`, `user_id`, `privacy_last_seen`, `privacy_profile_photo`, `privacy_about`, `privacy_status_updates`, `read_receipts`, `security_notifications`, `two_factor_enabled`, `notification_push`, `notification_sounds`, `notification_previews`, `timestamps`
    - **Purpose**: Stores individual user privacy controls and sound/push notification settings.
11. **`blocks`**
    - **Fields**: `id`, `user_id`, `blocked_user_id`, `timestamps`
    - **Unique Key**: `user_id` + `blocked_user_id`
    - **Purpose**: Restricts blocked users from initiating chats or viewing statuses.
12. **`statuses`**
    - **Fields**: `id`, `user_id`, `type` (text/media), `content`, `caption`, `expires_at`, `timestamps`
    - **Purpose**: Stores user status cards (stories) that expire after 24 hours.

### 🤖 Reactions & AI Operations
13. **`reactions`**
    - **Fields**: `id`, `message_id`, `user_id`, `emoji`, `timestamps`
    - **Unique Key**: `message_id` + `user_id`
    - **Purpose**: Maps user emoji reactions to individual messages.
14. **`ai_settings`**
    - **Fields**: `id`, `user_id`, `is_auto_reply_enabled`, `prompt_behavior`, `tone`, `summary_frequency`, `timestamps`
    - **Purpose**: Stores configuration for the Gemini Auto-Reply bot.
15. **`ai_logs`**
    - **Fields**: `id`, `user_id`, `conversation_id`, `message_id`, `type` (moderation/auto_reply/smart_reply/chugli), `prompt_tokens`, `completion_tokens`, `cost`, `status` (success/failed), `response_content`, `raw_payload`, `timestamps`
    - **Purpose**: Real-time audit trail of AI model usage and content moderation events.

### ⚙️ Laravel Queue & Cache Internals
16. **`cache`**
    - **Purpose**: Session caching and system lookup acceleration.
17. **`cache_locks`**
    - **Purpose**: Atomic locks preventing race conditions in background job execution.
18. **`jobs`**
    - **Purpose**: Storage for queued tasks (e.g. executing asynchronous AI moderation or dispatching auto-replies).
19. **`job_batches`**
    - **Purpose**: Track bulk-processed jobs.
20. **`failed_jobs`**
    - **Purpose**: Error logs for background jobs that couldn't complete.
