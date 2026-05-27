# 🟢 ChatPulse — Online / Offline Status System

> This document explains how the green online dot works, how last seen is tracked, how typing indicators function, and how the real-time presence system is architected.

---

## 1. Overview of Presence System

```mermaid
graph TD
    A[User Presence System] --> B[Online / Offline Status]
    A --> C[Last Seen Timestamp]
    A --> D[Typing Indicator]
    A --> E[Read Receipts - Blue Ticks]

    B --> B1["Green dot in sidebar + chat header\nUpdated every 30 seconds via polling\nStored in users.status column"]
    C --> C1["users.last_seen_at timestamp\nShown as '12:45 PM' or 'Yesterday'\nControlled by privacy_last_seen setting"]
    D --> D1["Bouncing dots animation when other user types\nBroadcast via Laravel Echo (UserTyping event)\nAuto-cleared after 1.5s of inactivity"]
    E --> E1["Gray ✓✓ = delivered, Blue ✓✓ = read\nControlled by read_receipts setting\nUpdated when chat is opened"]
```

---

## 2. Database Fields for Presence

### `users` table columns used for presence:
| Column | Type | What it stores |
|---|---|---|
| `status` | enum | `online` or `offline` (or `banned`) |
| `last_seen_at` | timestamp nullable | When user was last active |

---

## 3. Online Status — How the Green Dot Works

### 3a. User Goes Online

```mermaid
sequenceDiagram
    participant B as Browser
    participant BE as Backend
    participant DB as Database

    B->>BE: User loads dashboard page
    BE->>DB: UPDATE users SET status='online', last_seen_at=NOW()
    DB-->>BE: OK
    BE-->>B: Page served with currentUser.status = 'online'

    loop Every 30 seconds (setInterval)
        B->>BE: POST /api/presence/heartbeat (or inline ping)
        BE->>DB: UPDATE users SET status='online', last_seen_at=NOW()
    end
```

### 3b. User Goes Offline

```mermaid
sequenceDiagram
    participant B as Browser
    participant BE as Backend
    participant DB as Database

    B->>B: User closes tab or disconnects
    note over B: Browser fires beforeunload event
    B->>BE: POST /api/presence/offline (sendBeacon)
    BE->>DB: UPDATE users SET status='offline', last_seen_at=NOW()

    note over BE: Also: Scheduled job marks users offline
    note over BE: if last_seen_at > 60 seconds ago
```

### 3c. How Other Users See the Status

```mermaid
flowchart TD
    A[User B loads chat list] --> B[GET /api/conversations]
    B --> C[Backend returns each conversation with other_user.status field]
    C --> D[Alpine.js renders green dot conditionally]
    D --> E{status === 'online'?}
    E -->|Yes| F[🟢 Green filled circle shown next to avatar]
    E -->|No| G[⚫ Gray/no dot shown]

    H[Polling refreshes every 30s] --> I[GET /api/conversations again]
    I --> J[Updated status values received]
    J --> K[Dots update automatically]
```

### 3d. Green Dot in UI — Code Logic

```html
<!-- In sidebar conversation list -->
<span x-show="chat.other_user && chat.other_user.status === 'online'"
      class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 rounded-full 
             border-2 border-white ring-1 ring-emerald-300 animate-pulse">
</span>
```

The `animate-pulse` Tailwind class creates the subtle pulsing animation on the green dot.

---

## 4. Last Seen Timestamp

### 4a. Privacy-Controlled Display

```mermaid
flowchart TD
    A[User B opens chat with User A] --> B[Fetch User A's last_seen_at]
    B --> C{Check User A's privacy_last_seen setting}
    C -->|everyone| D[Show timestamp to everyone]
    C -->|contacts| E[Show only to people A has chatted with]
    C -->|nobody| F[Show 'Last seen: Hidden' or nothing]
    D --> G{Format the timestamp}
    G --> H{Less than 24h ago?}
    H -->|Yes| I["Show 'Today at 2:45 PM'"]
    H -->|No| J{Yesterday?}
    J -->|Yes| K["Show 'Yesterday at 11:30 AM'"]
    J -->|No| L["Show '15 May at 9:00 AM'"]
```

### 4b. Where Last Seen is Shown in UI

- **Chat header** → below the contact name ("Last seen today at 2:45 PM")
- **Profile cards** when clicking on a user's avatar
- **Search results** when initiating a new chat

---

## 5. Typing Indicator

### 5a. How the Sender Triggers It

```mermaid
flowchart TD
    A[User starts typing in textarea] --> B[handleInput function fires on keyup]
    B --> C{isTyping already true?}
    C -->|No| D[Set isTyping = true]
    D --> E[POST /api/conversations/id/typing - is_typing: true]
    C -->|Yes| F[Already broadcasting, skip]

    G[User stops typing] --> H[Wait 1500ms debounce timer]
    H --> I[Set isTyping = false]
    I --> J[POST /api/conversations/id/typing - is_typing: false]
```

### 5b. How the Receiver Sees It

