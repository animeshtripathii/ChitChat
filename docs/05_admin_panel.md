# 🛡️ ChatPulse — Admin Panel (Complete Reference)

> This document explains everything about the Admin Panel — how to access it, every feature it provides, and the exact code logic behind each action including banning users, banning groups, deleting conversations, and monitoring AI usage.

---

## 1. Admin Panel Access

```mermaid
flowchart TD
    A[User logs in] --> B{users.role = 'admin'?}
    B -->|Yes| C[Admin Panel link appears in sidebar]
    B -->|No| D[Normal user - no admin access]
    C --> E[Navigate to /admin route]
    E --> F[AdminController::index renders admin.blade.php view]
    F --> G[Admin Panel fully loaded]
```

**How to make a user admin:**
```sql
UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
```

Or via Laravel Tinker:
```php
User::where('email', 'your@email.com')->update(['role' => 'admin']);
```

**Protection:** The admin panel routes use the `auth:sanctum` middleware + a role check. Normal users who try to access `/admin` or call admin API endpoints get a `403 Unauthorized` response.

---

## 2. Admin Panel Structure

```mermaid
graph TD
    A[Admin Panel - /admin] --> B[Users Management Tab]
    A --> C[Groups & Channels Tab]
    A --> D[AI Analytics Tab]

    B --> B1[View all users with status + role]
    B --> B2[Search users by name/email/phone]
    B --> B3[Filter by role - admin/user]
    B --> B4[Filter by status - active/banned]
    B --> B5[Ban a user]
    B --> B6[Unban a user]

    C --> C1[View all groups and channels]
    C --> C2[Search by name]
    C --> C3[Filter by type - group/channel]
    C --> C4[Filter by status - active/banned]
    C --> C5[Ban a group/channel]
    C --> C6[Unban a group/channel]
    C --> C7[Permanently delete a conversation]

    D --> D1[Total AI requests]
    D --> D2[Average API latency]
    D --> D3[Total API cost]
    D --> D4[Budget utilization bar]
    D --> D5[Recent AI generation logs]
    D --> D6[Cost breakdown by model]
    D --> D7[Real-time chart data]
```

---

## 3. User Management

### 3a. Loading the User List

```mermaid
sequenceDiagram
    participant A as Admin Browser
    participant BE as Backend

    A->>BE: GET /api/admin/users?query=&role=all&status=all
    BE->>DB: SELECT id,name,email,phone,avatar,role,status,last_seen_at FROM users
    DB-->>BE: User list
    BE-->>A: JSON array of users
    A->>A: Render user cards with status badges
```

**Filter Parameters:**
| Parameter | Values | Effect |
|---|---|---|
| `query` | any string | Search by name, email, or phone |
| `role` | `all`, `user`, `admin` | Filter by role |
| `status` | `all`, `active`, `offline`, `banned` | Filter by account status |

### 3b. Status Badges in Admin Panel

```
🟢 Online badge    = status = 'online'  (currently active)
⚫ Offline badge   = status = 'offline' (was active, now gone)
🔴 Banned badge   = status = 'banned'  (blocked by admin)
👑 Admin badge    = role = 'admin'
```

---

## 4. Banning a User

### 4a. How Ban Works

```mermaid
sequenceDiagram
    participant A as Admin
    participant BE as Backend
    participant DB as Database
    participant U as Banned User

    A->>BE: POST /api/admin/users/{user}/toggle-ban
    BE->>BE: Check if user.email is root admin (tripathianimesh38@gmail.com)
    alt Root admin → protected
        BE-->>A: 400 Error "Cannot moderate system root admin"
    else Normal user
        BE->>DB: Check current user.status
        alt status = 'banned' → Unban
            DB->>DB: UPDATE users SET status = 'offline'
            BE-->>A: { success: true, message: 'User unbanned' }
        else status = 'online'/'offline' → Ban
            DB->>DB: UPDATE users SET status = 'banned'
            BE-->>A: { success: true, message: 'User banned' }
        end
    end

    note over U: Banned user tries to use the app
    U->>BE: Any API request
    BE->>BE: banned.block middleware checks user.status
    alt status = 'banned'
        BE-->>U: 403 "Your account has been suspended"
    end
```

