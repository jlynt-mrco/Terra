<?php
/**
 * Shared Chat Sidebar Component - WhatsApp Style
 */
$current_room_type = $current_room_type ?? 'none';
$current_room_id = $current_room_id ?? 'none';
?>
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
    <a href="<?= BASE_URL ?>/pages/chat/global.php" id="room-global" class="chat-list-item <?= ($current_room_type === 'global') ? 'active' : '' ?>">
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

<!-- Modal Buat Group (Shared) -->
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
(function() {
    const activeRoomType = '<?= $current_room_type ?>';
    const activeRoomId = '<?= $current_room_id ?>';
    const baseUrl = '<?= BASE_URL ?>';

    // Initial sidebar fetch
    refreshSidebarData();

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

            fetch(`${baseUrl}/api/chat.php?action=search_users&query=${encodeURIComponent(query)}`)
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

    // Refresh Sidebar Lists (Groups, DMs, Badge notifications)
    function refreshSidebarData() {
        const groupListContainer = document.getElementById('groupListContainer');
        const dmListContainer = document.getElementById('dmListContainer');
        const chatBadgeGlobal = document.getElementById('chat-badge-global');

        // Groups
        if (groupListContainer) {
            fetch(`${baseUrl}/api/chat.php?action=get_groups`)
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
                        const isActive = (activeRoomType === 'group' && activeRoomId === g.id);
                        html += `
                            <a href="${baseUrl}/pages/chat/group.php?group_id=${g.id}" id="room-${g.id}" class="chat-list-item ${isActive ? 'active' : ''}">
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
            fetch(`${baseUrl}/api/chat.php?action=get_dm_rooms`)
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
                        const isActive = (activeRoomType === 'direct' && activeRoomId === r.id);
                        const onlineClass = r.other_user.online ? 'online' : '';
                        const badgeHtml = r.unread_count > 0 ? `<span class="chat-badge" style="background: var(--danger); color: white;">${r.unread_count}</span>` : '';
                        
                        html += `
                            <a href="${baseUrl}/pages/chat/direct.php?room_id=${r.id}" id="room-${r.id}" class="chat-list-item ${isActive ? 'active' : ''}">
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
            fetch(`${baseUrl}/api/chat.php?action=get_status`)
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

    // Expose functions globally for UI action trigger in HTML elements
    window.openCreateGroupModal = function() {
        document.getElementById('createGroupModal').style.display = 'flex';
    };

    window.closeCreateGroupModal = function() {
        document.getElementById('createGroupModal').style.display = 'none';
        document.getElementById('groupNameInput').value = '';
        document.getElementById('groupDescInput').value = '';
    };

    window.handleCreateGroup = function(e) {
        e.preventDefault();
        const name = document.getElementById('groupNameInput').value.trim();
        const description = document.getElementById('groupDescInput').value.trim();

        if (name === '') return;

        const formData = new FormData();
        formData.append('action', 'create_group');
        formData.append('name', name);
        formData.append('description', description);

        fetch(`${baseUrl}/api/chat.php`, {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(group => {
                closeCreateGroupModal();
                // Navigate to the newly created group page using PJAX
                if (typeof window.executeScripts === 'function' || window.history.pushState) {
                    // Trigger click/load of new room page
                    const link = document.createElement('a');
                    link.href = `${baseUrl}/pages/chat/group.php?group_id=${group.id}`;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                } else {
                    window.location.href = `${baseUrl}/pages/chat/group.php?group_id=${group.id}`;
                }
            })
            .catch(err => console.error(err));
    };

    window.startDirectMessage = function(targetUserId) {
        const searchResults = document.getElementById('searchResults');
        const userSearchInput = document.getElementById('userSearchInput');
        if (searchResults) searchResults.style.display = 'none';
        if (userSearchInput) userSearchInput.value = '';

        const formData = new FormData();
        formData.append('action', 'start_dm');
        formData.append('target_user_id', targetUserId);

        fetch(`${baseUrl}/api/chat.php`, {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(room => {
                // Navigate to DM page
                if (typeof window.executeScripts === 'function' || window.history.pushState) {
                    const link = document.createElement('a');
                    link.href = `${baseUrl}/pages/chat/direct.php?room_id=${room.id}`;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                } else {
                    window.location.href = `${baseUrl}/pages/chat/direct.php?room_id=${room.id}`;
                }
            })
            .catch(err => console.error(err));
    };

    // Periodically update sidebar list (every 5 seconds)
    const sidebarTimer = setInterval(() => {
        if (!document.getElementById('groupListContainer')) {
            clearInterval(sidebarTimer);
            return;
        }
        refreshSidebarData();
    }, 5000);
})();
</script>
