<?php
/**
 * TERRA — Group Chat Coordination (WhatsApp Style)
 */
require_once __DIR__ . '/../../config.php';
requireLogin();

$user = getCurrentUser();
$groupId = $_GET['group_id'] ?? '';

if (empty($groupId)) {
    redirect('pages/chat/index.php');
}

$page_title = 'Group Chat';
$page_desc = 'Koordinasi Pendakian Kelompok — TERRA';
$extra_css = '
    <style>
        .chat-container-inner {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            margin: 20px auto;
            max-width: 1100px;
            overflow: hidden;
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
        .chat-message-bubble {
            border-radius: 12px;
        }
        .chat-message-group.mine .chat-message-bubble {
            border-bottom-right-radius: 2px;
        }
        .chat-message-group:not(.mine) .chat-message-bubble {
            border-bottom-left-radius: 2px;
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
    </style>
';

$active_page = 'chat';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container" style="padding: 0;">
    <div class="chat-container-inner">
        <div id="chatContainer" class="chat-page-container active-room">
            
            <!-- Sidebar Component -->
            <?php 
            $current_room_type = 'group';
            $current_room_id = $groupId;
            include __DIR__ . '/sidebar.php'; 
            ?>

            <!-- Chat Area -->
            <div class="chat-area-middle" id="chatArea">
                <div id="chatActiveWrapper" style="display: flex; flex-direction: column; height: 100%;">
                    
                    <!-- Chat Header -->
                    <div class="chat-messages-header">
                        <div style="display: flex; align-items: center; min-width: 0;">
                            <!-- Mobile Back Button -->
                            <a class="chat-back-mobile-btn" href="<?= BASE_URL ?>/pages/chat/index.php">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width: 18px; height: 18px;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            </a>
                            <div class="chat-avatar-wrapper" id="activeChatAvatar">🧗</div>
                            <div style="margin-left: 10px; min-width: 0;">
                                <div class="chat-item-name" id="activeChatName" style="font-size: 13px;">Memuat grup...</div>
                                <div id="activeChatStatus" style="font-size: 9px; color: var(--text-secondary); margin-top: 1px;">Detail grup</div>
                            </div>
                        </div>
                        <div id="activeChatHeaderActions"></div>
                    </div>

                    <!-- Messages scroll area -->
                    <div class="chat-messages-list" id="messageList">
                        <!-- Loaded dynamically via JS -->
                    </div>

                    <!-- Input Bar -->
                    <div class="chat-input-bar" id="chatInputBar">
                        <form id="chatInputForm" class="chat-input-form" onsubmit="handleSendMessage(event)">
                            <input type="text" id="messageInput" class="chat-input-field" placeholder="Ketik pesan..." autocomplete="off">
                            <button type="submit" class="btn btn-primary" style="padding: 10px 18px; border-radius: var(--radius-sm); font-size: 11px; font-weight: 800;">KIRIM</button>
                        </form>
                    </div>

                </div>
            </div>

            <!-- Details Panel (Desktop Right Column) -->
            <div class="chat-panel-right" id="chatRightPanel">
                <!-- Loaded dynamically via JS -->
            </div>

        </div>
    </div>
</div>

<script>
// Scoped JavaScript for Group Chat
(function () {
    const baseUrl = '<?= BASE_URL ?>';
    const userId = '<?= $user['id'] ?>';
    const groupId = '<?= $groupId ?>';
    
    let lastMessageId = null;
    let isSending = false;
    let groupPollInterval = null;

    // Load initial data
    loadGroupChatData(true);

    // Setup polling (every 3 seconds)
    groupPollInterval = setInterval(() => {
        // If we are no longer on this page, clear the interval
        const container = document.getElementById('chatContainer');
        if (!container || window.location.search.indexOf(groupId) === -1 || window.location.pathname.indexOf('group.php') === -1) {
            clearInterval(groupPollInterval);
            return;
        }
        loadGroupChatData(false);
    }, 3000);

    function loadGroupChatData(isInitial = false) {
        if (isSending) return;
        const messageList = document.getElementById('messageList');
        if (!messageList) return;

        // Fetch messages first
        fetch(`${baseUrl}/api/chat.php?action=get_group_messages&group_id=${groupId}`)
            .then(res => {
                if (!res.ok) throw new Error('Not member');
                return res.json();
            })
            .then(messages => {
                // Fetch group details
                fetch(`${baseUrl}/api/chat.php?action=get_group_details&group_id=${groupId}`)
                    .then(res => res.json())
                    .then(group => {
                        updateGroupUI(group, messages, isInitial);
                    });
            })
            .catch(err => {
                // If not a member, show join group overlay
                showJoinGroupOverlay();
            });
    }

    function updateGroupUI(group, messages, isInitial) {
        const activeChatName = document.getElementById('activeChatName');
        const activeChatStatus = document.getElementById('activeChatStatus');
        const activeChatAvatar = document.getElementById('activeChatAvatar');
        const activeChatHeaderActions = document.getElementById('activeChatHeaderActions');
        const inputBar = document.getElementById('chatInputBar');
        const list = document.getElementById('messageList');

        if (activeChatName) activeChatName.innerText = group.name;
        if (activeChatStatus) activeChatStatus.innerText = `${group.member_count} anggota koordinasi`;
        if (activeChatAvatar) {
            activeChatAvatar.innerHTML = '🧗';
            activeChatAvatar.style.background = 'var(--bg-tertiary)';
            activeChatAvatar.style.color = 'var(--text-primary)';
        }
        if (inputBar) inputBar.style.display = 'block';

        // Header actions (Leave Group button)
        if (activeChatHeaderActions) {
            activeChatHeaderActions.innerHTML = `
                <button onclick="handleLeaveGroup('${group.id}')" class="btn btn-secondary btn-sm" style="border-color: var(--danger); color: var(--danger); font-size:9.5px; padding: 4px 10px;">KELUAR GRUP</button>
            `;
        }

        renderMessagesList(messages, isInitial);
        renderRightPanel(group);
    }

    function showJoinGroupOverlay() {
        fetch(`${baseUrl}/api/chat.php?action=get_group_details&group_id=${groupId}`)
            .then(res => res.json())
            .then(group => {
                const activeChatName = document.getElementById('activeChatName');
                const activeChatStatus = document.getElementById('activeChatStatus');
                const activeChatAvatar = document.getElementById('activeChatAvatar');
                const activeChatHeaderActions = document.getElementById('activeChatHeaderActions');
                const inputBar = document.getElementById('chatInputBar');
                const list = document.getElementById('messageList');

                if (activeChatName) activeChatName.innerText = group.name;
                if (activeChatStatus) activeChatStatus.innerText = 'Belum bergabung';
                if (activeChatAvatar) activeChatAvatar.innerHTML = '🧗';
                if (activeChatHeaderActions) activeChatHeaderActions.innerHTML = '';
                if (inputBar) inputBar.style.display = 'none';
                
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
                
                renderRightPanelLocked(group);
            });
    }

    function renderMessagesList(messages, isInitial) {
        const list = document.getElementById('messageList');
        if (!list) return;
        
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
            const isMine = m.sender_id === userId;
            const formattedTime = new Date(m.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            html += `
                <div class="chat-message-group ${isMine ? 'mine' : ''}">
                    <div class="chat-message-bubble">
                        ${!isMine ? `<div class="chat-message-sender">${m.sender_name}</div>` : ''}
                        <div class="chat-message-text">${m.message}</div>
                        <div class="chat-message-time">${formattedTime}</div>
                    </div>
                </div>
            `;
        });
        list.innerHTML = html;
        list.scrollTop = list.scrollHeight;
    }

    function renderRightPanel(group) {
        const panel = document.getElementById('chatRightPanel');
        if (!panel) return;

        let membersHtml = '';
        group.members.forEach(m => {
            membersHtml += `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed var(--border-color); font-size:11px;">
                    <span style="font-weight: 600; color: var(--text-primary);">${m.name}</span>
                    <span style="font-size: 9px; color: ${m.online ? '#10B981' : 'var(--text-tertiary)'}; font-weight: 700;">${m.online ? '🟢 Online' : 'Offline'}</span>
                </div>
            `;
        });

        panel.innerHTML = `
            <h3 style="font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); border-bottom: 1.5px solid var(--border-color); padding-bottom: 8px; margin-bottom: 8px;">Detail Grup</h3>
            <h4 style="font-size: 12.5px; font-weight: 800; color: var(--accent); margin-bottom: 4px;">${group.name}</h4>
            <p style="font-size: 11px; color: var(--text-secondary); line-height: 1.3; margin-bottom: 14px;">"${group.description || 'Tidak ada deskripsi'}"</p>
            
            <h3 style="font-size: 10px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); border-bottom: 1.5px solid var(--border-color); padding-bottom: 4px; margin-bottom: 8px; margin-top: 14px;">Anggota Grup (${group.members.length})</h3>
            <div style="display: flex; flex-direction: column; max-height: 240px; overflow-y: auto;">
                ${membersHtml}
            </div>
        `;
    }

    function renderRightPanelLocked(group) {
        const panel = document.getElementById('chatRightPanel');
        if (!panel) return;

        panel.innerHTML = `
            <h3 style="font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); border-bottom: 1.5px solid var(--border-color); padding-bottom: 8px; margin-bottom: 8px;">Detail Grup</h3>
            <h4 style="font-size: 12.5px; font-weight: 800; color: var(--accent); margin-bottom: 4px;">${group.name}</h4>
            <p style="font-size: 11px; color: var(--text-secondary); line-height: 1.3; margin-bottom: 14px;">"${group.description || 'Tidak ada deskripsi'}"</p>
            <div style="background: var(--bg-tertiary); padding: 10px; border-radius: var(--radius-sm); border:1px solid var(--border-color); font-size: 10px; color: var(--text-secondary);">
                🔒 Konten grup dikunci. Anda harus bergabung dengan menekan tombol <b>Gabung Sekarang</b> untuk melihat seluruh anggota dan riwayat obrolan grup.
            </div>
        `;
    }

    window.handleSendMessage = function (e) {
        e.preventDefault();
        const input = document.getElementById('messageInput');
        if (!input) return;
        const message = input.value.trim();
        if (message === '') return;

        input.value = '';
        isSending = true;

        const formData = new FormData();
        formData.append('action', 'send_group_message');
        formData.append('group_id', groupId);
        formData.append('message', message);

        // Optimistic UI update
        const list = document.getElementById('messageList');
        if (list) {
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
            if (list.querySelector('.chat-empty-state') || list.innerText.includes('Belum ada obrolan')) {
                list.innerHTML = '';
            }
            list.appendChild(div);
            list.scrollTop = list.scrollHeight;

            fetch(`${baseUrl}/api/chat.php`, {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(() => {
                    isSending = false;
                    const tempEl = document.getElementById(tempId);
                    if (tempEl) tempEl.remove();
                    loadGroupChatData(false);
                })
                .catch(err => {
                    isSending = false;
                    console.error(err);
                    const tempEl = document.getElementById(tempId);
                    if (tempEl) {
                        tempEl.style.opacity = '0.5';
                        const timeEl = tempEl.querySelector('.chat-message-time');
                        if (timeEl) timeEl.innerHTML = '<span style="color:var(--danger);">Gagal mengirim</span>';
                    }
                });
        }
    };

    window.handleJoinGroup = function (id) {
        const formData = new FormData();
        formData.append('action', 'join_group');
        formData.append('group_id', id);

        fetch(`${baseUrl}/api/chat.php`, {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(() => {
                loadGroupChatData(true);
            })
            .catch(err => console.error(err));
    };

    window.handleLeaveGroup = function (id) {
        if (!confirm('Apakah Anda yakin ingin keluar dari grup koordinasi ini?')) return;

        const formData = new FormData();
        formData.append('action', 'leave_group');
        formData.append('group_id', id);

        fetch(`${baseUrl}/api/chat.php`, {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(() => {
                loadGroupChatData(true);
            })
            .catch(err => console.error(err));
    };

})();
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
