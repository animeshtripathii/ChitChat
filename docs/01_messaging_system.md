# 📨 ChatPulse — Messaging System (From Basic to Advanced)

> This document explains **exactly how a message travels** from the moment a user presses Send, to the moment every participant sees it — covering Direct Chats, Groups, and Channels, including media, replies, mentions, @Chugli Bot, AI Auto-Pilot, and real-time delivery.

---

## 1. Architecture Overview

ChatPulse messaging is a **3-layer system**:

| Layer | Technology | Role |
|---|---|---|
| **Frontend** | Alpine.js + Blade (no framework) | Captures input, shows messages, polls for updates |
| **Backend** | Laravel 11 (PHP) | Validates, stores, broadcasts |
| **Real-time** | Laravel Echo + Pusher / AJAX Polling fallback | Pushes events to other participants |

---

## 2. Conversation Types

```mermaid
graph TD
    A[User opens ChatPulse] --> B{Which tab?}
    B --> C[Chats Tab]
    B --> D[Groups Tab]
    B --> E[Channels Tab]

    C --> C1["Direct (1-to-1) conversation\ntype = 'direct'\nStored in conversations table\nLinked via conversation_user pivot"]
    D --> D1["Group conversation\ntype = 'group'\nMultiple users, roles: owner/member\nStored in conversations table"]
    E --> E1["Channel conversation\ntype = 'channel'\nPublic or Private\nLinked via channel_user pivot"]
```

**Key DB Tables:**
- `conversations` — stores all chat types (direct, group, channel)
- `conversation_user` — pivot for groups (with `role` column: owner/member)
- `channel_user` — pivot for channels (with `role` column: owner/admin/member)
- `messages` — every single message ever sent

---

## 3. Initiating a Direct Chat

```mermaid
sequenceDiagram
    participant U as User (Browser)
    participant B as Backend (Laravel)
    participant DB as Database

    U->>B: POST /api/conversations/initiate { target_user_id }
    B->>DB: Find existing direct conversation between the two users
    alt Already exists
        DB-->>B: Return existing conversation
    else New chat
        B->>DB: Create new conversations row (type=direct)
        B->>DB: Attach both users via conversation_user
    end
    B-->>U: { conversation_id, chat details }
    U->>U: selectChat() → Load messages
```

**Code Path:**
- Route: `POST /api/conversations/initiate`
- Controller: `DashboardController::initiateConversation()`
- The system first checks if a conversation between these two users already exists to avoid duplicates.

---

## 4. Sending a Text Message — Step by Step

### 4a. Frontend Side (Alpine.js)

```mermaid
flowchart TD
    A[User types in textarea] --> B{Enter key or Send button?}
    B -->|Enter key| C{Mention dropdown active?}
    C -->|Yes| D[Select mention, don't send]
    C -->|No| E[sendMessage function called]
    B -->|Send button click| E

    E --> F{Has selectedFile?}
    F -->|Yes| G[Build FormData with file + caption + body]
    F -->|No| H[Build JSON payload]

    G --> I[POST /api/conversations/id/messages multipart/form-data]
    H --> I

    I --> J{Response success?}
    J -->|Yes| K[Append message to this.messages array]
    J -->|No| L[Show error toast]

    K --> M[scrollMessagesDown]
    K --> N[Clear input / file / replyToMessage]
    K --> O[loadChats - refresh sidebar snippet]
```

### 4b. Backend Side (DashboardController::sendMessage)

```mermaid
flowchart TD
    A[Request arrives] --> B{Body contains '@chugli'?}
    B -->|Yes| CHUGLI[→ Route to Chugli Bot flow]
    B -->|No| C

    C[Validate request fields] --> D{Has file attachment?}
    D -->|Yes| E[Detect MIME type - image/video/audio/document]
    E --> F[Upload via MediaUploadService → Cloudinary CDN]
    F --> G[Set body = CDN URL, type = file type]
    D -->|No| G2[Keep body = text, type = text]

    G --> H
    G2 --> H

    H{Conversation banned?} -->|Yes| ERR403[Return 403 error]
    H -->|No| I

    I{User is a member?} -->|No| ERR403b[Return 403 error]
    I -->|Yes| J

    J{Channel with admins-only posting?} -->|User not admin| ERR403c[Return 403]
    J -->|OK| K

    K[Resolve @mentions → validate against member list] --> L

    L[Create Message row in DB] --> M[Load sender + reactions + parent eager-loads]

    M --> N["broadcast(new MessageSent(message))->toOthers()"]
    N --> O{Is AI Auto-Pilot enabled for receiver?}
    O -->|Yes| P[Generate smart reply via AIService]
    P --> Q[Create auto-reply Message row]
    Q --> R["broadcast(new MessageSent(replyMessage))"]
    O -->|No| S

    S --> T[Return 201 JSON with full message object]
```

---

## 5. Media Upload Flow (Images, Videos, PDFs, Audio)

```mermaid
sequenceDiagram
    participant U as User
    participant F as Frontend
    participant B as Backend
    participant C as Cloudinary CDN

    U->>F: Selects file via attach icon
    F->>F: handleFileSelect() - detect MIME type
    F->>F: Show attachment preview bar above input
    U->>F: Press Send
    F->>B: POST multipart/form-data { file, caption, body }
    B->>B: Detect MIME type (image/video/audio/document)
    B->>C: MediaUploadService::upload(file, type)
    C-->>B: Return permanent CDN URL
    B->>DB: Create message { body: CDN_URL, type: image/video/... }
    B-->>F: Response with message.body = CDN URL
    F->>F: Render video/img/audio element with CDN URL
```

