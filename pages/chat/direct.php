<?php
/**
 * TERRA — Direct Message Chat (WhatsApp Style)
 */
require_once __DIR__ . '/../../config.php';
requireLogin();

$user = getCurrentUser();
$roomId = $_GET['room_id'] ?? '';

if (empty($roomId)) {
    redirect('pages/chat/index.php');
}

$page_title = 'Direct Message';
$page_desc = 'Obrolan Pribadi — TERRA';
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
            $current_room_type = 'direct';
            $current_room_id = $roomId;
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
                            <div class="chat-avatar-wrapper" id="activeChatAvatar">--</div>
                            <div style="margin-left: 10px; min-width: 0;">
                                <div class="chat-item-name" id="activeChatName" style="font-size: 13px;">Memuat obrolan...</div>
                                <div id="activeChatStatus" style="font-size: 9px; color: var(--text-secondary); margin-top: 1px;">Status pengguna</div>
                            </div>
                        </div>
                        <div id="activeChatHeaderActions"></div>
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

            <!-- Details Panel (Desktop Right Column) -->
            <div class="chat-panel-right" id="chatRightPanel">
                <!-- Loaded dynamically via JS -->
            </div>

        </div>
    </div>
</div>

<script>
// Scoped JavaScript for Direct Message Chat
(function () {
    const baseUrl = '<?= BASE_URL ?>';
    const userId = '<?= $user['id'] ?>';
    const roomId = '<?= $roomId ?>';
    
    let lastMessageId = null;
    let isSending = false;
    let dmPollInterval = null;

    // Load initial messages
    loadDMChatData(true);

    // Setup polling (every 3 seconds)
    dmPollInterval = setInterval(() => {
        // If we are no longer on this page, clear the interval
        const container = document.getElementById('chatContainer');
        if (!container || window.location.search.indexOf(roomId) === -1 || window.location.pathname.indexOf('direct.php') === -1) {
            clearInterval(dmPollInterval);
            return;
        }
        loadDMChatData(false);
    }, 3000);

    function loadDMChatData(isInitial = false) {
        if (isSending) return;
        const messageList = document.getElementById('messageList');
        if (!messageList) return;

        fetch(`${baseUrl}/api/chat.php?action=get_dm_messages&room_id=${roomId}`)
            .then(res => res.json())
            .then(data => {
                const messages = data.messages || [];
                const otherUser = data.other_user || {};

                updateDMUI(otherUser, messages, isInitial);
            })
            .catch(err => console.error(err));
    }

    function updateDMUI(otherUser, messages, isInitial) {
        const activeChatName = document.getElementById('activeChatName');
        const activeChatStatus = document.getElementById('activeChatStatus');
        const activeChatAvatar = document.getElementById('activeChatAvatar');
        const list = document.getElementById('messageList');

        if (activeChatName) activeChatName.innerText = otherUser.name;
        if (activeChatStatus) {
            activeChatStatus.innerHTML = otherUser.online 
                ? '<span style="color:#10B981;font-weight:700;">🟢 Online</span>' 
                : '<span style="color:var(--text-tertiary);">Offline</span>';
        }
            
        if (activeChatAvatar) {
            activeChatAvatar.innerHTML = otherUser.name ? otherUser.name.substring(0,2).toUpperCase() : '--';
            activeChatAvatar.style.background = 'var(--bg-tertiary)';
            activeChatAvatar.style.color = 'var(--text-primary)';
        }

        renderMessagesList(messages, isInitial);
        renderRightPanel(otherUser);
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
                        <div class="chat-message-text">${m.message}</div>
                        <div class="chat-message-time">${formattedTime}</div>
                    </div>
                </div>
            `;
        });
        list.innerHTML = html;
        list.scrollTop = list.scrollHeight;
    }

    function renderRightPanel(otherUser) {
        const panel = document.getElementById('chatRightPanel');
        if (!panel) return;

        panel.innerHTML = `
            <h3 style="font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); border-bottom: 1.5px solid var(--border-color); padding-bottom: 8px; margin-bottom: 12px;">Profil Teman</h3>
            <div style="display:flex; flex-direction:column; align-items:center; text-align:center; padding: 10px 0; margin-bottom: 12px;">
                <div style="width: 54px; height: 54px; border-radius:50%; background: var(--bg-tertiary); border:1px solid var(--border-color); display:flex; align-items:center; justify-content:center; font-size: 20px; font-weight:800; color: var(--accent); box-shadow:var(--shadow-sm); margin-bottom:8px;">
                    ${otherUser.name ? otherUser.name.substring(0,2).toUpperCase() : '--'}
                </div>
                <div style="font-size: 13px; font-weight: 800; color: var(--text-primary);">${otherUser.name || ''}</div>
                <div style="font-size: 9.5px; font-weight: 700; color: ${otherUser.online ? '#10B981' : 'var(--text-tertiary)'}; margin-top:2px;">
                    ${otherUser.online ? '🟢 Online Sekarang' : 'Offline'}
                </div>
            </div>
            
            <div style="display:flex; flex-direction:column; gap:8px; border-top: 1px solid var(--border-color); padding-top:12px;">
                <div>
                    <div style="font-size: 8px; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.02em;">Alamat Email</div>
                    <div style="font-size:11px; color:var(--text-primary); font-weight:600; margin-top:1px;">${otherUser.email || ''}</div>
                </div>
                ${otherUser.phone ? `
                <div>
                    <div style="font-size: 8px; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.02em;">Nomor Telepon</div>
                    <div style="font-size:11px; color:var(--text-primary); font-weight:600; margin-top:1px;">${otherUser.phone}</div>
                </div>
                ` : ''}
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
        formData.append('action', 'send_dm_message');
        formData.append('room_id', roomId);
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
                    loadDMChatData(false);
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
})();
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
