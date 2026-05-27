// ==========================================================================
// AETHERCHAT - SINGLE PAGE APP STATE ENGINE & CLIENT
// ==========================================================================

const EchoConstructor = window.Echo;

// 1. GLOBAL CLIENT-SIDE STATE
const state = {
    user: null,
    token: null,
    chats: [],
    activeChat: null,
    messages: {},         // Map of chat_id => Array of messages
    presenceList: new Set(), // Set of online user IDs
    typingStatus: {},     // Map of chat_id => timeout
    replyMessage: null,   // Current parent message selected for reply
    activeStatusFeed: [], // List of contact stories
    statusViewerTimer: null,
    selectedAvatarFile: null,
    authMode: 'signup'
};

// ==========================================================================
// DOM SECURE HANDLES
// ==========================================================================
const DOM = {
    // Screen Panels
    onboardingScreen: document.getElementById('onboarding-screen'),
    mainWorkspace: document.getElementById('main-workspace'),
    
    // Forms & Buttons
    profileForm: document.getElementById('profile-form'),
    profileUsername: document.getElementById('profile-username'),
    profileBio: document.getElementById('profile-bio'),
    profilePictureInput: document.getElementById('profile-picture-input'),
    profileAvatarPreview: document.getElementById('profile-avatar-preview'),
    profileSubmitBtn: document.getElementById('profile-submit-btn'),
    
    // Sidebar Workspace
    chatsListContainer: document.getElementById('chats-list-container'),
    chatsSearchInput: document.getElementById('chats-search-input'),
    btnShareStatus: document.getElementById('btn-share-status'),
    statusUserAvatar: document.getElementById('status-user-avatar'),
    activeContactsStories: document.getElementById('active-contacts-stories'),
    
    // Right Chat Room Window
    roomWelcomeSplash: document.getElementById('room-welcome-splash'),
    roomActiveWindow: document.getElementById('room-active-window'),
    activeChatAvatar: document.getElementById('active-chat-avatar'),
    activeChatTitle: document.getElementById('active-chat-title'),
    activeChatSubtitle: document.getElementById('active-chat-subtitle'),
    messagesListWrapper: document.getElementById('messages-list-wrapper'),
    messageTextInput: document.getElementById('message-text-input'),
    actionSendMessage: document.getElementById('action-send-message'),
    mediaAttachmentInput: document.getElementById('media-attachment-input'),
    actionAttachFile: document.getElementById('action-attach-file'),
    
    // Message Reply Preview
    messageReplyPreview: document.getElementById('message-reply-preview'),
    replyPreviewSender: document.getElementById('reply-preview-sender'),
    replyPreviewSnippet: document.getElementById('reply-preview-snippet'),
    actionCloseReply: document.getElementById('action-close-reply'),
    actionRoomBack: document.getElementById('action-room-back'),
    
    // Modals & Panels
    settingsSlidePanel: document.getElementById('settings-slide-panel'),
    formInitiateChat: document.getElementById('form-initiate-chat'),
    newChatPhone: document.getElementById('new-chat-phone'),
    formCreateGroup: document.getElementById('form-create-group'),
    groupIconInput: document.getElementById('group-icon-input'),
    groupIconPreview: document.getElementById('group-icon-preview'),
    groupNameInput: document.getElementById('group-name-input'),
    groupDescInput: document.getElementById('group-desc-input'),
    groupContactsMultiselect: document.getElementById('group-contacts-multiselect'),
    
    // Privacy Settings Inputs
    settingReadReceipts: document.getElementById('setting-read-receipts'),
    settingLastSeen: document.getElementById('setting-last-seen'),
    settingProfilePhoto: document.getElementById('setting-profile-photo'),
    settingAbout: document.getElementById('setting-about'),
    setting2faToggle: document.getElementById('setting-2fa-toggle'),
    panel2faSetup: document.getElementById('2fa-setup-panel'),
    settings2faPin: document.getElementById('settings-2fa-pin'),
    actionSave2fa: document.getElementById('action-save-2fa'),
    actionCloseSettings: document.getElementById('action-close-settings'),
    blockedUsersList: document.getElementById('blocked-users-list'),
    
    // Story status viewer modal
    modalStatusViewer: document.getElementById('modal-status-viewer'),
    statusTimerBar: document.getElementById('status-timer-bar'),
    statusViewerAvatar: document.getElementById('status-viewer-avatar'),
    statusViewerName: document.getElementById('status-viewer-name'),
    statusViewerTime: document.getElementById('status-viewer-time'),
    statusViewTextScreen: document.getElementById('status-view-text-screen'),
    statusViewTextBody: document.getElementById('status-view-text-body'),
    statusViewImageScreen: document.getElementById('status-view-image-screen'),
    statusViewImageElement: document.getElementById('status-view-image-element'),
    statusViewCaptionElement: document.getElementById('status-view-caption-element')
};

