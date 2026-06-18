<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$user = getCurrentUser();

$page_title = 'Chat Room';
$page_desc = 'Komunitas & Pesan Langsung — TERRA';
$extra_css = '
    <style>
        /* Local layout helpers */
        .chat-container-inner {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            margin: 20px auto;
            max-width: 1100px;
            overflow: hidden;
        }
        
        .chat-empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-tertiary);
            padding: var(--space-xl);
            text-align: center;
            background: var(--bg-primary);
        }
        
        .chat-back-mobile-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            cursor: pointer;
            padding: 8px;
            margin-right: 8px;
            align-items: center;
            justify-content: center;
        }
        
        @media (max-width: 768px) {
            .chat-back-mobile-btn {
                display: flex;
            }
            .chat-container-inner {
                margin: 0;
                border-radius: 0;
                border: none;
            }
        }
        
        /* Message bubble shape tweaks */
        .chat-message-bubble {
            border-radius: 12px;
        }
        .chat-message-group.mine .chat-message-bubble {
            border-bottom-right-radius: 2px;
        }
        .chat-message-group:not(.mine) .chat-message-bubble {
            border-bottom-left-radius: 2px;
        }
        
        /* Modal Buat Group */
        .group-modal {
            position: fixed;
            inset: 0;
            background: var(--bg-overlay);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: var(--space-lg);
        }
        .group-modal-content {
            background: white;
            border: 1.5px solid var(--accent);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            width: 100%;
            max-width: 440px;
            position: relative;
        }
    </style>
';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 0;">
    <div class="chat-container-inner">
        <div id="chatContainer" class="chat-page-container">
            
            <!-- 1. SIDEBAR KIRI -->
            <div class="chat-sidebar-left">
                
                <!-- Search bar for DMs -->
                <div class="chat-search-container" style="position: relative;">
                    <div style="position: relative;">
                        <input type="text" id="userSearchInput" class="chat-input-field" style="padding-left: 32px;" placeholder="Cari teman untuk DM..." autocomplete="off">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--text-tertiary);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </div>
                    <!-- Search Results Overlay -->
                    <div id="searchResults" class="chat-search-results"></div>
                </div>

                <!-- Global Chat -->
                <div class="chat-section-header">Saluran Utama</div>
                <a href="#" onclick="selectRoom('global', 'global')" id="room-global" class="chat-list-item active">
                    <div class="chat-avatar-wrapper" style="background: var(--accent); color: white;">
                        🌐
                    </div>
                    <div class="chat-item-info">
                        <div class="chat-item-name">Global Chat</div>
                        <div class="chat-item-meta">
                            <span class="chat-item-preview">Forum obrolan semua pendaki</span>
                        </div>
                    </div>
                </a>

                <!-- Groups Section -->
                <div class="chat-section-header">
                    <span>Grup Pendakian</span>
                    <button onclick="openCreateGroupModal()" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 8px; font-weight: 800; border-radius: var(--radius-sm);">+ BUAT</button>
                </div>
                <div id="groupListContainer">
                    <!-- Loaded dynamically via JS -->
                    <div style="padding: 16px; text-align: center; font-size: 11px; color: var(--text-tertiary);">Memuat grup...</div>
                </div>

                <!-- Direct Messages Section -->
                <div class="chat-section-header">Pesan Pribadi (DM)</div>
                <div id="dmListContainer">
                    <!-- Loaded dynamically via JS -->
                    <div style="padding: 16px; text-align: center; font-size: 11px; color: var(--text-tertiary);">Tidak ada pesan aktif</div>
                </div>

            </div>

            <!-- 2. KONTEN TENGAH (CHAT AREA) -->
            <div class="chat-area-middle" id="chatArea">
                <!-- Empty state initially -->
                <div class="chat-empty-state" id="chatEmptyState" style="display: none;">
                    <div style="font-size: 40px; margin-bottom: 12px;">💬</div>
                    <h3 style="font-size: 13px; font-weight: 800; color: var(--text-primary); text-transform: uppercase;">Mulai Obrolan</h3>
                    <p style="font-size: 11px; max-width: 280px; margin-top: 4px;">Pilih saluran global, grup koordinasi, atau kirim pesan pribadi ke teman pendaki.</p>
                </div>

                <!-- Active Chat Wrapper -->
                <div id="chatActiveWrapper" style="display: flex; flex-direction: column; height: 100%;">
                    
                    <!-- Chat Header -->
                    <div class="chat-messages-header">
                        <div style="display: flex; align-items: center; min-width: 0;">
                            <!-- Mobile Back Button -->
                            <button class="chat-back-mobile-btn" onclick="backToSidebar()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width: 18px; height: 18px;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            </button>
                            <div class="chat-avatar-wrapper" id="activeChatAvatar">🌐</div>
                            <div style="margin-left: 10px; min-width: 0;">
                                <div class="chat-item-name" id="activeChatName" style="font-size: 13px;">Global Chat</div>
                                <div id="activeChatStatus" style="font-size: 9px; color: var(--text-secondary); margin-top: 1px;">Semua pendaki Indonesia</div>
                            </div>
                        </div>
                        <div id="activeChatHeaderActions">
                            <!-- Action buttons like Join Group or Info -->
                        </div>
                    </div>

                    <!-- Messages scroll area -->
                    <div class="chat-messages-list" id="messageList">
                        <!-- Loaded dynamically via JS -->
                    </div>

                    <!-- Input Bar -->
                    <div class="chat-input-bar">
                        <form id="chatInputForm" class="chat-input-form" onsubmit="handleSendMessage(event)">
                            <input type="text" id="messageInput" class="chat-input-field" placeholder="Ketik pesan..." autocomplete="off">
                            <button type="submit" class="btn btn-primary" style="padding: 10px 18px; border-radius: var(--radius-sm); font-size: 11px; font-weight: 800;">KIRIM</button>
                        </form>
                    </div>

                </div>
            </div>

            <!-- 3. PANEL KANAN (DETAILS PANEL) -->
            <div class="chat-panel-right" id="chatRightPanel">
                <!-- Loaded dynamically via JS -->
            </div>

        </div>
    </div>
