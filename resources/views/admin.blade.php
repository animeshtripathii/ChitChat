@extends('layouts.app')

@section('title', 'ChitChat - Admin Console')

@section('content')
<div class="fixed inset-0 flex overflow-hidden bg-[#f8fafc] font-sans text-gray-800 z-50 select-none"
     x-data="adminApp()" x-init="initApp()">

    <!-- SIDEBAR NAVIGATION -->
    <aside class="bg-white border-r border-gray-200 flex flex-col justify-between shrink-0 transition-all duration-300"
           :class="navCollapsed ? 'w-20 p-2.5 py-4' : 'w-64 p-4'">
        <div class="space-y-6">
            <!-- Branding -->
            <div class="flex items-center gap-3 px-2 py-1" :class="navCollapsed ? 'justify-center' : ''">
                <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center text-white shadow-md shadow-emerald-500/20 shrink-0">
                    <span class="material-symbols-outlined text-[24px]">security</span>
                </div>
                <div x-show="!navCollapsed" x-cloak>
                    <h3 class="font-outfit font-extrabold text-emerald-600 leading-tight">ChitChat</h3>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Admin Console</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="space-y-1">
                <button @click="switchTab('dashboard')"
                        class="w-full flex items-center gap-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-left"
                        :class="[
                            activeTab === 'dashboard' ? 'bg-[#d1fae5] text-emerald-800 font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                            navCollapsed ? 'justify-center px-0' : 'px-3'
                        ]">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>
                    <span x-show="!navCollapsed" x-cloak>Dashboard Overview</span>
                </button>
                <button @click="switchTab('users')"
                        class="w-full flex items-center gap-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-left"
                        :class="[
                            activeTab === 'users' ? 'bg-[#d1fae5] text-emerald-800 font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                            navCollapsed ? 'justify-center px-0' : 'px-3'
                        ]">
                    <span class="material-symbols-outlined text-[20px]">group</span>
                    <span x-show="!navCollapsed" x-cloak>User Management</span>
                </button>
                <button @click="switchTab('groups')"
                        class="w-full flex items-center gap-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-left"
                        :class="[
                            activeTab === 'groups' ? 'bg-[#d1fae5] text-emerald-800 font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                            navCollapsed ? 'justify-center px-0' : 'px-3'
                        ]">
                    <span class="material-symbols-outlined text-[20px]">forum</span>
                    <span x-show="!navCollapsed" x-cloak>Group Moderation</span>
                </button>
                <button @click="switchTab('ai')"
                        class="w-full flex items-center gap-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-left"
                        :class="[
                            activeTab === 'ai' ? 'bg-[#d1fae5] text-emerald-800 font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                            navCollapsed ? 'justify-center px-0' : 'px-3'
                        ]">
                    <span class="material-symbols-outlined text-[20px]">auto_awesome</span>
                    <span x-show="!navCollapsed" x-cloak>AI Logging</span>
                </button>
            </nav>
        </div>

        <!-- Footer actions -->
        <div class="space-y-3 pt-4 border-t border-gray-100">
            <a href="/dashboard" class="w-full flex items-center gap-3 py-2 rounded-xl text-xs font-bold text-emerald-600 hover:bg-emerald-50 transition-colors"
               :class="navCollapsed ? 'justify-center px-0' : 'px-3'">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span x-show="!navCollapsed" x-cloak>Back to Messenger</span>
            </a>
            <form action="{{ route('logout') }}" method="POST" class="block w-full">
                @csrf
                <button type="submit" 
                        class="w-full bg-red-50 hover:bg-red-100 text-red-600 border border-red-200/30 font-semibold py-2 rounded-xl text-xs flex justify-center items-center gap-1.5 transition-colors"
                        :class="navCollapsed ? 'px-0 py-2.5 justify-center' : 'py-2 px-3'">
                    <span class="material-symbols-outlined text-[14px]">logout</span>
                    <span x-show="!navCollapsed" x-cloak>Disconnect Console</span>
                </button>
            </form>
            
            <!-- Collapse / Expand Toggle for Admin Sidebar -->
            <button @click="navCollapsed = !navCollapsed" 
                    class="w-full flex items-center gap-3 py-2 rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-100 transition-colors"
                    :class="navCollapsed ? 'justify-center px-0' : 'px-3'">
                <span class="material-symbols-outlined text-[18px]" x-text="navCollapsed ? 'menu' : 'menu_open'"></span>
                <span x-show="!navCollapsed" x-cloak>Collapse Sidebar</span>
            </button>
        </div>
    </aside>

    <!-- MAIN PANEL -->
    <main class="flex-1 bg-[#f8fafc] flex flex-col overflow-hidden relative">

        <!-- Top Header Bar -->
        <header class="bg-white border-b border-gray-200 px-8 py-4 flex justify-between items-center shrink-0">
            <div class="flex items-center gap-3">
                <h2 class="font-outfit text-lg font-black text-gray-900">ChitChat Admin</h2>
                <span class="bg-red-50 text-red-700 font-extrabold text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full border border-red-200">System Root</span>
            </div>
            
            <!-- Global header search -->
            <div class="relative w-72">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]">search</span>
                <input type="text" placeholder="Search logs, IDs, or queries..."
                       class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-3 py-2 text-xs focus:ring-1 focus:ring-emerald-500 focus:outline-none">
            </div>

            <!-- Profile Info -->
            <div class="flex items-center gap-3">
                <button class="text-gray-500 hover:text-gray-700 p-1 rounded-lg hover:bg-gray-100 transition-all">
                    <span class="material-symbols-outlined text-[20px]">settings</span>
                </button>
                <div class="flex items-center gap-2">
                    <img src="https://api.dicebear.com/7.x/pixel-art/svg?seed=admin" class="w-8 h-8 rounded-full border border-emerald-500">
                    <span class="text-xs font-bold text-gray-700">Admin Root</span>
                </div>
            </div>
        </header>

        <!-- Dynamic Content Area -->
        <div class="flex-1 overflow-y-auto p-8">

            <!-- ------------------------------------------------------------- -->
            <!-- TAB 1: DASHBOARD OVERVIEW -->
            <!-- ------------------------------------------------------------- -->
            <div x-show="activeTab === 'dashboard'" class="space-y-6 animate-fade-in" x-cloak>
                <div class="border-b border-gray-200 pb-4">
                    <h1 class="font-outfit text-2xl font-black text-gray-900">Dashboard Overview</h1>
                    <p class="text-xs text-gray-500 mt-1">Real-time status and operational health of the ChitChat workspace.</p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">Total Registered Users</div>
                        <div class="text-3xl font-black text-gray-900" x-text="aiStats.metrics ? aiStats.metrics.real_total_users : 0">0</div>
                        <div class="text-[10px] text-emerald-600 font-bold mt-1">â— Real-time database</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">Groups Created</div>
                        <div class="text-3xl font-black text-gray-900" x-text="aiStats.metrics ? aiStats.metrics.real_total_groups : 0">0</div>
                        <div class="text-[10px] text-emerald-600 font-bold mt-1">â— Real-time database</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">Broadcast Channels</div>
                        <div class="text-3xl font-black text-gray-900" x-text="aiStats.metrics ? aiStats.metrics.real_total_channels : 0">0</div>
                        <div class="text-[10px] text-gray-500 font-bold mt-1">â— Real-time database</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-2">Flagged Items</div>
                        <div class="text-3xl font-black text-red-650" x-text="aiStats.metrics ? aiStats.metrics.real_flagged_items : 0">0</div>
                        <div class="text-[10px] text-red-500 font-bold mt-1">â— Potential spam patterns</div>
                    </div>
                </div>

                <!-- Quick info sections -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">System Alerts</h3>
                        <div class="space-y-4">
                            <div class="flex gap-3 items-start bg-red-50 text-red-900 p-3.5 rounded-xl border border-red-200">
                                <span class="material-symbols-outlined text-[20px] text-red-600 shrink-0">warning</span>
                                <div>
                                    <h5 class="text-xs font-bold">AI Phishing Spikes Detected</h5>
                                    <p class="text-[10px] text-red-700/80 mt-0.5 leading-normal">High-velocity messages in 'Crypto Traders Alpha' matched anti-phishing parameters.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 items-start bg-amber-50 text-amber-900 p-3.5 rounded-xl border border-amber-250">
                                <span class="material-symbols-outlined text-[20px] text-amber-600 shrink-0">info</span>
                                <div>
                                    <h5 class="text-xs font-bold">Budget Utilization Exceeds 60%</h5>
                                    <p class="text-[10px] text-amber-700/80 mt-0.5 leading-normal">AI query budget is at 65%. 12 days remaining in cycle.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-2 mb-4">Server Connections</h3>
                            <div class="flex justify-between items-center py-2 border-b border-gray-50 text-xs">
                                <span class="text-gray-500">API Gateway Status</span>
                                <span class="text-emerald-600 font-bold">Operational</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-50 text-xs">
                                <span class="text-gray-500">WebSocket Connections</span>
                                <span class="font-bold text-gray-700">1,842 concurrent</span>
                            </div>
                            <div class="flex justify-between items-center py-2 text-xs">
                                <span class="text-gray-500">Database Latency</span>
                                <span class="font-bold text-gray-700">12ms avg</span>
                            </div>
                        </div>
                        <button @click="switchTab('ai')" class="w-full text-center text-xs bg-gray-50 hover:bg-gray-100 py-2.5 rounded-xl border border-gray-200 font-bold transition-all text-gray-600 mt-4">
                            View Telemetry Data
                        </button>
                    </div>
                </div>
            </div>

            <!-- ------------------------------------------------------------- -->
            <!-- TAB 2: USER MANAGEMENT -->
            <!-- ------------------------------------------------------------- -->
            <div x-show="activeTab === 'users'" class="space-y-6 animate-fade-in" x-cloak>
                <div class="flex justify-between items-start border-b border-gray-200 pb-4">
                    <div>
                        <h1 class="font-outfit text-2xl font-black text-gray-900">User Management</h1>
                        <p class="text-xs text-gray-500 mt-1">Manage, audit, and moderate ChitChat platform users.</p>
                    </div>
                    <button class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs flex items-center gap-1.5 shadow-md shadow-emerald-500/10 transition-all hover:scale-[1.01] active:scale-[0.99]">
                        <span class="material-symbols-outlined text-[16px]">add_circle</span>
                        <span>Add New User</span>
                    </button>
                </div>

                <!-- Stats and Filters Row -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-end">
                    <div class="lg:col-span-3 bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
                        <div class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Total Registered Users</div>
                        <div class="text-2xl font-black text-gray-900" x-text="users.length"></div>
                    </div>
                    <div class="lg:col-span-3 bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
                        <div class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Active Status</div>
                        <div class="w-full bg-gray-100 rounded-full h-2 mt-2 relative">
                            <div class="bg-emerald-500 h-2 rounded-full" style="width: 80%"></div>
                        </div>
                        <div class="flex justify-between text-[9px] text-gray-400 font-bold mt-1.5 uppercase">
                            <span>80% Active</span>
                            <span>20% Banned</span>
                        </div>
                    </div>

                    <!-- Search Input -->
                    <div class="lg:col-span-2 space-y-1.5">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Search Users</label>
                        <input type="text" x-model="userFilter.query" @input.debounce.300ms="loadUsers()" placeholder="Search name/email/phone..."
                               class="w-full text-xs font-semibold border border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-white py-2 px-3 focus:outline-none">
                    </div>

                    <!-- Role Dropdown -->
                    <div class="lg:col-span-2 space-y-1.5">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Filter by Role</label>
                        <select x-model="userFilter.role" @change="loadUsers()"
                                class="w-full text-xs font-semibold border border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-white py-2 px-3 focus:outline-none">
                            <option value="all">All Roles</option>
                            <option value="user">User</option>
                            <option value="moderator">Moderator</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <!-- Status Dropdown -->
                    <div class="lg:col-span-2 space-y-1.5">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Filter by Status</label>
                        <select x-model="userFilter.status" @change="loadUsers()"
                                class="w-full text-xs font-semibold border border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl bg-white py-2 px-3 focus:outline-none">
                            <option value="all">All Statuses</option>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                            <option value="banned">Banned</option>
                        </select>
                    </div>
                </div>

                <!-- User Management Table -->
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-gray-400 font-bold uppercase text-[9px] tracking-wider select-none">
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">Email/Phone</th>
                                    <th class="px-6 py-3">Role</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Last Active</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="user in users" :key="user.id">
                                    <tr class="hover:bg-gray-50 transition-colors text-xs">
                                        <td class="px-6 py-4 flex items-center gap-3">
                                            <img :src="user.avatar || 'https://api.dicebear.com/7.x/pixel-art/svg?seed=' + user.email"
                                                 class="w-8 h-8 rounded-full border border-gray-200">
                                            <div>
                                                <h4 class="font-bold text-gray-900" x-text="user.name"></h4>
                                                <span class="text-[9px] text-gray-400 font-bold tracking-wide uppercase">ID: #CP<span x-text="user.id"></span></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-gray-700" x-text="user.email || 'N/A'"></div>
                                            <div class="text-[10px] text-gray-400 mt-0.5" x-text="user.phone || 'N/A'"></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="font-extrabold text-[9px] uppercase px-2.5 py-0.5 rounded-full border"
                                                  :class="{
                                                      'bg-purple-50 text-purple-700 border-purple-200': user.role === 'admin',
                                                      'bg-blue-50 text-blue-700 border-blue-200': user.role === 'moderator',
                                                      'bg-gray-50 text-gray-600 border-gray-200': user.role === 'user'
                                                  }"
                                                  x-text="user.role"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="font-extrabold text-[9px] uppercase px-2.5 py-0.5 rounded-full border"
                                                  :class="{
                                                      'bg-emerald-50 text-emerald-700 border-emerald-200': user.status === 'online',
                                                      'bg-gray-50 text-gray-500 border-gray-200': user.status === 'offline',
                                                      'bg-red-50 text-red-700 border-red-200': user.status === 'banned'
                                                  }"
                                                  x-text="user.status"></span>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-gray-500" x-text="formatDate(user.last_seen_at) || 'Just now'"></td>
                                        <td class="px-6 py-4 text-right">
                                            <button @click="toggleStatus(user)"
                                                    class="font-bold py-1.5 px-3 rounded-lg text-[10px] uppercase transition-all shadow-sm focus:outline-none"
                                                    :disabled="user.email === 'tripathianimesh38@gmail.com'"
                                                    :class="user.status === 'banned' 
                                                        ? 'bg-emerald-550 hover:bg-emerald-600 bg-[#10b981] text-white shadow-emerald-500/10' 
                                                        : 'bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 disabled:opacity-50'">
                                                <span x-text="user.status === 'banned' ? 'Unban User' : 'Ban User'"></span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="users.length === 0">
                                    <td colspan="6" class="p-8 text-center text-xs font-semibold text-gray-400">
                                        No users found matching query filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center text-[10px] text-gray-400 font-bold select-none uppercase">
                        <span>Showing <span x-text="users.length"></span> of <span x-text="users.length"></span> results</span>
                        <div class="flex gap-1">
                            <button class="px-2 py-1 rounded border border-gray-250 hover:bg-gray-50 bg-white" disabled><span class="material-symbols-outlined text-[12px] block">chevron_left</span></button>
                            <button class="px-3 py-1 rounded bg-[#d1fae5] text-emerald-800 border border-emerald-200">1</button>
                            <button class="px-2 py-1 rounded border border-gray-250 hover:bg-gray-50 bg-white" disabled><span class="material-symbols-outlined text-[12px] block">chevron_right</span></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ------------------------------------------------------------- -->
            <!-- TAB 3: GROUP MODERATION -->
            <!-- ------------------------------------------------------------- -->
            <div x-show="activeTab === 'groups'" class="space-y-6 animate-fade-in" x-cloak>
                <div class="border-b border-gray-200 pb-4">
                    <h1 class="font-outfit text-2xl font-black text-gray-900">Group Moderation</h1>
                    <p class="text-xs text-gray-500 mt-1">Audit, moderate, and manage communities and broadcast channels.</p>
                                <!-- Group Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 shrink-0">
                            <span class="material-symbols-outlined text-[24px]">group</span>
                        </div>
                        <div>
                            <div class="text-gray-400 text-[10px] font-bold uppercase tracking-wider">Active Groups</div>
                            <div class="text-2xl font-black text-gray-900" x-text="aiStats.metrics ? aiStats.metrics.real_total_groups : 0">0</div>
                            <p class="text-[9px] text-emerald-650 font-bold mt-0.5">â— Real-time database</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center text-teal-600 shrink-0">
                            <span class="material-symbols-outlined text-[24px]">campaign</span>
                        </div>
                        <div>
                            <div class="text-gray-400 text-[10px] font-bold uppercase tracking-wider">Broadcast Channels</div>
                            <div class="text-2xl font-black text-gray-900" x-text="aiStats.metrics ? aiStats.metrics.real_total_channels : 0">0</div>
                            <p class="text-[9px] text-gray-400 font-bold mt-0.5">â— Real-time database</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center text-red-600 shrink-0">
                            <span class="material-symbols-outlined text-[24px]">warning</span>
                        </div>
                        <div>
                            <div class="text-gray-400 text-[10px] font-bold uppercase tracking-wider">Flagged Items</div>
                            <div class="text-2xl font-black text-red-650" x-text="aiStats.metrics ? aiStats.metrics.real_flagged_items : 0">0</div>
                            <p class="text-[9px] text-red-500 font-bold mt-0.5">âš ï¸ Real-time alert counts</p>
                        </div>
                    </div>
                </div>

                <!-- Urgent Filter Alert Block -->
                <div class="bg-white border border-gray-250 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex gap-4 items-start">
                        <div class="w-12 h-12 bg-red-100 text-red-700 rounded-xl flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[26px]">warning</span>
                        </div>
                        <div>
                            <h4 class="font-extrabold text-sm text-gray-900 flex items-center gap-1.5">
                                <span>Urgent: AI Filter Alert</span>
                            </h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-xl leading-relaxed">Multiple high-velocity message spikes detected in 'Crypto Traders Alpha' with potential phishing patterns. Admin Intervention required.</p>
                        </div>
                    </div>
                    <button class="bg-[#059669] hover:bg-[#047857] text-white font-bold py-2.5 px-6 rounded-xl text-xs shrink-0 shadow-md shadow-emerald-500/10">
                        Review Cluster
                    </button>
                </div>

                <!-- Community Activity Feed -->
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden p-6 space-y-4">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                        <h3 class="text-sm font-bold text-gray-900">Community Activity Feed</h3>
                        <a href="#" class="text-xs text-emerald-600 hover:underline font-bold">View All</a>
                    </div>

                    <!-- Items Feed -->
                    <div class="divide-y divide-gray-100">
                        <template x-for="conv in conversations" :key="conv.id">
                            <div class="flex justify-between items-center py-3.5 hover:bg-gray-50/50 px-2 rounded-xl transition-all">
                                <div class="flex items-center gap-3">
                                    <img :src="conv.icon" class="w-10 h-10 rounded-xl border border-gray-250 object-cover shadow-sm">
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-950" x-text="conv.name"></h4>
                                        <div class="flex gap-2 items-center mt-0.5">
                                            <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.2 rounded border"
                                                  :class="conv.type === 'channel' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                                  x-text="conv.type"></span>
                                            <span class="text-[9px] text-gray-400 font-bold">ID: #CP<span x-text="conv.id"></span></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-6 text-xs">
                                    <div class="text-right">
                                        <span class="font-bold text-gray-700" x-text="conv.member_count"></span>
                                        <p class="text-[9px] text-gray-400 font-bold uppercase mt-0.5">Members</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-bold text-[9px] uppercase px-2 py-0.5 rounded-full border"
                                              :class="{
                                                  'bg-emerald-50 text-emerald-750 border-emerald-250': conv.activity === 'High',
                                                  'bg-blue-50 text-blue-750 border-blue-200': conv.activity === 'Medium',
                                                  'bg-gray-50 text-gray-500 border-gray-200': conv.activity === 'Low'
                                              }"
                                              x-text="conv.activity"></span>
                                        <p class="text-[9px] text-gray-400 font-bold uppercase mt-0.5">Activity</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="font-bold text-[9px] uppercase px-2.5 py-0.5 rounded-full border"
                                              :class="conv.status === 'banned' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                              x-text="conv.status"></span>
                                        <p class="text-[9px] text-gray-400 font-bold uppercase mt-0.5">Status</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="toggleConversationBan(conv)"
                                                class="font-bold py-1.5 px-3 rounded-lg text-[10px] uppercase transition-all shadow-sm focus:outline-none"
                                                :class="conv.status === 'banned' 
                                                    ? 'bg-[#10b981] hover:bg-emerald-600 text-white shadow-emerald-500/10' 
                                                    : 'bg-red-50 text-red-605 border border-red-200 hover:bg-red-100'">
                                            <span x-text="conv.status === 'banned' ? 'Unban Group' : 'Ban Group'"></span>
                                        </button>
                                        <button @click="deleteConversation(conv)"
                                                class="font-bold py-1.5 px-3 rounded-lg text-[10px] uppercase bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition-all shadow-sm focus:outline-none">
                                            Purge
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="conversations.length === 0" class="p-8 text-center text-xs font-semibold text-gray-400">
                            No groups or channels found in the database.
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="pt-4 flex justify-between items-center text-[10px] text-gray-400 font-bold uppercase select-none">
                        <span>Showing <span x-text="conversations.length"></span> moderated communities</span>
                    </div>
                </div>              </div>
            </div>

            <!-- ------------------------------------------------------------- -->
            <!-- TAB 4: AI LOGGING (PERFORMANCE & COST) -->
            <!-- ------------------------------------------------------------- -->
            <div x-show="activeTab === 'ai'" class="space-y-6 animate-fade-in" x-cloak>
                <div class="flex justify-between items-start border-b border-gray-200 pb-4">
                    <div>
                        <h1 class="font-outfit text-2xl font-black text-gray-900">AI Performance & Cost</h1>
                        <p class="text-xs text-gray-500 mt-1">Real-time telemetry and financial impact of generative AI services.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <select class="text-xs font-semibold border border-gray-300 focus:border-emerald-500 rounded-xl bg-white py-2 px-3 focus:outline-none">
                            <option>Last 24 Hours</option>
                            <option>Last 7 Days</option>
                            <option>Last 30 Days</option>
                        </select>
                        <button class="bg-[#10b981] hover:bg-[#059669] text-white font-bold py-2 px-4 rounded-xl text-xs flex items-center gap-1.5 shadow-md shadow-emerald-500/10">
                            <span class="material-symbols-outlined text-[16px]">download</span>
                            <span>Export</span>
                        </button>
                    </div>
                </div>

                <!-- Stats row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                        <div class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1.5">Total Requests</div>
                        <div class="text-3xl font-black text-gray-900" x-text="aiStats.metrics ? aiStats.metrics.total_requests : '1.24M'"></div>
                        <div class="text-[9px] text-emerald-600 font-bold mt-1">â–² +8.2% from last week</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                        <div class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1.5">Avg Latency (P95)</div>
                        <div class="text-3xl font-black text-red-500" x-text="aiStats.metrics ? aiStats.metrics.avg_latency : '420ms'"></div>
                        <div class="text-[9px] text-red-500 font-bold mt-1">âš ï¸ +15ms from last week</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                        <div class="text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1.5">Total Cost</div>
                        <div class="text-3xl font-black text-gray-900" x-text="aiStats.metrics ? aiStats.metrics.total_cost : '$4,520'"></div>
                        <div class="text-[9px] text-emerald-600 font-bold mt-1">â–¼ -2% from last week</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="text-gray-400 text-[10px] font-bold uppercase tracking-wider flex justify-between">
                                <span>Budget Utilization</span>
                                <span class="font-bold text-gray-700" x-text="(aiStats.metrics ? aiStats.metrics.budget_utilization : 65) + '%'"></span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1.5">
                                <div class="bg-emerald-500 h-1.5 rounded-full" :style="'width: ' + (aiStats.metrics ? aiStats.metrics.budget_utilization : 65) + '%'"></div>
                            </div>
                        </div>
                        <div class="text-[8px] text-gray-400 font-bold uppercase tracking-wider flex justify-between mt-2">
                            <span>Spent: <span x-text="aiStats.metrics ? aiStats.metrics.spent_budget : '$6,500'"></span></span>
                            <span>Limit: <span x-text="aiStats.metrics ? aiStats.metrics.limit_budget : '$10,000'"></span></span>
                        </div>
                    </div>
                </div>

                <!-- Graphs & Recent Generations Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Graph -->
                    <div class="lg:col-span-8 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center border-b border-gray-150 pb-2 mb-4">
                                <h3 class="text-sm font-bold text-gray-900">Request Volume & Latency</h3>
                                <div class="flex gap-4 text-[9px] font-bold uppercase tracking-wider select-none">
                                    <span class="flex items-center gap-1.5 text-emerald-600"><span class="w-2.5 h-2.5 bg-emerald-500 rounded-full inline-block"></span> Volume</span>
                                    <span class="flex items-center gap-1.5 text-blue-600"><span class="w-2.5 h-0.5 bg-blue-500 inline-block"></span> Latency</span>
                                </div>
                            </div>
                            <!-- Telemetry Chart simulation -->
                            <div class="h-56 flex items-end justify-between gap-1.5 border-b border-l border-gray-150 pl-2 pb-1.5 relative select-none">
                                <!-- Dynamic Bars -->
                                <template x-for="(point, idx) in (aiStats.chart || [])" :key="idx">
                                    <div class="w-7 bg-emerald-500/20 hover:bg-emerald-500/35 transition-colors rounded-t flex items-end justify-center z-10"
                                         :style="'height: ' + Math.min(100, Math.max(10, (point.volume / 50) * 100)) + '%'">
                                         <span class="text-[8px] text-emerald-800 font-bold mb-1" x-text="point.volume_label"></span>
                                    </div>
                                </template>
                                
                                <div x-show="!aiStats.chart || aiStats.chart.length === 0" class="absolute inset-0 flex items-center justify-center text-xs font-semibold text-gray-400">
                                    No dynamic telemetry data points found.
                                </div>

                                <!-- Latency dynamic line overlay -->
                                <template x-if="aiStats.chart && aiStats.chart.length > 0">
                                    <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                                        <path :d="getLatencyPath()" fill="none" stroke="#2563eb" stroke-width="2"></path>
                                    </svg>
                                </template>
                            </div>
                            <div class="flex justify-between text-[8px] font-bold text-gray-400 mt-2 select-none uppercase tracking-wide px-2">
                                <template x-for="(point, idx) in (aiStats.chart || [])" :key="idx">
                                    <span x-text="point.label"></span>
                                </template>
                            </div>
                        </div>

                        <!-- Cost Breakdown table nested -->
                        <div class="mt-6 border-t border-gray-100 pt-4">
                            <div class="flex justify-between items-center pb-2.5">
                                <h4 class="text-xs font-bold text-gray-900">Cost Breakdown by Group</h4>
                                <a href="#" class="text-[10px] text-emerald-600 hover:underline font-bold select-none uppercase tracking-wide">View All</a>
                            </div>
                            <div class="grid grid-cols-4 gap-4 text-[10px] font-bold uppercase tracking-wider text-gray-400 select-none pb-1.5 border-b border-gray-100">
                                <span>Group Name</span>
                                <span>Tokens Used</span>
                                <span>Cost (USD)</span>
                                <span>% of Total</span>
                            </div>
                            <div class="divide-y divide-gray-50 max-h-36 overflow-y-auto pr-1">
                                <template x-for="item in aiStats.cost_breakdown" :key="item.group">
                                    <div class="grid grid-cols-4 gap-4 text-xs py-2 items-center text-gray-700">
                                        <span class="font-bold text-gray-900" x-text="item.group"></span>
                                        <span x-text="item.tokens"></span>
                                        <span class="font-bold text-emerald-700" x-text="item.cost"></span>
                                        <div class="flex items-center gap-1.5">
                                            <span x-text="item.percent + '%'"></span>
                                            <div class="w-12 bg-gray-150 h-1 rounded-full overflow-hidden shrink-0">
                                                <div class="bg-emerald-500 h-1" :style="'width: ' + item.percent + '%'"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Generations Feed -->
                    <div class="lg:col-span-4 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center border-b border-gray-150 pb-2">
                                <h3 class="text-sm font-bold text-gray-900">Recent Generations</h3>
                                <div class="flex gap-1.5 select-none">
                                    <button class="bg-emerald-50 text-emerald-800 border border-emerald-200 text-[8px] font-bold uppercase py-0.5 px-2 rounded-lg">All</button>
                                    <button class="hover:bg-gray-100 text-gray-500 text-[8px] font-bold uppercase py-0.5 px-2 rounded-lg">Errors</button>
                                </div>
                            </div>

                            <!-- Generation Items -->
                            <div class="space-y-3 max-h-[460px] overflow-y-auto pr-1">
                                <template x-for="gen in aiStats.recent_generations" :key="gen.id">
                                    <div class="p-3 border rounded-xl space-y-2 text-xs transition-shadow hover:shadow-sm"
                                         :class="gen.status === 'success' ? 'bg-gray-50 border-gray-200' : 'bg-red-50/50 border-red-200'">
                                        <div class="flex justify-between items-center">
                                            <span class="font-extrabold text-[9px] px-1.5 py-0.5 rounded uppercase tracking-wider text-white"
                                                  :class="gen.status === 'success' ? 'bg-emerald-500' : 'bg-red-500'"
                                                  x-text="gen.status === 'success' ? gen.model : 'FAILED'"></span>
                                            <span class="text-[9px] text-gray-400 font-semibold" x-text="gen.time_ago"></span>
                                        </div>
                                        <div>
                                            <div class="text-[10px] text-gray-500 font-medium italic truncate" x-text="'&ldquo;' + gen.prompt + '&rdquo;'"></div>
                                            <div class="text-[10px] text-gray-700 font-bold mt-1 truncate" x-text="gen.response"></div>
                                        </div>
                                        <div class="flex gap-4 text-[9px] text-gray-400 font-bold uppercase tracking-wider select-none border-t border-gray-200/50 pt-1.5">
                                            <span x-text="'Latency: ' + gen.latency"></span>
                                            <span x-text="'Tokens: ' + gen.tokens"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