// ==========================================================================
// TOAST NOTIFICATIONS (Professional Glass Style)
// ==========================================================================
function showToast(message, type = 'info', duration = 3000) {
    let container = document.getElementById('doodle-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'doodle-toast-container';
        container.className = 'fixed bottom-24 right-6 flex flex-col gap-3 z-[1000] pointer-events-none max-w-[320px] w-full';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    
    let bgClass = 'bg-white';
    let borderClass = 'border-slate-200';
    let textClass = 'text-slate-800';
    let icon = '📢';
    
    if (type === 'warning' || type === 'error') {
        bgClass = 'bg-red-50';
        borderClass = 'border-red-200';
        textClass = 'text-red-800';
        icon = '⚠️';
    } else if (type === 'success') {
        bgClass = 'bg-teal-50';
        borderClass = 'border-teal-200';
        textClass = 'text-teal-800';
        icon = '✨';
    } else if (type === 'info') {
        bgClass = 'bg-blue-50';
        borderClass = 'border-blue-200';
        textClass = 'text-blue-800';
        icon = '💡';
    }
    
    toast.className = `glass-card ${bgClass} border ${borderClass} ${textClass} rounded-2xl px-5 py-3 shadow-lg flex items-center gap-3 transform translate-y-4 opacity-0 transition-all duration-300 pointer-events-auto`;
    toast.innerHTML = `
        <span class="text-base">${icon}</span>
        <div class="flex-1 font-semibold text-sm">${message}</div>
        <button class="text-xs opacity-60 hover:opacity-100 font-bold ml-2" onclick="this.parentElement.remove()">✕</button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('translate-y-4', 'opacity-0');
    }, 10);
    
    setTimeout(() => {
        toast.classList.add('translate-y-[-4px]', 'opacity-0');
        setTimeout(() => {
            toast.remove();
            if (container.children.length === 0) {
                container.remove();
            }
        }, 300);
    }, duration);
}

window.showToast = showToast;

// ==========================================================================
// CORE API CALL WRAPPER (Sanctum Tokens & Multiparts)
// ==========================================================================
async function apiCall(endpoint, method = 'GET', body = null, isMultipart = false) {
    const headers = {};
    
    if (state.token) {
        headers['Authorization'] = `Bearer ${state.token}`;
    }
    
    if (!isMultipart) {
        headers['Content-Type'] = 'application/json';
        headers['Accept'] = 'application/json';
    } else {
        headers['Accept'] = 'application/json';
    }

    const options = {
        method,
        headers
    };

    if (body) {
        options.body = isMultipart ? body : JSON.stringify(body);
    }

    try {
        const response = await fetch(endpoint, options);
        
        if (response.status === 401) {
            localStorage.removeItem('whatsapp_token');
            localStorage.removeItem('whatsapp_user');
            state.token = null;
            state.user = null;
            state.activeChat = null;
            if (window.Echo) {
                try { window.Echo.disconnect(); } catch (e) {}
            }
            
            const landing = document.getElementById('guest-landing-screen');
            if (landing) {
                landing.classList.add('active');
                landing.classList.remove('hide');
            }
            DOM.mainWorkspace?.classList.add('hide');
            DOM.onboardingScreen?.classList.add('hide');
            DOM.onboardingScreen?.classList.remove('active');
            
            throw new Error('Session expired. Please log in again.');
        }

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'API request failed');
        }
        
        return data;
    } catch (error) {
        console.error(`API Error on ${endpoint}:`, error);
        throw error;
    }
}

// ==========================================================================
// INITIAL BOOTSTRAPPING & ROUTING
// ==========================================================================
document.addEventListener('DOMContentLoaded', () => {
    // 1. Recover token and user session caches
    state.token = localStorage.getItem('whatsapp_token');
    const cachedUser = localStorage.getItem('whatsapp_user');

    if (state.token && cachedUser) {
        state.user = JSON.parse(cachedUser);
        
        // Hide landing and show workspace
        const landing = document.getElementById('guest-landing-screen');
        if (landing) {
            landing.classList.add('hide');
            landing.classList.remove('active');
        }
        
        enterWorkspace();
    } else {
        // Show landing screen
        const landing = document.getElementById('guest-landing-screen');
        if (landing) {
            landing.classList.remove('hide');
            landing.classList.add('active');
        }
    }

    // Restore font style
    const cachedFont = localStorage.getItem('doodle_font');
    if (cachedFont) {
        window.changeGlobalFont(cachedFont);
    }
    
    // Restore paper background theme
    const cachedTheme = localStorage.getItem('doodle_theme');
    if (cachedTheme) {
        window.changeGlobalTheme(cachedTheme);
    }

    // 2. Wire event listeners
    bindEvents();
});

// ==========================================================================
// EVENT LISTENERS REGISTER
// ==========================================================================
function bindEvents() {
    // --- Guest Login Submit ---
    document.getElementById('guest-login-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('guest-username').value.trim();
        const email = document.getElementById('guest-email').value.trim();
        
        try {
            const data = await apiCall('/api/v1/auth/guest-login', 'POST', { username, email });
            state.token = data.token;
            state.user = data.user;
            localStorage.setItem('whatsapp_token', data.token);
            localStorage.setItem('whatsapp_user', JSON.stringify(data.user));
            
            showToast('Logged in as Guest! ⏱️', 'success');
            
            // Hide landing and onboarding
            document.getElementById('guest-landing-screen')?.classList.add('hide');
            document.getElementById('guest-landing-screen')?.classList.remove('active');
            DOM.onboardingScreen?.classList.add('hide');
            DOM.onboardingScreen?.classList.remove('active');
            
            enterWorkspace();
        } catch (err) {
            showToast(err.message || 'Guest login failed.', 'error');
        }
    });

    // --- Email Login Submit ---
    document.getElementById('email-login-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-password').value;
        
        try {
            const data = await apiCall('/api/v1/auth/email-login', 'POST', { email, password });
            state.token = data.token;
            state.user = data.user;
            localStorage.setItem('whatsapp_token', data.token);
            localStorage.setItem('whatsapp_user', JSON.stringify(data.user));
            
            showToast('Workspace signed in successfully! 🔐', 'success');
            
            // Hide landing and onboarding
            document.getElementById('guest-landing-screen')?.classList.add('hide');
            document.getElementById('guest-landing-screen')?.classList.remove('active');
            DOM.onboardingScreen?.classList.add('hide');
            DOM.onboardingScreen?.classList.remove('active');
            
            enterWorkspace();
        } catch (err) {
            showToast(err.message || 'Login failed.', 'error');
        }
    });

    // --- Email Signup Submit ---
    document.getElementById('email-signup-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('signup-username').value.trim();
        const email = document.getElementById('signup-email').value.trim();
        const password = document.getElementById('signup-password').value;
        
        try {
            const data = await apiCall('/api/v1/auth/email-register', 'POST', { username, email, password });
            state.token = data.token;
            state.user = data.user;
            localStorage.setItem('whatsapp_token', data.token);
            localStorage.setItem('whatsapp_user', JSON.stringify(data.user));
            
            showToast('Workspace account created! ✨', 'success');
            
            // Hide landing and onboarding
            document.getElementById('guest-landing-screen')?.classList.add('hide');
            document.getElementById('guest-landing-screen')?.classList.remove('active');
            DOM.onboardingScreen?.classList.add('hide');
            DOM.onboardingScreen?.classList.remove('active');
            
            enterWorkspace();
        } catch (err) {
            showToast(err.message || 'Registration failed.', 'error');
        }
    });

    // --- Profile Setup Form submission ---
    DOM.profileForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = DOM.profileUsername.value.trim();
        const bio = DOM.profileBio.value.trim();
        const file = DOM.profilePictureInput.files[0];
        
        const formData = new FormData();
        formData.append('username', username);
        formData.append('bio', bio);
        if (file) {
            formData.append('profile_picture', file);
        }
        
        try {
            DOM.profileSubmitBtn.disabled = true;
            DOM.profileSubmitBtn.textContent = 'Updating...';
            
            const data = await apiCall('/api/v1/auth/profile-setup', 'POST', formData, true);
            state.user = data.user;
            localStorage.setItem('whatsapp_user', JSON.stringify(data.user));
            
            // Sync UI
            document.querySelectorAll('.profile-display-username').forEach(el => {
                el.textContent = data.user.username;
            });
            const sidebarAvatar = document.getElementById('sidebar-user-avatar');
            const statusAvatar = document.getElementById('status-user-avatar');
            if (sidebarAvatar && data.user.profile_picture_url) sidebarAvatar.src = data.user.profile_picture_url;
            if (statusAvatar && data.user.profile_picture_url) statusAvatar.src = data.user.profile_picture_url;
            
            showToast('Profile configuration updated!', 'success');
            switchSPAPanel('section-chats');
        } catch (err) {
            showToast(err.message || 'Profile setup failed.', 'error');
        } finally {
            DOM.profileSubmitBtn.disabled = false;
            DOM.profileSubmitBtn.textContent = 'Save Changes';
        }
    });

    DOM.profilePictureInput?.addEventListener('change', () => {
        const file = DOM.profilePictureInput.files[0];
        if (file && DOM.profileAvatarPreview) {
            const reader = new FileReader();
            reader.onload = (e) => DOM.profileAvatarPreview.src = e.target.result;
            reader.readAsDataURL(file);
        }
    });

    // --- Workspace Modals Triggers ---
    DOM.chatsSearchInput?.addEventListener('input', () => filterChatsList());
    
    document.getElementById('action-new-chat')?.addEventListener('click', () => {
        openModal('modal-new-chat');
        DOM.newChatPhone.focus();
    });

    DOM.formInitiateChat?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const phone = DOM.newChatPhone.value.trim();
        try {
            const data = await apiCall('/api/v1/chats/initiate', 'POST', { phone_number: phone });
            closeModal('modal-new-chat');
            DOM.newChatPhone.value = '';
            
            // Reload Chats and auto-select new chat room
            await refreshChatsList();
            selectChat(data.chat.id);
        } catch (err) {
            alert(err.message);
        }
    });

    // Group Setup Modal
    document.getElementById('action-new-group')?.addEventListener('click', () => {
        openModal('modal-new-group');
        loadContactsForGroupSelect();
    });

    DOM.groupIconInput?.addEventListener('change', () => {
        const file = DOM.groupIconInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => DOM.groupIconPreview.src = e.target.result;
            reader.readAsDataURL(file);
        }
    });

    DOM.formCreateGroup?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = DOM.groupNameInput.value.trim();
        const desc = DOM.groupDescInput.value.trim();
        
        const selectedMemberIds = [];
        document.querySelectorAll('.contact-checkbox:checked').forEach(cb => {
            selectedMemberIds.push(parseInt(cb.value));
        });

        if (selectedMemberIds.length === 0) {
            alert('Please select at least 1 member to create group.');
            return;
        }

        const formData = new FormData();
        formData.append('group_name', name);
        formData.append('group_description', desc);
        selectedMemberIds.forEach(id => formData.append('member_ids[]', id));

        const iconFile = DOM.groupIconInput.files[0];
        if (iconFile) {
            formData.append('group_icon', iconFile);
        }

        try {
            const data = await apiCall('/api/v1/groups/create', 'POST', formData, true);
            closeModal('modal-new-group');
            DOM.groupNameInput.value = '';
            DOM.groupDescInput.value = '';
            DOM.groupIconInput.value = '';
            DOM.groupIconPreview.src = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';

            await refreshChatsList();
            selectChat(data.chat.id);
        } catch (err) {
            alert(err.message);
        }
    });

    // Privacy Sidebar slide toggles
    document.getElementById('action-settings')?.addEventListener('click', () => {
        DOM.settingsSlidePanel?.classList.add('active');
        loadPrivacySettings();
    });
    DOM.actionCloseSettings?.addEventListener('click', () => {
        DOM.settingsSlidePanel?.classList.remove('active');
    });

    // Logging out
    document.getElementById('action-logout')?.addEventListener('click', () => {
        if (confirm('Are you sure you want to disconnect from this workspace?')) {
            localStorage.removeItem('whatsapp_token');
            localStorage.removeItem('whatsapp_user');
            state.token = null;
            state.user = null;
            state.activeChat = null;
            if (window.Echo) {
                window.Echo.disconnect();
            }
            DOM.mainWorkspace?.classList.add('hide');
            DOM.onboardingScreen?.classList.remove('hide');
            DOM.onboardingScreen?.classList.add('active');
            toggleAuthTab('signup');
        }
    });

    // Sending messages
    DOM.actionSendMessage?.addEventListener('click', () => sendMessage());
    DOM.messageTextInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Attach File
    DOM.actionAttachFile?.addEventListener('click', () => {
        DOM.mediaAttachmentInput?.click();
    });
    DOM.mediaAttachmentInput?.addEventListener('change', () => {
        const file = DOM.mediaAttachmentInput.files[0];
        if (file) {
            sendMessage(file);
        }
    });

    // Reply close banner
    DOM.actionCloseReply?.addEventListener('click', () => {
        state.replyMessage = null;
        DOM.messageReplyPreview?.classList.add('hide');
    });

    // Mobile back navigation trigger
    DOM.actionRoomBack?.addEventListener('click', () => {
        state.activeChat = null;
        DOM.mainWorkspace?.classList.remove('show-chat');
        document.querySelectorAll('.chat-card').forEach(c => c.classList.remove('active'));
    });

    // Expiring Status stories trigger modal
    DOM.btnShareStatus?.addEventListener('click', () => {
        openModal('modal-share-status');
    });

    document.getElementById('status-type-select')?.addEventListener('change', (e) => {
        if (e.target.value === 'text') {
            document.getElementById('status-text-field')?.classList.remove('hide');
            document.getElementById('status-media-field')?.classList.add('hide');
        } else {
            document.getElementById('status-text-field')?.classList.add('hide');
            document.getElementById('status-media-field')?.classList.remove('hide');
        }
    });

    document.getElementById('form-share-status')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const type = document.getElementById('status-type-select').value;
        const text = document.getElementById('status-text-body').value.trim();
        const fileInput = document.getElementById('status-file-input');
        const caption = document.getElementById('status-caption').value.trim();

        const formData = new FormData();
        formData.append('type', type);

        if (type === 'text') {
            formData.append('content', text);
        } else {
            const file = fileInput.files[0];
            if (!file) {
                alert('Please pick a photo to share.');
                return;
            }
            formData.append('file', file);
            formData.append('caption', caption);
        }

        try {
            await apiCall('/api/v1/status', 'POST', formData, true);
            closeModal('modal-share-status');
            document.getElementById('status-text-body').value = '';
            fileInput.value = '';
            document.getElementById('status-caption').value = '';
            loadStoriesFeed();
        } catch (err) {
            alert(err.message);
        }
    });
}

// ==========================================================================
// WORKSPACE INITIALIZATION
// ==========================================================================
async function enterWorkspace() {
    DOM.onboardingScreen.classList.remove('active');
    DOM.onboardingScreen.classList.add('hide');
    
    DOM.mainWorkspace.classList.remove('hide');
    DOM.mainWorkspace.classList.add('workspace-screen');

    // Populate profile inputs
    if (DOM.profileUsername) DOM.profileUsername.value = state.user.username || '';
    if (DOM.profileBio) DOM.profileBio.value = state.user.bio || '';
    if (DOM.profileAvatarPreview) DOM.profileAvatarPreview.src = state.user.profile_picture_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';

    // Populate Sidebar Header User details (null-safe)
    const sidebarAvatarEl = document.getElementById('sidebar-user-avatar');
    if (sidebarAvatarEl) sidebarAvatarEl.src = state.user.profile_picture_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
    if (DOM.statusUserAvatar) DOM.statusUserAvatar.src = state.user.profile_picture_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
    document.querySelectorAll('.profile-display-username').forEach(el => { el.textContent = state.user.username || 'User'; });

    // Admin buttons visibility check
    const navAdminBtn = document.getElementById('nav-admin-btn');
    const mobileNavAdminBtn = document.getElementById('mobile-nav-admin-btn');
    if (state.user && state.user.role === 'admin') {
        if (navAdminBtn) {
            navAdminBtn.classList.remove('hide');
            navAdminBtn.style.display = 'flex';
        }
        if (mobileNavAdminBtn) {
            mobileNavAdminBtn.classList.remove('hide');
            mobileNavAdminBtn.style.display = 'flex';
        }
    } else {
        if (navAdminBtn) {
            navAdminBtn.classList.add('hide');
            navAdminBtn.style.display = 'none';
        }
        if (mobileNavAdminBtn) {
            mobileNavAdminBtn.classList.add('hide');
            mobileNavAdminBtn.style.display = 'none';
        }
    }

    // 1. Initialize Reverb WebSockets
    initReverbWebSockets();

    // 2. Fetch initial chats row, stories feed
    await refreshChatsList();
    loadStoriesFeed();
}

// ==========================================================================
// WEBSOCKET BROADCASTING INTEGRATION
// ==========================================================================
let _echoRetryTimer = null;

function initReverbWebSockets() {
    const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

    if (_echoRetryTimer) { clearInterval(_echoRetryTimer); _echoRetryTimer = null; }

    try {
        if (typeof Pusher === 'undefined' && typeof window.Pusher === 'undefined') {
            console.warn('[AetherChat] Pusher library not loaded. WebSocket features disabled.');
            showToast('⚡ Live updates not available — Pusher missing.', 'warning');
            return;
        }

        if (!EchoConstructor) {
            console.warn('[AetherChat] Laravel Echo constructor is missing. WebSocket features disabled.');
            showToast('⚡ Live updates not available — Echo missing.', 'warning');
            return;
        }

        window.Echo = new EchoConstructor({
            broadcaster: 'reverb',
            key: 'whatsappreverbkey',
            wsHost: window.location.hostname,
            wsPort: isLocal ? 8080 : 443,
            wssPort: isLocal ? 8080 : 443,
            forceTLS: !isLocal,
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
        });

        _tryJoinPresenceChannel();

    } catch (err) {
        console.warn('[AetherChat] Echo initialization failed:', err.message);
        showToast('⚡ Real-time connection unavailable.', 'warning');
        _scheduleEchoRetry();
    }
}

function _tryJoinPresenceChannel() {
    if (!window.Echo || !window.Echo.connector || !window.Echo.connector.pusher) {
        console.warn('[AetherChat] Echo connector not ready — scheduling retry');
        _scheduleEchoRetry();
        return;
    }

    try {
        window.Echo.join('presence.chat')
            .here((users) => {
                users.forEach(u => state.presenceList.add(u.id));
                updateChatsPresenceUI();
            })
            .joining((user) => {
                state.presenceList.add(user.id);
                updateChatsPresenceUI();
                if (state.activeChat && state.activeChat.type === 'individual') {
                    const recipient = state.activeChat.users.find(u => u.id === user.id);
                    if (recipient) {
                        if (DOM.activeChatSubtitle) DOM.activeChatSubtitle.textContent = 'Online';
                        if (DOM.activeChatSubtitle) DOM.activeChatSubtitle.className = 'user-status-text online';
                    }
                }
            })
            .leaving((user) => {
                state.presenceList.delete(user.id);
                updateChatsPresenceUI();
                if (state.activeChat && state.activeChat.type === 'individual') {
                    const recipient = state.activeChat.users.find(u => u.id === user.id);
                    if (recipient) {
                        if (DOM.activeChatSubtitle) DOM.activeChatSubtitle.textContent = 'offline';
                        if (DOM.activeChatSubtitle) DOM.activeChatSubtitle.className = 'user-status-text';
                    }
                }
            })
            .error((err) => {
                console.warn('[AetherChat] Presence channel error:', err);
                _scheduleEchoRetry();
            });
    } catch (err) {
        console.warn('[AetherChat] Failed to join presence channel:', err.message);
        _scheduleEchoRetry();
    }
}

function _scheduleEchoRetry() {
    if (_echoRetryTimer) return;
    _echoRetryTimer = setInterval(() => {
        if (state.token) {
            console.log('[AetherChat] Retrying Echo connection...');
            _tryJoinPresenceChannel();
        }
    }, 30000);
}

function subscribeToChatRoom(chatId) {
    if (!window.Echo || !window.Echo.connector) {
        return;
    }

    try {
        if (state.subscribedChatId) {
            try { window.Echo.leave(`channel.${state.subscribedChatId}`); } catch(e) {}
        }

        state.subscribedChatId = chatId;

        window.Echo.private(`channel.${chatId}`)
            .listen('.MessageSent', (payload) => {
                const message = payload.message;
                
                if (!state.messages[chatId]) {
                    state.messages[chatId] = [];
                }
                state.messages[chatId].push(message);

                if (state.activeChat && state.activeChat.id === chatId) {
                    appendMessageBubble(message);
                    if (message.sender_id !== state.user.id) {
                        markMessageAsRead(message.id);
                    }
                } else {
                    refreshChatsList();
                }
            })
            .listen('.MessageStatusUpdated', (payload) => {
                if (state.messages[chatId]) {
                    const msg = state.messages[chatId].find(m => m.id === payload.message_id);
                    if (msg) {
                        msg.status = payload.status;
                        if (state.activeChat && state.activeChat.id === chatId) {
                            updateMessageTickUI(payload.message_id, payload.status);
                        }
                    }
                }
            })
            .listen('.MessageDeleted', (payload) => {
                if (state.messages[chatId]) {
                    const msg = state.messages[chatId].find(m => m.id === payload.message_id);
                    if (msg) {
                        msg.is_deleted = true;
                        msg.is_moderated = payload.is_moderated;
                        msg.body = payload.body;
                        if (state.activeChat && state.activeChat.id === chatId) {
                            const row = document.getElementById(`msg-row-${payload.message_id}`);
                            if (row) {
                                row.querySelector('.msg-bubble').innerHTML = `
                                    <p class="msg-body italic text-muted"><span class="material-symbols-outlined text-xs inline-block align-middle mr-1">block</span> ${payload.body}</p>
                                    <div class="msg-footer-info">
                                        <span>${formatTime(new Date(msg.created_at))}</span>
                                    </div>
                                `;
                            }
                        }
                    }
                }
                refreshChatsList();
            })
            .listen('.ReactionUpdated', (payload) => {
                if (state.messages[chatId]) {
                    const msg = state.messages[chatId].find(m => m.id === payload.message_id);
                    if (msg) {
                        msg.reactions = payload.reactions;
                        if (state.activeChat && state.activeChat.id === chatId) {
                            renderMessageReactionsUI(payload.message_id);
                        }
                    }
                }
            });
    } catch (err) {
        console.warn('[AetherChat] Failed to subscribe to chat channel:', err.message);
    }
}

// ==========================================================================
// CORE LAYOUT & BUBBLES RENDERING ENGINE
// ==========================================================================
async function refreshChatsList() {
    try {
        const data = await apiCall('/api/v1/chats');
        state.chats = data.chats;
        
        injectAiAssistantChat();
        renderChatsList();
    } catch (err) {
        console.error('Failed to load chats:', err);
    }
}

function renderChatsList() {
    DOM.chatsListContainer.innerHTML = '';

    if (state.chats.length === 0) {
        DOM.chatsListContainer.innerHTML = '<p class="empty-state py-4 text-center text-slate-400">No conversation started. Click start chat to begin!</p>';
        return;
    }

    state.chats.forEach(chat => {
        const isGroup = chat.type === 'group';
        
        let title = '';
        let avatar = '';
        let recipientId = null;

        if (isGroup) {
            title = chat.group_metadata.group_name;
            avatar = chat.group_metadata.group_icon_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
        } else {
            const recipient = chat.users.find(u => u.id !== state.user.id);
            title = recipient ? recipient.username : 'Unknown Contact';
            avatar = (recipient && recipient.profile_picture_url) ? recipient.profile_picture_url : 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
            recipientId = recipient ? recipient.id : null;
        }

        const isOnline = recipientId && state.presenceList.has(recipientId);
        const isActive = state.activeChat && state.activeChat.id === chat.id;

        let snippet = 'No messages';
        let timeText = '';
        if (chat.latest_message) {
            snippet = chat.latest_message.is_deleted ? 'This message was deleted.' :
                      chat.latest_message.type === 'text' ? chat.latest_message.body : `[Media ${chat.latest_message.type}]`;
            
            const date = new Date(chat.latest_message.created_at);
            timeText = formatTime(date);
        }

        const card = document.createElement('div');
        card.className = `chat-card ${isActive ? 'active' : ''}`;
        card.id = `chat-card-${chat.id}`;
        card.onclick = () => selectChat(chat.id);

        card.innerHTML = `
            <div class="avatar-wrapper">
                <img src="${avatar}" alt="Avatar">
                ${!isGroup ? `<div class="presence-dot ${isOnline ? 'online' : ''}" id="presence-dot-${recipientId}"></div>` : ''}
            </div>
            <div class="card-meta">
                <div class="card-header-row">
                    <span class="card-title">${title}</span>
                    <span class="card-time">${timeText}</span>
                </div>
                <div class="card-body-row">
                    <span class="card-snippet">${snippet}</span>
                    ${chat.unread_count > 0 ? `<span class="unread-badge">${chat.unread_count}</span>` : ''}
                </div>
            </div>
        `;

        DOM.chatsListContainer.appendChild(card);
    });
}

function updateChatsPresenceUI() {
    state.chats.forEach(chat => {
        if (chat.type === 'individual') {
            const recipient = chat.users.find(u => u.id !== state.user.id);
            if (recipient) {
                const dot = document.getElementById(`presence-dot-${recipient.id}`);
                if (dot) {
                    const isOnline = state.presenceList.has(recipient.id);
                    dot.className = `presence-dot ${isOnline ? 'online' : ''}`;
                }
            }
        }
    });
}

async function selectChat(chatId) {
    const chat = state.chats.find(c => c.id === chatId);
    if (!chat) return;

    if (chatId === 'bot') {
        state.activeChat = chat;
        
        document.querySelectorAll('.chat-card').forEach(c => c.classList.remove('active'));
        const activeCard = document.getElementById(`chat-card-bot`);
        if (activeCard) activeCard.classList.add('active');

        DOM.roomWelcomeSplash.classList.remove('active');
        DOM.roomWelcomeSplash.classList.add('hide');
        DOM.mainWorkspace.classList.add('show-chat');

        DOM.activeChatAvatar.src = chat.users[0].profile_picture_url;
        DOM.activeChatTitle.textContent = chat.users[0].username;
        DOM.activeChatSubtitle.textContent = 'Online';
        DOM.activeChatSubtitle.className = 'user-status-text online';

        state.replyMessage = null;
        DOM.messageReplyPreview.classList.add('hide');

        DOM.messagesListWrapper.innerHTML = '';
        const botMsgs = getAiAssistantMessages();
        state.messages['bot'] = botMsgs;
        
        botMsgs.forEach(msg => {
            appendMessageBubble(msg);
        });
        scrollChatToBottom();
        return;
    }

    state.activeChat = chat;
    
    document.querySelectorAll('.chat-card').forEach(c => c.classList.remove('active'));
    const activeCard = document.getElementById(`chat-card-${chatId}`);
    if (activeCard) activeCard.classList.add('active');

    chat.unread_count = 0;
    const badge = activeCard?.querySelector('.unread-badge');
    if (badge) badge.remove();

    DOM.roomWelcomeSplash.classList.remove('active');
    DOM.roomWelcomeSplash.classList.add('hide');
    DOM.mainWorkspace.classList.add('show-chat');

    const isGroup = chat.type === 'group';
    let title = '';
    let avatar = '';
    let statusText = 'offline';

    if (isGroup) {
        title = chat.group_metadata.group_name;
        avatar = chat.group_metadata.group_icon_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
        statusText = `${chat.users.length} members`;
    } else {
        const recipient = chat.users.find(u => u.id !== state.user.id);
        title = recipient ? recipient.username : 'Unknown Contact';
        avatar = (recipient && recipient.profile_picture_url) ? recipient.profile_picture_url : 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
        statusText = recipient && state.presenceList.has(recipient.id) ? 'Online' : 'offline';
    }

    DOM.activeChatAvatar.src = avatar;
    DOM.activeChatTitle.textContent = title;
    DOM.activeChatSubtitle.textContent = statusText;
    DOM.activeChatSubtitle.className = `user-status-text ${statusText === 'Online' ? 'online' : ''}`;

    state.replyMessage = null;
    DOM.messageReplyPreview.classList.add('hide');

    subscribeToChatRoom(chatId);

    DOM.messagesListWrapper.innerHTML = '<p class="empty-state text-slate-400 text-center py-4">Decrypting chats...</p>';
    try {
        const data = await apiCall(`/api/v1/chats/${chatId}/messages`);
        state.messages[chatId] = data.messages;
        
        DOM.messagesListWrapper.innerHTML = '';
        if (data.messages.length === 0) {
            DOM.messagesListWrapper.innerHTML = '<p class="empty-state text-slate-400 text-center py-4"><span class="material-symbols-outlined text-xs inline-block align-middle mr-1">lock</span> Secured workspace logs.</p>';
        } else {
            data.messages.forEach(msg => {
                appendMessageBubble(msg);
                if (msg.sender_id !== state.user.id && msg.status !== 'read') {
                    markMessageAsRead(msg.id);
                }
            });
        }
        scrollChatToBottom();
    } catch (err) {
        DOM.messagesListWrapper.innerHTML = `<p class="empty-state text-red-500 text-center py-4">Failed to decrypt messages: ${err.message}</p>`;
    }
}

function appendMessageBubble(msg) {
    const isSent = msg.sender_id === state.user.id;
    
    const row = document.createElement('div');
    row.className = `msg-bubble-row ${isSent ? 'sent' : 'received'}`;
    row.id = `msg-row-${msg.id}`;

    const timeText = formatTime(new Date(msg.created_at));

    let ticksHtml = '';
    if (isSent) {
        const isRead = msg.status === 'read';
        const isDelivered = msg.status === 'delivered' || isRead;
        ticksHtml = `
            <span class="status-ticks ${isRead ? 'read' : ''}" id="tick-${msg.id}">
                <span class="material-symbols-outlined text-[10px] font-bold inline-block align-middle">${isDelivered ? 'done_all' : 'done'}</span>
            </span>
        `;
    }

    let replyContextHtml = '';
    if (msg.parent_message && !msg.is_deleted) {
        const parentSender = msg.parent_message.sender_id === state.user.id ? 'You' : msg.parent_message.sender?.username || 'User';
        const snippet = msg.parent_message.is_deleted ? 'This message was deleted.' :
                         msg.parent_message.type === 'text' ? msg.parent_message.body : `[Media ${msg.parent_message.type}]`;
        replyContextHtml = `
            <div class="bubble-reply-context">
                <span class="reply-context-sender font-bold block text-[10px] text-teal-600 mb-0.5">${parentSender}</span>
                <p class="text-xs truncate">${snippet}</p>
            </div>
        `;
    }

    let bodyHtml = '';
    if (msg.is_deleted) {
        bodyHtml = `<p class="msg-body italic text-slate-400"><span class="material-symbols-outlined text-xs inline-block align-middle mr-1">block</span> ${msg.body}</p>`;
    } else if (msg.type === 'text') {
        bodyHtml = `<p class="msg-body">${msg.body}</p>`;
    } else if (msg.type === 'image') {
        bodyHtml = `
            <div class="msg-media cursor-pointer">
                <img src="${msg.body}" alt="Shared Image" onclick="window.open('${msg.body}')">
            </div>
            ${msg.caption ? `<p class="msg-body text-xs mt-1">${msg.caption}</p>` : ''}
        `;
    } else if (msg.type === 'video') {
        bodyHtml = `
            <div class="msg-media">
                <video src="${msg.body}" controls></video>
            </div>
            ${msg.caption ? `<p class="msg-body text-xs mt-1">${msg.caption}</p>` : ''}
        `;
    } else if (msg.type === 'audio') {
        bodyHtml = `
            <div class="voice-note-card">
                <button class="voice-play-btn" onclick="togglePlayVoiceNote(this, '${msg.body}')">
                    <span class="material-symbols-outlined text-[16px]">play_arrow</span>
                </button>
                <div class="voice-waveform">
                    <div class="wave-bar" style="height: 40%"></div>
                    <div class="wave-bar" style="height: 60%"></div>
                    <div class="wave-bar" style="height: 80%"></div>
                    <div class="wave-bar" style="height: 50%"></div>
                    <div class="wave-bar" style="height: 70%"></div>
                    <div class="wave-bar" style="height: 90%"></div>
                    <div class="wave-bar" style="height: 60%"></div>
                    <div class="wave-bar" style="height: 40%"></div>
                    <div class="wave-bar" style="height: 30%"></div>
                    <div class="wave-bar" style="height: 50%"></div>
                </div>
                <audio src="${msg.body}" class="hide voice-note-audio"></audio>
            </div>
        `;
    } else {
        bodyHtml = `
            <a href="${msg.body}" target="_blank" class="msg-doc-preview hover:bg-slate-100 transition-colors">
                <span class="material-symbols-outlined text-teal-600 text-[20px]">description</span>
                <span>Workspace Attachment</span>
            </a>
        `;
    }

    row.innerHTML = `
        <div class="msg-bubble" ondblclick="triggerReply('${msg.id}')">
            ${!msg.is_deleted ? `<div class="msg-actions-trigger" onclick="toggleMsgDropdown(event, '${msg.id}')"><span class="material-symbols-outlined text-[12px]">keyboard_arrow_down</span></div>` : ''}
            
            ${!msg.is_deleted ? `<div class="msg-reaction-trigger" onclick="toggleReactionsPopover(event, '${msg.id}')"><span class="material-symbols-outlined text-[12px]">add_reaction</span></div>` : ''}
            
            ${!msg.is_deleted && state.activeChat && state.activeChat.id !== 'bot' ? `<div class="msg-forward-trigger" onclick="openForwardModal('${msg.id}', event)" title="Forward Message"><span class="material-symbols-outlined text-[12px]">forward</span></div>` : ''}
            
            <div class="reaction-popover" id="reaction-popover-${msg.id}">
                <button onclick="addMessageReaction('${msg.id}', '👍', event)">👍</button>
                <button onclick="addMessageReaction('${msg.id}', '❤️', event)">❤️</button>
                <button onclick="addMessageReaction('${msg.id}', '😂', event)">😂</button>
                <button onclick="addMessageReaction('${msg.id}', '😮', event)">😮</button>
                <button onclick="addMessageReaction('${msg.id}', '😢', event)">😢</button>
                <button onclick="addMessageReaction('${msg.id}', '🙏', event)">🙏</button>
            </div>

            <div class="msg-dropdown-menu" id="msg-dropdown-${msg.id}">
                <button onclick="triggerReply('${msg.id}')"><span class="material-symbols-outlined text-xs">reply</span> Reply</button>
                ${isSent && !msg.is_deleted ? `<button class="delete-btn" onclick="triggerDelete('${msg.id}')"><span class="material-symbols-outlined text-xs">delete</span> Delete</button>` : ''}
            </div>

            ${state.activeChat && state.activeChat.type === 'group' && !isSent && !msg.is_deleted ? `<span class="msg-sender-name">${msg.sender?.username || 'User'}</span>` : ''}
            
            ${replyContextHtml}
            ${bodyHtml}
            
            <div class="msg-footer-info">
                <span>${timeText}</span>
                ${ticksHtml}
            </div>
            
            ${!isSent && !msg.is_deleted && msg.type === 'text' && state.activeChat && state.activeChat.id !== 'bot' ? `
                <button class="ai-reply-trigger-btn flex items-center gap-1 text-[10px] text-teal-600 font-bold bg-teal-50 hover:bg-teal-100 rounded-lg px-2.5 py-1 mt-2.5 border border-teal-200 shadow-sm transition-all" onclick="triggerAiSmartReply('${msg.id}')">
                    <span>🤖 Ask AI Assistant</span>
                </button>
            ` : ''}
            
            <div class="bubble-reactions-row" id="reactions-row-${msg.id}"></div>
        </div>
    `;

    DOM.messagesListWrapper.appendChild(row);
    
    setTimeout(() => {
        renderMessageReactionsUI(msg.id);
    }, 0);
    
    scrollChatToBottom();
}

function scrollChatToBottom() {
    DOM.messagesListWrapper.scrollTop = DOM.messagesListWrapper.scrollHeight;
}

// ==========================================================================
// SENDING & STATUS TAPPING METHODS
// ==========================================================================
async function sendMessage(file = null) {
    if (!state.activeChat) return;
    const text = DOM.messageTextInput.value.trim();
    if (!text && !file) return;

    if (state.activeChat && state.activeChat.id === 'bot') {
        DOM.messageTextInput.value = '';
        state.replyMessage = null;
        DOM.messageReplyPreview.classList.add('hide');

        const userMsg = {
            id: 'bot-user-' + Date.now(),
            sender_id: state.user.id,
            body: text || '[Attachment shared]',
            type: text ? 'text' : 'image',
            created_at: new Date().toISOString(),
            status: 'read'
        };

        if (!state.messages['bot']) {
            state.messages['bot'] = [];
        }
        state.messages['bot'].push(userMsg);
        saveAiAssistantMessages(state.messages['bot']);
        appendMessageBubble(userMsg);

        injectAiAssistantChat();
        renderChatsList();

        triggerAiAssistantReply(text || '');
        return;
    }

    const formData = new FormData();
    formData.append('chat_id', state.activeChat.id);
    
    if (text) {
        formData.append('body', text);
    }
    
    if (file) {
        formData.append('file', file);
    }

    if (state.replyMessage) {
        formData.append('parent_message_id', state.replyMessage.id);
    }

    DOM.messageTextInput.value = '';
    DOM.mediaAttachmentInput.value = '';
    state.replyMessage = null;
    DOM.messageReplyPreview.classList.add('hide');

    try {
        const data = await apiCall('/api/v1/messages/send', 'POST', formData, true);
        
        if (!state.messages[state.activeChat.id]) {
            state.messages[state.activeChat.id] = [];
        }
        state.messages[state.activeChat.id].push(data.data);
        appendMessageBubble(data.data);
        refreshChatsList();
    } catch (err) {
        alert('Failed to transmit message: ' + err.message);
    }
}

async function markMessageAsRead(messageId) {
    try {
        await apiCall(`/api/v1/messages/${messageId}/status`, 'PUT', { status: 'read' });
    } catch (err) {
        console.error('Failed to mark read receipt:', err);
    }
}

function updateMessageTickUI(messageId, status) {
    const tick = document.getElementById(`tick-${messageId}`);
    if (tick) {
        const isRead = status === 'read';
        tick.className = `status-ticks ${isRead ? 'read' : ''}`;
        tick.innerHTML = `<span class="material-symbols-outlined text-[10px] font-bold inline-block align-middle">${isRead || status === 'delivered' ? 'done_all' : 'done'}</span>`;
    }
}

// ==========================================================================
// MESSAGE INTERACTION DROPDOWNS (REPLIES & SOFT DELETION)
// ==========================================================================
function toggleMsgDropdown(event, msgId) {
    event.stopPropagation();
    
    document.querySelectorAll('.msg-dropdown-menu').forEach(menu => {
        if (menu.id !== `msg-dropdown-${msgId}`) {
            menu.classList.remove('active');
        }
    });

    const menu = document.getElementById(`msg-dropdown-${msgId}`);
    if (menu) {
        menu.classList.toggle('active');
    }

    document.addEventListener('click', function closeMenu() {
        if (menu) menu.classList.remove('active');
        document.removeEventListener('click', closeMenu);
    });
}

function triggerReply(msgId) {
    const chatMsgs = state.messages[state.activeChat.id];
    const msg = chatMsgs.find(m => m.id === msgId);
    if (!msg) return;

    state.replyMessage = msg;

    const sender = msg.sender_id === state.user.id ? 'You' : msg.sender?.username || 'User';
    const snippet = msg.is_deleted ? 'This message was deleted.' :
                    msg.type === 'text' ? msg.body : `[Media ${msg.type}]`;

    DOM.replyPreviewSender.textContent = `Replying to ${sender}`;
    DOM.replyPreviewSnippet.textContent = snippet;
    DOM.messageReplyPreview.classList.remove('hide');
    DOM.messageTextInput.focus();
}

async function triggerDelete(msgId) {
    if (confirm('Delete this message for everyone?')) {
        try {
            await apiCall(`/api/v1/messages/${msgId}`, 'DELETE');
            
            const chatMsgs = state.messages[state.activeChat.id];
            const msg = chatMsgs.find(m => m.id === msgId);
            if (msg) {
                selectChat(state.activeChat.id);
            }
            refreshChatsList();
        } catch (err) {
            alert('Failed to retract message.');
        }
    }
}

// ==========================================================================
// GRANULAR PRIVACY SETTINGS CONFIGURES
// ==========================================================================
async function loadPrivacySettings() {
    try {
        const data = await apiCall('/api/v1/settings/privacy');
        const settings = data.settings;

        DOM.settingReadReceipts.checked = settings.read_receipts;
        DOM.settingLastSeen.value = settings.privacy_last_seen;
        DOM.settingProfilePhoto.value = settings.privacy_profile_photo;
        DOM.settingAbout.value = settings.privacy_about;

        DOM.setting2faToggle.checked = settings.two_factor_enabled;
        if (settings.two_factor_enabled) {
            DOM.panel2faSetup.classList.remove('hide');
        } else {
            DOM.panel2faSetup.classList.add('hide');
        }

        loadBlockedUsersList();
    } catch (err) {
        console.error('Failed to load settings:', err);
    }
}

DOM.settingReadReceipts.addEventListener('change', () => updatePrivacySetting());
DOM.settingLastSeen.addEventListener('change', () => updatePrivacySetting());
DOM.settingProfilePhoto.addEventListener('change', () => updatePrivacySetting());
DOM.settingAbout.addEventListener('change', () => updatePrivacySetting());

DOM.setting2faToggle.addEventListener('change', (e) => {
    if (e.target.checked) {
        DOM.panel2faSetup.classList.remove('hide');
    } else {
        apiCall('/api/v1/settings/account/two-factor', 'PUT', { enable: false })
            .then(() => {
                DOM.panel2faSetup.classList.add('hide');
                DOM.settings2faPin.value = '';
                alert('Two-factor PIN disabled.');
            });
    }
});

DOM.actionSave2fa.addEventListener('click', async () => {
    const pin = DOM.settings2faPin.value.trim();
    if (pin.length !== 6 || isNaN(pin)) {
        alert('Please enter a valid 6-digit numeric PIN.');
        return;
    }

    try {
        await apiCall('/api/v1/settings/account/two-factor', 'PUT', { enable: true, pin: pin });
        alert('Two-factor PIN enabled successfully.');
        DOM.settings2faPin.value = '';
    } catch (err) {
        alert(err.message);
    }
});

async function updatePrivacySetting() {
    const payload = {
        read_receipts: DOM.settingReadReceipts.checked,
        privacy_last_seen: DOM.settingLastSeen.value,
        privacy_profile_photo: DOM.settingProfilePhoto.value,
        privacy_about: DOM.settingAbout.value
    };

    try {
        await apiCall('/api/v1/settings/privacy', 'PUT', payload);
    } catch (err) {
        console.error('Failed to update privacy settings:', err);
    }
}

async function loadBlockedUsersList() {
    DOM.blockedUsersList.innerHTML = `
        <div class="space-y-3 mt-4">
            <div class="flex flex-col gap-1.5">
                <label for="block-new-phone" class="text-xs text-slate-500 font-bold uppercase">Block User by ID</label>
                <div class="flex gap-2">
                    <input type="text" id="block-new-phone" placeholder="Enter target user ID" class="w-full border border-slate-300 rounded-xl px-3 py-1.5 text-xs focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    <button onclick="blockContactById()" class="bg-slate-900 text-white font-semibold text-xs px-4 py-1.5 rounded-xl hover:bg-slate-800 transition-all">Block</button>
                </div>
            </div>
        </div>
    `;
}

async function blockContactById() {
    const userIdInput = document.getElementById('block-new-phone');
    const id = userIdInput.value.trim();
    if (!id) return;

    try {
        await apiCall('/api/v1/settings/block', 'POST', { blocked_user_id: id });
        userIdInput.value = '';
        alert('User blocked successfully.');
        loadBlockedUsersList();
    } catch (err) {
        alert(err.message);
    }
}

// ==========================================================================
// GROUP CREATOR CONTACT SELECTIONS
// ==========================================================================
function loadContactsForGroupSelect() {
    DOM.groupContactsMultiselect.innerHTML = '';
    
    const contacts = [];
    state.chats.forEach(c => {
        if (c.type === 'individual' && c.id !== 'bot') {
            const recipient = c.users.find(u => u.id !== state.user.id);
            if (recipient) contacts.push(recipient);
        }
    });

    if (contacts.length === 0) {
        DOM.groupContactsMultiselect.innerHTML = '<p class="empty-state text-slate-400 text-xs italic py-2 text-center">No active direct contacts found.</p>';
        return;
    }

    contacts.forEach(contact => {
        const row = document.createElement('div');
        row.className = 'flex items-center justify-between p-2 bg-white rounded-lg border border-slate-100 hover:bg-slate-50 transition-colors mb-1';
        row.innerHTML = `
            <div class="flex items-center gap-2">
                <img src="${contact.profile_picture_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y'}" alt="Avatar" class="w-7 h-7 rounded-full object-cover">
                <span class="text-xs font-bold text-slate-800">${contact.username}</span>
            </div>
            <input type="checkbox" value="${contact.id}" class="contact-checkbox rounded text-teal-600 focus:ring-teal-500 w-4 h-4">
        `;
        DOM.groupContactsMultiselect.appendChild(row);
    });
}

// ==========================================================================
// WHATSAPP STORIES STATUS FEED
// ==========================================================================
async function loadStoriesFeed() {
    try {
        const data = await apiCall('/api/v1/status');
        state.activeStatusFeed = data.statuses;
        renderStoriesFeed();
    } catch (err) {
        console.error('Failed to load status updates:', err);
    }
}

function renderStoriesFeed() {
    DOM.activeContactsStories.innerHTML = '';

    if (state.activeStatusFeed.length === 0) {
        return;
    }

    const storiesByUser = {};
    state.activeStatusFeed.forEach(status => {
        const userId = status.user.id;
        if (!storiesByUser[userId]) {
            storiesByUser[userId] = [];
        }
        storiesByUser[userId].push(status);
    });

    Object.keys(storiesByUser).forEach(userId => {
        const userStories = storiesByUser[userId];
        const latestStory = userStories[0];
        const contact = latestStory.user;

        const container = document.createElement('div');
        container.className = 'story-item shrink-0 cursor-pointer flex flex-col items-center';
        container.onclick = () => launchStatusViewer(userStories);

        container.innerHTML = `
            <div class="story-circle ring-active w-11 h-11 rounded-full p-0.5 border border-teal-500">
                <img src="${contact.profile_picture_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y'}" alt="Avatar" class="w-full h-full rounded-full object-cover">
            </div>
            <span class="text-[10px] font-semibold mt-1 text-slate-700 truncate w-14 text-center">${contact.username}</span>
        `;

        DOM.activeContactsStories.appendChild(container);
    });
}

function launchStatusViewer(userStories) {
    let currentIndex = 0;
    DOM.modalStatusViewer.classList.add('active');

    function renderActiveStatus() {
        const story = userStories[currentIndex];
        
        DOM.statusViewerAvatar.src = story.user.profile_picture_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
        DOM.statusViewerName.textContent = story.user.username;
        DOM.statusViewerTime.textContent = formatTime(new Date(story.created_at));

        if (story.type === 'text') {
            DOM.statusViewTextScreen.classList.remove('hide');
            DOM.statusViewImageScreen.classList.add('hide');
            DOM.statusViewTextBody.textContent = story.content;
        } else {
            DOM.statusViewImageScreen.classList.remove('hide');
            DOM.statusViewTextScreen.classList.add('hide');
            DOM.statusViewImageElement.src = story.content;
            DOM.statusViewCaptionElement.textContent = story.caption || '';
        }

        DOM.statusTimerBar.style.width = '0%';
        DOM.statusTimerBar.style.transition = 'none';
        DOM.statusTimerBar.offsetHeight;

        DOM.statusTimerBar.style.transition = 'width 5000ms linear';
        DOM.statusTimerBar.style.width = '100%';

        clearTimeout(state.statusViewerTimer);

        state.statusViewerTimer = setTimeout(() => {
            if (currentIndex < userStories.length - 1) {
                currentIndex++;
                renderActiveStatus();
            } else {
                closeStatusViewer();
            }
        }, 5000);
    }

    renderActiveStatus();
}

function closeStatusViewer() {
    clearTimeout(state.statusViewerTimer);
    DOM.modalStatusViewer.classList.remove('active');
}

// ==========================================================================
// CORE SYSTEM UTILITY & TIME FORMATTING HELPER
// ==========================================================================
function formatTime(date) {
    let hours = date.getHours();
    let minutes = date.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    hours = hours % 12;
    hours = hours ? hours : 12;
    minutes = minutes < 10 ? '0' + minutes : minutes;
    
    return `${hours}:${minutes} ${ampm}`;
}

function filterChatsList() {
    const filter = DOM.chatsSearchInput.value.toLowerCase();
    document.querySelectorAll('.chat-card').forEach(card => {
        const title = card.querySelector('.card-title').textContent.toLowerCase();
        const snippet = card.querySelector('.card-snippet').textContent.toLowerCase();
        
        if (title.includes(filter) || snippet.includes(filter)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}

// ==========================================================================
// AUTHENTICATION & STYLE HOOKS - FONTS & THEMES
// ==========================================================================
window.showAuthFromLanding = function(isLogin) {
    const landing = document.getElementById('guest-landing-screen');
    const auth = document.getElementById('onboarding-screen');
    if (landing) landing.classList.add('hide');
    if (auth) {
        auth.classList.remove('hide');
        auth.classList.add('active');
    }
    toggleAuthTab(isLogin ? 'login' : 'signup');
};

window.goBackToLanding = function() {
    const landing = document.getElementById('guest-landing-screen');
    const auth = document.getElementById('onboarding-screen');
    if (landing) landing.classList.remove('hide');
    if (auth) {
        auth.classList.add('hide');
        auth.classList.remove('active');
    }
};

window.toggleAuthTab = function(mode) {
    const tabGuest = document.getElementById('tab-guest');
    const tabLogin = document.getElementById('tab-login');
    const tabSignup = document.getElementById('tab-signup');
    const panelGuest = document.getElementById('auth-guest-panel');
    const panelLogin = document.getElementById('auth-login-panel');
    const panelSignup = document.getElementById('auth-signup-panel');
    
    state.authMode = mode;
    
    // Reset all tabs
    [tabGuest, tabLogin, tabSignup].forEach(tab => {
        if (tab) {
            tab.className = "flex-1 text-center py-1.5 font-bold rounded-lg text-xs text-slate-500 hover:text-slate-800 transition-all";
        }
    });
    
    // Hide all panels
    [panelGuest, panelLogin, panelSignup].forEach(panel => {
        if (panel) {
            panel.classList.add('hide');
            panel.classList.remove('active');
        }
    });
    
    if (mode === 'guest') {
        tabGuest.className = "flex-1 text-center py-1.5 font-bold rounded-lg text-xs bg-teal-600 text-white transition-all";
        panelGuest?.classList.remove('hide');
        panelGuest?.classList.add('active');
    } else if (mode === 'login') {
        tabLogin.className = "flex-1 text-center py-1.5 font-bold rounded-lg text-xs bg-teal-600 text-white transition-all";
        panelLogin?.classList.remove('hide');
        panelLogin?.classList.add('active');
    } else if (mode === 'signup') {
        tabSignup.className = "flex-1 text-center py-1.5 font-bold rounded-lg text-xs bg-teal-600 text-white transition-all";
        panelSignup?.classList.remove('hide');
        panelSignup?.classList.add('active');
    }
};

window.changeGlobalFont = function(fontName) {
    document.body.classList.remove('font-nunito', 'font-baloo', 'font-fredoka', 'font-poppins');
    document.body.classList.add(`font-${fontName}`);
    localStorage.setItem('doodle_font', fontName);
};

window.changeGlobalTheme = function(themeName) {
    document.body.classList.remove('theme-plain', 'theme-yellow', 'theme-blue', 'theme-pink', 'theme-mint');
    document.body.classList.add(`theme-${themeName}`);
    localStorage.setItem('doodle_theme', themeName);
};

// ==========================================================================
// AETHER AI ASSISTANT CHAT DATA AND RESPONSE LOGIC
// ==========================================================================
window.getAiAssistantMessages = function() {
    const key = `aether_ai_messages_${state.user ? state.user.id : 'guest'}`;
    const stored = localStorage.getItem(key);
    if (stored) {
        return JSON.parse(stored);
    }
    
    const defaults = [
        {
            id: 'bot-welcome',
            sender_id: 'bot',
            body: "Welcome! I'm Aether AI, your virtual assistant. 🤖 I can help you with programming queries, productivity tips, or general information. Try asking for a 'productivity tip' or 'code design pattern'!",
            type: 'text',
            created_at: new Date().toISOString(),
            status: 'read'
        }
    ];
    localStorage.setItem(key, JSON.stringify(defaults));
    return defaults;
};

window.saveAiAssistantMessages = function(messages) {
    const key = `aether_ai_messages_${state.user ? state.user.id : 'guest'}`;
    localStorage.setItem(key, JSON.stringify(messages));
};

window.injectAiAssistantChat = function() {
    const hasBot = state.chats.some(c => c.id === 'bot');
    const botMessages = getAiAssistantMessages();
    const latestMsg = botMessages.length > 0 ? botMessages[botMessages.length - 1] : null;
    
    const botChat = {
        id: 'bot',
        type: 'individual',
        users: [
            {
                id: 'bot',
                username: 'Aether AI Assistant 🤖',
                profile_picture_url: 'https://www.gravatar.com/avatar/aetherbot?d=identicon&f=y',
                bio: 'Secure workspace virtual assistant.'
            }
        ],
        latest_message: latestMsg ? {
            body: latestMsg.body,
            type: latestMsg.type,
            created_at: latestMsg.created_at,
            is_deleted: false
        } : {
            body: "Aether AI Assistant. Ask for tips or patterns.",
            type: 'text',
            created_at: new Date().toISOString(),
            is_deleted: false
        },
        unread_count: 0
    };
    
    if (!hasBot) {
        state.chats.unshift(botChat);
    } else {
        const index = state.chats.findIndex(c => c.id === 'bot');
        state.chats[index].latest_message = botChat.latest_message;
    }
};

window.triggerAiAssistantReply = function(userText) {
    showTypingIndicator(true);
    
    setTimeout(() => {
        showTypingIndicator(false);
        
        let responseText = "";
        const query = userText.toLowerCase();
        
        const productivityTips = [
            "Productivity Tip: Try block scheduling. Set aside specific, uninterrupted blocks of time (e.g., 90 minutes) for deep focus work, followed by a 15-minute recess. This maximizes cognitive focus.",
            "Productivity Tip: Try the Pomodoro Technique. Focus on a single task for 25 minutes, then take a 5-minute break. This keeps your mind fresh and avoids burnout.",
            "Productivity Tip: Practice Inbox Zero. Process emails and notifications at scheduled intervals rather than leaving them open all day to minimize cognitive distractions."
        ];
        
        const patterns = [
            "Architecture Pattern Tip: The Repository Pattern decouples your app's business logic from data access queries. In Laravel, binding an interface to a concrete repository implementation in a Service Provider makes switching drivers simple!",
            "Architecture Pattern Tip: Use the Service Layer pattern in Laravel to keep your controllers clean. Extract business logic from controllers into dedicated Service classes, keeping controllers focused on parsing requests and returning responses.",
            "Architecture Pattern Tip: The Singleton pattern ensures a class has only one instance and provides a global point of access to it. Ideal for shared connections like database or logging services."
        ];
        
        if (query.includes('tip') || query.includes('productivity') || query.includes('focus')) {
            const randIndex = Math.floor(Math.random() * productivityTips.length);
            responseText = "Here is a productivity tip for you: 💡\n" + productivityTips[randIndex];
        } else if (query.includes('pattern') || query.includes('code') || query.includes('design') || query.includes('architecture')) {
            const randIndex = Math.floor(Math.random() * patterns.length);
            responseText = "Here is an architecture tip: 💻\n" + patterns[randIndex];
        } else if (query.includes('help') || query.includes('command') || query.includes('info')) {
            responseText = "I can assist you with:\n- Productivity tips 💡\n- Code design patterns 💻\n- General conversational support 💬";
        } else {
            responseText = "Hello! I am Aether AI 🤖, your virtual helper. How can I assist you today? Feel free to ask for a 'productivity tip' or a 'design pattern'!";
        }
        
        const botMsg = {
            id: 'bot-response-' + Date.now(),
            sender_id: 'bot',
            body: responseText,
            type: 'text',
            created_at: new Date().toISOString(),
            status: 'read'
        };
        
        if (!state.messages['bot']) state.messages['bot'] = [];
        state.messages['bot'].push(botMsg);
        saveAiAssistantMessages(state.messages['bot']);
        
        if (state.activeChat && state.activeChat.id === 'bot') {
            appendMessageBubble(botMsg);
        }
        
        injectAiAssistantChat();
        renderChatsList();
    }, 1200);
};

function showTypingIndicator(show) {
    const existing = document.getElementById('doodlebot-typing-indicator');
    if (existing) existing.remove();
    
    if (show) {
        const row = document.createElement('div');
        row.className = 'msg-bubble-row received';
        row.id = 'doodlebot-typing-indicator';
        row.innerHTML = `
            <div class="typing-indicator">
                <span>Aether AI is thinking</span>
                <div class="typing-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        `;
        DOM.messagesListWrapper.appendChild(row);
        scrollChatToBottom();
    }
}

