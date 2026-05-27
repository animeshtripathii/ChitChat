# 💬 ChitChat — Premium Real-Time AI Chat Application

ChitChat is a state-of-the-art, feature-rich real-time messaging application designed with modern aesthetics and intelligent AI features. Built with Laravel 11, TailwindCSS, Alpine.js, and powered by Gemini 2.5 Flash, ChitChat delivers a premium, alive, and interactive user experience.

---

## ✨ Features

### 💬 Messaging Core
- **1-on-1 & Group Chats**: Seamlessly start private direct messages or dynamic group conversations.
- **Broadcast Channels**: Admin-only broadcasting platforms for sending announcements to subscribers.
- **Message Pinning & Forwarding**: Keep important conversations at the top or forward messages across rooms.
- **Threaded Replies**: Reply directly to any message to start organized conversation threads.

### 🎭 Reactions & Emoji Integration
- **Quick Reactions**: Instantly react to messages using a rich popup context bar (`👍` `❤️` `😂` `😮` `😢` `🙏`).
- **Emoji Picker**: Explore and append a full suite of emojis, optimized with safe HTML-entity decoding for uniform cross-device rendering.
- **Live Syncing**: Reactions update instantly on both sender and receiver screens without page refreshes.

### 🤖 AI-Powered Capabilities (Gemini 2.5 Flash)
- **Auto-Reply Bot**: Enable an AI agent that automatically answers messages on your behalf when you are busy or offline.
- **Chugli Bot**: A quirky group-chat bot that makes lighthearted gossip or summaries based on recent user updates.
- **Smart Replies**: Contextual suggestion bubbles appearing under messages to facilitate quick, one-click replies.
- **Automated Content Moderation**: An event-driven listener intercepts messages and utilizes AI to flag and blur toxic or inappropriate content.

### 🟢 Presence & Statuses
- **Real-Time Online Presence**: Live green dot indicators show when users are actively online using WebSocket heartbeats.
- **Temporary Statuses**: Share what you are up to with custom, rich status updates that automatically expire and prune after 24 hours.

### 🛡️ Admin Control Center
- **Banning & Unbanning**: Ban toxic users instantly to restrict access.
- **Conversation Management**: Oversee and delete group chats or public channels.
- **AI Analytics**: Track AI usage logs, moderation triggers, and performance statistics directly from the admin dashboard.

---

## 🛠️ Technology Stack

- **Framework**: Laravel 11 (PHP 8.2+)
- **Frontend Logic**: Alpine.js & TailwindCSS
- **Database**: PostgreSQL (Production) / SQLite (Local Development)
- **Real-Time Broadcasting**: Laravel Reverb (WebSockets)
- **Asset Bundler**: Vite
- **Media CDN**: Cloudinary
- **Large Language Model (LLM)**: Gemini 2.5 Flash API
- **Queue Worker**: Redis (Production) / Database (Local)

---

## 🚀 Local Installation & Setup

Follow these steps to run ChitChat locally:

### 1. Clone & Install Dependencies
```bash
git clone https://github.com/animeshtripathii/ChitChat.git
cd ChitChat
composer install
npm install
```

### 2. Environment Configuration
Copy the sample environment file:
```bash
cp .env.example .env
```
Open `.env` and fill in your keys:
- **Database Connection** (SQLite or MySQL/PostgreSQL)
- **Cloudinary Keys**: `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET`
- **Gemini API Key**: `GEMINI_API_KEY`
- **Broadcasting**: Set `BROADCAST_CONNECTION=reverb`

Generate the application key:
```bash
php artisan key:generate
```

### 3. Run Migrations & Seed Database
Initialize your tables and generate demo accounts (including admins, users, and pre-populated conversations):
```bash
php artisan migrate --seed
```

### 4. Build Assets & Start Servers
Start the Vite asset development server:
```bash
npm run dev
```

In a new terminal window, start the Laravel local server:
```bash
php artisan serve
```

In a third terminal window, start the Laravel Reverb WebSocket server to enable instant messaging:
```bash
php artisan reverb:start
```

If you use queues locally for AI moderation/jobs, run:
```bash
php artisan queue:work
```

---

## 🧪 Testing Suite

ChitChat includes a comprehensive feature test suite validating authorization, group management, media uploads, AI integrations, settings, and general messaging features.

To run the automated tests:
```bash
php artisan test
```

---

## ☁️ Deployment on Render

This project is pre-configured for a smooth single-click deployment to **Render** using Docker and the included infrastructure blueprints.

### Setup using Blueprint (`render.yaml`)
1. Create a new **Blueprint** on your Render Dashboard.
2. Link your ChitChat repository.
3. Render will auto-provision:
   - **PostgreSQL Database** (`chitchat-db`)
   - **Redis Cache & Queue Store** (`chitchat-redis`)
   - **Laravel App Container** (`chitchat-app`)
4. Fill in the following environment secrets in your Render App dashboard:
   - `GEMINI_API_KEY` (Your Gemini developer key)
   - `CLOUDINARY_API_SECRET` (Your Cloudinary API secret)
5. Deploy! The custom Docker setup (`Dockerfile` & `start.sh`) will automatically handle database migrations, cache optimization, and run the background queue processes alongside the web server.

---

## 📄 License
ChitChat is open-sourced software licensed under the [MIT license](LICENSE).
