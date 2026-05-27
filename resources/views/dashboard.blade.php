@extends('layouts.app')

@section('title', 'ChitChat - Main Dashboard')

@section('content')
<!-- Full-screen ChitChat Workspace Wrapper -->
<div class="fixed inset-0 flex overflow-hidden bg-[#f0f2f5] font-sans text-gray-800 z-50 select-none"
     x-data="chitchatApp()" x-init="initApp()">

    <!-- COLUMN 1: FAR-LEFT NAVIGATION PANEL -->
    <aside class="bg-[#f0f2f5] border-r border-gray-300/60 flex flex-col justify-between shrink-0 transition-all duration-300"
           :class="navCollapsed ? 'w-20 p-2.5' : 'w-64 p-4'">
        <div class="space-y-6">
            <!-- App Logo / Active User Profile -->
            <div class="flex items-center gap-3 px-2 py-1" :class="navCollapsed ? 'justify-center' : ''">
                <div class="relative shrink-0">
                    <img :src="currentUser.avatar || 'https://api.dicebear.com/7.x/pixel-art/svg?seed=' + currentUser.email" 
                         alt="Avatar" class="w-11 h-11 rounded-full object-cover border border-gray-300">
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                </div>
                <div class="min-w-0" x-show="!navCollapsed" x-cloak>
                    <h3 class="font-outfit font-extrabold text-gray-900 truncate" x-text="currentUser.name"></h3>
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Online</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="space-y-1">
                <button @click="switchTab('chats')"
                        class="w-full flex items-center gap-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-left"
                        :class="[
                            activeTab === 'chats' ? 'bg-[#d1fae5] text-emerald-800 font-bold' : 'text-gray-600 hover:bg-gray-200/50 hover:text-gray-900',
                            navCollapsed ? 'justify-center px-0' : 'px-3'
                        ]">
                    <span class="material-symbols-outlined text-[20px]">chat</span>
                    <span x-show="!navCollapsed" x-cloak>Chats</span>
                </button>
                <button @click="switchTab('groups')"
                        class="w-full flex items-center gap-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-left"
                        :class="[
                            activeTab === 'groups' ? 'bg-[#d1fae5] text-emerald-800 font-bold' : 'text-gray-600 hover:bg-gray-200/50 hover:text-gray-900',
                            navCollapsed ? 'justify-center px-0' : 'px-3'
                        ]">
                    <span class="material-symbols-outlined text-[20px]">group</span>
                    <span x-show="!navCollapsed" x-cloak>Groups</span>
                </button>
                <button @click="switchTab('channels')"
                        class="w-full flex items-center gap-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-left"
                        :class="[
                            activeTab === 'channels' ? 'bg-[#d1fae5] text-emerald-800 font-bold' : 'text-gray-600 hover:bg-gray-200/50 hover:text-gray-900',
                            navCollapsed ? 'justify-center px-0' : 'px-3'
                        ]">
                    <span class="material-symbols-outlined text-[20px]">campaign</span>
                    <span x-show="!navCollapsed" x-cloak>Channels</span>
                </button>
                <button @click="switchTab('invitations')"
                        class="w-full flex items-center gap-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-left relative"
                        :class="[
                            activeTab === 'invitations' ? 'bg-[#d1fae5] text-emerald-800 font-bold' : 'text-gray-600 hover:bg-gray-200/50 hover:text-gray-900',
                            navCollapsed ? 'justify-center px-0' : 'px-3'
                        ]"
                        title="Invitations">
                    <span class="material-symbols-outlined text-[20px]">mail</span>
                    <span x-show="!navCollapsed" x-cloak>Invites</span>
                    <!-- Pending invites count badge -->
                    <span x-show="invitations.length > 0" 
                          class="absolute right-3 bg-red-500 text-white font-extrabold text-[9px] px-1.5 py-0.5 rounded-full flex items-center justify-center min-w-[18px] h-[18px]"
                          :class="navCollapsed ? 'right-1 top-1' : 'right-3 top-1/2 -translate-y-1/2'"
                          x-text="invitations.length" x-cloak></span>
                </button>
                <button @click="switchTab('settings')"
                        class="w-full flex items-center gap-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-left"
                        :class="[
                            activeTab === 'settings' ? 'bg-[#d1fae5] text-emerald-800 font-bold' : 'text-gray-600 hover:bg-gray-200/50 hover:text-gray-900',
                            navCollapsed ? 'justify-center px-0' : 'px-3'
                        ]">
                    <span class="material-symbols-outlined text-[20px]">settings</span>
                    <span x-show="!navCollapsed" x-cloak>Settings</span>
                </button>
                @if(auth()->user()->email === 'tripathianimesh38@gmail.com')
                <a href="/admin"
                   class="w-full flex items-center gap-3 py-2.5 rounded-xl text-sm font-bold text-red-650 hover:bg-red-50/50 transition-all duration-200 text-left"
                   :class="[
                       navCollapsed ? 'justify-center px-0' : 'px-3'
                   ]">
                    <span class="material-symbols-outlined text-[20px] text-red-650">security</span>
                    <span x-show="!navCollapsed" x-cloak>Admin Console</span>
                </a>
                @endif
                
                <!-- Expand middle sidebar button (contextual) -->
                <button x-show="middleCollapsed && activeTab !== 'settings' && activeTab !== 'invitations' && activeView === 'chat'" @click="middleCollapsed = false"
                        class="w-full flex items-center gap-3 py-2.5 rounded-xl text-sm font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-all duration-200 text-left"
                        :class="navCollapsed ? 'justify-center px-0' : 'px-3'"
                        title="Expand Chat List" x-cloak>
                    <span class="material-symbols-outlined text-[20px]">keyboard_double_arrow_right</span>
                    <span x-show="!navCollapsed" x-cloak>Expand Chat List</span>
                </button>
            </nav>
        </div>

        <!-- Disconnect / Logout Action -->
        <div class="space-y-3">
            <button @click="openSearchModal()" 
                    class="w-full bg-[#10b981] hover:bg-[#059669] text-white font-bold py-2.5 rounded-xl text-sm transition-all duration-200 shadow-md shadow-emerald-500/10 flex justify-center items-center gap-1.5 hover:scale-[1.01] active:scale-[0.99]"
                    :class="navCollapsed ? 'px-0 justify-center' : 'px-4'">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span x-show="!navCollapsed" x-cloak>New Chat</span>
            </button>
            <form action="{{ route('logout') }}" method="POST" class="block w-full">
                @csrf
                <button type="submit" 
                        class="w-full bg-red-50 hover:bg-red-100 text-red-600 border border-red-200/30 font-semibold py-2 rounded-xl text-xs flex justify-center items-center gap-1.5 transition-colors"
                        :class="navCollapsed ? 'px-0 py-2.5 justify-center' : 'py-2 px-3'">
                    <span class="material-symbols-outlined text-[14px]">logout</span>
                    <span x-show="!navCollapsed" x-cloak>Disconnect Session</span>
                </button>
            </form>
            
            <!-- Collapse / Expand Toggle for Far-Left Sidebar -->
            <button @click="navCollapsed = !navCollapsed" 
                    class="w-full flex items-center gap-3 py-2 rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-200/50 transition-colors"
                    :class="navCollapsed ? 'justify-center px-0' : 'px-3'">
                <span class="material-symbols-outlined text-[18px]" x-text="navCollapsed ? 'menu' : 'menu_open'"></span>
                <span x-show="!navCollapsed" x-cloak>Collapse Navigation</span>
            </button>
        </div>
    </aside>

    <!-- COLUMN 2: MIDDLE CONVERSATION LIST PANEL -->
    <!-- Only visible when NOT on the settings tab and in chat view -->
    <aside class="w-80 bg-white border-r border-gray-200 flex flex-col shrink-0 transition-all duration-300" 
           x-show="activeTab !== 'settings' && activeTab !== 'invitations' && activeView === 'chat' && !middleCollapsed" x-transition:enter="transition-all duration-300" x-cloak>
        
        <!-- Header Section -->
        <div class="p-4 pb-2">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <button @click="middleCollapsed = true" 
                            class="text-gray-400 hover:bg-gray-100 hover:text-gray-600 p-1 rounded-lg transition-colors flex items-center justify-center"
                            title="Collapse Chat List">
                        <span class="material-symbols-outlined text-[18px]">keyboard_double_arrow_left</span>
                    </button>
                    <h2 class="font-outfit text-xl font-black text-gray-900 capitalize" x-text="activeTab"></h2>
                </div>
                
                <!-- Action buttons to open Create Group / Create Channel forms -->
                <template x-if="activeTab === 'groups'">
                    <button @click="openCreateGroupForm()" 
                            class="text-emerald-600 hover:text-emerald-800 p-1.5 rounded-lg hover:bg-emerald-50 transition-colors flex items-center gap-1 font-bold text-xs"
                            title="Create New Group">
                        <span class="material-symbols-outlined text-[18px]">group_add</span>
                        <span>New Group</span>
                    </button>
                </template>
                <template x-if="activeTab === 'channels'">
                    <button @click="openCreateChannelForm()" 
                            class="text-emerald-600 hover:text-emerald-800 p-1.5 rounded-lg hover:bg-emerald-50 transition-colors flex items-center gap-1 font-bold text-xs"
                            title="Create New Channel">
                        <span class="material-symbols-outlined text-[18px]">campaign</span>
                        <span>New Channel</span>
                    </button>
                </template>
            </div>
            
            <!-- Chat / Group Filtering Search Bar -->
            <div class="mt-3 relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]">search</span>
                <input type="text" x-model="searchQuery" placeholder="Search or start a new chat"
                       class="w-full bg-[#f0f2f5] border-none rounded-xl pl-9 pr-3 py-2 text-xs focus:ring-1 focus:ring-emerald-500">
            </div>
        </div>

        <!-- Chat Scroll List -->
        <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
            <!-- Loading Indicator -->
            <div x-show="loadingChats" class="p-8 text-center text-xs font-semibold text-gray-400">
                <span class="animate-spin inline-block h-4 w-4 border-2 border-emerald-500 border-t-transparent rounded-full mr-2"></span>
                <span>Loading active threads...</span>
            </div>

            <!-- Empty State -->
            <div x-show="!loadingChats && filteredChats.length === 0" class="p-8 text-center text-xs font-semibold text-gray-400">
                <span>No active <span x-text="activeTab"></span> threads.</span>
                <button @click="openSearchModal()" class="block text-emerald-600 font-bold hover:underline mx-auto mt-2">Start a conversation</button>
            </div>

            <!-- Conversation Items -->
            <template x-for="chat in filteredChats" :key="chat.id">
                <div @click="selectChat(chat)" 
                     class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 transition-all duration-150 border-l-4"
                     :class="activeChat && activeChat.id === chat.id ? 'bg-gray-150 border-emerald-500 bg-[#f0f2f5]' : 'border-transparent'">
                    
                    <!-- Avatar -->
                    <div class="relative shrink-0">
                        <img :src="chat.icon || 'https://api.dicebear.com/7.x/pixel-art/svg?seed=' + chat.name" 
                             alt="Avatar" class="w-11 h-11 rounded-full object-cover border border-gray-100">
                        <span x-show="chat.other_user && chat.other_user.status === 'online'" 
                              class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                    </div>

                    <!-- Details -->
                    <div class="min-w-0 flex-1">
                        <div class="flex justify-between items-baseline mb-0.5">
                            <h4 class="font-semibold text-gray-900 truncate text-sm" x-text="chat.name"></h4>
                            <span class="text-[10px] text-gray-400 font-medium shrink-0" 
                                  x-text="chat.latest_message ? chat.latest_message.time_ago : ''"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-gray-500 truncate" x-text="chat.latest_message ? chat.latest_message.body : 'No messages yet.'"></p>
                            
                            <!-- Unread Badge -->
                            <span x-show="chat.unread_count > 0" 
                                  class="bg-emerald-500 text-white font-extrabold text-[10px] w-5 h-5 rounded-full flex items-center justify-center shrink-0 ml-1.5"
                                  x-text="chat.unread_count"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </aside>

    <!-- COLUMN 3: RIGHT PANEL (ACTIVE CHAT / SETTINGS) -->
    <main class="flex-1 bg-[#efeae2] flex flex-col relative overflow-hidden">
        
        <!-- Welcome Splash Screen (Visible if no chat/settings selected) -->
        <div x-show="!activeChat && activeTab !== 'settings' && activeTab !== 'invitations' && activeView === 'chat'" 
             class="absolute inset-0 bg-[#f8fafc] flex flex-col items-center justify-center p-8 z-10">
            <div class="max-w-[420px] text-center flex flex-col items-center">
                <div class="w-20 h-20 bg-emerald-50 rounded-2xl flex items-center justify-center shadow-inner border border-emerald-100 mb-6 text-emerald-600">
                    <span class="material-symbols-outlined text-[44px]">forum</span>
                </div>
                <h2 class="font-outfit text-2xl font-black text-gray-800 mb-2">ChitChat Web Workspace</h2>
                <p class="text-sm text-gray-400 leading-relaxed">Select a conversation from the list or start a new direct chat with active teammates. Auto-syncing updates ticks and typing status instantly.</p>
                <div class="flex items-center gap-1.5 mt-8 text-xs font-bold text-gray-400 border-t border-gray-200 pt-5 w-full justify-center">
                    <span class="material-symbols-outlined text-[16px]">verified_user</span>
                    <span>Encrypted workspace connection handshakes</span>
                </div>
            </div>
        </div>

        <!-- ------------------------------------------------------------- -->
        <!-- ACTIVE CONVERSATION PANE -->
        <!-- ------------------------------------------------------------- -->
        <div x-show="activeChat && activeTab !== 'settings' && activeTab !== 'invitations' && activeView === 'chat'" class="w-full h-full flex flex-col" x-cloak>
            
            <!-- ======================================================== -->
            <!-- DIRECT / GROUP CHAT HEADER (shown for direct & group) -->
            <!-- ======================================================== -->
            <template x-if="activeChat && activeChat.type !== 'channel'">
                <header class="bg-[#f0f2f5] border-b border-gray-300/40 px-6 py-2.5 flex justify-between items-center z-10 shrink-0 shadow-sm">
                    <div class="flex items-center gap-3.5">
                        <img :src="activeChat.icon || 'https://api.dicebear.com/7.x/pixel-art/svg?seed=' + activeChat.name" 
                             alt="Avatar" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                        <div>
                            <h3 class="font-outfit font-extrabold text-gray-900 leading-tight text-sm" x-text="activeChat.name"></h3>
                            <span class="text-[10px] font-bold"
                                  :class="activeChat.type === 'direct' && activeChat.other_user && activeChat.other_user.status === 'online' ? 'text-emerald-600' : (activeChat.type === 'group' ? 'text-gray-500' : 'text-gray-400')"
                                  x-text="activeChat.type === 'direct' ? (activeChat.other_user && activeChat.other_user.status === 'online' ? 'Online' : 'Offline') : 'Group Chat'"></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button x-show="middleCollapsed" @click="middleCollapsed = false"
                                class="text-emerald-600 hover:bg-emerald-50 p-2 rounded-xl transition-all mr-2 flex items-center justify-center"
                                title="Expand Chat List" x-cloak>
                            <span class="material-symbols-outlined text-[20px]">keyboard_double_arrow_right</span>
                        </button>
                        <button class="text-gray-600 hover:bg-gray-200/50 p-2 rounded-xl transition-all"><span class="material-symbols-outlined text-[20px]">videocam</span></button>
                        <button class="text-gray-600 hover:bg-gray-200/50 p-2 rounded-xl transition-all"><span class="material-symbols-outlined text-[20px]">call</span></button>
                        <button class="text-gray-600 hover:bg-gray-200/50 p-2 rounded-xl transition-all"><span class="material-symbols-outlined text-[20px]">search</span></button>
                    </div>
                </header>
            </template>

            <!-- ======================================================== -->
            <!-- CHANNEL HEADER (shown only for channels) -->
            <!-- ======================================================== -->
            <template x-if="activeChat && activeChat.type === 'channel'">
                <header class="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center z-10 shrink-0 shadow-sm">
                    <div class="flex items-center gap-3.5">
                        <div class="relative">
                            <img :src="activeChat.icon || 'https://api.dicebear.com/7.x/pixel-art/svg?seed=' + activeChat.name" 
                                 alt="Channel Icon" class="w-10 h-10 rounded-xl object-cover border border-emerald-100">
                            <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full flex items-center justify-center">
                                <span class="material-symbols-outlined text-white" style="font-size:10px">campaign</span>
                            </span>
                        </div>
                        <div>
                            <h3 class="font-outfit font-extrabold text-gray-900 leading-tight text-sm flex items-center gap-2">
                                <span x-text="activeChat.name"></span>
                                <span class="bg-emerald-50 text-emerald-700 text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full border border-emerald-200">Channel</span>
                            </h3>
                            <span class="text-[10px] text-gray-400 font-semibold">Public broadcast channel</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button x-show="middleCollapsed" @click="middleCollapsed = false"
                                class="text-emerald-600 hover:bg-emerald-50 p-2 rounded-xl transition-all mr-2 flex items-center justify-center"
                                title="Expand Channel List" x-cloak>
                            <span class="material-symbols-outlined text-[20px]">keyboard_double_arrow_right</span>
                        </button>
                        <button class="text-gray-600 hover:bg-gray-200/50 p-2 rounded-xl transition-all" title="Search in channel"><span class="material-symbols-outlined text-[20px]">search</span></button>
                        <button class="text-gray-600 hover:bg-gray-200/50 p-2 rounded-xl transition-all" title="Channel info"><span class="material-symbols-outlined text-[20px]">info</span></button>
                    </div>
                </header>
            </template>

            <!-- ======================================================== -->
            <!-- PINNED MESSAGE BANNER -->
            <!-- ======================================================== -->
            <template x-if="messages.some(m => m.is_pinned)">
                <div class="bg-amber-50 border-b border-amber-200/80 px-5 py-2.5 flex items-center gap-3 z-10 shrink-0 shadow-sm animate-fade-in select-none"
                     @click="scrollToMessage(messages.filter(m => m.is_pinned).at(-1)?.id)">
                    <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center shrink-0 border border-amber-300/60">
                        <span class="material-symbols-outlined text-[16px] text-amber-600">keep</span>
                    </div>
                    <div class="flex-1 min-w-0 cursor-pointer hover:opacity-75 transition-opacity">
                        <p class="text-[9px] font-extrabold text-amber-700 uppercase tracking-wider mb-0.5">Pinned Message</p>
                        <p class="text-xs text-amber-900 truncate font-medium"
                           x-text="messages.filter(m => m.is_pinned).at(-1)?.body || 'ðŸ“Ž Attachment'"></p>
                    </div>
                    <button @click.stop="pinMessage(messages.filter(m => m.is_pinned).at(-1))"
                            class="text-amber-500 hover:text-amber-700 p-1 rounded-lg hover:bg-amber-100 transition-colors shrink-0"
                            title="Unpin message">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>
            </template>

            <!-- Messages Stream Area -->
            <!-- Layout background has the classic WhatsApp doodle wallpaper (simulated via CSS pattern) -->
            <div id="messages-container" 
                 class="flex-1 overflow-y-auto p-6 space-y-3.5 relative"
                 :class="activeChat && activeChat.type === 'channel' ? 'bg-white' : 'bg-[#efeae2]'"
                 :style="activeChat && activeChat.type !== 'channel' ? 'background-image: radial-gradient(#dfdcd6 1px, transparent 1px); background-size: 16px 16px;' : ''">
                
                <!-- Loading Messages Spinner -->
                <div x-show="loadingMessages" class="absolute inset-0 bg-white/40 flex items-center justify-center">
                    <span class="animate-spin inline-block h-6 w-6 border-3 border-emerald-500 border-t-transparent rounded-full"></span>
                </div>

                <!-- Channel empty state -->
                <div x-show="!loadingMessages && messages.length === 0 && activeChat && activeChat.type === 'channel'"
                     class="flex flex-col items-center justify-center h-full text-center py-16">
                    <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-500 mb-4 border border-emerald-100">
                        <span class="material-symbols-outlined text-[32px]">campaign</span>
                    </div>
                    <h4 class="font-outfit font-black text-gray-800 text-lg mb-1" x-text="activeChat ? activeChat.name : ''"></h4>
                    <p class="text-xs text-gray-400 max-w-[260px] leading-relaxed">This is the beginning of the channel. Posts will appear here.</p>
                </div>

                <!-- Messages List -->
                <template x-for="message in messages" :key="message.id">
                    <div class="flex flex-col animate-fade-in" 
                         :class="activeChat && activeChat.type === 'channel' ? 'items-start' : (message.sender_id === currentUser.id ? 'items-end' : 'items-start')">
                        
                        <!-- Sender name label (shown in channels and groups for messages from others) -->
                        <template x-if="activeChat && (activeChat.type === 'channel' || activeChat.type === 'group') && message.sender_name && !message.is_ephemeral">
                            <span class="text-[10px] font-bold mb-0.5 px-1"
                                  :class="message.sender_id === currentUser.id ? 'text-emerald-600' : 'text-blue-600'"
                                  x-text="message.sender_id === currentUser.id ? 'You' : message.sender_name"></span>
                        </template>

                        <!-- Chat Bubble -->
                        <div :id="'msg-bubble-' + message.id"
                             @contextmenu.prevent="openContextMenu($event, message)"
                             class="group max-w-[75%] rounded-2xl px-3.5 py-2 text-sm shadow-sm relative transition-all duration-300"
                             :class="activeChat && activeChat.type === 'channel'
                                ? 'bg-white border border-gray-100 text-gray-900 rounded-tl-none w-full max-w-2xl'
                                : (message.is_ephemeral
                                    ? (message.sender_id === currentUser.id ? 'bg-sky-50 border border-sky-200/60 text-sky-950 rounded-tr-none' : 'bg-gradient-to-br from-indigo-50 to-sky-50 border border-indigo-200/60 text-indigo-950 rounded-tl-none')
                                    : (message.sender_id === currentUser.id ? 'bg-[#d9fdd3] text-gray-900 rounded-tr-none' : 'bg-white text-gray-900 rounded-tl-none')
                                  )">
                            
                            <!-- Accessible dropdown toggle button on hover (exclude ephemeral) -->
                            <template x-if="!message.is_ephemeral">
                                <button @click.stop="openContextMenu($event, message)" 
                                        class="absolute top-1.5 right-1.5 opacity-0 group-hover:opacity-100 transition-opacity bg-white/90 hover:bg-white text-gray-500 hover:text-gray-800 rounded-full w-5 h-5 flex items-center justify-center shadow z-10 focus:outline-none">
                                    <span class="material-symbols-outlined text-[14px]">keyboard_arrow_down</span>
                                </button>
                            </template>

                            <!-- Pinned indicator badge -->
                            <template x-if="message.is_pinned">
                                <span class="absolute top-1.5 left-1.5 text-amber-500 opacity-80" title="Pinned message">
                                    <span class="material-symbols-outlined text-[14px]">keep</span>
                                </span>
                            </template>

                            <!-- Ephemeral banners -->
                            <template x-if="message.is_ephemeral && message.sender_id === currentUser.id">
                                <div class="flex items-center gap-1 text-[10px] font-bold text-sky-600 mb-1 border-b border-sky-100/50 pb-1">
                                    <span class="material-symbols-outlined text-[13px]">visibility_off</span>
                                    <span>Secret Gossip Request (Only visible to you)</span>
                                </div>
                            </template>
                            <template x-if="message.is_ephemeral && message.sender_id !== currentUser.id">
                                <div class="flex items-center gap-1 text-[10px] font-bold text-indigo-600 mb-1 border-b border-indigo-150/50 pb-1">
                                    <span class="material-symbols-outlined text-[13px]">lock</span>
                                    <span>ðŸ¤« Chugli Bot Secret Gossip (Only visible to you)</span>
                                </div>
                            </template>

                            <!-- Threaded Quote block -->
                            <template x-if="message.parent">
                                <div @click="scrollToMessage(message.parent.id)"
                                     class="border-l-4 border-emerald-500 bg-black/5 hover:bg-black/10 transition-colors rounded-r-lg px-2.5 py-1.5 mb-2 text-xs text-gray-700 flex flex-col gap-0.5 cursor-pointer select-none">
                                    <span class="font-bold text-emerald-700" x-text="message.parent.sender_name"></span>
                                    <span class="truncate text-gray-650" x-text="message.parent.type === 'text' ? message.parent.body : ('ðŸ“Ž ' + (message.parent.type.toUpperCase()))"></span>
                                </div>
                            </template>
                            
                             <!-- Media render templates -->
                             <!-- Image Type -->
                             <template x-if="message.type === 'image'">
                                 <div class="mb-2 rounded-xl overflow-hidden border border-gray-150 bg-gray-50/50 max-w-sm cursor-pointer hover:opacity-95 transition-opacity"
                                      @click="window.open(message.body, '_blank')">
                                     <img :src="message.body" @load="scrollMessagesDown()" class="w-full max-h-72 object-cover">
                                 </div>
                             </template>

                             <!-- Video Type -->
                             <template x-if="message.type === 'video'">
                                 <div class="mb-2 rounded-xl overflow-hidden border border-gray-150 bg-gray-50/50 max-w-sm">
                                     <video :src="message.body" @loadeddata="scrollMessagesDown()" controls class="w-full max-h-72 object-cover"></video>
                                 </div>
                             </template>

                             <!-- Document / PDF Type -->
                             <template x-if="message.type === 'document'">
                                 <div class="mb-2 rounded-xl bg-gray-50 border border-gray-250 p-3 flex items-center gap-3 max-w-sm shadow-sm select-none">
                                     <div class="w-10 h-10 bg-red-50 text-red-650 rounded-lg flex items-center justify-center shrink-0 border border-red-200">
                                         <span class="material-symbols-outlined text-[24px]">description</span>
                                     </div>
                                     <div class="flex-1 min-w-0">
                                         <p class="text-xs font-bold text-gray-800 truncate" x-text="message.caption || 'Document File'"></p>
                                         <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">PDF / Attachment</p>
                                     </div>
                                     <a :href="message.body" target="_blank" download class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-emerald-600 hover:border-emerald-250 shadow-sm shrink-0 transition-all">
                                         <span class="material-symbols-outlined text-[16px]">download</span>
                                     </a>
                                 </div>
                             </template>

                             <!-- Audio / Music Type -->
                             <template x-if="message.type === 'audio'">
                                 <div class="mb-2 rounded-xl bg-gray-50 border border-gray-250 p-3.5 flex flex-col gap-2.5 max-w-sm shadow-sm select-none">
                                     <div class="flex items-center gap-3">
                                         <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center shrink-0 border border-emerald-150">
                                             <span class="material-symbols-outlined text-[24px]">music_note</span>
                                         </div>
                                         <div class="flex-1 min-w-0">
                                             <p class="text-xs font-bold text-gray-800 truncate" x-text="message.caption || 'Audio Track'"></p>
                                             <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Voice Recording / Music</p>
                                         </div>
                                     </div>
                                     <audio :src="message.body" @loadeddata="scrollMessagesDown()" controls class="w-full h-8 mt-1 focus:outline-none"></audio>
                                 </div>
                             </template>

                             <!-- Body text - renders @mention chips via x-html (if there is text) -->
                             <p x-show="message.body && message.type !== 'image' && message.type !== 'video' && message.type !== 'document' && message.type !== 'audio'" 
                                class="leading-relaxed whitespace-pre-wrap pr-10 mention-body" 
                                x-html="renderMessageBody(message)"></p>
                                
                             <!-- Captions for image/video media messages (if caption is present and different from filename) -->
                             <p x-show="message.caption && (message.type === 'image' || message.type === 'video') && !message.caption.match(/\.(png|jpe?g|gif|mp4|webm|pdf)$/i)" 
                                class="text-xs text-gray-650 mt-1 leading-normal italic" 
                                x-text="message.caption"></p>
                            
                            <!-- Timestamp & Status Ticks inside bubble -->
                            <div class="absolute bottom-1 right-2 flex items-center gap-1">
                                <span class="text-[9px] text-gray-400 font-medium" 
                                      x-text="formatMessageTime(message.created_at)"></span>
                                
                                <!-- Outgoing Message State ticks (only for DM/group, not channel, not ephemeral) -->
                                <template x-if="message.sender_id === currentUser.id && activeChat && activeChat.type !== 'channel' && !message.is_ephemeral">
                                    <span class="material-symbols-outlined text-[14px] flex"
                                          :class="message.is_read ? 'text-[#34b7f1]' : 'text-gray-400'">
                                        <span x-text="message.is_read ? 'done_all' : 'done'"></span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Reactions badge row under bubble -->
                        <template x-if="message.reactions && message.reactions.length > 0">
                            <div class="flex items-center mt-0.5 select-none"
                                 :class="message.sender_id === currentUser.id ? 'self-end mr-3' : 'self-start ml-3'">
                                <div class="bg-white hover:bg-gray-50 border border-gray-150 rounded-full py-0.5 px-2 flex items-center gap-1 text-[10px] shadow-sm cursor-pointer transition-colors"
                                     @click="reactMessage(message, message.reactions[0].emoji)">
                                    <template x-for="react in getGroupedReactions(message.reactions)" :key="react.emoji">
                                        <span class="flex items-center gap-0.5">
                                            <span x-html="react.emoji"></span>
                                            <span class="text-[9px] text-gray-500 font-bold" x-show="react.count > 1" x-text="react.count"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- typing indicator bubble -->
                <div x-show="otherUserTyping" class="flex items-start" x-cloak>
                    <div class="bg-white text-gray-700 max-w-[70%] rounded-2xl rounded-tl-none px-4 py-2 text-xs shadow-sm flex items-center gap-1.5">
                        <span class="animate-bounce inline-block w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                        <span class="animate-bounce inline-block w-1.5 h-1.5 bg-gray-400 rounded-full" style="animation-delay: 0.2s"></span>
                        <span class="animate-bounce inline-block w-1.5 h-1.5 bg-gray-400 rounded-full" style="animation-delay: 0.4s"></span>
                        <span class="italic text-gray-500 font-medium ml-1.5"><span x-text="activeChat ? activeChat.name : 'Someone'"></span> is typing...</span>
                    </div>
                </div>
            </div>

            <!-- Footer Message Input bar -->
            <!-- ======================================================== -->
            <!-- JOIN CHANNEL BANNER (shown when user is NOT a member of a public channel) -->
            <!-- ======================================================== -->
            <template x-if="activeChat && activeChat.type === 'channel' && activeChat.is_member === false">
                <div class="bg-white border-t border-emerald-100 px-6 py-4 flex items-center justify-between gap-4 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-emerald-50 rounded-full flex items-center justify-center border border-emerald-100">
                            <span class="material-symbols-outlined text-emerald-500 text-[18px]">campaign</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-800">You're viewing this channel</p>
                            <p class="text-[11px] text-gray-400">Join to post and receive updates</p>
                        </div>
                    </div>
                    <button @click="joinChannel(activeChat)"
                            class="bg-[#10b981] hover:bg-[#059669] text-white font-bold px-5 py-2.5 rounded-xl text-sm flex items-center gap-2 transition-all shadow-md shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span>Join Channel</span>
                    </button>
                </div>
            </template>

            <!-- ======================================================== -->
            <!-- @MENTION DROPDOWN (floating above input, groups & channels) -->
            <!-- ======================================================== -->
            <div x-show="mentionState.active && mentionState.filtered.length > 0"
                 x-cloak
                 class="absolute bottom-[72px] left-4 right-4 z-50 bg-white border border-gray-200 rounded-2xl shadow-xl overflow-hidden max-h-52 overflow-y-auto">
                <!-- Dropdown Header -->
                <div class="px-3 py-2 border-b border-gray-100 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[14px] text-emerald-500">alternate_email</span>
                    <span class="text-[10px] font-extrabold text-gray-500 uppercase tracking-wider">Mention a member</span>
                    <span class="ml-auto text-[9px] text-gray-400 font-semibold">â†‘â†“ navigate Â· Enter to select Â· Esc to close</span>
                </div>
                <!-- Member List -->
                <template x-for="(member, idx) in mentionState.filtered" :key="member.id">
                    <div @click="selectMention(member)"
                         @mouseenter="mentionState.selectedIdx = idx"
                         class="flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors"
                         :class="idx === mentionState.selectedIdx ? 'bg-emerald-50 border-l-2 border-emerald-500' : 'hover:bg-gray-50 border-l-2 border-transparent'">
                        <!-- Avatar or initial -->
                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-xs font-black text-white bg-gradient-to-br from-emerald-400 to-teal-600 shadow-sm">
                            <span x-text="member.name.charAt(0).toUpperCase()"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-gray-900 truncate" x-text="member.name"></p>
                            <p class="text-[10px] text-gray-400 truncate" x-text="member.email || ''"></p>
                        </div>
                        <span class="text-[10px] font-bold text-emerald-500 shrink-0">@<span x-text="member.name.split(' ')[0]"></span></span>
                    </div>
                </template>
                <!-- Empty state -->
                <div x-show="mentionState.filtered.length === 0" class="px-4 py-4 text-center">
                    <p class="text-xs text-gray-400 font-semibold">No members match "@<span x-text="mentionState.query"></span>"</p>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- BANNED CHAT / BANNED USER BANNER -->
            <!-- ======================================================== -->
            <div x-show="activeChat && (activeChat.status === 'banned' || activeChat.other_user_banned)"
                 class="bg-red-50 border-t border-red-250 px-6 py-4 flex items-center gap-4 shrink-0 shadow-inner z-10" x-cloak>
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center border border-red-250 text-red-650 shrink-0 shadow-sm">
                    <span class="material-symbols-outlined text-[22px]">gavel</span>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-red-900" x-text="(activeChat && activeChat.other_user_banned) ? 'This account has been suspended' : 'This conversation has been banned'"></p>
                    <p class="text-xs text-red-700 mt-0.5 leading-normal" 
                       x-text="(activeChat && activeChat.other_user_banned) 
                           ? 'This user was suspended by the system administrator for violating workspace policies. Messaging is disabled.' 
                           : 'This community has been suspended by the administrator. Message sending and group operations are disabled.'"></p>
                </div>
            </div>
                       <!-- Normal Message Footer (for members of all active/unbanned conversation types) -->
            <template x-if="activeChat && (activeChat.type !== 'channel' || activeChat.is_member !== false) && activeChat.status !== 'banned' && !activeChat.other_user_banned">
                <div class="flex flex-col shrink-0">
                    <!-- Attachment Preview Bar -->
                    <div x-show="selectedFile" class="bg-white border-t border-gray-200 px-6 py-4 flex items-center justify-between gap-4 animate-fade-in shrink-0" x-cloak>
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Image/Video Thumbnail Preview -->
                            <template x-if="selectedFilePreview">
                                <div class="w-12 h-12 rounded-xl overflow-hidden border border-gray-200 shrink-0 shadow-sm relative bg-gray-50 flex items-center justify-center">
                                    <template x-if="selectedFileType === 'image'">
                                        <img :src="selectedFilePreview" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="selectedFileType === 'video'">
                                        <video :src="selectedFilePreview" class="w-full h-full object-cover"></video>
                                    </template>
                                </div>
                            </template>
                            <!-- PDF / Document Icon -->
                            <template x-if="!selectedFilePreview">
                                <div class="w-12 h-12 bg-red-50 text-red-650 rounded-xl flex items-center justify-center shrink-0 border border-red-200 shadow-sm">
                                    <span class="material-symbols-outlined text-[26px]">description</span>
                                </div>
                            </template>
                            
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-gray-800 truncate" x-text="selectedFileName"></p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider" x-text="selectedFileType + ' â€¢ ' + formatFileSize(selectedFileSize)"></p>
                            </div>
                        </div>
                        <button @click="clearSelectedFile()" class="text-gray-400 hover:text-red-500 p-1.5 rounded-lg hover:bg-gray-150/50 transition-colors shrink-0 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Reply Preview Bar -->
                    <div x-show="replyToMessage" class="bg-emerald-50 border-t border-emerald-150 px-6 py-3 flex items-center justify-between gap-4 animate-fade-in shrink-0" x-cloak>
                        <div class="flex items-center gap-3 min-w-0 border-l-4 border-emerald-500 pl-3">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-emerald-800">Replying to <span x-text="replyToMessage ? replyToMessage.sender_name : ''"></span></p>
                                <p class="text-xs text-emerald-600 truncate" x-text="replyToMessage ? (replyToMessage.type === 'text' ? replyToMessage.body : ('ðŸ“Ž ' + (replyToMessage.type.toUpperCase()))) : ''"></p>
                            </div>
                        </div>
                        <button @click="replyToMessage = null" class="text-emerald-500 hover:text-emerald-700 p-1.5 rounded-lg hover:bg-emerald-100/50 transition-colors shrink-0 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>

                    <footer class="bg-[#f0f2f5] px-4 py-3 flex items-end gap-2.5 z-10 shrink-0">
                        <!-- Attachments icon -->
                        <button @click="$refs.chatFileInput.click()" class="text-gray-500 hover:text-gray-700 p-1.5 rounded-lg hover:bg-gray-200/50 shrink-0 mb-0.5" title="Attach file, video or PDF">
                            <span class="material-symbols-outlined text-[24px]">attach_file</span>
                        </button>
                        <input type="file" x-ref="chatFileInput" class="hidden" @change="handleFileSelect($event)" accept="image/*,video/*,application/pdf">

                        <!-- Text Area Input box -->
                        <div class="flex-1 bg-white border border-gray-300/40 rounded-xl px-3 py-1 flex items-center relative">
                            <!-- Emojis Picker Container -->
                            <div class="relative shrink-0 flex items-center">
                                <button @click.stop="showEmojiPicker = !showEmojiPicker" class="text-gray-450 hover:text-gray-650 shrink-0 flex items-center justify-center p-1 hover:bg-gray-100 rounded-lg" title="Add Emojis">
                                    <span class="material-symbols-outlined text-[22px]">mood</span>
                                </button>
                                <!-- Emoji Picker Popover -->
                                <div x-show="showEmojiPicker" 
                                     @click.away="showEmojiPicker = false"
                                     class="absolute bottom-12 left-0 bg-white border border-gray-200 rounded-2xl p-4 shadow-xl grid grid-cols-7 gap-2.5 z-50 w-64 select-none animate-fade-in"
                                     x-cloak>
                                    <template x-for="emoji in ['&#128514;', '&#10084;&#65039;', '&#128077;', '&#128512;', '&#128591;', '&#127881;', '&#128293;', '&#128079;', '&#127883;', '&#128526;', '&#128557;', '&#129300;', '&#128640;', '&#128064;', '&#10024;', '&#128175;', '&#127775;', '&#128161;', '&#127752;', '&#129321;', '&#127800;']" :key="emoji">
                                        <button @click="appendEmoji(emoji)" class="text-xl hover:scale-125 transition-transform flex items-center justify-center p-1 rounded-lg hover:bg-gray-50 focus:outline-none">
                                            <span x-html="emoji"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            
                            <textarea x-model="newMessage"
                                      data-mention-input
                                      @keydown="handleMentionKeydown($event)"
                                      @keydown.enter.prevent="mentionState.active && mentionState.filtered.length ? selectMention(mentionState.filtered[mentionState.selectedIdx]) : sendMessage()"
                                      @input="handleInput($event)"
                                      rows="1"
                                      :placeholder="activeChat && activeChat.type === 'channel' ? 'Post to channel...' : (activeChat && activeChat.type === 'group' ? 'Message group... (@ to mention)' : 'Type a message')"
                                      class="w-full bg-transparent border-none focus:ring-0 py-1.5 px-2 text-sm text-gray-800 placeholder:text-gray-400 resize-none max-h-24 leading-normal focus:outline-none"></textarea>
                            
                            <!-- AI Response Suggestion Button -->
                            <button @click="draftAIReply()" 
                                    :disabled="draftingAI || !messages.length"
                                    class="text-teal-600 hover:text-teal-800 disabled:opacity-50 shrink-0 p-1.5 rounded-lg hover:bg-teal-50/50 transition-all flex items-center justify-center"
                                    title="Draft AI Reply (âœ¨)">
                                <span class="material-symbols-outlined text-[20px] animate-spin" x-show="draftingAI">sync</span>
                                <span class="material-symbols-outlined text-[20px]" x-show="!draftingAI">auto_awesome</span>
                            </button>
                        </div>

                        <!-- Send Button -->
                        <button @click="sendMessage()" :disabled="!newMessage.trim() && !selectedFile"
                                class="bg-[#10b981] hover:bg-[#059669] disabled:opacity-50 text-white w-10 h-10 rounded-full flex items-center justify-center shrink-0 transition-all shadow-md shadow-emerald-500/10">
                            <span class="material-symbols-outlined text-[20px] ml-0.5">send</span>
                        </button>
                    </footer>
                </div>
            </template>
        </div>

        <!-- ------------------------------------------------------------- -->
        <!-- CREATE NEW GROUP PANE -->
        <!-- ------------------------------------------------------------- -->
        <div x-show="activeView === 'create_group' && activeTab !== 'settings'" class="w-full h-full bg-[#f8fafc] flex flex-col justify-between animate-fade-in" x-cloak>
            <!-- Header -->
            <header class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center shrink-0">
                <div class="flex items-center gap-3">
                    <button @click="activeView = 'chat'" class="text-gray-600 hover:text-emerald-600 transition-colors">
                        <span class="material-symbols-outlined text-[24px]">arrow_back</span>
                    </button>
                    <h3 class="font-outfit font-extrabold text-gray-900 text-lg leading-tight">Create New Group</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button class="text-gray-600 hover:bg-gray-100 p-2 rounded-xl transition-all"><span class="material-symbols-outlined text-[20px]">search</span></button>
                    <button class="text-gray-600 hover:bg-gray-100 p-2 rounded-xl transition-all"><span class="material-symbols-outlined text-[20px]">more_vert</span></button>
                </div>
            </header>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto p-8 space-y-6 max-w-3xl w-full mx-auto">
                <!-- Group Info Card -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm flex gap-6 items-start">
                    <!-- Icon Uploader -->
                    <div class="flex flex-col items-center gap-2 shrink-0">
                        <div class="w-24 h-24 rounded-full bg-gray-50 hover:bg-gray-200 cursor-pointer flex items-center justify-center border border-gray-300 relative overflow-hidden group"
                             @click="$refs.groupIconFile.click()">
                            <template x-if="groupForm.icon_preview">
                                <img :src="groupForm.icon_preview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!groupForm.icon_preview">
                                <div class="flex flex-col items-center text-gray-400">
                                    <span class="material-symbols-outlined text-[36px]">group</span>
                                </div>
                            </template>
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity text-white text-xs font-semibold">
                                Change
                            </div>
                        </div>
                        <span class="text-xs font-bold text-gray-500">Add Group Icon</span>
                        <input type="file" x-ref="groupIconFile" @change="handleGroupIconUpload" class="hidden" accept="image/*">
                    </div>

                    <!-- Inputs -->
                    <div class="flex-1 space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Group Name</label>
                            <input type="text" x-model="groupForm.name" placeholder="e.g., Marketing Team Sync"
                                   class="w-full text-xs font-semibold border border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-gray-50/50 py-2.5 px-3 focus:outline-none">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Group Description (Optional)</label>
                            <textarea x-model="groupForm.description" placeholder="What is this group about?" rows="3"
                                      class="w-full text-xs font-medium border border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-gray-50/50 py-2.5 px-3 focus:outline-none resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Group Visibility -->
                <div class="space-y-3">
                    <h4 class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Group Visibility</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Public Group Option -->
                        <div @click="groupForm.visibility = 'public'"
                             class="bg-white border-2 rounded-2xl p-4 flex items-center gap-4 cursor-pointer transition-all select-none"
                             :class="groupForm.visibility === 'public' ? 'border-emerald-500 bg-emerald-50/10' : 'border-gray-200 hover:border-gray-300'">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                 :class="groupForm.visibility === 'public' ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-400'">
                                <span class="material-symbols-outlined">public</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h5 class="text-xs font-bold text-gray-900">Public Group</h5>
                                <p class="text-[10px] text-gray-500 leading-normal">Anyone can find and join this group.</p>
                            </div>
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                                 :class="groupForm.visibility === 'public' ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-gray-300'">
                                <div x-show="groupForm.visibility === 'public'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                            </div>
                        </div>

                        <!-- Private Group Option -->
                        <div @click="groupForm.visibility = 'private'"
                             class="bg-white border-2 rounded-2xl p-4 flex items-center gap-4 cursor-pointer transition-all select-none"
                             :class="groupForm.visibility === 'private' ? 'border-emerald-500 bg-emerald-50/10' : 'border-gray-200 hover:border-gray-300'">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                 :class="groupForm.visibility === 'private' ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-400'">
                                <span class="material-symbols-outlined">lock</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h5 class="text-xs font-bold text-gray-900">Private Group</h5>
                                <p class="text-[10px] text-gray-500 leading-normal">Members join by invitation only.</p>
                            </div>
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                                 :class="groupForm.visibility === 'private' ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-gray-300'">
                                <div x-show="groupForm.visibility === 'private'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Members -->
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Add Members</h4>
                            <p class="text-[10px] text-emerald-600 font-semibold mt-0.5"><span x-text="groupForm.members.length"></span> members selected</p>
                        </div>
                        <div class="relative w-48 sm:w-64">
                            <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-[16px]">search</span>
                            <input type="text" x-model="groupForm.search" placeholder="Search contacts..."
                                   class="w-full bg-[#f0f2f5] border-none rounded-xl pl-8 pr-3 py-1.5 text-[11px] focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>

                    <!-- Members Checklist Scroll area -->
                    <div class="bg-white border border-gray-200 rounded-2xl divide-y divide-gray-100 max-h-60 overflow-y-auto">
                        <template x-for="user in filteredGroupCandidates" :key="user.id">
                            <div @click="toggleGroupMember(user.id)"
                                 class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-gray-50 select-none">
                                <img :src="user.avatar || 'https://api.dicebear.com/7.x/pixel-art/svg?seed=' + user.email"
                                     alt="Avatar" class="w-9 h-9 rounded-full object-cover border border-gray-100">
                                <div class="min-w-0 flex-1">
                                    <h5 class="text-xs font-bold text-gray-900 truncate" x-text="user.name"></h5>
                                    <p class="text-[10px] text-gray-400 truncate" x-text="user.status_message || user.email"></p>
                                </div>
                                <div class="w-5 h-5 rounded-full border flex items-center justify-center shrink-0"
                                     :class="groupForm.members.includes(user.id) ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-gray-300'">
                                    <span x-show="groupForm.members.includes(user.id)" class="material-symbols-outlined text-[14px]">done</span>
                                </div>
                            </div>
                        </template>
                        <div x-show="filteredGroupCandidates.length === 0" class="p-6 text-center text-xs font-semibold text-gray-400">
                            No contacts available.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Bottom Footer -->
            <footer class="bg-white border-t border-gray-200 px-8 py-4 shrink-0 flex justify-between items-center">
                <div>
                    <h5 class="text-xs font-bold text-gray-900">Ready to start?</h5>
                    <p class="text-[10px] text-gray-400">Review your settings and click Create to begin your new group</p>
                </div>
                <button @click="submitCreateGroup()" :disabled="!groupForm.name.trim()"
                        class="bg-[#10b981] hover:bg-[#059669] disabled:opacity-50 text-white font-bold py-2.5 px-6 rounded-xl text-xs flex items-center gap-1.5 transition-all shadow-md shadow-emerald-500/10 hover:scale-[1.01] active:scale-[0.99]">
                    <span>Create</span>
                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </button>
            </footer>
        </div>

        <!-- ------------------------------------------------------------- -->
        <!-- CREATE NEW CHANNEL PANE -->
        <!-- ------------------------------------------------------------- -->
        <div x-show="activeView === 'create_channel' && activeTab !== 'settings'" class="w-full h-full bg-[#f8fafc] flex flex-col justify-between animate-fade-in" x-cloak>
            <!-- Header -->
            <header class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center shrink-0">
                <div class="flex items-center gap-3">
                    <button @click="activeView = 'chat'" class="text-gray-600 hover:text-emerald-600 transition-colors">
                        <span class="material-symbols-outlined text-[24px]">arrow_back</span>
                    </button>
                    <div>
                        <h3 class="font-outfit font-extrabold text-gray-900 text-lg leading-tight">Create New Channel</h3>
                        <p class="text-[10px] text-gray-400 mt-0.5">Channels are a great way to broadcast messages to unlimited audiences.</p>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto p-8 space-y-6 max-w-3xl w-full mx-auto">
                <!-- Channel Icon Uploader Card -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm flex flex-col items-center text-center space-y-3 relative group cursor-pointer"
                     @click="$refs.channelIconFile.click()">
                    <div class="w-20 h-20 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 relative">
                        <template x-if="channelForm.icon_preview">
                            <img :src="channelForm.icon_preview" class="w-full h-full rounded-full object-cover">
                        </template>
                        <template x-if="!channelForm.icon_preview">
                            <span class="material-symbols-outlined text-[36px]">campaign</span>
                        </template>
                        <div class="absolute bottom-0 right-0 bg-[#10b981] text-white p-1.5 rounded-full border border-white flex items-center justify-center hover:scale-105 transition-transform">
                            <span class="material-symbols-outlined text-[14px]">edit</span>
                        </div>
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-gray-900">Channel Icon</h5>
                        <p class="text-[10px] text-gray-400 mt-0.5">Recommended: 500x500px JPG or PNG</p>
                    </div>
                    <input type="file" x-ref="channelIconFile" @change="handleChannelIconUpload" class="hidden" accept="image/*">
                </div>

                <!-- Form Fields -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Channel Name</label>
                        <input type="text" x-model="channelForm.name" placeholder="Enter channel name"
                               class="w-full text-xs font-semibold border border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-gray-50/50 py-2.5 px-3 focus:outline-none">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Channel Description</label>
                        <textarea x-model="channelForm.description" placeholder="What is this channel about?" rows="3"
                                  class="w-full text-xs font-medium border border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-gray-50/50 py-2.5 px-3 focus:outline-none resize-none"></textarea>
                    </div>
                </div>


                <!-- Discoverability Info (channels are always public) -->
                <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 flex items-start gap-3">
                    <span class="material-symbols-outlined text-emerald-500 text-[20px] shrink-0 mt-0.5">public</span>
                    <div>
                        <h5 class="text-xs font-bold text-emerald-800">Channels are always Public</h5>
                        <p class="text-[10px] text-emerald-600 mt-0.5 leading-relaxed">Anyone can discover and join your channel. Use Admin Settings below to control who can post messages.</p>
                    </div>
                </div>


                <!-- Admin Settings -->
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm space-y-4">
                    <h4 class="text-xs font-bold text-gray-900 flex items-center gap-1.5 border-b border-gray-100 pb-2 uppercase tracking-wider">
                        <span class="material-symbols-outlined text-gray-500 text-[18px]">admin_panel_settings</span>
                        <span>Admin Settings</span>
                    </h4>

                    <div class="space-y-4">
                        <!-- Dropdown select -->
                        <div class="flex justify-between items-center gap-4">
                            <div>
                                <h5 class="text-xs font-bold text-gray-800">Who can send messages</h5>
                                <p class="text-[10px] text-gray-400">Everyone can post by default</p>
                            </div>
                            <select x-model="channelForm.who_can_send_messages"
                                    class="text-xs font-semibold border border-gray-350 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-white py-1.5 px-3 select-none shrink-0 max-w-[130px] focus:outline-none">
                                <option value="everyone">Everyone</option>
                                <option value="admins">Admins Only</option>
                            </select>
                        </div>

                        <!-- Toggle switch -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h5 class="text-xs font-bold text-gray-800">Member visibility</h5>
                                <p class="text-[10px] text-gray-400">Show member list to everyone</p>
                            </div>
                            <button @click="channelForm.member_visibility = !channelForm.member_visibility"
                                    type="button"
                                    :style="'position:relative;display:inline-flex;align-items:center;width:44px;height:24px;border-radius:9999px;padding:3px;border:none;outline:none;flex-shrink:0;cursor:pointer;transition:background-color 0.2s ease;background-color:' + (channelForm.member_visibility ? '#10b981' : '#d1d5db')">
                                <span :style="'display:block;width:18px;height:18px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.25);flex-shrink:0;transition:transform 0.2s ease;transform:' + (channelForm.member_visibility ? 'translateX(20px)' : 'translateX(0)')"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Bottom Footer -->
            <footer class="bg-white border-t border-gray-200 px-8 py-4 shrink-0 flex justify-end">
                <button @click="submitCreateChannel()" :disabled="!channelForm.name.trim()"
                        class="bg-[#10b981] hover:bg-[#059669] disabled:opacity-50 text-white font-bold py-2.5 px-8 rounded-xl text-xs transition-all shadow-md shadow-emerald-500/10 hover:scale-[1.01] active:scale-[0.99]">
                    <span>Create Channel</span>
                </button>
            </footer>
        </div>

        <!-- ------------------------------------------------------------- -->
        <!-- ------------------------------------------------------------- -->
        <!-- SETTINGS WORKSPACE (PERSONAL & AI TABS) -->
        <!-- ------------------------------------------------------------- -->
        <div x-show="activeTab === 'settings'" class="w-full h-full bg-[#f8fafc] overflow-y-auto p-8 sm:p-10 flex flex-col justify-start" x-cloak>
            <div class="max-w-4xl w-full">
                
                <!-- Main Header (Dynamic) -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="font-outfit text-3xl font-black text-gray-900" 
                            x-text="settingsTab === 'personal' ? 'Settings' : 'AI Assistant Settings'"></h1>
                        <p class="text-xs text-gray-500 mt-1" 
                           x-text="settingsTab === 'personal' ? 'Manage your account preferences and application behavior.' : 'Configure how your intelligent assistant interacts with your messages.'"></p>
                    </div>
                </div>

                <!-- Sub-Navigation Tabs (Dynamic Style matching screenshots) -->
                <!-- Personal Tab Underline Style -->
                <div x-show="settingsTab === 'personal'" class="flex gap-6 border-b border-gray-200 pb-2 mb-6">
                    <button @click="settingsTab = 'personal'" 
                            class="text-xs font-bold pb-2 border-b-2 border-emerald-500 text-emerald-600 transition-all focus:outline-none">
                        Personal
                    </button>
                    <button @click="settingsTab = 'ai'" 
                            class="text-xs font-bold pb-2 text-gray-400 hover:text-gray-600 transition-all focus:outline-none">
                        AI Assistant
                    </button>
                </div>

                <!-- AI Tab Pill Style -->
                <div x-show="settingsTab === 'ai'" class="flex gap-3 mb-6">
                    <button @click="settingsTab = 'personal'" 
                            class="px-4 py-1.5 rounded-lg text-xs font-bold bg-[#f1f5f9] text-gray-600 hover:bg-gray-200 transition-all focus:outline-none">
                        Personal
                    </button>
                    <button @click="settingsTab = 'ai'" 
                            class="px-4 py-1.5 rounded-lg text-xs font-bold bg-[#0f766e] text-white shadow-sm transition-all focus:outline-none">
                        AI Assistant
                    </button>
                </div>

                <!-- Alert Messages -->
                <div x-show="settingsSuccess" x-transition class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    <span x-text="settingsSuccessMessage"></span>
                </div>
                <div x-show="settingsError" x-transition class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    <span x-text="settingsErrorMessage"></span>
                </div>

                <!-- ============================================ -->
                <!-- PERSONAL TAB CONTENT -->
                <!-- ============================================ -->
                <div x-show="settingsTab === 'personal'" class="space-y-6">
                    <!-- Profile Information Card -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2 border-b border-gray-100 pb-2 mb-4">
                            <span class="material-symbols-outlined text-gray-500 text-[18px]">person</span>
                            <span>Profile Information</span>
                        </h3>
                        
                        <div class="flex flex-col sm:flex-row gap-6 items-center sm:items-start">
                            <!-- Avatar Upload Section -->
                            <div class="relative group shrink-0">
                                <img :src="settingsForm.avatar_preview || 'https://api.dicebear.com/7.x/pixel-art/svg?seed=' + settingsForm.email" 
                                     alt="Avatar Preview" class="w-24 h-24 rounded-full object-cover border-2 border-emerald-500/20 shadow-inner">
                                <button @click="$refs.avatarFile.click()" 
                                        class="absolute bottom-0 right-0 bg-[#10b981] hover:bg-[#059669] text-white p-2 rounded-full shadow-md border-2 border-white hover:scale-105 active:scale-95 transition-all focus:outline-none">
                                    <span class="material-symbols-outlined text-[16px] block">photo_camera</span>
                                </button>
                                <input type="file" x-ref="avatarFile" @change="handleAvatarUpload" class="hidden" accept="image/*">
                            </div>

                            <!-- Name and Status Fields -->
                            <div class="flex-1 w-full space-y-4">
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Display Name</label>
                                    <input type="text" x-model="settingsForm.name" 
                                           class="w-full text-xs font-semibold border border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-gray-50/50 py-2.5 px-3 focus:outline-none">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Status Message</label>
                                    <input type="text" x-model="settingsForm.status_message" 
                                           class="w-full text-xs font-semibold border border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-gray-50/50 py-2.5 px-3 focus:outline-none">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Account & Security Card -->
                        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2 border-b border-gray-100 pb-2 mb-4">
                                    <span class="material-symbols-outlined text-gray-500 text-[18px]">security</span>
                                    <span>Account & Security</span>
                                </h3>
                                
                                <div class="space-y-4">
                                    <div class="space-y-1.5">
                                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Email Address</label>
                                        <div class="relative flex items-center">
                                            <span class="material-symbols-outlined text-gray-400 absolute left-3 text-[18px]">mail</span>
                                            <input type="email" x-model="settingsForm.email" 
                                                   class="w-full text-xs font-semibold border border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-gray-50/50 py-2.5 pl-10 pr-3 focus:outline-none">
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Phone Number</label>
                                        <div class="relative flex items-center">
                                            <span class="material-symbols-outlined text-gray-400 absolute left-3 text-[18px]">phone</span>
                                            <input type="text" x-model="settingsForm.phone" 
                                                   class="w-full text-xs font-semibold border border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-gray-50/50 py-2.5 pl-10 pr-3 focus:outline-none">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button @click="showPasswordModal = true" 
                                    class="mt-6 w-full bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-xl text-xs flex justify-between items-center transition-all focus:outline-none">
                                <span class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-gray-500 text-[18px]">lock_reset</span>
                                    <span>Change Password</span>
                                </span>
                                <span class="material-symbols-outlined text-[16px] text-gray-400">chevron_right</span>
                            </button>
                        </div>

                        <!-- Stacked Right Column: Notifications & Privacy -->
                        <div class="space-y-6">
                            <!-- Notifications Card -->
                            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                                <h3 class="text-xs font-bold text-gray-900 flex items-center gap-1.5 border-b border-gray-100 pb-2 mb-4 uppercase tracking-wider">
                                    <span class="material-symbols-outlined text-gray-500 text-[18px]">notifications</span>
                                    <span>Notifications</span>
                                </h3>

                                <div class="space-y-3.5">
                                    <!-- Push Notifications Switch -->
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-800">Push Notifications</h5>
                                            <p class="text-[10px] text-gray-400">Receive alerts on device</p>
                                        </div>
                                        <button @click="notificationSettings.notification_push = !notificationSettings.notification_push"
                                                type="button"
                                                :style="'position:relative;display:inline-flex;align-items:center;width:44px;height:24px;border-radius:9999px;padding:3px;border:none;outline:none;flex-shrink:0;cursor:pointer;transition:background-color 0.2s ease;background-color:' + (notificationSettings.notification_push ? '#10b981' : '#d1d5db')">
                                            <span :style="'display:block;width:18px;height:18px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.25);flex-shrink:0;transition:transform 0.2s ease;transform:' + (notificationSettings.notification_push ? 'translateX(20px)' : 'translateX(0)')"></span>
                                        </button>
                                    </div>
                                    <!-- Notification Sounds Switch -->
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-800">Notification Sounds</h5>
                                            <p class="text-[10px] text-gray-400">Play sound for new messages</p>
                                        </div>
                                        <button @click="notificationSettings.notification_sounds = !notificationSettings.notification_sounds"
                                                type="button"
                                                :style="'position:relative;display:inline-flex;align-items:center;width:44px;height:24px;border-radius:9999px;padding:3px;border:none;outline:none;flex-shrink:0;cursor:pointer;transition:background-color 0.2s ease;background-color:' + (notificationSettings.notification_sounds ? '#10b981' : '#d1d5db')">
                                            <span :style="'display:block;width:18px;height:18px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.25);flex-shrink:0;transition:transform 0.2s ease;transform:' + (notificationSettings.notification_sounds ? 'translateX(20px)' : 'translateX(0)')"></span>
                                        </button>
                                    </div>
                                    <!-- Message Previews Switch -->
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-800">Message Previews</h5>
                                            <p class="text-[10px] text-gray-400">Show text in notifications</p>
                                        </div>
                                        <button @click="notificationSettings.notification_previews = !notificationSettings.notification_previews"
                                                type="button"
                                                :style="'position:relative;display:inline-flex;align-items:center;width:44px;height:24px;border-radius:9999px;padding:3px;border:none;outline:none;flex-shrink:0;cursor:pointer;transition:background-color 0.2s ease;background-color:' + (notificationSettings.notification_previews ? '#10b981' : '#d1d5db')">
                                            <span :style="'display:block;width:18px;height:18px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.25);flex-shrink:0;transition:transform 0.2s ease;transform:' + (notificationSettings.notification_previews ? 'translateX(20px)' : 'translateX(0)')"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Privacy Card -->
                            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                                <h3 class="text-xs font-bold text-gray-900 flex items-center gap-1.5 border-b border-gray-100 pb-2 mb-4 uppercase tracking-wider">
                                    <span class="material-symbols-outlined text-gray-500 text-[18px]">security</span>
                                    <span>Privacy</span>
                                </h3>

                                <div class="space-y-4">
                                    <!-- Last Seen Dropdown -->
                                    <div class="flex justify-between items-center gap-4">
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-800">Last Seen</h5>
                                            <p class="text-[10px] text-gray-400">Who can see when you are online</p>
                                        </div>
                                        <select x-model="privacySettings.privacy_last_seen" 
                                                class="text-xs font-semibold border border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-white py-1.5 px-3 select-none shrink-0 max-w-[130px] focus:outline-none">
                                            <option value="everyone">Everyone</option>
                                            <option value="nobody">Nobody</option>
                                        </select>
                                    </div>

                                    <!-- Read Receipts Switch -->
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-800">Read Receipts</h5>
                                            <p class="text-[10px] text-gray-400">Send double-blue receipts</p>
                                        </div>
                                        <button @click="privacySettings.read_receipts = !privacySettings.read_receipts"
                                                type="button"
                                                :style="'position:relative;display:inline-flex;align-items:center;width:44px;height:24px;border-radius:9999px;padding:3px;border:none;outline:none;flex-shrink:0;cursor:pointer;transition:background-color 0.2s ease;background-color:' + (privacySettings.read_receipts ? '#10b981' : '#d1d5db')">
                                            <span :style="'display:block;width:18px;height:18px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.25);flex-shrink:0;transition:transform 0.2s ease;transform:' + (privacySettings.read_receipts ? 'translateX(20px)' : 'translateX(0)')"></span>
                                        </button>
                                    </div>

                                    <!-- Profile Photo Visibility Dropdown -->
                                    <div class="flex justify-between items-center gap-4">
                                        <div>
                                            <h5 class="text-xs font-bold text-gray-800">Profile Photo Visibility</h5>
                                            <p class="text-[10px] text-gray-400">Who can see your avatar image</p>
                                        </div>
                                        <select x-model="privacySettings.privacy_profile_photo" 
                                                class="text-xs font-semibold border border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-white py-1.5 px-3 select-none shrink-0 max-w-[130px] focus:outline-none">
                                            <option value="everyone">Everyone</option>
                                            <option value="nobody">Nobody</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Changes Bottom Bar -->
                    <div class="flex justify-end pt-4">
                        <button @click="savePersonalSettings()" :disabled="savingSettings"
                                class="bg-[#10b981] hover:bg-[#059669] disabled:opacity-50 text-white font-bold py-2.5 px-6 rounded-xl text-xs flex items-center gap-2 shadow-md shadow-emerald-500/10 hover:scale-[1.01] active:scale-[0.99] transition-all">
                            <span x-show="savingSettings" class="animate-spin inline-block h-3.5 w-3.5 border-2 border-white border-t-transparent rounded-full shrink-0"></span>
                            <span x-text="savingSettings ? 'Saving...' : 'Save Changes'"></span>
                        </button>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- AI ASSISTANT TAB CONTENT -->
                <!-- ============================================ -->
                <div x-show="settingsTab === 'ai'" class="space-y-6">
                    <!-- AI Auto-Pilot Switch Card -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 flex items-center justify-between shadow-sm">
                        <div class="flex gap-4 items-start pr-8">
                            <div class="w-11 h-11 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600 border border-teal-100 shrink-0 mt-0.5">
                                <span class="material-symbols-outlined text-[22px]">auto_awesome</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">AI Auto-Pilot</h4>
                                <p class="text-xs text-gray-400 mt-0.5 leading-relaxed">Allow ChitChat AI to automatically draft replies, prioritize unread threads, and suggest contextual actions based on conversation history.</p>
                            </div>
                        </div>
                        <button @click="toggleAutoPilot()"
                                type="button"
                                :style="'position:relative;display:inline-flex;align-items:center;width:52px;height:28px;border-radius:9999px;padding:4px;border:none;outline:none;flex-shrink:0;cursor:pointer;transition:background-color 0.2s ease;background-color:' + (aiSettings.is_auto_reply_enabled ? '#10b981' : '#d1d5db')">
                            <span :style="'display:block;width:20px;height:20px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.25);flex-shrink:0;transition:transform 0.2s ease;transform:' + (aiSettings.is_auto_reply_enabled ? 'translateX(24px)' : 'translateX(0)')"></span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                        <!-- Prompt Behavior Panel -->
                        <div class="md:col-span-8 bg-white border border-gray-200 rounded-2xl p-5 shadow-sm space-y-4">
                            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2 border-b border-gray-150 pb-2">
                                <span class="material-symbols-outlined text-teal-600 text-[18px]">psychology</span>
                                <span>Prompt Behavior</span>
                            </h3>
                            <p class="text-xs text-gray-400">Define the default tone and personality for generated responses.</p>
                            
                            <div class="space-y-3">
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Tone Preset</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <template x-for="tone in ['Professional', 'Casual', 'Direct']" :key="tone">
                                        <button @click="setTone(tone)"
                                                class="py-2 px-3 border text-xs font-bold rounded-xl transition-all"
                                                :class="aiSettings.tone === tone ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 hover:bg-gray-50 text-gray-600'"
                                                x-text="tone"></button>
                                    </template>
                                </div>
                            </div>

                            <div class="space-y-1.5 pt-2">
                                <label for="custom_instructions" class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Custom Instructions</label>
                                <textarea id="custom_instructions" x-model="aiSettings.prompt_behavior" rows="3" @blur="saveAISettings()"
                                          placeholder="e.g. Always use emojis, keep responses under 2 sentences..."
                                          class="w-full text-xs font-medium border-gray-250 border rounded-xl focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50/50 py-2.5 px-3 focus:outline-none"></textarea>
                            </div>
                        </div>

                        <!-- Summaries Panel -->
                        <div class="md:col-span-4 bg-white border border-gray-200 rounded-2xl p-5 shadow-sm space-y-4">
                            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2 border-b border-gray-150 pb-2">
                                <span class="material-symbols-outlined text-teal-600 text-[18px]">summarize</span>
                                <span>Summaries</span>
                            </h3>
                            <p class="text-xs text-gray-400">How often should AI summarize active threads?</p>

                            <div class="space-y-3 pt-2">
                                <template x-for="freq in ['On Demand', 'Every 10 messages', 'Daily Digest']" :key="freq">
                                    <label class="flex items-center justify-between cursor-pointer py-1">
                                        <span class="text-xs font-semibold text-gray-700" x-text="freq"></span>
                                        <input type="radio" name="summary_frequency" :value="freq" x-model="aiSettings.summary_frequency" @change="saveAISettings()"
                                               class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300">
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-xs font-semibold text-gray-400 border-t border-gray-200 pt-5">
                        <span class="material-symbols-outlined text-[16px]">security</span>
                        <span>Your data is processed securely and is never used to train public models. <a href="#" class="text-emerald-600 underline font-bold">Learn more about our AI Privacy Policy.</a></span>
                    </div>
                </div>

            </div>
        </div>

        <!-- ------------------------------------------------------------- -->
        <!-- INVITATIONS WORKSPACE -->
        <!-- ------------------------------------------------------------- -->
        <div x-show="activeTab === 'invitations'" class="absolute inset-0 z-20 bg-[#f8fafc] overflow-y-auto p-8 sm:p-10 flex flex-col justify-start" x-cloak>
            <div class="max-w-4xl w-full">
                
                <!-- Main Header -->
                <div class="border-b border-gray-200 pb-4 mb-6">
                    <h1 class="font-outfit text-3xl font-black text-gray-900">Group & Channel Invites</h1>
                    <p class="text-xs text-gray-500 mt-1">Accept or decline invitations to join private groups and channels.</p>
                </div>

                <!-- Invites Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Dynamic Invites Feed -->
                    <template x-for="invite in invitations" :key="invite.id">
                        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                            <div>
                                <div class="flex items-center gap-3 mb-4">
                                    <img :src="invite.conversation.icon || 'https://api.dicebear.com/7.x/pixel-art/svg?seed=' + invite.conversation.name" 
                                         alt="Group/Channel Icon" class="w-12 h-12 rounded-xl object-cover border border-gray-200">
                                    <div>
                                        <h4 class="font-extrabold text-sm text-gray-950 flex items-center gap-1.5">
                                            <span x-text="invite.conversation.name"></span>
                                            <span class="bg-red-50 text-red-700 font-extrabold text-[9px] uppercase tracking-wider px-2 py-0.5 rounded-full border border-red-200">Private</span>
                                        </h4>
                                        <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider" x-text="invite.conversation.type"></span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 line-clamp-2 min-h-[32px]" x-text="invite.conversation.description || 'No description provided.'"></p>
                                
                                <div class="flex items-center gap-2 mt-4 bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                                    <img :src="invite.inviter.avatar || 'https://api.dicebear.com/7.x/pixel-art/svg?seed=' + invite.inviter.name" 
                                         alt="Inviter Avatar" class="w-6 h-6 rounded-full border border-gray-200">
                                    <div class="text-[10px] text-gray-500">
                                        Invited by <span class="font-bold text-gray-800" x-text="invite.inviter.name"></span>
                                        <span class="text-gray-400 font-semibold" x-text="'â€¢ ' + invite.time_ago"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-3 mt-5 pt-3 border-t border-gray-100">
                                <button @click="respondToInvite(invite, 'accept')"
                                        class="flex-1 bg-[#10b981] hover:bg-[#059669] text-white font-bold py-2 px-3 rounded-xl text-xs flex justify-center items-center gap-1 hover:scale-[1.01] active:scale-[0.99] transition-all">
                                    <span class="material-symbols-outlined text-[16px]">check</span>
                                    <span>Accept</span>
                                </button>
                                <button @click="respondToInvite(invite, 'decline')"
                                        class="flex-1 bg-red-50 hover:bg-red-100 text-red-650 font-bold py-2 px-3 rounded-xl text-xs flex justify-center items-center gap-1 transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">close</span>
                                    <span>Decline</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="invitations.length === 0" class="bg-white border border-gray-200 rounded-3xl p-10 text-center shadow-sm max-w-lg mx-auto mt-6 flex flex-col items-center select-none" x-cloak>
                    <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-400 mb-4 border border-gray-100">
                        <span class="material-symbols-outlined text-[32px]">mail_outline</span>
                    </div>
                    <h3 class="font-outfit text-lg font-black text-gray-850">No Pending Invitations</h3>
                    <p class="text-xs text-gray-400 mt-1 max-w-[280px] leading-relaxed">You don't have any pending invites to join private groups or channels at the moment.</p>
                </div>

            </div>
        </div>

        <!-- PASSWORD CHANGE MODAL -->
        <div x-show="showPasswordModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in" x-cloak>
            <div class="bg-white rounded-3xl w-full max-w-[420px] p-6 shadow-2xl border border-gray-100 flex flex-col"
                 @click.away="showPasswordModal = false">
                
                <div class="flex justify-between items-center border-b border-gray-150 pb-3.5 mb-4">
                    <h3 class="font-outfit text-lg font-black text-gray-900">Change Password</h3>
                    <button @click="showPasswordModal = false" class="text-gray-400 hover:text-gray-700"><span class="material-symbols-outlined">close</span></button>
                </div>

                <!-- Error Banner -->
                <div x-show="passwordError" x-transition class="mb-4 bg-red-50 border border-red-200 text-red-800 px-3.5 py-2.5 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    <span x-text="passwordErrorMessage"></span>
                </div>

                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Current Password</label>
                        <input type="password" x-model="passwordForm.current_password" 
                               class="w-full text-xs font-semibold border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-gray-50/50 py-2.5 px-3">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">New Password</label>
                        <input type="password" x-model="passwordForm.new_password" 
                               class="w-full text-xs font-semibold border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-gray-50/50 py-2.5 px-3">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Confirm New Password</label>
                        <input type="password" x-model="passwordForm.new_password_confirmation" 
                               class="w-full text-xs font-semibold border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-gray-50/50 py-2.5 px-3">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showPasswordModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-xl text-xs">Cancel</button>
                    <button @click="changePassword()" :disabled="savingPassword"
                            class="bg-[#10b981] hover:bg-[#059669] text-white font-bold py-2.5 px-4 rounded-xl text-xs flex items-center gap-1.5">
                        <span x-show="savingPassword" class="animate-spin inline-block h-3.5 w-3.5 border-2 border-white border-t-transparent rounded-full shrink-0"></span>
                        <span>Update Password</span>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- ------------------------------------------------------------- -->
    <!-- GLOBAL NEW CHAT MODAL POPUP -->
    <!-- ------------------------------------------------------------- -->
    <div x-show="showSearchModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-[420px] p-6 shadow-2xl border border-gray-100 flex flex-col"
             @click.away="closeSearchModal()">
            
            <!-- Modal Header -->
            <div class="flex justify-between items-center border-b border-gray-150 pb-3.5 mb-4">
                <h3 class="font-outfit text-lg font-black text-gray-900">Start a Conversation</h3>
                <button @click="closeSearchModal()" class="text-gray-400 hover:text-gray-700"><span class="material-symbols-outlined">close</span></button>
            </div>

            <!-- Global Search Field -->
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]">search</span>
                <input type="text" x-model="userSearchQuery" @input.debounce.300ms="searchUsers()"
                       placeholder="Search users by name, email, or phone..."
                       class="w-full border-gray-300 rounded-xl pl-9 pr-3 py-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Search Results list -->
            <div class="mt-4 flex-1 overflow-y-auto max-h-60 space-y-1 pr-1">
                <!-- Searching Indicator -->
                <div x-show="searchingUsers" class="p-8 text-center text-xs font-semibold text-gray-400">
                    <span class="animate-spin inline-block h-4 w-4 border-2 border-emerald-500 border-t-transparent rounded-full mr-2"></span>
                    <span>Searching database...</span>
                </div>

                <!-- Empty State -->
                <div x-show="!searchingUsers && userSearchQuery && searchResults.length === 0" class="p-8 text-center text-xs font-semibold text-gray-400">
                    <span>No users found matching query.</span>
                </div>

                <!-- Default instructions -->
                <div x-show="!searchingUsers && !userSearchQuery" class="p-6 text-center text-xs font-medium text-gray-400">
                    <span>Enter a query to lookup registered ChitChat accounts.</span>
                </div>

                <!-- Result Items -->
                <template x-for="user in searchResults" :key="user.id">
                    <div @click="startChatWith(user)" 
                         class="flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-gray-50 rounded-xl transition-all duration-150">
                        <img :src="user.avatar || 'https://api.dicebear.com/7.x/pixel-art/svg?seed=' + user.email" 
                             alt="Avatar" class="w-10 h-10 rounded-full object-cover border border-gray-100">
                        <div class="min-w-0 flex-1">
                            <h4 class="font-bold text-gray-900 truncate text-sm" x-text="user.name"></h4>
                            <p class="text-xs text-gray-400 truncate" x-text="user.email || user.phone"></p>
                        </div>
                        <span class="material-symbols-outlined text-emerald-500 text-[20px]">chevron_right</span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Floating Message Context Menu -->
    <div x-show="contextMenu.show"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="fixed bg-white border border-gray-200/80 rounded-2xl shadow-2xl z-[100] w-64 overflow-hidden focus:outline-none select-none py-1 animate-fade-in"
         :style="'left: ' + contextMenu.x + 'px; top: ' + contextMenu.y + 'px;'"
         @click.away="closeContextMenu()"
         x-cloak>
        
        <!-- Quick Reactions Bar at the Top of Menu -->
        <div class="px-3 py-2 border-b border-gray-100 flex items-center justify-between gap-1 bg-gray-50/50">
            <template x-for="emoji in ['&#128077;', '&#10084;&#65039;', '&#128514;', '&#128558;', '&#128546;', '&#128591;']" :key="emoji">
                <button @click="reactMessage(contextMenu.message, emoji)"
                        class="text-[20px] hover:scale-125 hover:rotate-6 transition-transform duration-150 p-1 flex items-center justify-center rounded-lg hover:bg-white focus:outline-none select-none">
                    <span x-html="emoji"></span>
                </button>
            </template>
        </div>

        <!-- Actions List -->
        <div class="flex flex-col">
            <button @click="startReply(contextMenu.message)"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors w-full text-left font-medium">
                <span class="material-symbols-outlined text-[18px] text-gray-400">reply</span>
                <span>Reply</span>
            </button>
            <button @click="copyMessage(contextMenu.message)"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors w-full text-left font-medium">
                <span class="material-symbols-outlined text-[18px] text-gray-400">content_copy</span>
                <span>Copy Text</span>
            </button>
            <button @click="openForwardModal(contextMenu.message)"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors w-full text-left font-medium">
                <span class="material-symbols-outlined text-[18px] text-gray-400">forward</span>
                <span>Forward</span>
            </button>

            <!-- Pin / Unpin option (not for ephemeral) -->
            <template x-if="contextMenu.message && !contextMenu.message.is_ephemeral">
                <button @click="pinMessage(contextMenu.message)"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-amber-700 hover:bg-amber-50 hover:text-amber-800 transition-colors w-full text-left font-medium">
                    <span class="material-symbols-outlined text-[18px] text-amber-500"
                          x-text="contextMenu.message && contextMenu.message.is_pinned ? 'keep_off' : 'keep'"></span>
                    <span x-text="contextMenu.message && contextMenu.message.is_pinned ? 'Unpin Message' : 'Pin Message'"></span>
                </button>
            </template>
            
            <!-- Only show Delete if sender or admin -->
            <template x-if="contextMenu.message && (contextMenu.message.sender_id === currentUser.id || currentUser.role === 'admin')">
                <button @click="deleteMessage(contextMenu.message)"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-650 hover:bg-red-50 hover:text-red-700 transition-colors w-full text-left font-medium border-t border-gray-100">
                    <span class="material-symbols-outlined text-[18px] text-red-400">delete</span>
                    <span>Delete for Everyone</span>
                </button>
            </template>
        </div>
    </div>

    <!-- FORWARD MESSAGE MODAL POPUP -->
    <div x-show="forwardModal.show" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-[420px] p-6 shadow-2xl border border-gray-100 flex flex-col"
             @click.away="forwardModal.show = false">
            
            <!-- Modal Header -->
            <div class="flex justify-between items-center border-b border-gray-150 pb-3.5 mb-4">
                <h3 class="font-outfit text-lg font-black text-gray-900">Forward Message</h3>
                <button @click="forwardModal.show = false" class="text-gray-400 hover:text-gray-700"><span class="material-symbols-outlined">close</span></button>
            </div>

            <!-- Forward Search Field -->
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]">search</span>
                <input type="text" x-model="forwardModal.search"
                       placeholder="Search chats by name..."
                       class="w-full border-gray-300 rounded-xl pl-9 pr-3 py-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <!-- Recipient chats list -->
            <div class="mt-4 flex-1 overflow-y-auto max-h-60 space-y-1 pr-1">
                <!-- Empty State -->
                <div x-show="chats.length === 0" class="p-8 text-center text-xs font-semibold text-gray-400">
                    <span>No active chats available.</span>
                </div>

                <!-- Active Conversations Items -->
                <template x-for="chat in chats.filter(c => !forwardModal.search || c.name.toLowerCase().includes(forwardModal.search.toLowerCase()))" :key="chat.id">
                    <div @click="submitForward(chat)" 
                         class="flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-gray-50 rounded-xl transition-all duration-150 border border-transparent hover:border-emerald-100">
                        <img :src="chat.icon || 'https://api.dicebear.com/7.x/pixel-art/svg?seed=' + chat.name" 
                             alt="Avatar" class="w-10 h-10 rounded-full object-cover border border-gray-100">
                        <div class="min-w-0 flex-1">
                            <h4 class="font-bold text-gray-900 truncate text-sm" x-text="chat.name"></h4>
                            <p class="text-xs text-gray-400 truncate capitalize" x-text="chat.type + ' Chat'"></p>
                        </div>
                        <span class="material-symbols-outlined text-emerald-500 text-[20px] hover:scale-110 transition-transform">send</span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function chitchatApp() {
        return {
            // App state
            currentUser: {
                id: {{ Auth::id() }},
                name: '{{ Auth::user()->name }}',
                email: '{{ Auth::user()->email }}',
                avatar: '{{ Auth::user()->avatar }}',
                role: '{{ Auth::user()->role }}'
            },
            activeTab: 'chats', // chats, groups, channels, settings, invitations
            chats: [],
            invitations: [],
            messages: [],
            contextMenu: {
                show: false,
                x: 0,
                y: 0,
                message: null
            },
            replyToMessage: null,
            forwardModal: {
                show: false,
                message: null,
                search: ''
            },
            searchResults: [],
            activeView: 'chat', // chat, create_group, create_channel
            groupCandidates: [],
            groupForm: {
                name: '',
                description: '',
                visibility: 'public',
                icon_file: null,
                icon_preview: '',
                members: [],
                search: ''
            },
            channelForm: {
                name: '',
                description: '',
                visibility: 'public',
                icon_file: null,
                icon_preview: '',
                who_can_send_messages: 'everyone',
                member_visibility: true
            },
            
            // Search / Filter states
            searchQuery: '',
            userSearchQuery: '',
            
            // Sidebar layout states
            navCollapsed: false,
            middleCollapsed: false,
            
            // Selection states
            activeChat: null,
            newMessage: '',
            selectedFile: null,
            selectedFilePreview: null,
            selectedFileName: '',
            selectedFileType: '',
            selectedFileSize: 0,
            showEmojiPicker: false,

            // @mention system state
            mentionState: {
                active: false,          // dropdown open?
                query: '',             // text after '@' being typed
                startIndex: -1,        // cursor index where '@' was typed
                confirmed: [],         // array of { id, name } confirmed mentions
                members: [],           // full member list for current chat
                filtered: [],          // filtered list shown in dropdown
                selectedIdx: 0,        // keyboard navigation index
            },
            // Status flags
            loadingChats: false,
            loadingMessages: false,
            searchingUsers: false,
            showSearchModal: false,
            otherUserTyping: false,
            typingTimeout: null,
            isTypingState: false,

            // Settings states
            settingsTab: 'personal',
            showPasswordModal: false,
            savingSettings: false,
            savingPassword: false,
            draftingAI: false,
            settingsSuccess: false,
            settingsSuccessMessage: '',
            settingsError: false,
            settingsErrorMessage: '',
            passwordError: false,
            passwordErrorMessage: '',
            
            settingsForm: {
                name: '',
                status_message: '',
                email: '',
                phone: '',
                avatar_file: null,
                avatar_preview: ''
            },
            privacySettings: {
                privacy_last_seen: 'everyone',
                privacy_profile_photo: 'everyone',
                read_receipts: true
            },
            notificationSettings: {
                notification_push: true,
                notification_sounds: true,
                notification_previews: true
            },
            aiSettings: {
                is_auto_reply_enabled: false,
                tone: 'Professional',
                prompt_behavior: '',
                summary_frequency: 'daily'
            },
            passwordForm: {
                current_password: '',
                new_password: '',
                new_password_confirmation: ''
            },

            // Polling interval identifiers
            pollingChatsId: null,
            pollingMessagesId: null,

            get filteredChats() {
                return this.chats.filter(c => {
                    // Filter based on tab type
                    if (this.activeTab === 'chats' && c.type !== 'direct') return false;
                    if (this.activeTab === 'groups' && c.type !== 'group') return false;
                    if (this.activeTab === 'channels' && c.type !== 'channel') return false;

                    // Filter based on search query
                    if (!this.searchQuery) return true;
                    return c.name.toLowerCase().includes(this.searchQuery.toLowerCase());
                });
            },

            get filteredGroupCandidates() {
                if (!this.groupForm.search) return this.groupCandidates;
                return this.groupCandidates.filter(u => 
                    u.name.toLowerCase().includes(this.groupForm.search.toLowerCase()) ||
                    (u.status_message && u.status_message.toLowerCase().includes(this.groupForm.search.toLowerCase())) ||
                    (u.email && u.email.toLowerCase().includes(this.groupForm.search.toLowerCase()))
                );
            },

            initApp() {
                this.loadChats();
                this.loadInvitations();
                
                // Set up polling for active conversations list
                this.pollingChatsId = setInterval(() => {
                    this.loadChats(true); // silent refresh
                }, 3000);

                setInterval(() => {
                    this.loadInvitations(true);
                }, 10000);

                // Setup WebSocket echo listeners if available
                this.setupWebSockets();
            },

            switchTab(tab) {
                this.activeTab = tab;
                this.searchQuery = '';
                this.activeView = 'chat';
                if (tab === 'settings') {
                    this.activeChat = null;
                    this.loadSettings();
                }
                if (tab === 'invitations') {
                    this.activeChat = null;
                    this.loadInvitations();
                }
            },

            openCreateGroupForm() {
                this.activeView = 'create_group';
                this.groupForm.name = '';
                this.groupForm.description = '';
                this.groupForm.visibility = 'public';
                this.groupForm.icon_file = null;
                this.groupForm.icon_preview = '';
                this.groupForm.members = [];
                this.groupForm.search = '';
                this.groupCandidates = [];
                
                fetch('/api/users/search')
                    .then(res => res.json())
                    .then(data => {
                        this.groupCandidates = data;
                    });
            },

            openCreateChannelForm() {
                this.activeView = 'create_channel';
                this.channelForm.name = '';
                this.channelForm.description = '';
                this.channelForm.visibility = 'public';
                this.channelForm.icon_file = null;
                this.channelForm.icon_preview = '';
                this.channelForm.who_can_send_messages = 'everyone';
                this.channelForm.member_visibility = true;
            },

            toggleGroupMember(userId) {
                const idx = this.groupForm.members.indexOf(userId);
                if (idx === -1) {
                    this.groupForm.members.push(userId);
                } else {
                    this.groupForm.members.splice(idx, 1);
                }
            },

            handleGroupIconUpload(event) {
                const file = event.target.files[0];
                if (file) {
                    this.groupForm.icon_file = file;
                    this.groupForm.icon_preview = URL.createObjectURL(file);
                }
            },

            handleChannelIconUpload(event) {
                const file = event.target.files[0];
                if (file) {
                    this.channelForm.icon_file = file;
                    this.channelForm.icon_preview = URL.createObjectURL(file);
                }
            },

            submitCreateGroup() {
                if (!this.groupForm.name.trim()) return;

                const formData = new FormData();
                formData.append('name', this.groupForm.name);
                formData.append('description', this.groupForm.description);
                formData.append('visibility', this.groupForm.visibility);
                if (this.groupForm.icon_file) {
                    formData.append('icon', this.groupForm.icon_file);
                }
                this.groupForm.members.forEach(id => {
                    formData.append('members[]', id);
                });
                formData.append('_token', '{{ csrf_token() }}');

                fetch('/api/groups', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.activeView = 'chat';
                        this.loadChats();
                        setTimeout(() => {
                            const newChat = this.chats.find(c => c.id === data.conversation_id);
                            if (newChat) this.selectChat(newChat);
                        }, 500);
                    }
                });
            },

            submitCreateChannel() {
                if (!this.channelForm.name.trim()) return;

                const formData = new FormData();
                formData.append('name', this.channelForm.name);
                formData.append('description', this.channelForm.description);
                formData.append('visibility', this.channelForm.visibility);
                if (this.channelForm.icon_file) {
                    formData.append('icon', this.channelForm.icon_file);
                }
                formData.append('who_can_send_messages', this.channelForm.who_can_send_messages);
                formData.append('member_visibility', this.channelForm.member_visibility ? 'true' : 'false');
                formData.append('_token', '{{ csrf_token() }}');

                fetch('/api/channels', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.activeView = 'chat';
                        this.loadChats();
                        setTimeout(() => {
                            const newChat = this.chats.find(c => c.id === data.conversation_id);
                            if (newChat) this.selectChat(newChat);
                        }, 500);
                    }
                });
            },

            loadChats(silent = false) {
                if (!silent) this.loadingChats = true;
                fetch('/api/conversations')
                    .then(res => res.json())
                    .then(data => {
                        this.chats = data;
                    })
                    .finally(() => {
                        this.loadingChats = false;
                    });
            },

            selectChat(chat) {
                this.activeChat = chat;
                this.otherUserTyping = false;
                this.loadMessages(chat.id);
                this.closeMentionDropdown();

                // Load member list for @mention dropdown (groups & channels only)
                this.loadChatMembers(chat);

                // Configure Laravel Echo channel if exists
                this.listenToEchoChannel(chat.id);

                // Polling fallback configuration (2 seconds interval for active messages)
                if (this.pollingMessagesId) clearInterval(this.pollingMessagesId);
                this.pollingMessagesId = setInterval(() => {
                    this.pollMessages(chat.id);
                }, 2000);
            },

            loadMessages(conversationId) {
                this.loadingMessages = true;
                fetch(`/api/conversations/${conversationId}/messages`)
                    .then(res => res.json())
                    .then(data => {
                        this.messages = data.messages;
                        this.scrollMessagesDown();
                        // Reset unread count for current chat locally
                        if (this.activeChat) {
                            this.activeChat.unread_count = 0;
                            // Store membership status so UI can show Join button
                            if (data.is_member !== undefined) {
                                this.activeChat.is_member = data.is_member;
                            }
                            if (data.visibility !== undefined) {
                                this.activeChat.visibility = data.visibility;
                            }
                        }
                    })
                    .finally(() => {
                        this.loadingMessages = false;
                    });
            },

            joinChannel(chat) {
                fetch(`/api/channels/${chat.id}/join`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Mark as member immediately in UI
                        this.activeChat.is_member = true;
                        // Reload conversations list to reflect new membership
                        this.loadChats();
                        // Reload messages now as a member
                        this.loadMessages(chat.id);
                    }
                });
            },

            pollMessages(conversationId) {
                // Fetch messages silently for real-time simulation fallback
                fetch(`/api/conversations/${conversationId}/messages`)
                    .then(res => res.json())
                    .then(data => {
                        const ephemeralMsgs = this.messages.filter(m => m.is_ephemeral);
                        const currentNonEphemeral = this.messages.filter(m => !m.is_ephemeral);
                        
                        // Check if any database message ID is missing from our current list
                        const hasNewMessages = data.messages.some(dbMsg => 
                            !currentNonEphemeral.some(currMsg => currMsg.id === dbMsg.id)
                        );
                        
                        // Check read receipts, reactions, or pin state changed
                        const stateChanged = data.messages.some(dbMsg => {
                            const currMsg = currentNonEphemeral.find(m => m.id === dbMsg.id);
                            if (!currMsg) return false;
                            // Compare is_read
                            if (dbMsg.is_read !== currMsg.is_read) return true;
                            // Compare is_pinned
                            if (!!dbMsg.is_pinned !== !!currMsg.is_pinned) return true;
                            // Compare reactions by count and content (serialized comparison)
                            const dbReact = JSON.stringify((dbMsg.reactions || []).map(r => r.emoji + r.user_id).sort());
                            const curReact = JSON.stringify((currMsg.reactions || []).map(r => r.emoji + r.user_id).sort());
                            if (dbReact !== curReact) return true;
                            return false;
                        });

                        if (hasNewMessages || data.messages.length !== currentNonEphemeral.length || stateChanged) {
                            this.messages = [...data.messages, ...ephemeralMsgs];
                            // Only auto-scroll if a new message arrived (not just state update)
                            if (hasNewMessages || data.messages.length !== currentNonEphemeral.length) {
                                this.scrollMessagesDown();
                            }
                        }
                    });
            },

            pinMessage(message) {
                this.closeContextMenu();
                if (!message) return;

                fetch(`/api/messages/${message.id}/pin`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update local message state immediately
                        const idx = this.messages.findIndex(m => m.id === message.id);
                        if (idx !== -1) {
                            const updated = { ...this.messages[idx], is_pinned: data.is_pinned };
                            this.messages.splice(idx, 1, updated);
                        }
                    }
                });
            },



            handleFileSelect(event) {
                const file = event.target.files[0];
                if (!file) return;

                this.selectedFile = file;
                this.selectedFileName = file.name;
                this.selectedFileSize = file.size;

                // Resolve type
                const mime = file.type;
                if (mime.startsWith('image/')) {
                    this.selectedFileType = 'image';
                } else if (mime.startsWith('video/')) {
                    this.selectedFileType = 'video';
                } else if (mime.startsWith('audio/')) {
                    this.selectedFileType = 'audio';
                } else {
                    this.selectedFileType = 'document';
                }

                // Generate preview URL if image or video
                if (this.selectedFileType === 'image' || this.selectedFileType === 'video') {
                    this.selectedFilePreview = URL.createObjectURL(file);
                } else {
                    this.selectedFilePreview = null;
                }
            },

            clearSelectedFile() {
                this.selectedFile = null;
                this.selectedFilePreview = null;
                this.selectedFileName = '';
                this.selectedFileType = '';
                this.selectedFileSize = 0;
                if (this.$refs.chatFileInput) {
                    this.$refs.chatFileInput.value = '';
                }
            },

            formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            },

            appendEmoji(emoji) {
                this.newMessage += emoji;
                this.showEmojiPicker = false;
                // Refocus textarea
                this.$nextTick(() => {
                    const textarea = document.querySelector('textarea[data-mention-input]');
                    if (textarea) textarea.focus();
                });
            },

            sendMessage() {
                if (!this.newMessage.trim() && !this.selectedFile) return;
                if (!this.activeChat) return;

                // Close any open mention dropdown
                this.closeMentionDropdown();

                const body = this.newMessage;
                const isChugli = body.toLowerCase().includes('@chugli');
                
                const mentionIds = this.mentionState.confirmed
                    .filter(m => m.id !== 'chugli')
                    .map(m => m.id);

                this.newMessage = '';
                this.mentionState.confirmed = [];

                // Store reply state locally & reset reply preview
                const activeReply = this.replyToMessage;
                this.replyToMessage = null;

                // Optimistic UI update
                const tempId = Date.now();
                let tempBody = body;
                if (this.selectedFile) {
                    tempBody = this.selectedFilePreview || 'Uploading attachment...';
                }

                this.messages.push({
                    id: tempId,
                    body: tempBody,
                    type: this.selectedFile ? this.selectedFileType : 'text',
                    caption: this.selectedFile ? this.selectedFileName : null,
                    sender_id: this.currentUser.id,
                    sender_name: this.currentUser.name,
                    mentions: isChugli ? [{ id: 'chugli', name: 'chugli' }] : (this.mentionState.confirmed.length ? [...this.mentionState.confirmed] : []),
                    is_read: true,
                    is_ephemeral: isChugli,
                    created_at: new Date().toISOString(),
                    parent_message_id: activeReply ? activeReply.id : null,
                    parent: activeReply ? {
                        id: activeReply.id,
                        body: activeReply.body,
                        type: activeReply.type,
                        sender_name: activeReply.sender_name
                    } : null
                });
                this.scrollMessagesDown();

                // Prepare Payload
                let postBody;
                let headers = {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                };

                if (this.selectedFile) {
                    const formData = new FormData();
                    formData.append('file', this.selectedFile);
                    formData.append('body', body);
                    formData.append('type', this.selectedFileType);
                    formData.append('caption', this.selectedFileName);
                    mentionIds.forEach(id => formData.append('mentions[]', id));
                    if (activeReply) {
                        formData.append('parent_message_id', activeReply.id);
                    }
                    postBody = formData;
                } else {
                    headers['Content-Type'] = 'application/json';
                    const payload = { body, mentions: mentionIds };
                    if (activeReply) {
                        payload.parent_message_id = activeReply.id;
                    }
                    postBody = JSON.stringify(payload);
                }

                // Clear selected file immediately
                this.clearSelectedFile();

                // Send to backend
                fetch(`/api/conversations/${this.activeChat.id}/messages`, {
                    method: 'POST',
                    headers: headers,
                    body: postBody
                })
                .then(res => res.json())
                .then(data => {
                    if (isChugli) {
                        const idx = this.messages.findIndex(m => m.id === tempId);
                        if (idx !== -1) {
                            if (data.user_message) {
                                this.messages.splice(idx, 1, data.user_message);
                            }
                        }
                        if (data.bot_message) {
                            this.messages.push(data.bot_message);
                            this.scrollMessagesDown();
                        }
                    } else {
                        if (data.success) {
                            const idx = this.messages.findIndex(m => m.id === tempId);
                            if (idx !== -1) {
                                this.messages.splice(idx, 1, data.message);
                            }
                            this.loadChats(true);
                        }
                    }
                });

                this.sendTypingStatus(false);
            },

            // â”€â”€ @mention engine â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

            handleInput(event) {
                // Also run the typing indicator logic
                this.handleTyping();

                const textarea = event.target;
                const value = textarea.value;
                const cursorPos = textarea.selectionStart;

                // Find the last '@' before the cursor that starts a mention
                const textBeforeCursor = value.substring(0, cursorPos);
                const atIdx = textBeforeCursor.lastIndexOf('@');

                if (atIdx !== -1) {
                    // Text between '@' and cursor must not contain a space (no spaces in names mid-type)
                    const afterAt = textBeforeCursor.substring(atIdx + 1);
                    if (!afterAt.includes(' ') || afterAt.length === 0) {
                        this.mentionState.active = true;
                        this.mentionState.query = afterAt.toLowerCase();
                        this.mentionState.startIndex = atIdx;
                        this.mentionState.selectedIdx = 0;
                        this.filterMentionMembers();
                        return;
                    }
                }

                // No active mention
                this.closeMentionDropdown();
            },

            handleMentionKeydown(event) {
                if (!this.mentionState.active) return;

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    this.mentionState.selectedIdx = Math.min(
                        this.mentionState.selectedIdx + 1,
                        this.mentionState.filtered.length - 1
                    );
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    this.mentionState.selectedIdx = Math.max(this.mentionState.selectedIdx - 1, 0);
                } else if (event.key === 'Enter' || event.key === 'Tab') {
                    if (this.mentionState.filtered.length > 0) {
                        event.preventDefault();
                        this.selectMention(this.mentionState.filtered[this.mentionState.selectedIdx]);
                    }
                } else if (event.key === 'Escape') {
                    this.closeMentionDropdown();
                }
            },

            filterMentionMembers() {
                const q = this.mentionState.query;
                this.mentionState.filtered = q
                    ? this.mentionState.members.filter(m =>
                        m.name.toLowerCase().includes(q) &&
                        m.id !== this.currentUser.id // don't mention yourself
                      )
                    : this.mentionState.members.filter(m => m.id !== this.currentUser.id);
            },

            selectMention(member) {
                // Replace the "@query" fragment in newMessage with "@Name "
                const before = this.newMessage.substring(0, this.mentionState.startIndex);
                const after = this.newMessage.substring(
                    this.mentionState.startIndex + 1 + this.mentionState.query.length
                );
                this.newMessage = before + '@' + member.name + ' ' + after;

                // Register confirmed mention
                if (!this.mentionState.confirmed.find(m => m.id === member.id)) {
                    this.mentionState.confirmed.push({ id: member.id, name: member.name });
                }

                this.closeMentionDropdown();

                // Refocus textarea
                this.$nextTick(() => {
                    const ta = this.$el.querySelector('textarea[data-mention-input]');
                    if (ta) {
                        const pos = before.length + member.name.length + 2;
                        ta.focus();
                        ta.setSelectionRange(pos, pos);
                    }
                });
            },

            closeMentionDropdown() {
                this.mentionState.active = false;
                this.mentionState.query = '';
                this.mentionState.startIndex = -1;
                this.mentionState.selectedIdx = 0;
                this.mentionState.filtered = [];
            },

            loadChatMembers(chat) {
                // Load member list for @mention dropdown
                const virtualMembers = [
                    { id: 'chugli', name: 'chugli', email: 'chugli.bot@chitchat.ai' }
                ];
                if (!chat || (chat.type !== 'group' && chat.type !== 'channel')) {
                    this.mentionState.members = virtualMembers;
                    return;
                }
                fetch(`/api/conversations/${chat.id}/members`)
                    .then(res => res.json())
                    .then(data => {
                        this.mentionState.members = [...(data.members || []), ...virtualMembers];
                        this.mentionState.confirmed = [];
                    })
                    .catch(() => { this.mentionState.members = virtualMembers; });
            },

            // Render message body: highlight @Name mentions as blue chips
            renderMessageBody(message) {
                let body = message.body || '';
                // Escape HTML first for security
                body = body.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

                const mentions = message.mentions || [];
                mentions.forEach(m => {
                    const escapedName = m.name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    const pattern = new RegExp('@' + escapedName + '(?=\\s|$|[^\\w])', 'g');
                    const isChugliClass = m.id === 'chugli' ? 'self' : '';
                    body = body.replace(pattern,
                        `<span class="mention-chip ${isChugliClass}" data-user-id="${m.id}">@${m.name}</span>`
                    );
                });
                return body;
            },

            handleTyping() {
                if (!this.activeChat) return;
                if (!this.isTypingState) {
                    this.isTypingState = true;
                    this.sendTypingStatus(true);
                }
                clearTimeout(this.typingTimeout);
                this.typingTimeout = setTimeout(() => {
                    this.isTypingState = false;
                    this.sendTypingStatus(false);
                }, 1500);
            },

            sendTypingStatus(isTyping) {
                fetch(`/api/conversations/${this.activeChat.id}/typing`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ is_typing: isTyping })
                });
            },

            scrollMessagesDown() {
                this.$nextTick(() => {
                    const container = document.getElementById('messages-container');
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                });
            },

            formatMessageTime(isoString) {
                const date = new Date(isoString);
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            },

            // Modal Controls
            openSearchModal() {
                this.showSearchModal = true;
                this.userSearchQuery = '';
                this.searchResults = [];
            },

            closeSearchModal() {
                this.showSearchModal = false;
            },

            searchUsers() {
                if (!this.userSearchQuery.trim()) {
                    this.searchResults = [];
                    return;
                }

                this.searchingUsers = true;
                fetch(`/api/users/search?query=${encodeURIComponent(this.userSearchQuery)}`)
                    .then(res => res.json())
                    .then(data => {
                        this.searchResults = data;
                    })
                    .finally(() => {
                        this.searchingUsers = false;
                    });
            },

            startChatWith(user) {
                this.closeSearchModal();
                this.loadingChats = true;

                fetch('/api/conversations/initiate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ user_id: user.id })
                })
                .then(res => res.json())
                .then(data => {
                    this.loadChats();
                    // Auto select the new chat room
                    setTimeout(() => {
                        const newChat = this.chats.find(c => c.id === data.conversation_id);
                        if (newChat) {
                            this.selectChat(newChat);
                        } else {
                            // If chat list hasn't loaded yet, build a basic shell to select
                            this.selectChat({
                                id: data.conversation_id,
                                type: 'direct',
                                name: user.name,
                                icon: user.avatar,
                                other_user: user
                            });
                        }
                    }, 500);
                });
            },

            // WebSockets Broadcaster Setup
            setupWebSockets() {
                if (typeof window.Echo === 'undefined') {
                    console.log('Echo WebSocket broadcaster not initialized. Running in AJAX polling fallback.');
                    return;
                }
            },

            listenToEchoChannel(conversationId) {
                if (typeof window.Echo === 'undefined') return;

                // Leave old channels
                window.Echo.leave(`chat.${conversationId}`);

                // Join new channel
                window.Echo.private(`chat.${conversationId}`)
                    .listen('MessageSent', (e) => {
                        // Append if currently in active chat
                        if (this.activeChat && this.activeChat.id === conversationId) {
                            this.messages.push(e);
                            this.scrollMessagesDown();
                            
                            // Send read receipt back since we are actively looking at this chat
                            fetch(`/api/conversations/${conversationId}/read`, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                            });
                        } else {
                            // Increment unread count locally on side list
                            const chat = this.chats.find(c => c.id === conversationId);
                            if (chat) chat.unread_count++;
                        }
                        this.loadChats(true); // silently reload list snippets
                    })
                    .listen('MessageRead', (e) => {
                        if (this.activeChat && this.activeChat.id === conversationId) {
                            // Set all outgoing messages as read
                            this.messages.forEach(m => {
                                if (m.sender_id !== e.reader_id) {
                                    m.is_read = true;
                                }
                            });
                        }
                    })
                    .listen('UserTyping', (e) => {
                        if (this.activeChat && this.activeChat.id === conversationId && e.user_id !== this.currentUser.id) {
                            this.otherUserTyping = e.is_typing;
                        }
                    })
                    .listen('ReactionUpdated', (e) => {
                        // Real-time reaction sync for all participants
                        if (this.activeChat && this.activeChat.id === conversationId) {
                            const idx = this.messages.findIndex(m => m.id === e.message_id);
                            if (idx !== -1) {
                                const updatedMessage = { ...this.messages[idx], reactions: e.reactions };
                                this.messages.splice(idx, 1, updatedMessage);
                            }
                        }
                    })
                    .listen('MessageDeleted', (e) => {
                        // Real-time deletion sync for all participants
                        if (this.activeChat && this.activeChat.id === conversationId) {
                            const idx = this.messages.findIndex(m => m.id === e.message_id);
                            if (idx !== -1) {
                                this.messages.splice(idx, 1);
                            }
                        }
                        this.loadChats(true);
                    });
            },

            // Settings Helpers
            loadSettings() {
                fetch('/api/settings')
                    .then(res => res.json())
                    .then(data => {
                        this.personalSettings = data.profile;
                        this.privacySettings = data.privacy;
                        this.notificationSettings = data.notifications;
                        this.aiSettings = data.ai;
                        // Populate form fields
                        this.settingsForm.name = data.profile.name || '';
                        this.settingsForm.status_message = data.profile.status_message || '';
                        this.settingsForm.email = data.profile.email || '';
                        this.settingsForm.phone = data.profile.phone || '';
                        this.settingsForm.avatar_preview = data.profile.avatar || '';
                    });
            },

            handleAvatarUpload(event) {
                const file = event.target.files[0];
                if (file) {
                    this.settingsForm.avatar_file = file;
                    // Create preview URL
                    this.settingsForm.avatar_preview = URL.createObjectURL(file);
                }
            },

            savePersonalSettings() {
                this.savingSettings = true;
                this.settingsSuccess = false;
                this.settingsError = false;

                const formData = new FormData();
                formData.append('name', this.settingsForm.name);
                formData.append('status_message', this.settingsForm.status_message);
                formData.append('email', this.settingsForm.email);
                formData.append('phone', this.settingsForm.phone);
                
                if (this.settingsForm.avatar_file) {
                    formData.append('avatar', this.settingsForm.avatar_file);
                }

                // Add Privacy settings
                formData.append('privacy_last_seen', this.privacySettings.privacy_last_seen);
                formData.append('privacy_profile_photo', this.privacySettings.privacy_profile_photo);
                formData.append('read_receipts', this.privacySettings.read_receipts ? '1' : '0');

                // Add Notifications settings
                formData.append('notification_push', this.notificationSettings.notification_push ? '1' : '0');
                formData.append('notification_sounds', this.notificationSettings.notification_sounds ? '1' : '0');
                formData.append('notification_previews', this.notificationSettings.notification_previews ? '1' : '0');

                formData.append('_token', '{{ csrf_token() }}');

                fetch('/api/settings', {
                    method: 'POST',
                    body: formData
                })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Failed to save settings.');
                    return data;
                })
                .then(data => {
                    this.settingsSuccessMessage = data.message;
                    this.settingsSuccess = true;
                    // update top-left header profile metadata reactively
                    this.currentUser.name = this.settingsForm.name;
                    if (this.settingsForm.avatar_preview) {
                        this.currentUser.avatar = this.settingsForm.avatar_preview;
                    }
                    this.loadSettings();
                })
                .catch(err => {
                    this.settingsErrorMessage = err.message;
                    this.settingsError = true;
                })
                .finally(() => {
                    this.savingSettings = false;
                });
            },

            saveAISettings() {
                const payload = {
                    is_auto_reply_enabled: this.aiSettings.is_auto_reply_enabled,
                    tone: this.aiSettings.tone,
                    prompt_behavior: this.aiSettings.prompt_behavior,
                    summary_frequency: this.aiSettings.summary_frequency
                };

                fetch('/api/settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    console.log('AI settings auto-saved:', data);
                });
            },

            toggleAutoPilot() {
                this.aiSettings.is_auto_reply_enabled = !this.aiSettings.is_auto_reply_enabled;
                this.saveAISettings();
            },

            setTone(tone) {
                this.aiSettings.tone = tone;
                this.saveAISettings();
            },

            draftAIReply() {
                if (!this.activeChat || this.messages.length === 0) return;
                
                // Find the last incoming text message to reply to
                const lastIncoming = [...this.messages]
                    .reverse()
                    .find(m => m.sender_id !== this.currentUser.id && m.type === 'text');
                
                if (!lastIncoming) {
                    // Fallback to the absolute last message in case there is no incoming message from others yet
                    const lastMsg = this.messages[this.messages.length - 1];
                    if (lastMsg.type !== 'text') return;
                    this.requestAIReply(lastMsg.id);
                } else {
                    this.requestAIReply(lastIncoming.id);
                }
            },
            
            requestAIReply(messageId) {
                this.draftingAI = true;
                fetch(`/api/v1/messages/${messageId}/ai-reply`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.reply) {
                        this.newMessage = data.reply;
                    }
                })
                .finally(() => {
                    this.draftingAI = false;
                });
            },

            changePassword() {
                this.savingPassword = true;
                this.passwordError = false;

                fetch('/api/settings/password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        current_password: this.passwordForm.current_password,
                        new_password: this.passwordForm.new_password,
                        new_password_confirmation: this.passwordForm.new_password_confirmation
                    })
                })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Failed to update password.');
                    return data;
                })
                .then(data => {
                    this.showPasswordModal = false;
                    this.passwordForm.current_password = '';
                    this.passwordForm.new_password = '';
                    this.passwordForm.new_password_confirmation = '';
                    
                    this.settingsSuccessMessage = data.message;
                    this.settingsSuccess = true;
                })
                .catch(err => {
                    this.passwordErrorMessage = err.message;
                    this.passwordError = true;
                })
                .finally(() => {
                    this.savingPassword = false;
                });
            },

            loadInvitations(silent = false) {
                fetch('/api/invitations')
                    .then(res => res.json())
                    .then(data => {
                        this.invitations = data;
                    });
            },

            respondToInvite(invite, response) {
                fetch(`/api/invitations/${invite.id}/respond`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ response: response })
                })
                .then(res => res.json())
                .then(data => {
                    this.loadInvitations();
                    this.loadChats();
                });
            },

            openContextMenu(event, message) {
                // If message is ephemeral (chugli bot request or response), prevent standard context menu
                if (message.is_ephemeral) return;

                this.contextMenu.message = message;
                
                // Prevent default browser context menu
                event.preventDefault();
                
                // Calculate position relative to container / window
                let x = event.clientX;
                let y = event.clientY;
                
                // Keep context menu inside screen boundaries
                const menuWidth = 256;
                const menuHeight = 220;
                
                if (x + menuWidth > window.innerWidth) {
                    x = window.innerWidth - menuWidth - 10;
                }
                if (y + menuHeight > window.innerHeight) {
                    y = window.innerHeight - menuHeight - 10;
                }
                
                this.contextMenu.x = x;
                this.contextMenu.y = y;
                this.contextMenu.show = true;
            },

            closeContextMenu() {
                this.contextMenu.show = false;
                this.contextMenu.message = null;
            },

            // Decode HTML entity strings (e.g. "&#128077;") into real Unicode emoji chars
            decodeHtmlEmoji(entity) {
                const txt = document.createElement('textarea');
                txt.innerHTML = entity;
                return txt.value;
            },

            getGroupedReactions(reactions) {
                if (!reactions || !reactions.length) return [];
                const groups = {};
                reactions.forEach(r => {
                    groups[r.emoji] = (groups[r.emoji] || 0) + 1;
                });
                return Object.keys(groups).map(emoji => ({
                    emoji: emoji,
                    count: groups[emoji]
                }));
            },

            reactMessage(message, emoji) {
                this.closeContextMenu();
                // Decode HTML entity to real emoji char before sending to API
                const realEmoji = this.decodeHtmlEmoji(emoji);
                
                fetch(`/api/messages/${message.id}/react`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ emoji: realEmoji })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const idx = this.messages.findIndex(m => m.id === message.id);
                        if (idx !== -1) {
                            const updatedMessage = { ...this.messages[idx], reactions: data.reactions };
                            this.messages.splice(idx, 1, updatedMessage);
                        }
                    }
                });
            },

            startReply(message) {
                this.closeContextMenu();
                this.replyToMessage = message;
                // Focus the textarea input box
                this.$nextTick(() => {
                    const ta = this.$el.querySelector('textarea[data-mention-input]');
                    if (ta) ta.focus();
                });
            },

            copyMessage(message) {
                this.closeContextMenu();
                if (message.body) {
                    navigator.clipboard.writeText(message.body);
                }
            },

            openForwardModal(message) {
                this.closeContextMenu();
                this.forwardModal.message = message;
                this.forwardModal.search = '';
                this.forwardModal.show = true;
            },

            submitForward(targetChat) {
                const message = this.forwardModal.message;
                if (!message || !targetChat) return;

                this.forwardModal.show = false;
                this.forwardModal.message = null;

                fetch(`/api/messages/${message.id}/forward`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ conversation_id: targetChat.id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Select target chat to view forwarded message copy
                        this.selectChat(targetChat);
                    }
                });
            },

            deleteMessage(message) {
                this.closeContextMenu();
                
                fetch(`/api/messages/${message.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Soft delete locally from array
                        const idx = this.messages.findIndex(m => m.id === message.id);
                        if (idx !== -1) {
                            this.messages.splice(idx, 1);
                        }
                        this.loadChats(true);
                    }
                });
            },

            scrollToMessage(parentId) {
                const el = document.getElementById(`msg-bubble-${parentId}`);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('bg-emerald-100/50', 'ring-2', 'ring-emerald-450');
                    setTimeout(() => {
                        el.classList.remove('bg-emerald-100/50', 'ring-2', 'ring-emerald-450');
                    }, 1500);
                }
            }
        };
    }
</script>
@endpush
@endsection