// ==========================================================================
// VOICE NOTE WAVEFORM AUDIO PLAYER
// ==========================================================================
window.togglePlayVoiceNote = function(btn, audioUrl) {
    const card = btn.closest('.voice-note-card');
    const audio = card.querySelector('.voice-note-audio');
    const waveform = card.querySelector('.voice-waveform');
    const icon = btn.querySelector('span');
    
    document.querySelectorAll('.voice-note-audio').forEach(aud => {
        if (aud !== audio) {
            aud.pause();
            aud.currentTime = 0;
            const otherCard = aud.closest('.voice-note-card');
            const otherBtnIcon = otherCard.querySelector('.voice-play-btn span');
            const otherWaveform = otherCard.querySelector('.voice-waveform');
            if (otherBtnIcon) otherBtnIcon.textContent = 'play_arrow';
            if (otherWaveform) {
                otherWaveform.classList.remove('playing');
                otherWaveform.querySelectorAll('.wave-bar').forEach(bar => bar.classList.remove('played'));
            }
        }
    });
    
    if (audio.paused) {
        audio.play();
        icon.textContent = 'pause';
        waveform.classList.add('playing');
        
        audio.ontimeupdate = () => {
            const progress = audio.currentTime / audio.duration;
            const bars = waveform.querySelectorAll('.wave-bar');
            const barsToLight = Math.floor(progress * bars.length);
            bars.forEach((bar, idx) => {
                if (idx <= barsToLight) {
                    bar.classList.add('played');
                } else {
                    bar.classList.remove('played');
                }
            });
        };
        
        audio.onended = () => {
            icon.textContent = 'play_arrow';
            waveform.classList.remove('playing');
            waveform.querySelectorAll('.wave-bar').forEach(bar => bar.classList.remove('played'));
        };
    } else {
        audio.pause();
        icon.textContent = 'play_arrow';
        waveform.classList.remove('playing');
    }
};