**Why CDN?** Files are stored on Cloudinary so they:
- Load fast globally
- Never expire
- Support transcoding for video

---

## 6. Threaded Replies (Quote-Reply)

```mermaid
flowchart LR
    A[User right-clicks a message] --> B[openContextMenu shown]
    B --> C[User clicks Reply]
    C --> D[startReply function called]
    D --> E[replyToMessage state = that message]
    E --> F[Reply preview banner appears above input]
    F --> G[User types and sends]
    G --> H{sendMessage sends parent_message_id}
    H --> I[Backend stores parent_message_id in DB]
    I --> J[getMessages eager-loads parent.sender]
    J --> K[Frontend renders green quote block inside bubble]
    K --> L[Click on quote → scrollToMessage + highlight]
```

**DB Column:** `messages.parent_message_id` → foreign key to `messages.id`

---

## 7. @Mention System

```mermaid
flowchart TD
    A[User types @ in textarea] --> B[handleInput detects @ character]
    B --> C[mentionState.active = true, query = typed text]
    C --> D[Filter conversation members by name]
    D --> E[Show mention dropdown]
    E --> F[User selects a member]
    F --> G[selectMention → append @name chip to textarea]
    G --> H[Mentions array gets user ID added]
    H --> I[On send → mentions array sent to backend]
    I --> J[Backend validates IDs are actual members]
    J --> K[Store mentions JSON in message row]
    K --> L[renderMessageBody converts @name to green pill chips in UI]
```

---

## 8. Real-Time Delivery (WebSocket + Polling Fallback)

```mermaid
sequenceDiagram
    participant A as User A (Sender)
    participant BE as Backend
    participant P as Pusher/WebSocket
    participant B as User B (Receiver)

    A->>BE: POST send message
    BE->>BE: Save to DB
    BE->>P: broadcast(MessageSent)->toOthers()
    P-->>B: Push MessageSent event via private channel chat.{id}
    B->>B: Echo .listen('MessageSent') appends message to UI

    note over B: If WebSocket unavailable:
    B->>BE: GET /api/conversations/id/messages [every 2 seconds]
    BE-->>B: Return full messages list
    B->>B: pollMessages() detects new IDs → update array
```

**Channel name:** `private-chat.{conversation_id}`

**Events broadcast:**
| Event | When |
|---|---|
| `MessageSent` | New message sent |
| `MessageRead` | Other user opens chat |
| `UserTyping` | User is typing |
| `ReactionUpdated` | Emoji reaction toggled |
| `MessageDeleted` | Message deleted |

---

## 9. Group Chat — Creating and Messaging

```mermaid
flowchart TD
    A[User clicks New Group] --> B[createGroup form opens]
    B --> C[Fill name, description, icon, visibility, add members]
    C --> D{Visibility?}
    D -->|Public| E[Members added immediately via conversation_user pivot]
    D -->|Private| F[ConversationInvitation rows created for each member]
    F --> G[Members receive invite in Invitations tab]
    G --> H[Member accepts → added to conversation_user]

    E --> I[Group appears in all member sidebars]
    I --> J[Messaging works same as direct - group_id set on message]
```

---

## 10. Channel — Creating and Posting

```mermaid
flowchart TD
    A[User clicks New Channel] --> B[createChannel form]
    B --> C["Fill name, visibility public/private\nwho_can_send_messages: everyone or admins_only"]
    C --> D[Channel created, creator gets owner role in channel_user]
    D --> E{Visibility public?}
    E -->|Yes| F[All users can see & join from Channels tab]
    E -->|No| G[Only invited users can see it]
    F --> H[User joins → channelUsers attach with role=member]
    H --> I[Posting: checked who_can_send_messages]
    I --> J{everyone or admins?}
    J -->|Admins only| K[Only owner/admin can post]
    J -->|Everyone| L[All members can post]
```

---

## 11. Message State Machine

```mermaid
stateDiagram-v2
    [*] --> Sent: User sends message
    Sent --> Delivered: Message stored in DB
    Delivered --> Read: Receiver opens chat
    Read --> Reacted: Receiver adds emoji reaction
    Reacted --> Read: Receiver removes reaction
    Sent --> Deleted: Sender deletes it
    Read --> Deleted: Sender/admin deletes it
    Delivered --> Pinned: Sender/admin pins it
    Pinned --> Delivered: Sender/admin unpins it
```

**Ticks in UI:**
- ✓ (single gray) = Sent
- ✓✓ (double gray) = Delivered (stored in DB)
- ✓✓ (double blue) = Read (receiver opened chat)

---

## 12. Typing Indicator

```mermaid
sequenceDiagram
    participant A as User A
    participant B as Backend
    participant C as User B

    A->>A: Starts typing in textarea (handleInput fires)
    A->>B: POST /api/conversations/id/typing { is_typing: true }
    B->>C: broadcast(UserTyping) -> toOthers()
    C->>C: otherUserTyping = true → show bouncing dots
    note over A: User stops typing for 1.5s
    A->>B: POST /api/conversations/id/typing { is_typing: false }
    B->>C: broadcast(UserTyping) -> toOthers()
    C->>C: otherUserTyping = false → hide dots
```

---

## 13. Key Files Reference

| File | Purpose |
|---|---|
| `app/Http/Controllers/DashboardController.php` | All messaging endpoints |
| `app/Services/MediaUploadService.php` | Cloudinary file upload |
| `app/Events/MessageSent.php` | WebSocket broadcast event |
| `app/Models/Message.php` | Message model + relationships |
| `app/Models/Conversation.php` | Conversation model |
| `resources/views/dashboard.blade.php` | Entire frontend (Alpine.js) |
| `routes/web.php` | All API route definitions |