### 4b. What Happens When a User is Banned

```mermaid
flowchart TD
    A[Admin bans User X] --> B[users.status = 'banned']
    B --> C{Next API request from User X}
    C --> D[banned.block middleware fires]
    D --> E[Return 403 - Account suspended]
    E --> F[User X sees error on all actions]
    F --> G[Cannot send messages]
    F --> H[Cannot join conversations]
    F --> I[Cannot access settings]

    J[Other users open chat with User X] --> K[Backend checks other_user.status === 'banned']
    K --> L[Show 'Banned User' indicator in chat header]
    L --> M[Input box disabled - cannot send to banned user]
```

### 4c. Banned User Middleware

**File:** `app/Http/Middleware/BannedBlock.php`

```php
// Every protected API route goes through this middleware
// If user.status === 'banned' → return 403 immediately
if ($user->status === 'banned') {
    return response()->json(['error' => 'Your account has been suspended.'], 403);
}
```

### 4d. Root Admin Protection

The email `tripathianimesh38@gmail.com` is hardcoded as the system root admin and **cannot be banned** by any admin action:

```php
if ($user->email === 'tripathianimesh38@gmail.com') {
    return response()->json(['error' => 'Cannot moderate the system root admin.'], 400);
}
```

---

## 5. Banning a Group or Channel

### 5a. How Group/Channel Ban Works

```mermaid
sequenceDiagram
    participant A as Admin
    participant BE as Backend
    participant DB as Database
    participant M as Members

    A->>BE: POST /api/admin/conversations/{conversation}/toggle-ban
    BE->>DB: Check conversations.status
    alt status = 'banned' → Unban
        DB->>DB: UPDATE conversations SET status = 'active'
        BE-->>A: { success, message: 'Conversation unbanned' }
    else status = 'active' → Ban
        DB->>DB: UPDATE conversations SET status = 'banned'
        BE-->>A: { success, message: 'Conversation banned' }
    end

    note over M: Members try to send a message
    M->>BE: POST /api/conversations/id/messages
    BE->>BE: Check conversation.status === 'banned'
    alt Banned
        BE-->>M: 403 "This group/channel has been banned by Administrator"
    end
```

### 5b. Effect on Members When Group is Banned

```mermaid
flowchart TD
    A[Group is banned] --> B[conversations.status = 'banned']
    B --> C[Members can still READ old messages]
    C --> D[But input is disabled - cannot send new messages]
    D --> E[Error shown: 'This group has been banned by the Administrator']
    E --> F[Group still appears in sidebar with red 'Banned' badge]
```

---

## 6. Permanently Deleting a Conversation (Group/Channel)

```mermaid
sequenceDiagram
    participant A as Admin
    participant BE as Backend
    participant DB as Database

    A->>BE: DELETE /api/admin/conversations/{conversation}
    BE->>DB: conversation->delete() [soft delete via SoftDeletes]
    DB->>DB: conversations.deleted_at = NOW()
    DB->>DB: All messages in this conversation are now orphaned (not shown)
    BE-->>A: { success, message: 'Conversation permanently purged' }
    A->>A: Remove conversation from admin panel list
```

> **Note:** This uses Laravel's `SoftDeletes` — the conversation row has `deleted_at` set, hiding it from all queries, but the data is not physically removed from the DB. This allows potential recovery by an engineer.

---

## 7. Admin Panel — Users Table Columns

| Column | What is shown |
|---|---|
| Avatar | Profile photo or DiceBear fallback |
| Name | Display name |
| Email | Login email |
| Phone | Phone number |
| Role | `user` or `admin` (badge) |
| Status | `online` / `offline` / `banned` (color badge) |
| Last Seen | `last_seen_at` formatted timestamp |
| Actions | **Ban / Unban** toggle button |