// ==========================================================================
// CLIENT MESSAGE STICKER REACTIONS
// ==========================================================================
window.toggleReactionsPopover = function(event, msgId) {
    event.stopPropagation();
    
    document.querySelectorAll('.reaction-popover').forEach(popover => {
        if (popover.id !== `reaction-popover-${msgId}`) {
            popover.classList.remove('active');
        }
    });

    const popover = document.getElementById(`reaction-popover-${msgId}`);
    if (popover) {
        popover.classList.toggle('active');
    }
    
    document.addEventListener('click', function closeReactions() {
        if (popover) popover.classList.remove('active');
        document.removeEventListener('click', closeReactions);
    });
};

window.addMessageReaction = async function(msgId, emoji, event) {
    if (event) event.stopPropagation();
    
    const popover = document.getElementById(`reaction-popover-${msgId}`);
    if (popover) popover.classList.remove('active');
    
    try {
        const data = await apiCall(`/api/v1/messages/${msgId}/react`, 'POST', { emoji });
        
        const chatId = state.activeChat ? state.activeChat.id : null;
        if (chatId && state.messages[chatId]) {
            const msg = state.messages[chatId].find(m => m.id == msgId);
            if (msg) {
                msg.reactions = data.reactions;
            }
        }
        
        renderMessageReactionsUI(msgId);
    } catch (err) {
        console.error('Failed to react to message:', err);
    }
};

