# 😍 ChatPulse — Reactions, Pin, Delete, Forward & AI Features

> This document covers all message interactions (reactions, pin, delete, forward) and all AI-powered features (Auto-Reply, Chugli Bot, Content Moderation, Smart Reply, AI Logs).

---

## PART A — Message Interactions

---

## 1. Reaction System (Emoji Reactions)

### 1a. Database Schema

```mermaid
erDiagram
    messages ||--o{ reactions : "has many"
    users ||--o{ reactions : "gives"

    reactions {
        int id PK
        int message_id FK
        int user_id FK
        string emoji
        timestamp created_at
    }
```

**Rule:** Each user can only have **one reaction per message** (the system toggles or replaces).

### 1b. How Reacting Works

```mermaid
flowchart TD
    A[User right-clicks message] --> B[Context menu appears]
    B --> C[Quick reaction bar shows 👍 ❤️ 😂 😮 😢 🙏]
    C --> D[User clicks an emoji]
    D --> E[reactMessage function called]
    E --> F[POST /api/messages/id/react - emoji: '❤️']
    F --> G[Backend: check if user already reacted to this message]

    G --> H{Existing reaction?}
    H -->|Same emoji| I[DELETE the reaction - toggle off]
    H -->|Different emoji| J[UPDATE emoji to new one]
    H -->|No reaction| K[CREATE new reaction row]

    I --> L[Return updated reactions array]
    J --> L
    K --> L

    L --> M[broadcast ReactionUpdated event to others]
    M --> N[Receiver gets reaction via WebSocket OR polling]
    L --> O[Sender: update local message.reactions array]
```

### 1c. How Reactions Are Displayed

```mermaid
flowchart LR
    A[message.reactions array] --> B[Group by emoji]
    B --> C{"{ '❤️': 2, '👍': 1, '😂': 3 }"}
    C --> D[Render badge pills below message bubble]
    D --> E["❤️ 2   👍 1   😂 3"]
```

**Code location:** Alpine.js `groupReactions()` computed function → renders under each message bubble.

### 1d. Real-Time Reaction Sync

```mermaid
sequenceDiagram
    participant A as User A (reacts)
    participant BE as Backend
    participant WS as WebSocket (Pusher)
    participant B as User B (receiver)

    A->>BE: POST react
    BE->>BE: Save/toggle reaction
    BE->>WS: broadcast ReactionUpdated { message_id, reactions[] }
    
    alt WebSocket connected
        WS-->>B: Push ReactionUpdated event
        B->>B: Find message by ID, replace .reactions array
    else Polling fallback (every 2 seconds)
        B->>BE: GET messages
        BE-->>B: All messages with updated reactions
        B->>B: pollMessages detects reaction diff via JSON comparison
        B->>B: Replace messages array
    end
```

**Why JSON comparison?** Each reaction is serialized as `emoji+user_id` string, sorted, and JSON-stringified. If the string differs, the UI refreshes that message.

---

## 2. Pin Message

### 2a. Database Column
`messages.is_pinned` → `boolean`, default `false`.

### 2b. Pin Flow

```mermaid
flowchart TD
    A[User right-clicks message] --> B[Context menu]
    B --> C["Pin Message / Unpin Message option"]
    C --> D[pinMessage function called]
    D --> E[POST /api/messages/id/pin]
    E --> F[Backend: check auth - sender OR admin OR group creator]
    F --> G{Authorized?}
    G -->|No| H[Return 403]
    G -->|Yes| I["UPDATE messages SET is_pinned = !is_pinned"]
    I --> J["Return { success, is_pinned, message_id }"]
    J --> K[Frontend: update messages[idx].is_pinned immediately]
    K --> L{is_pinned = true?}
    L -->|Yes| M[Golden banner appears below chat header]
    L -->|No| N[Banner disappears]
    M --> O[Click banner → scrollToMessage]
    M --> P[✕ button on banner → calls pinMessage to unpin]
```

### 2c. Pinned Message UI

- **Golden banner** appears below the chat header when any message is pinned
- Shows the most recently pinned message content (truncated)
- Click the banner body → scrolls to that message and highlights it
- Press ✕ → immediately unpins
- Each pinned bubble shows a small 📌 `keep` icon at top-left corner
- Other participants see the pin via the 2-second polling cycle (`pollMessages` detects `is_pinned` diff)

---

## 3. Delete Message (Delete for Everyone)

