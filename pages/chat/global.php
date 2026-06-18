<?php
/**
 * TERRA — Global Chat Channel (WhatsApp Style)
 */
require_once __DIR__ . '/../../config.php';
requireLogin();

$user = getCurrentUser();

$page_title = 'Global Chat';
$page_desc = 'Saluran Utama Obrolan — TERRA';
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
        <!-- active-room is applied so that on mobile the chat area is shown and sidebar is hidden -->
        <div id="chatContainer" class="chat-page-container active-room">
            
            <!-- Sidebar Component -->
            <?php 
            $current_room_type = 'global';
            $current_room_id = 'global';
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
                            <div class="chat-avatar-wrapper" id="activeChatAvatar" style="background: var(--accent); color: white;">🌐</div>
                            <div style="margin-left: 10px; min-width: 0;">
                                <div class="chat-item-name" id="activeChatName" style="font-size: 13px;">Global Chat</div>
                                <div id="activeChatStatus" style="font-size: 9px; color: var(--text-secondary); margin-top: 1px;">Forum obrolan semua pendaki</div>
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
                <h3 style="font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); border-bottom: 1.5px solid var(--border-color); padding-bottom: 8px; margin-bottom: 12px;">Global Chat</h3>
                <p style="font-size: 11.5px; color: var(--text-secondary); line-height: 1.4; margin-bottom: 12px;">Saluran diskusi umum untuk seluruh anggota dan pengguna aplikasi TERRA di Indonesia.</p>
                <div style="background: var(--bg-tertiary); padding: 10px; border-radius: var(--radius-sm); border:1px solid var(--border-color); font-size: 10px; color: var(--text-secondary);">
                    💡 Gunakan saluran ini untuk saling bertukar informasi seputar kondisi basecamp, cuaca terbaru, pendakian bersama, atau tip perjalanan. Harap jaga etika dan sopan santun.
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Scoped JavaScript for Global Chat
(function () {
    const baseUrl = '<?= BASE_URL ?>';
    const userId = '<?= $user['id'] ?>';
    
    let lastMessageId = null;
    let isSending = false;
    let globalPollInterval = null;

    // Load messages initially
    loadGlobalMessages(true);

    // Setup polling (every 3 seconds)
    globalPollInterval = setInterval(() => {
        // If we are no longer on this page, clear the interval
        if (!document.getElementById('chatContainer') || window.location.pathname.indexOf('global.php') === -1) {
            clearInterval(globalPollInterval);
            return;
        }
        loadGlobalMessages(false);
    }, 3000);

    function loadGlobalMessages(isInitial = false) {
        if (isSending) return;
        const list = document.getElementById('messageList');
        if (!list) return;

        fetch(`${baseUrl}/api/chat.php?action=get_global_messages`)
            .then(res => res.json())
            .then(messages => {
                const listEl = document.getElementById('messageList');
                if (!listEl) return;

                // Check if message count or last message ID has changed
                const currentLastMsg = messages.length > 0 ? messages[messages.length - 1].id : null;
                if (lastMessageId === currentLastMsg) {
                    return; // Skip rendering if no new messages to prevent flicker/jumpy scroll
                }
                
                lastMessageId = currentLastMsg;

                if (messages.length === 0) {
                    listEl.innerHTML = '<div style="padding: 30px; text-align: center; font-size: 11px; color: var(--text-tertiary);">Belum ada obrolan. Kirim pesan pertama untuk memulai!</div>';
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
                listEl.innerHTML = html;
                listEl.scrollTop = listEl.scrollHeight;
            })
            .catch(err => console.error(err));
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
        formData.append('action', 'send_global_message');
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
                    loadGlobalMessages(false);
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