window.renderMessageReactionsUI = function(msgId) {
    const container = document.getElementById(`reactions-row-${msgId}`);
    if (!container) return;
    
    container.innerHTML = '';
    
    const chatId = state.activeChat ? state.activeChat.id : null;
    if (!chatId || !state.messages[chatId]) return;
    
    const msg = state.messages[chatId].find(m => m.id == msgId);
    if (!msg || !msg.reactions) return;
    
    const counts = {};
    msg.reactions.forEach(r => {
        counts[r.emoji] = (counts[r.emoji] || 0) + 1;
    });
    
    Object.keys(counts).forEach(emoji => {
        const badge = document.createElement('div');
        badge.className = 'bubble-reaction cursor-pointer hover:scale-105 transition-transform';
        badge.innerHTML = `<span>${emoji}</span>${counts[emoji] > 1 ? `<span class="text-[10px] ml-1 font-black">${counts[emoji]}</span>` : ''}`;
        badge.onclick = (e) => addMessageReaction(msgId, emoji, e);
        container.appendChild(badge);
    });
};

// ==========================================================================
// MESSAGE FORWARDING MODAL AND ACTION
// ==========================================================================
window.openForwardModal = function(msgId, event) {
    if (event) event.stopPropagation();
    state.forwardingMessageId = msgId;
    
    const listContainer = document.getElementById('forward-channels-list');
    if (!listContainer) return;
    
    listContainer.innerHTML = '';
    
    const chatsToForward = state.chats.filter(c => c.id !== 'bot');
    
    if (chatsToForward.length === 0) {
        listContainer.innerHTML = '<p class="empty-state text-center text-slate-400 py-4 text-xs font-semibold">No active direct or group conversations available.</p>';
    } else {
        chatsToForward.forEach(chat => {
            const isGroup = chat.type === 'group';
            let title = '';
            let avatar = '';
            
            if (isGroup) {
                title = chat.group_metadata.group_name;
                avatar = chat.group_metadata.group_icon_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
            } else {
                const recipient = chat.users.find(u => u.id !== state.user.id);
                title = recipient ? recipient.username : 'Unknown Contact';
                avatar = (recipient && recipient.profile_picture_url) ? recipient.profile_picture_url : 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
            }
            
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between p-3 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200 transition-all';
            div.innerHTML = `
                <div class="flex items-center gap-3">
                    <img src="${avatar}" alt="Avatar" class="w-8 h-8 rounded-full object-cover border border-slate-200">
                    <span class="font-bold text-xs text-slate-800">${title}</span>
                </div>
                <button onclick="forwardMessageTo(${chat.id})" class="bg-teal-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:scale-105 active:scale-95 transition-transform shadow-sm">
                    Forward
                </button>
            `;
            listContainer.appendChild(div);
        });
    }
    
    openModal('modal-forward-message');
};