</div>

<!-- Modal Buat Group -->
<div class="group-modal" id="createGroupModal">
    <div class="group-modal-content">
        <button onclick="closeCreateGroupModal()" style="position: absolute; top: 14px; right: 14px; background: none; border: none; font-size: 18px; cursor: pointer; color: var(--text-secondary);">&times;</button>
        <h3 style="font-size: 13px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 14px;">Buat Grup Baru</h3>
        
        <form id="createGroupForm" onsubmit="handleCreateGroup(event)" style="display: flex; flex-direction: column; gap: var(--space-sm);">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 9px; margin-bottom: 2px;">Nama Grup</label>
                <input type="text" id="groupNameInput" class="form-input" style="height: 38px; padding: 0 10px; font-size: 11px;" placeholder="Contoh: Rencana Summit Merbabu" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 9px; margin-bottom: 2px;">Deskripsi Grup</label>
                <textarea id="groupDescInput" class="form-input" style="height: 60px; padding: 8px 10px; font-size: 11px; resize: none;" placeholder="Sebutkan tujuan pendakian, kuota, atau tanggal..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="height: 38px; font-size: 11px; font-weight: 800; border-radius: var(--radius-sm); margin-top: 8px;">BUAT GRUP</button>
        </form>
    </div>
</div>

<script>
// Scoped JavaScript for Chat
let currentRoomType = 'global'; // 'global', 'group', or 'direct'
let currentRoomId = 'global';
let pollInterval = null;
let lastMessageId = null;
let isSending = false;