```mermaid
sequenceDiagram
    participant A as User A (typing)
    participant BE as Backend
    participant WS as WebSocket Pusher
    participant B as User B (receiver)

    A->>BE: POST /api/conversations/id/typing { is_typing: true }
    BE->>WS: broadcast(UserTyping { conv_id, user_id, name, is_typing: true })
    WS-->>B: UserTyping event on private-chat.{id} channel
    B->>B: otherUserTyping = true
    B->>B: Show animated "..." bouncing dots in chat

    note over A: A stops typing (1.5s timeout)
    A->>BE: POST /api/conversations/id/typing { is_typing: false }
    BE->>WS: broadcast(UserTyping { is_typing: false })
    WS-->>B: UserTyping event
    B->>B: otherUserTyping = false → hide dots
```

### 5c. Typing Indicator UI

```html
<!-- Bouncing dots shown when other user is typing -->
<div x-show="otherUserTyping" class="flex items-center gap-1 px-3 py-2">
    <div class="flex gap-1 items-end">
        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
    </div>
    <span class="text-xs text-gray-400 ml-1" x-text="otherUserName + ' is typing...'"></span>
</div>
```

**WebSocket Channel:** `private-chat.{conversation_id}`
**Event:** `UserTyping`
**File:** `app/Events/UserTyping.php`

---

## 6. Read Receipts (Blue Ticks)

### 6a. Tick States

```
✓     = Gray single tick     → Message sent (stored in DB)
✓✓    = Gray double tick     → Message delivered (other side has loaded the chat)
✓✓    = Blue double tick     → Message read (other user opened the conversation)
```

### 6b. When Blue Ticks Appear

```mermaid
sequenceDiagram
    participant A as User A (sender)
    participant B as User B (receiver)
    participant BE as Backend

    A->>BE: Sends message → is_read = false
    A->>A: Shows single/double gray tick

    B->>BE: Opens chat → GET /api/conversations/id/messages
    BE->>BE: Check B's read_receipts setting
    alt B has read_receipts = ON
        BE->>DB: UPDATE messages SET is_read=true, read_at=NOW()
        BE->>WS: broadcast MessageRead event to A
        WS-->>A: MessageRead { conversation_id, reader_id }
        A->>A: Update local messages → show blue ✓✓
    else B has read_receipts = OFF
        BE->>BE: Skip marking as read
        note over A: Ticks stay gray forever - respecting privacy
    end
```

### 6c. Tick Rendering in UI

```javascript
// Alpine.js logic for tick color
function getTickColor(message) {
    if (message.sender_id !== currentUser.id) return ''; // not my message
    if (message.is_read) return 'text-blue-500';  // blue ticks
    return 'text-gray-400'; // gray ticks
}
```

---

## 7. Status Badge Colors

```
🟢 Emerald (#10b981)   = Online  (status = 'online')
⚫ Gray (#9ca3af)      = Offline (status = 'offline')
🔴 Red  (#ef4444)      = Banned  (status = 'banned') - shown in Admin Panel only
```

---

## 8. Polling vs WebSocket for Presence

```mermaid
flowchart TD
    A[App initializes] --> B{WebSocket connected?}
    B -->|Yes - Pusher connected| C[Real-time events via Echo]
    B -->|No - Fallback mode| D[AJAX Polling every 2 seconds]

    C --> C1["UserTyping → instant typing dots\nMessageRead → instant blue ticks\nMessageSent → instant new messages"]
    D --> D1["pollMessages() fetches /messages every 2s\nDetects: new IDs, read changes, reaction diffs, pin changes"]

    E[Chat list polling - every 30s] --> F[GET /api/conversations]
    F --> G[Updates: online status, last message, unread counts]
```

> **Production Tip:** Set up Pusher credentials in `.env` to enable real WebSocket. Without it, the app gracefully degrades to 2-second AJAX polling.

---

## 9. Key Events & Channels Reference

| Event Class | Channel | Fired When | Data Broadcast |
|---|---|---|---|
| `MessageSent` | `private-chat.{id}` | New message | Full message object |
| `MessageRead` | `private-chat.{id}` | Chat opened by receiver | `conversation_id`, `reader_id`, `read_at` |
| `UserTyping` | `private-chat.{id}` | User starts/stops typing | `user_id`, `name`, `is_typing` |
| `ReactionUpdated` | `private-chat.{id}` | Emoji reaction toggled | `message_id`, `reactions[]` |
| `MessageDeleted` | `private-chat.{id}` | Message deleted | `message_id`, `is_moderated` |

---

## 10. Key Files Reference

| File | Purpose |
|---|---|
| `app/Events/UserTyping.php` | Typing indicator event |
| `app/Events/MessageRead.php` | Read receipt broadcast event |
| `app/Events/MessageSent.php` | New message broadcast event |
| `app/Events/ReactionUpdated.php` | Reaction sync event |
| `app/Events/MessageDeleted.php` | Delete sync event |
| `app/Http/Controllers/DashboardController.php` | `typing()`, `getMessages()` (read marks) |
| `resources/views/dashboard.blade.php` | Frontend presence UI + Echo listeners |
| `config/broadcasting.php` | WebSocket / Pusher configuration |
| `.env` | `PUSHER_APP_*` keys for WebSocket |