window.forwardMessageTo = async function(channelId) {
    const msgId = state.forwardingMessageId;
    if (!msgId) return;
    
    try {
        const data = await apiCall(`/api/v1/messages/${msgId}/forward`, 'POST', { channel_id: channelId });
        closeModal('modal-forward-message');
        showToast('Message forwarded successfully! 🚀', 'success');
        
        if (state.activeChat && state.activeChat.id === channelId) {
            if (!state.messages[channelId]) {
                state.messages[channelId] = [];
            }
            state.messages[channelId].push(data.data);
            appendMessageBubble(data.data);
        }
        
        refreshChatsList();
    } catch (err) {
        showToast(err.message || 'Failed to forward message.', 'error');
    }
};

// ==========================================================================
// AI ASSISTANT SMART REPLY BINDING
// ==========================================================================
window.triggerAiSmartReply = async function(msgId) {
    showToast('🤖 AI is thinking...', 'info');
    
    try {
        const data = await apiCall(`/api/v1/messages/${msgId}/ai-reply`, 'POST');
        if (data.success && data.reply) {
            const input = document.getElementById('message-text-input');
            if (input) {
                input.value = data.reply;
                input.focus();
                showToast('🤖 AI reply populated!', 'success');
            }
        }
    } catch (err) {
        showToast(err.message || 'Failed to generate AI reply.', 'error');
    }
};