### 3a. Authorization Rules

```mermaid
flowchart TD
    A[User clicks Delete for Everyone] --> B{Is user the message sender?}
    B -->|Yes| C[Allowed]
    B -->|No| D{Is user a system admin?}
    D -->|Yes| C
    D -->|No| E[403 Unauthorized]
```

### 3b. Delete Flow

```mermaid
sequenceDiagram
    participant A as User A (deletes)
    participant BE as Backend
    participant WS as WebSocket
    participant B as User B

    A->>BE: DELETE /api/messages/{id}
    BE->>BE: Check sender_id == user.id OR user.role == admin
    BE->>DB: message->delete() [soft delete via SoftDeletes]
    BE->>WS: broadcast(MessageDeleted { message_id, channel_id, is_moderated: false })
    WS-->>B: MessageDeleted event
    B->>B: Remove message from messages array by ID
    B->>B: Message disappears from both screens
```

**Soft Delete:** Laravel's `SoftDeletes` trait means the row is NOT physically removed. `deleted_at` is set, and the message is hidden from queries via `whereNull('deleted_at')`.

---

## 4. Forward Message

### 4a. Forward Flow

```mermaid
flowchart TD
    A[User right-clicks message → Forward] --> B[openForwardModal called]
    B --> C[Forward modal opens with conversation list + search bar]
    C --> D[User searches for a chat]
    D --> E[User clicks a conversation to select it]
    E --> F[confirmForward called]
    F --> G[POST /api/messages/id/forward { conversation_id: targetId }]
    G --> H[Backend validates target conversation]
    H --> I[Backend checks user is a member of target]
    I --> J{Member?}
    J -->|No| K[403 error]
    J -->|Yes| L[Create a NEW message in target conversation]
    L --> M[Copy body, type, caption with 'Forwarded:' prefix]
    M --> N[Return 201 with new message object]
    N --> O[Close modal]
```

---

## PART B — AI Features

---

## 5. AI Feature Overview

```mermaid
graph TD
    A[AI Features in ChatPulse] --> B[Content Moderation]
    A --> C[AI Auto-Pilot / Auto-Reply]
    A --> D[Chugli Bot - Chat Summary]
    A --> E[Smart Reply API endpoint]
    A --> F[AI Logs Dashboard]

    B --> B1["Checks every message for violence/abuse\nUsing Gemini 2.5 Flash API\nAuto-deletes if unsafe"]
    C --> C1["Replies on your behalf when you're away\nCustom tone + personality\nEnabled per user in Settings"]
    D --> D1["Type @chugli in any message\nSummarizes last 20 messages\nGossipy, fun AI persona"]
    E --> E1["POST /api/v1/messages/id/ai-reply\nGenerates a smart contextual reply"]
    F --> F1["Admin panel: see all AI requests\nLatency, cost, token counts\nModel used, prompt logged"]
```

---

## 6. Content Moderation (Auto)

```mermaid
sequenceDiagram
    participant U as User A
    participant BE as Backend
    participant AI as Gemini AI API

    U->>BE: Sends message
    BE->>AI: isUnsafe(message.body)
    Note over AI: Prompt: "Is this message violence/abuse/hate? Reply BAD or SAFE"
    AI-->>BE: "BAD" or "SAFE"

    alt Response = BAD
        BE->>DB: Create message with body = moderation warning
        BE->>DB: Soft-delete the original unsafe message
        BE->>WS: broadcast MessageDeleted { is_moderated: true }
        WS-->>All: Message replaced with "Removed by Moderator Bot"
    else Response = SAFE
        BE->>DB: Store message normally
        BE->>WS: broadcast MessageSent
    end
```

**Sandbox Mode (no API key):** Uses a hardcoded keyword list (`kill`, `murder`, `abuse`, `fuck`, `shit`, etc.) to detect unsafe content locally without an API call.

---

## 7. AI Auto-Reply (Autopilot)

```mermaid
flowchart TD
    A["User B enables is_auto_reply_enabled = true in Settings"] --> B[Saved to ai_settings table]
    B --> C[User A sends message to User B]
    C --> D[Backend: sendMessage checks receiver's AI settings]
    D --> E{is_auto_reply_enabled?}
    E -->|No| F[Normal flow]
    E -->|Yes| G[Build prompt with tone + custom instructions]
    G --> H{GEMINI_API_KEY in .env?}
    H -->|Yes| I[POST to Gemini 2.5 Flash API]
    H -->|No| J[Return tone-based fallback reply]
    I --> K[Get AI-generated reply text]
    J --> K
    K --> L[Create Message row with sender_id = User B]
    L --> M["broadcast(MessageSent) both ways"]
    M --> N[User A sees auto-reply from B instantly]
```

