# ⚙️ ChatPulse — Settings System (Complete Reference)

> This document explains every setting in ChatPulse — what it does, where it is stored, and how it affects the application behaviour.

---

## 1. Settings Architecture

```mermaid
graph TD
    A[User clicks Settings tab] --> B[getSettings API called]
    B --> C[DashboardController::getSettings]
    C --> D[users table - profile fields]
    C --> E[user_settings table - privacy + notifications]
    C --> F[ai_settings table - AI autopilot]
    D --> G[Return combined JSON]
    E --> G
    F --> G
    G --> H[Alpine.js fills settings panel]
    H --> I[User edits values]
    I --> J{Save button}
    J --> K[saveSettings API - single endpoint for all]
    K --> L[Update users / user_settings / ai_settings]
```

---

## 2. Database Tables

### `users` table (Profile fields)
| Column | Type | What it controls |
|---|---|---|
| `name` | string | Display name shown in all chats |
| `email` | string unique | Login email |
| `phone` | string | Phone number |
| `avatar` | string (URL) | Profile photo (Cloudinary URL) |
| `status_message` | string | Bio / "About" text shown in profile |
| `status` | enum | `active` or `banned` |
| `role` | enum | `user` or `admin` |

### `user_settings` table (Privacy + Notifications)
| Column | Type | Default | What it controls |
|---|---|---|---|
| `privacy_last_seen` | enum | `everyone` | Who can see your last seen |
| `privacy_profile_photo` | enum | `everyone` | Who can see your avatar |
| `read_receipts` | boolean | `true` | Blue ticks on/off |
| `notification_push` | boolean | `true` | Push notifications |
| `notification_sounds` | boolean | `true` | Notification sounds |
| `notification_previews` | boolean | `true` | Message preview in notification |

### `ai_settings` table (AI Features)
| Column | Type | Default | What it controls |
|---|---|---|---|
| `is_auto_reply_enabled` | boolean | `false` | AI Auto-Pilot replies on your behalf |
| `tone` | enum | `Professional` | Auto-reply tone: Professional/Casual/Direct |
| `prompt_behavior` | text | empty | Custom personality/instruction for AI |
| `summary_frequency` | enum | `daily` | How often Chugli summarises chats |

---

## 3. Profile Settings

```mermaid
sequenceDiagram
    participant U as User
    participant F as Frontend
    participant B as Backend
    participant CDN as Cloudinary

    U->>F: Edit name, bio, email, phone
    U->>F: Upload new avatar image

    F->>B: POST /api/settings (multipart/form-data)
    B->>B: Validate (name max:50, email unique, etc.)
    
    alt Avatar file present
        B->>CDN: MediaUploadService::upload(avatar, 'avatars')
        CDN-->>B: Permanent avatar URL
        B->>DB: Update users.avatar = URL
    end

    B->>DB: Update users.name, email, phone, status_message
    B-->>F: { success: true }
    F->>F: Re-render avatar + name in sidebar
```

**Validation Rules:**
- `name` → max 50 characters
- `email` → must be unique across all users
- `phone` → must be unique across all users
- `avatar` → max 5MB, JPEG/PNG/GIF/JPG only

---

## 4. Privacy Settings

### 4a. Last Seen Privacy

```mermaid
flowchart TD
    A[User sets privacy_last_seen] --> B{Value?}
    B -->|everyone| C[Everyone can see your last seen]
    B -->|contacts| D[Only users you've chatted with can see it]
    B -->|nobody| E[Nobody sees your last seen - shown as blank]
```

> **Note:** Last seen is controlled by the `privacy_last_seen` setting stored in `user_settings`. The frontend reads this when rendering contact info.

### 4b. Profile Photo Privacy

```mermaid
flowchart TD
    A[User sets privacy_profile_photo] --> B{Value?}
    B -->|everyone| C[Avatar visible to all users]
    B -->|contacts| D[Only people you've talked to see it]
    B -->|nobody| E[Generic placeholder shown to everyone]
```

### 4c. Read Receipts (Blue Ticks)

```mermaid
sequenceDiagram
    participant A as User A (Receiver)
    participant B as Backend
    participant C as User B (Sender)

    note over A: A has read_receipts = OFF

    A->>B: Opens chat (GET /api/conversations/id/messages)
    B->>B: Check A's read_receipts setting
    alt read_receipts = true
        B->>DB: UPDATE messages SET is_read=true WHERE sender_id != A.id
        B->>C: broadcast(MessageRead event)
        C->>C: Messages show blue double ticks ✓✓
    else read_receipts = false
        B->>B: Skip marking as read entirely
        note over C: Messages stay gray ✓✓ forever
    end
```