// ==========================================================================
// ADMIN CONTROL TOWER BINDINGS & ACTIONS
// ==========================================================================
const originalSwitchSPAPanel = window.switchSPAPanel;
window.switchSPAPanel = function(sectionId) {
    if (originalSwitchSPAPanel) {
        originalSwitchSPAPanel(sectionId);
    }
    if (sectionId === 'section-admin') {
        loadAdminUsers();
        loadAdminChannels();
    }
};

async function loadAdminUsers() {
    const tableBody = document.getElementById('admin-users-table-body');
    if (!tableBody) return;
    tableBody.innerHTML = '<tr><td colspan="5" class="py-4 text-center text-slate-400">Loading users...</td></tr>';
    
    try {
        const data = await apiCall('/api/v1/admin/users');
        tableBody.innerHTML = '';
        
        if (data.users.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="py-4 text-center text-slate-400">No users found.</td></tr>';
            return;
        }
        
        data.users.forEach(u => {
            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-200 font-semibold text-slate-800 hover:bg-slate-50/50';
            
            const isSelf = u.id === state.user.id;
            
            tr.innerHTML = `
                <td class="py-3 px-4">
                    <img src="${u.profile_picture_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y'}" alt="Avatar" class="w-8 h-8 rounded-full object-cover border border-slate-200">
                </td>
                <td class="py-3 px-4 text-xs">${u.username}</td>
                <td class="py-3 px-4 font-mono text-[11px] text-slate-500">${u.email || 'N/A'}</td>
                <td class="py-3 px-4">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                        u.role === 'admin' ? 'bg-red-50 text-red-800 border border-red-200' :
                        u.role === 'temp' ? 'bg-teal-50 text-teal-800 border border-teal-200' :
                        'bg-slate-100 text-slate-800 border border-slate-200'
                    }">${u.role}</span>
                </td>
                <td class="py-3 px-4 text-xs">
                    ${!isSelf ? `
                        <button onclick="deleteAdminUser(${u.id})" class="text-red-600 hover:text-white hover:bg-red-600 border border-red-200 rounded-lg px-2.5 py-1 transition-colors">
                            Purge
                        </button>
                    ` : '<span class="text-xs text-slate-400 italic">Self</span>'}
                </td>
            `;
            tableBody.appendChild(tr);
        });
    } catch (err) {
        tableBody.innerHTML = `<tr><td colspan="5" class="py-4 text-center text-red-500">Error: ${err.message}</td></tr>`;
    }
}