---

## 8. Admin Panel — Groups & Channels Table Columns

| Column | What is shown |
|---|---|
| Icon | Group/channel icon or fallback |
| Name | Group or channel name |
| Type | `group` or `channel` badge |
| Members | Count of members |
| Messages | Total message count |
| Activity | `Low` / `Medium` / `High` based on message count |
| Flagged | Count of messages with suspicious keywords |
| Status | `active` or `banned` badge |
| Actions | **Ban / Unban** toggle + **Delete** button |

**Activity Levels:**
- 🔴 Low → 0–3 messages
- 🟡 Medium → 4–10 messages
- 🟢 High → 11+ messages

**Flagged Keywords detected:** `phish`, `crypto`, `gift`, `free`, `click`

---

## 9. AI Analytics Dashboard

### 9a. Metrics Shown

```mermaid
graph LR
    A[AI Analytics] --> B["Total Requests\n(count of ai_logs rows)"]
    A --> C["Avg Latency\n(avg latency_ms of successful calls)"]
    A --> D["Total Cost\n(sum of ai_logs.cost)"]
    A --> E["Budget Utilization\n(spent / $1.00 limit × 100%)"]
```

### 9b. How AI Costs are Calculated

```
Cost per request = (tokens_used / 1000) × $0.00015

Where $0.00015 per 1k tokens is the Gemini 2.5 Flash pricing approximation.
Gemini 2.5 Pro requests use $0.00075 per 1k tokens.
```

### 9c. Recent AI Generations Log Table

Shows last 15 AI requests with:
- **Model** used (gemini-2.5-flash or gemini-2.5-pro)
- **Status** (success / failed)
- **Latency** in ms or seconds
- **Tokens** used
- **Prompt** (truncated to 60 chars)
- **Response** (truncated to 60 chars)
- **Time ago** (relative timestamp)

### 9d. Cost Breakdown by Model

```mermaid
pie title AI Cost by Model
    "gemini-2.5-flash" : 85
    "gemini-2.5-pro" : 15
```

Breakdown shows per-model:
- Token usage (e.g. `1.2k` tokens)
- Total cost (e.g. `$0.000180`)
- Percentage of total spend (progress bar)

### 9e. Chart Data

The AI analytics panel shows a bar/line chart of the last 8 AI requests showing:
- X-axis: Time of request (e.g. `14:30`)
- Y-axis left: Message volume (simulated from token count)
- Y-axis right: Latency in ms

---

## 10. Seed Data Behavior

If the `ai_logs` table is empty when the admin first loads, the system **auto-seeds 8 realistic demo entries** including:
- Content moderation calls (`SAFE`/`BAD` responses)
- Smart reply generations
- Chugli bot summaries
- Failed requests

This ensures the admin panel always looks populated even on a fresh install.

---

## 11. Admin API Routes Reference

| Method | Route | Action |
|---|---|---|
| `GET` | `/api/admin/users` | Get all users with filters |
| `POST` | `/api/admin/users/{user}/toggle-ban` | Ban or unban a user |
| `GET` | `/api/admin/ai-stats` | Get AI analytics + metrics |
| `GET` | `/api/admin/conversations` | Get all groups/channels with filters |
| `POST` | `/api/admin/conversations/{conversation}/toggle-ban` | Ban or unban a conversation |
| `DELETE` | `/api/admin/conversations/{conversation}` | Permanently delete |

All routes are wrapped in `auth` + `admin` middleware.

---

## 12. Key Files Reference

| File | Purpose |
|---|---|
| `app/Http/Controllers/AdminController.php` | All admin API logic |
| `app/Http/Middleware/BannedBlock.php` | Blocks banned users on every request |
| `app/Models/AILog.php` | AI request log model |
| `resources/views/admin.blade.php` | Admin panel HTML + Alpine.js UI |
| `routes/web.php` | Admin route definitions (lines ~70-73) |
| `database/migrations/*_create_ai_logs_table.php` | AI logs schema |