@push('scripts')
<script>
    function adminApp() {
        return {
            activeTab: 'ai', // default to ai logging (as in first screenshot)
            navCollapsed: false,
            users: [],
            conversations: [],
            aiStats: {},
            userFilter: {
                query: '',
                role: 'all',
                status: 'all'
            },

            initApp() {
                this.loadUsers();
                this.loadAIStats();
                this.loadConversations();

                // Periodic status refresh
                setInterval(() => {
                    this.loadAIStats();
                }, 10000);
            },

            switchTab(tab) {
                this.activeTab = tab;
            },

            loadUsers() {
                const params = new URLSearchParams({
                    query: this.userFilter.query,
                    role: this.userFilter.role,
                    status: this.userFilter.status
                });

                fetch('/api/admin/users?' + params.toString())
                    .then(res => res.json())
                    .then(data => {
                        this.users = data;
                    });
            },

            loadConversations() {
                fetch('/api/admin/conversations')
                    .then(res => res.json())
                    .then(data => {
                        this.conversations = data;
                    });
            },

            toggleStatus(user) {
                fetch(`/api/admin/users/${user.id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.loadUsers();
                        this.loadAIStats(); // refresh online/offline metrics
                    }
                });
            },

            toggleConversationBan(conv) {
                fetch(`/api/admin/conversations/${conv.id}/toggle-ban`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.loadConversations();
                        this.loadAIStats(); // update group count metrics
                    }
                });
            },

            deleteConversation(conv) {
                if (!confirm(`Are you sure you want to permanently purge "${conv.name}"? This will delete all messages inside. This cannot be undone.`)) {
                    return;
                }
                fetch(`/api/admin/conversations/${conv.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.loadConversations();
                        this.loadAIStats();
                    }
                });
            },

            loadAIStats() {
                fetch('/api/admin/ai-stats')
                    .then(res => res.json())
                    .then(data => {
                        this.aiStats = data;
                    });
            },

            getLatencyPath() {
                if (!this.aiStats.chart || this.aiStats.chart.length === 0) return '';
                const points = this.aiStats.chart;
                const maxLatency = Math.max(...points.map(p => p.latency), 100);
                
                return points.map((p, idx) => {
                    const x = idx * (100 / Math.max(1, points.length - 1));
                    const y = 85 - (p.latency / maxLatency) * 70;
                    return `${idx === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`;
                }).join(' ');
            },

            formatDate(isoString) {
                if (!isoString) return '';
                const date = new Date(isoString);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
        };
    }
</script>
@endpush
@endsection