window.deleteAdminUser = async function(id) {
    if (!confirm('Are you sure you want to permanently delete this user and all their chats/messages? This action cannot be undone.')) {
        return;
    }
    try {
        const data = await apiCall(`/api/v1/admin/users/${id}`, 'DELETE');
        showToast(data.message || 'User deleted.', 'success');
        loadAdminUsers();
        refreshChatsList();
    } catch (err) {
        showToast(err.message || 'Failed to delete user.', 'error');
    }
};

async function loadAdminChannels() {
    const tableBody = document.getElementById('admin-rooms-table-body');
    if (!tableBody) return;
    tableBody.innerHTML = '<tr><td colspan="6" class="py-4 text-center text-slate-400">Loading rooms...</td></tr>';
    
    try {
        const data = await apiCall('/api/v1/admin/channels');
        tableBody.innerHTML = '';
        
        if (data.channels.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="py-4 text-center text-slate-400">No active rooms or channels.</td></tr>';
            return;
        }
        
        data.channels.forEach(ch => {
            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-200 font-semibold text-slate-800 hover:bg-slate-50/50';
            
            const meta = ch.group_metadata || {};
            const icon = meta.group_icon_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
            const name = meta.group_name || 'Unnamed Group';
            const desc = meta.group_description || 'No description';
            
            tr.innerHTML = `
                <td class="py-3 px-4">
                    <img src="${icon}" alt="Icon" class="w-8 h-8 rounded-full object-cover border border-slate-200">
                </td>
                <td class="py-3 px-4 text-xs">${name}</td>
                <td class="py-3 px-4 uppercase text-[10px] font-bold text-slate-500">${ch.type}</td>
                <td class="py-3 px-4 text-xs max-w-[150px] truncate">${desc}</td>
                <td class="py-3 px-4">${ch.users ? ch.users.length : 0}</td>
                <td class="py-3 px-4 text-xs">
                    <button onclick="deleteAdminChannel(${ch.id})" class="text-red-600 hover:text-white hover:bg-red-600 border border-red-200 rounded-lg px-2.5 py-1 transition-colors">
                        Delete
                    </button>
                </td>
            `;
            tableBody.appendChild(tr);
        });
    } catch (err) {
        tableBody.innerHTML = `<tr><td colspan="6" class="py-4 text-center text-red-500">Error: ${err.message}</td></tr>`;
    }
}

window.deleteAdminChannel = async function(id) {
    if (!confirm('Are you sure you want to permanently delete this chat room/channel? This will delete all messages inside it.')) {
        return;
    }
    try {
        const data = await apiCall(`/api/v1/admin/channels/${id}`, 'DELETE');
        showToast(data.message || 'Room deleted.', 'success');
        loadAdminChannels();
        refreshChatsList();
        
        if (state.activeChat && state.activeChat.id === id) {
            state.activeChat = null;
            DOM.roomWelcomeSplash.classList.add('active');
            DOM.roomWelcomeSplash.classList.remove('hide');
            DOM.roomActiveWindow.classList.add('hide');
        }
    } catch (err) {
        showToast(err.message || 'Failed to delete room.', 'error');
    }
};

window.toggleAdminSubTab = function(subTab) {
    const btnUsers = document.getElementById('btn-admin-tab-users');
    const btnRooms = document.getElementById('btn-admin-tab-rooms');
    const panelUsers = document.getElementById('admin-users-panel');
    const panelRooms = document.getElementById('admin-rooms-panel');
    
    if (subTab === 'users') {
        btnUsers.className = "px-4 py-2 font-bold bg-teal-600 text-white rounded-xl text-xs transition-all shadow-sm";
        btnRooms.className = "px-4 py-2 font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-200 rounded-xl text-xs transition-all";
        
        panelUsers?.classList.remove('hide');
        panelRooms?.classList.add('hide');
    } else {
        btnRooms.className = "px-4 py-2 font-bold bg-teal-600 text-white rounded-xl text-xs transition-all shadow-sm";
        btnUsers.className = "px-4 py-2 font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-200 rounded-xl text-xs transition-all";
        
        panelRooms?.classList.remove('hide');
        panelUsers?.classList.add('hide');
    }
};