**Logic Location:** `DashboardController::getMessages()` lines 229-248.

---

## 5. Notification Settings

| Setting | Effect |
|---|---|
| `notification_push` | Enables/disables browser push notifications (future: mobile) |
| `notification_sounds` | Plays a chime when a new message arrives |
| `notification_previews` | Shows message text preview in the notification badge |

> These settings are stored in `user_settings` and read by the frontend Alpine.js to decide whether to play sounds or show previews.

---

## 6. Password Change

```mermaid
sequenceDiagram
    participant U as User
    participant B as Backend

    U->>B: POST /api/settings/password { current_password, new_password, new_password_confirmation }
    B->>B: Hash::check(current_password, user.password)
    alt Password matches
        B->>DB: UPDATE users SET password = bcrypt(new_password)
        B-->>U: { success: true, message: 'Password updated!' }
    else Wrong password
        B-->>U: 422 { message: 'Current password does not match' }
    end
```

---

## 7. AI Settings Panel

### 7a. Auto-Reply (AI Autopilot)

```mermaid
flowchart TD
    A[User enables is_auto_reply_enabled toggle] --> B[Saved to ai_settings table]
    B --> C{When someone sends User A a message}
    C --> D[sendMessage checks if receiver's AI is enabled]
    D -->|enabled| E[Call AIService::generateSmartReply]
    E --> F{GEMINI_API_KEY set in .env?}
    F -->|Yes| G[Call Gemini 2.5 Flash API]
    F -->|No| H[Use tone-based fallback reply]
    G --> I[Get reply text]
    H --> I
    I --> J[Create new Message in DB as from receiver]
    J --> K[broadcast MessageSent to both parties]
    K --> L[Sender sees auto-reply instantly]
```

### 7b. Tone Options

| Tone | Example Auto-Reply |
|---|---|
| **Professional** | "Great point. I will review and update you soon. 👍" |
| **Casual** | "No worries, sounds good! 😎" |
| **Direct** | "Understood. Will look into it." |

### 7c. Custom Personality Instructions

User can write custom behavior like:
- _"Always reply in Hindi"_
- _"Start every reply with a joke"_
- _"Be very formal and never use emojis"_

This text is sent as part of the Gemini API prompt:
```
Follow these custom personality guidelines: {prompt_behavior}
```

---

## 8. Group & Channel Settings (Admin Controls)

### Group Permission Settings

When creating or managing a group, the creator (owner) controls:

```mermaid
graph TD
    A[Group Settings] --> B{Visibility}
    B --> C[Public: Anyone can join by searching]
    B --> D[Private: Invite-only via ConversationInvitation]

    A --> E{Who can send messages}
    E --> F[Everyone: all members]
    E --> G[Admins only: only owner/admin role]
```

### Channel Permission Settings

```mermaid
graph TD
    A[Channel Settings] --> B{Visibility}
    B --> C[Public: Discoverable by all users]
    B --> D[Private: Only invited members can see]

    A --> E{who_can_send_messages}
    E --> F[everyone: all members can post]
    E --> G[admins: only owner/admin role]

    A --> H{member_visibility}
    H --> I[true: members list is visible to all members]
    H --> J[false: members list is hidden]
```

---

## 9. Full Settings Save Flow

```mermaid
flowchart TD
    A[User presses Save Settings] --> B[Frontend collects all form values]
    B --> C[POST /api/settings with all changed fields]
    C --> D[Validate all fields on backend]
    D --> E[Update users table if profile fields present]
    D --> F[Update user_settings table if privacy/notifications present]
    D --> G[Update ai_settings table if AI fields present]
    E --> H[Return success JSON]
    F --> H
    G --> H
    H --> I[Frontend shows success toast notification]
```

> **Design:** The save endpoint accepts **any subset** of settings — it only updates fields that are present in the request, leaving others unchanged.

---

## 10. Key Files Reference

| File | Purpose |
|---|---|
| `app/Http/Controllers/DashboardController.php` | `getSettings()` + `saveSettings()` + `changePassword()` |
| `app/Models/UserSetting.php` | Privacy + notification settings model |
| `app/Models/AISetting.php` | AI settings model |
| `app/Services/AIService.php` | Auto-reply + Chugli bot logic |
| `resources/views/dashboard.blade.php` | Settings panel UI (line ~757 onwards) |
| `database/migrations/` | Schema for user_settings + ai_settings tables |
