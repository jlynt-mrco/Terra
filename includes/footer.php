<?php
/**
 * TERRA — Common Footer
 */
?>
        </main>
    </div>

    <?php if (!isset($hide_bottom_nav) || !$hide_bottom_nav): ?>
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="<?= BASE_URL ?>/pages/home.php" class="bottom-nav-item <?= ($active_page ?? '') === 'home' ? 'active' : '' ?>">
            <svg class="icon nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?= ($active_page ?? '') === 'home' ? '2.5' : '2' ?>" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Beranda</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/explore.php" class="bottom-nav-item <?= ($active_page ?? '') === 'explore' ? 'active' : '' ?>">
            <svg class="icon nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?= ($active_page ?? '') === 'explore' ? '2.5' : '2' ?>" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
            <span>Explore</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/my_bookings.php" class="bottom-nav-item <?= ($active_page ?? '') === 'bookings' ? 'active' : '' ?>">
            <svg class="icon nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?= ($active_page ?? '') === 'bookings' ? '2.5' : '2' ?>" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/></svg>
            <span>Ticket</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/chat.php" class="bottom-nav-item <?= ($active_page ?? '') === 'chat' ? 'active' : '' ?>">
            <div style="position: relative; display: flex; flex-direction: column; align-items: center;">
                <svg class="icon nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?= ($active_page ?? '') === 'chat' ? '2.5' : '2' ?>" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span id="chat-badge-global" style="display: none; position: absolute; top: -4px; right: -4px; background: var(--danger, #DC2626); color: white; font-size: 8px; font-weight: 800; padding: 1px 4px; border-radius: 99px; border: 1px solid white; line-height: 1;"></span>
            </div>
            <span>Chat</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/profile.php" class="bottom-nav-item <?= ($active_page ?? '') === 'profile' ? 'active' : '' ?>">
            <svg class="icon nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?= ($active_page ?? '') === 'profile' ? '2.5' : '2' ?>" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>Profil</span>
        </a>
    </nav>
    <?php endif; ?>
</body>
</html>