**Example Prompts Sent to Gemini:**
```
You are an automated AI assistant auto-replying on behalf of a user in ChatPulse.
Generate a short, natural reply (1 sentence max) to:
Tone: Professional
Custom guidelines: Always be polite and mention availability.
Incoming Message: "Are you free tomorrow?"
Reply:
```

---

## 8. Chugli Bot (@chugli)

```mermaid
sequenceDiagram
    participant U as User
    participant BE as Backend
    participant AI as Gemini API

    U->>BE: POST message body = "@chugli what's going on?"
    BE->>BE: Detect '@chugli' in message body
    BE->>DB: Fetch last 20 messages in this conversation
    BE->>AI: generateSummary(messages list)
    Note over AI: Prompt: "You are Chugli, a gossipy AI.\nSummarize this chat in 1-2 funny sentences."
    AI-->>BE: "🤫 Shhh... looks like they're debating cricket again!"
    BE-->>U: Return TWO ephemeral messages:
    note right of U: 1) User's @chugli message (shown only to sender)
    note right of U: 2) Chugli Bot reply (shown only to sender)
    U->>U: Both shown with 'is_ephemeral=true' - NOT saved to DB
    note over U: Other users CANNOT see these messages
```

**Key Concept: Ephemeral Messages**
- `is_ephemeral: true` messages exist **only in the frontend state**
- They are NOT saved to the database
- Only the requesting user can see them
- They disappear on page refresh or chat switch

---

## 9. Smart Reply (AI-Assisted Compose)

```mermaid
flowchart TD
    A[User clicks Smart Reply button on a message] --> B[POST /api/v1/messages/id/ai-reply]
    B --> C[MessageController::aiReply fetches message body]
    C --> D[Call AIService::generateSmartReply]
    D --> E[Return suggested reply text]
    E --> F[Frontend fills textarea with suggestion]
    F --> G[User can edit and send]
```

---

## 10. AI Logs (Audit Trail)

Every AI request is logged to the `ai_logs` table:

| Column | What's stored |
|---|---|
| `user_id` | Which user triggered it |
| `model` | `gemini-2.5-flash` |
| `status` | `success` or `failed` |
| `latency_ms` | How long API took (milliseconds) |
| `tokens_used` | Estimated token count |
| `cost` | Estimated cost ($0.00015 per 1k tokens) |
| `prompt` | Full prompt sent to Gemini |
| `response` | Full response received |

These logs are visible in the Admin Panel → AI Stats section.

---

## 11. AI API Key — Sandbox vs Live Mode

```mermaid
flowchart TD
    A[App boots] --> B[AIService reads GEMINI_API_KEY from .env]
    B --> C{Key present and valid?}
    C -->|Yes - real key| D[LIVE MODE: All AI calls go to Gemini API]
    C -->|No or placeholder| E[SANDBOX MODE: Use local fallbacks]

    E --> E1["Moderation: Keyword list check\nAuto-reply: Hardcoded tone replies\nChugli: Random cricket gossip fallback"]
    D --> D1["Moderation: Real Gemini classification\nAuto-reply: Intelligent context-aware reply\nChugli: Real gossipy summary of chat"]
```

**To switch to LIVE mode:** Set `GEMINI_API_KEY=your_actual_key` in `.env` file.

---

## 12. Key Files Reference

| File | Purpose |
|---|---|
| `app/Services/AIService.php` | All AI logic: moderation, auto-reply, Chugli, smart reply |
| `app/Models/Reaction.php` | Reaction model |
| `app/Events/ReactionUpdated.php` | WebSocket event for reactions |
| `app/Events/MessageDeleted.php` | WebSocket event for deletes |
| `app/Http/Controllers/DashboardController.php` | `reactToMessage()`, `deleteMessage()`, `forwardMessage()`, `pinMessage()` |
| `app/Http/Controllers/Api/V1/MessageController.php` | `aiReply()`, `react()`, `forward()` (v1 API) |
| `database/migrations/*_create_reactions_table.php` | Reactions schema |
| `database/migrations/*_add_is_pinned_to_messages_table.php` | Pin column schema |