document.addEventListener('DOMContentLoaded', () => {
    // Initial fetch
    refreshSidebar();
    loadMessages(true);
    
    // Setup background polling (every 3 seconds)
    pollInterval = setInterval(() => {
        // Stop polling if the chat room container is no longer present in the DOM (navigated away)
        if (!document.getElementById('chatContainer')) {
            clearInterval(pollInterval);
            return;
        }
        refreshSidebar();
        loadMessages(false);
    }, 3000);

    // Setup DM User Search functionality
    const searchInput = document.getElementById('userSearchInput');
    const resultsDiv = document.getElementById('searchResults');

    if (searchInput && resultsDiv) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim();
            if (query.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }

            fetch(`<?= BASE_URL ?>/api/chat.php?action=search_users&query=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(users => {
                    const resultsEl = document.getElementById('searchResults');
                    if (!resultsEl) return;
                    resultsEl.innerHTML = '';
                    if (users.length === 0) {
                        resultsEl.innerHTML = '<div style="padding: 10px 16px; font-size: 11px; color: var(--text-tertiary); text-align:center;">User tidak ditemukan</div>';
                    } else {
                        users.forEach(u => {
                            const div = document.createElement('div');
                            div.className = 'chat-search-item';
                            div.innerHTML = `
                                <div class="chat-avatar-wrapper">${u.name.substring(0,2).toUpperCase()}</div>
                                <div>
                                    <div style="font-size:11.5px; font-weight:700;">${u.name}</div>
                                    <div style="font-size:9.5px; color:var(--text-secondary);">${u.email}</div>
                                </div>
                            `;
                            div.onclick = () => startDirectMessage(u.id);
                            resultsEl.appendChild(div);
                        });
                    }
                    resultsEl.style.display = 'block';
                })
                .catch(err => console.error(err));
        });

        // Hide search results on click outside
        document.addEventListener('click', (e) => {
            const currentSearchInput = document.getElementById('userSearchInput');
            const currentResultsDiv = document.getElementById('searchResults');
            if (currentSearchInput && currentResultsDiv) {
                if (!currentSearchInput.contains(e.target) && !currentResultsDiv.contains(e.target)) {
                    currentResultsDiv.style.display = 'none';
                }
            }
        });
    }
});

// Refresh Sidebar Lists (Groups, DMs, Badge notifications)
function refreshSidebar() {
    const groupListContainer = document.getElementById('groupListContainer');
    const dmListContainer = document.getElementById('dmListContainer');
    const chatBadgeGlobal = document.getElementById('chat-badge-global');

    // Groups
    if (groupListContainer) {
        fetch('<?= BASE_URL ?>/api/chat.php?action=get_groups')
            .then(res => res.json())
            .then(groups => {
                const container = document.getElementById('groupListContainer');
                if (!container) return;
                if (groups.length === 0) {
                    container.innerHTML = '<div style="padding: 10px 16px; text-align: center; font-size: 10.5px; color: var(--text-tertiary);">Belum ada grup koordinasi</div>';
                    return;
                }
                let html = '';
                groups.forEach(g => {
                    const isActive = (currentRoomType === 'group' && currentRoomId === g.id);
                    html += `
                        <a href="#" onclick="selectRoom('group', '${g.id}')" id="room-${g.id}" class="chat-list-item ${isActive ? 'active' : ''}">
                            <div class="chat-avatar-wrapper" style="background: var(--bg-tertiary); color: var(--text-primary);">
                                🧗
                            </div>
                            <div class="chat-item-info">
                                <div class="chat-item-name">${g.name}</div>
                                <div class="chat-item-meta">
                                    <span class="chat-item-preview">${g.description || 'Koordinasi mendaki'}</span>
                                    <span class="chat-badge" style="background: var(--border-color); color: var(--text-primary); font-size: 7.5px;">${g.member_count} Mbr</span>
                                </div>
                            </div>
                        </a>
                    `;
                });
                container.innerHTML = html;
            })
            .catch(err => console.error(err));
    }

    // Direct Messages
    if (dmListContainer) {
        fetch('<?= BASE_URL ?>/api/chat.php?action=get_dm_rooms')
            .then(res => res.json())
            .then(rooms => {
                const container = document.getElementById('dmListContainer');
                if (!container) return;
                if (rooms.length === 0) {
                    container.innerHTML = '<div style="padding: 10px 16px; text-align: center; font-size: 10.5px; color: var(--text-tertiary);">Cari teman diatas untuk DM</div>';
                    return;
                }
                let html = '';
                rooms.forEach(r => {
                    const isActive = (currentRoomType === 'direct' && currentRoomId === r.id);
                    const onlineClass = r.other_user.online ? 'online' : '';
                    const badgeHtml = r.unread_count > 0 ? `<span class="chat-badge" style="background: var(--danger); color: white;">${r.unread_count}</span>` : '';
                    
                    html += `
                        <a href="#" onclick="selectRoom('direct', '${r.id}')" id="room-${r.id}" class="chat-list-item ${isActive ? 'active' : ''}">
                            <div class="chat-avatar-wrapper">
                                ${r.other_user.name.substring(0,2).toUpperCase()}
                                <span class="status-dot ${onlineClass}"></span>
                            </div>
                            <div class="chat-item-info">
                                <div class="chat-item-name">${r.other_user.name}</div>
                                <div class="chat-item-meta">
                                    <span class="chat-item-preview">${r.last_message || 'Kirim pesan pribadi...'}</span>
                                    ${badgeHtml}
                                </div>
                            </div>
                        </a>
                    `;
                });
                container.innerHTML = html;
            })
            .catch(err => console.error(err));
    }

    // Global notifications badge
    if (chatBadgeGlobal) {
        fetch('<?= BASE_URL ?>/api/chat.php?action=get_status')
            .then(res => res.json())
            .then(status => {
                const badgeEl = document.getElementById('chat-badge-global');
                if (badgeEl) {
                    if (status.unread_dm_count > 0) {
                        badgeEl.innerText = status.unread_dm_count;
                        badgeEl.style.display = 'inline-block';
                    } else {
                        badgeEl.style.display = 'none';
                    }
                }
            })
            .catch(err => console.error(err));
    }
}

// Select chat room
function selectRoom(type, id) {
    // Update active highlight classes in sidebar
    document.querySelectorAll('.chat-list-item').forEach(item => {
        item.classList.remove('active');
    });
    const el = document.getElementById(`room-${id}`);
    if (el) el.classList.add('active');

    currentRoomType = type;
    currentRoomId = id;
    lastMessageId = null;

    // Mobile navigation active room trigger
    const container = document.getElementById('chatContainer');
    if (container) container.classList.add('active-room');

    loadMessages(true);
}

// Back to sidebar on mobile
function backToSidebar() {
    const container = document.getElementById('chatContainer');
    if (container) container.classList.remove('active-room');
}

// Start Direct Message with User
function startDirectMessage(targetUserId) {
    const searchResults = document.getElementById('searchResults');
    const userSearchInput = document.getElementById('userSearchInput');
    if (searchResults) searchResults.style.display = 'none';
    if (userSearchInput) userSearchInput.value = '';

    const formData = new FormData();
    formData.append('action', 'start_dm');
    formData.append('target_user_id', targetUserId);

    fetch('<?= BASE_URL ?>/api/chat.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(room => {
            refreshSidebar();
            selectRoom('direct', room.id);
        })
        .catch(err => console.error(err));
}

// Load message history for active room
function loadMessages(isInitial = false) {
    if (isSending) return; // Prevent loading while optimistic UI runs
    if (!document.getElementById('messageList')) return;
    
    let url = '';
    if (currentRoomType === 'global') {
        url = '<?= BASE_URL ?>/api/chat.php?action=get_global_messages';
    } else if (currentRoomType === 'group') {
        url = `<?= BASE_URL ?>/api/chat.php?action=get_group_messages&group_id=${currentRoomId}`;
    } else if (currentRoomType === 'direct') {
        url = `<?= BASE_URL ?>/api/chat.php?action=get_dm_messages&room_id=${currentRoomId}`;
    }

    fetch(url)
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('messageList');
            if (!list) return;
            
            const activeChatName = document.getElementById('activeChatName');
            const activeChatStatus = document.getElementById('activeChatStatus');
            const activeChatAvatar = document.getElementById('activeChatAvatar');
            const activeChatHeaderActions = document.getElementById('activeChatHeaderActions');

            // Format and display details
            if (currentRoomType === 'global') {
                if (activeChatName) activeChatName.innerText = 'Global Chat';
                if (activeChatStatus) activeChatStatus.innerText = 'Forum obrolan semua pendaki';
                if (activeChatAvatar) {
                    activeChatAvatar.innerHTML = '🌐';
                    activeChatAvatar.style.background = 'var(--accent)';
                    activeChatAvatar.style.color = 'white';
                }
                if (activeChatHeaderActions) activeChatHeaderActions.innerHTML = '';
                
                renderMessagesList(data, isInitial);
                renderRightPanel('global', null);
            } 
            else if (currentRoomType === 'group') {
                // Fetch group info & members
                fetch(`<?= BASE_URL ?>/api/chat.php?action=get_group_details&group_id=${currentRoomId}`)
                    .then(res => res.json())
                    .then(group => {
                        const currentActiveChatName = document.getElementById('activeChatName');
                        const currentActiveChatStatus = document.getElementById('activeChatStatus');
                        const currentActiveChatAvatar = document.getElementById('activeChatAvatar');
                        const currentActiveChatHeaderActions = document.getElementById('activeChatHeaderActions');

                        if (currentActiveChatName) currentActiveChatName.innerText = group.name;
                        if (currentActiveChatStatus) currentActiveChatStatus.innerText = `${group.member_count} anggota koordinasi`;
                        if (currentActiveChatAvatar) {
                            currentActiveChatAvatar.innerHTML = '🧗';
                            currentActiveChatAvatar.style.background = 'var(--bg-tertiary)';
                            currentActiveChatAvatar.style.color = 'var(--text-primary)';
                        }
                        
                        // Header actions (Leave Group button)
                        if (currentActiveChatHeaderActions) {
                            currentActiveChatHeaderActions.innerHTML = `
                                <button onclick="handleLeaveGroup('${group.id}')" class="btn btn-secondary btn-sm" style="border-color: var(--danger); color: var(--danger); font-size:9.5px; padding: 4px 10px;">KELUAR GRUP</button>
                            `;
                        }
                        
                        renderMessagesList(data, isInitial);
                        renderRightPanel('group', group);
                    });
            } 
            else if (currentRoomType === 'direct') {
                // Data has direct messages and target user info
                const messages = data.messages || [];
                const otherUser = data.other_user || {};
                
                if (activeChatName) activeChatName.innerText = otherUser.name;
                if (activeChatStatus) {
                    activeChatStatus.innerHTML = otherUser.online 
                        ? '<span style="color:#10B981;font-weight:700;">🟢 Online</span>' 
                        : '<span style="color:var(--text-tertiary);">Offline</span>';
                }
                    
                if (activeChatAvatar) {
                    activeChatAvatar.innerHTML = otherUser.name.substring(0,2).toUpperCase();
                    activeChatAvatar.style.background = 'var(--bg-tertiary)';
                    activeChatAvatar.style.color = 'var(--text-primary)';
                }
                if (activeChatHeaderActions) activeChatHeaderActions.innerHTML = '';
                
                renderMessagesList(messages, isInitial);
                renderRightPanel('direct', otherUser);
            }
        })
        .catch(err => {
            console.error(err);
            // If group is not member (e.g. left group) or other auth issues, show empty state
            if (currentRoomType === 'group') {
                showJoinGroupOverlay();
            }
        });
}

function showJoinGroupOverlay() {
    fetch(`<?= BASE_URL ?>/api/chat.php?action=get_group_details&group_id=${currentRoomId}`)
        .then(res => res.json())
        .then(group => {
            const activeChatName = document.getElementById('activeChatName');
            const activeChatStatus = document.getElementById('activeChatStatus');
            const activeChatAvatar = document.getElementById('activeChatAvatar');
            const activeChatHeaderActions = document.getElementById('activeChatHeaderActions');
            const list = document.getElementById('messageList');

            if (activeChatName) activeChatName.innerText = group.name;
            if (activeChatStatus) activeChatStatus.innerText = 'Belum bergabung';
            if (activeChatAvatar) activeChatAvatar.innerHTML = '🧗';
            if (activeChatHeaderActions) activeChatHeaderActions.innerHTML = '';
            
            if (list) {
                list.innerHTML = `
                    <div class="chat-empty-state" style="background: transparent;">
                        <div style="font-size: 32px; margin-bottom: 12px;">🔒</div>
                        <h3 style="font-size: 12.5px; font-weight: 800; text-transform: uppercase;">Bergabung ke Grup</h3>
                        <p style="font-size: 11px; max-width: 250px; margin-top: 4px; margin-bottom: 16px;">Grup ini merupakan wadah koordinasi khusus. Anda harus bergabung terlebih dahulu untuk melihat pesan dan berdiskusi.</p>
                        <button onclick="handleJoinGroup('${group.id}')" class="btn btn-primary" style="padding: 10px 24px; font-size:11px; font-weight:800;">GABUNG SEKARANG</button>
                    </div>
                `;
            }
            
            renderRightPanel('group_lock', group);
        });
}

// Render list of message bubbles inside the container
function renderMessagesList(messages, isInitial) {
    const list = document.getElementById('messageList');
    if (!list) return;
    
    // Check if message count or last message ID has changed
    const currentLastMsg = messages.length > 0 ? messages[messages.length - 1].id : null;
    if (lastMessageId === currentLastMsg) {
        return; // Skip rendering if no new messages to prevent flicker/jumpy scroll
    }
    
    lastMessageId = currentLastMsg;

    if (messages.length === 0) {
        list.innerHTML = '<div style="padding: 30px; text-align: center; font-size: 11px; color: var(--text-tertiary);">Belum ada obrolan. Kirim pesan pertama untuk memulai!</div>';
        return;
    }

    let html = '';
    messages.forEach(m => {
        const isMine = m.sender_id === '<?= $user['id'] ?>';
        const formattedTime = new Date(m.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        html += `
            <div class="chat-message-group ${isMine ? 'mine' : ''}">
                <div class="chat-message-bubble">
                    ${!isMine && currentRoomType !== 'direct' ? `<div class="chat-message-sender">${m.sender_name}</div>` : ''}
                    <div class="chat-message-text">${m.message}</div>
                    <div class="chat-message-time">${formattedTime}</div>
                </div>
            </div>
        `;
    });
    list.innerHTML = html;
    
    // Auto-scroll to bottom of chat
    list.scrollTop = list.scrollHeight;
}

// Render the right panel details based on active chat
function renderRightPanel(type, data) {
    const panel = document.getElementById('chatRightPanel');
    if (!panel) return;
    
    if (type === 'global') {
        panel.innerHTML = `
            <h3 style="font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); border-bottom: 1.5px solid var(--border-color); padding-bottom: 8px; margin-bottom: 12px;">Global Chat</h3>
            <p style="font-size: 11.5px; color: var(--text-secondary); line-height: 1.4; margin-bottom: 12px;">Saluran diskusi umum untuk seluruh anggota dan pengguna aplikasi TERRA di Indonesia.</p>
            <div style="background: var(--bg-tertiary); padding: 10px; border-radius: var(--radius-sm); border:1px solid var(--border-color); font-size: 10px; color: var(--text-secondary);">
                💡 Gunakan saluran ini untuk saling bertukar informasi seputar kondisi basecamp, cuaca terbaru, pendakian bersama, atau tip perjalanan. Harap jaga etika dan sopan santun.
            </div>
        `;
    } 
    else if (type === 'group' && data) {
        let membersHtml = '';
        data.members.forEach(m => {
            membersHtml += `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed var(--border-color); font-size:11px;">
                    <span style="font-weight: 600; color: var(--text-primary);">${m.name}</span>
                    <span style="font-size: 9px; color: ${m.online ? '#10B981' : 'var(--text-tertiary)'}; font-weight: 700;">${m.online ? '🟢 Online' : 'Offline'}</span>
                </div>
            `;
        });

        panel.innerHTML = `
            <h3 style="font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); border-bottom: 1.5px solid var(--border-color); padding-bottom: 8px; margin-bottom: 8px;">Detail Grup</h3>
            <h4 style="font-size: 12.5px; font-weight: 800; color: var(--accent); margin-bottom: 4px;">${data.name}</h4>
            <p style="font-size: 11px; color: var(--text-secondary); line-height: 1.3; margin-bottom: 14px;">"${data.description || 'Tidak ada deskripsi'}"</p>
            
            <h3 style="font-size: 10px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); border-bottom: 1.5px solid var(--border-color); padding-bottom: 4px; margin-bottom: 8px; margin-top: 14px;">Anggota Grup (${data.members.length})</h3>
            <div style="display: flex; flex-direction: column; max-height: 240px; overflow-y: auto;">
                ${membersHtml}
            </div>
        `;
    } 
    else if (type === 'group_lock' && data) {
        panel.innerHTML = `
            <h3 style="font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); border-bottom: 1.5px solid var(--border-color); padding-bottom: 8px; margin-bottom: 8px;">Detail Grup</h3>
            <h4 style="font-size: 12.5px; font-weight: 800; color: var(--accent); margin-bottom: 4px;">${data.name}</h4>
            <p style="font-size: 11px; color: var(--text-secondary); line-height: 1.3; margin-bottom: 14px;">"${data.description || 'Tidak ada deskripsi'}"</p>
            <div style="background: var(--bg-tertiary); padding: 10px; border-radius: var(--radius-sm); border:1px solid var(--border-color); font-size: 10px; color: var(--text-secondary);">
                🔒 Konten grup dikunci. Anda harus bergabung dengan menekan tombol <b>Gabung Sekarang</b> untuk melihat seluruh anggota dan riwayat obrolan grup.
            </div>
        `;
    }
    else if (type === 'direct' && data) {
        panel.innerHTML = `
            <h3 style="font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); border-bottom: 1.5px solid var(--border-color); padding-bottom: 8px; margin-bottom: 12px;">Profil Teman</h3>
            <div style="display:flex; flex-direction:column; align-items:center; text-align:center; padding: 10px 0; margin-bottom: 12px;">
                <div style="width: 54px; height: 54px; border-radius:50%; background: var(--bg-tertiary); border:1px solid var(--border-color); display:flex; align-items:center; justify-content:center; font-size: 20px; font-weight:800; color: var(--accent); box-shadow:var(--shadow-sm); margin-bottom:8px;">
                    ${data.name.substring(0,2).toUpperCase()}
                </div>
                <div style="font-size: 13px; font-weight: 800; color: var(--text-primary);">${data.name}</div>
                <div style="font-size: 9.5px; font-weight: 700; color: ${data.online ? '#10B981' : 'var(--text-tertiary)'}; margin-top:2px;">
                    ${data.online ? '🟢 Online Sekarang' : 'Offline'}
                </div>
            </div>
            
            <div style="display:flex; flex-direction:column; gap:8px; border-top: 1px solid var(--border-color); padding-top:12px;">
                <div>
                    <div style="font-size: 8px; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.02em;">Alamat Email</div>
                    <div style="font-size:11px; color:var(--text-primary); font-weight:600; margin-top:1px;">${data.email}</div>
                </div>
                ${data.phone ? `
                <div>
                    <div style="font-size: 8px; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.02em;">Nomor Telepon</div>
                    <div style="font-size:11px; color:var(--text-primary); font-weight:600; margin-top:1px;">${data.phone}</div>
                </div>
                ` : ''}
            </div>
        `;
    }
}

// Send Message
function handleSendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('messageInput');
    if (!input) return;
    const message = input.value.trim();
    if (empty(message)) return;

    input.value = '';
    isSending = true; // Block polling logic during transition

    const formData = new FormData();
    formData.append('message', message);
    
    let url = '';
    if (currentRoomType === 'global') {
        url = '<?= BASE_URL ?>/api/chat.php';
        formData.append('action', 'send_global_message');
    } else if (currentRoomType === 'group') {
        url = '<?= BASE_URL ?>/api/chat.php';
        formData.append('action', 'send_group_message');
        formData.append('group_id', currentRoomId);
    } else if (currentRoomType === 'direct') {
        url = '<?= BASE_URL ?>/api/chat.php';
        formData.append('action', 'send_dm_message');
        formData.append('room_id', currentRoomId);
    }

    // Optimistic UI update: append bubble locally before fetch returns
    const list = document.getElementById('messageList');
    const formattedTime = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    const tempId = 'temp-' + Date.now();
    
    const div = document.createElement('div');
    div.className = 'chat-message-group mine';
    div.id = tempId;
    div.innerHTML = `
        <div class="chat-message-bubble">
            <div class="chat-message-text">${message}</div>
            <div class="chat-message-time">${formattedTime}</div>
        </div>
    `;
    // If list was empty, clear loading text
    if (list.querySelector('.chat-empty-state') || list.innerText.includes('Belum ada obrolan')) {
        list.innerHTML = '';
    }
    list.appendChild(div);
    list.scrollTop = list.scrollHeight;

    fetch(url, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(newMsg => {
            isSending = false;
            // Replace temporary bubble with real one to preserve real date/ID
            const tempEl = document.getElementById(tempId);
            if (tempEl) tempEl.remove();
            
            loadMessages(false);
            refreshSidebar();
        })
        .catch(err => {
            isSending = false;
            console.error(err);
            const tempEl = document.getElementById(tempId);
            if (tempEl) {
                tempEl.style.opacity = '0.5';
                tempEl.querySelector('.chat-message-time').innerHTML = '<span style="color:var(--danger);">Gagal mengirim</span>';
            }
        });
}

// Group Membership Buttons Actions
function handleJoinGroup(groupId) {
    const formData = new FormData();
    formData.append('action', 'join_group');
    formData.append('group_id', groupId);

    fetch('<?= BASE_URL ?>/api/chat.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(() => {
            refreshSidebar();
            loadMessages(true);
        })
        .catch(err => console.error(err));
}

function handleLeaveGroup(groupId) {
    if (!confirm('Apakah Anda yakin ingin keluar dari grup koordinasi ini?')) return;

    const formData = new FormData();
    formData.append('action', 'leave_group');
    formData.append('group_id', groupId);

    fetch('<?= BASE_URL ?>/api/chat.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(() => {
            refreshSidebar();
            loadMessages(true);
        })
        .catch(err => console.error(err));
}

// Create Group Modal Handlers
function openCreateGroupModal() {
    document.getElementById('createGroupModal').style.display = 'flex';
}
function closeCreateGroupModal() {
    document.getElementById('createGroupModal').style.display = 'none';
    document.getElementById('groupNameInput').value = '';
    document.getElementById('groupDescInput').value = '';
}
function handleCreateGroup(e) {
    e.preventDefault();
    const name = document.getElementById('groupNameInput').value.trim();
    const description = document.getElementById('groupDescInput').value.trim();

    if (empty(name)) return;

    const formData = new FormData();
    formData.append('action', 'create_group');
    formData.append('name', name);
    formData.append('description', description);

    fetch('<?= BASE_URL ?>/api/chat.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(group => {
            closeCreateGroupModal();
            refreshSidebar();
            selectRoom('group', group.id);
        })
        .catch(err => console.error(err));
}

// PHP empty-like helper for JS
function empty(val) {
    return val === undefined || val === null || val === '';
}
</script>

<?php
$active_page = 'chat';
require_once __DIR__ . '/../includes/footer.php';
?>
