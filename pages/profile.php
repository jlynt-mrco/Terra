<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$user = getCurrentUser();

// Handle selected achievement save POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_selected_achievement') {
    header('Content-Type: application/json');
    $achievementId = $_POST['achievement_id'] ?? '';
    
    // Validate if user has unlocked this achievement
    $achData = getUserAchievementsData($user);
    $isValid = false;
    
    if ($achievementId === '') {
        $isValid = true; // Clear selection is always valid
    } else {
        foreach ($achData['achievements'] as $ach) {
            if ($ach['id'] === $achievementId && $ach['is_unlocked']) {
                $isValid = true;
                break;
            }
        }
    }
    
    if ($isValid) {
        $success = updateSelectedAchievement($user['id'], $achievementId);
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lencana belum terbuka atau tidak valid']);
    }
    exit;
}

$bookings = getUserBookings($user['id']);

$totalBookings = count($bookings);
$totalMembers = 0;
foreach ($bookings as $b) $totalMembers += count($b['members']);

$initials = '';
$nameParts = explode(' ', $user['name']);
foreach ($nameParts as $part) {
    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    if (strlen($initials) >= 2) break;
}

$uniqueMountains = [];
foreach ($bookings as $b) {
    $uniqueMountains[$b['mountain_id']] = true;
}
$uniqueMountainsCount = count($uniqueMountains);

// Get achievements data for the menu pill
$achData = getUserAchievementsData($user);
$unlockedCount = $achData['unlockedCount'];
$totalPoints = $achData['totalPoints'];
$achievementsCount = count($achData['achievements']);

// Calculate completed summits and user tier rank
$today = date('Y-m-d');
$completedHikes = 0;
foreach ($bookings as $b) {
    if ($b['date'] <= $today && $b['status'] === 'confirmed') {
        $completedHikes++;
    }
}
$userTier = getUserTier($completedHikes);


// Resolve selected achievement title (empty by default)
$selectedAchievementId = $user['selected_achievement'] ?? '';
$selectedTitle = '';

if (!empty($selectedAchievementId)) {
    foreach ($achData['achievements'] as $ach) {
        if ($ach['id'] === $selectedAchievementId && $ach['is_unlocked']) {
            $badgePrefix = '';
            if ($ach['badge_level'] === 'gold') $badgePrefix = '🥇 ';
            elseif ($ach['badge_level'] === 'silver') $badgePrefix = '🥈 ';
            elseif ($ach['badge_level'] === 'bronze') $badgePrefix = '🥉 ';
            else $badgePrefix = $ach['icon'] . ' ';
            
            $selectedTitle = $badgePrefix . $ach['title'];
            break;
        }
    }
}

// Filter only unlocked achievements for modal selection
$unlockedAchievements = [];
foreach ($achData['achievements'] as $ach) {
    if ($ach['is_unlocked']) {
        $unlockedAchievements[] = $ach;
    }
}
?>
<?php
$page_title = 'Profil';
$page_desc = 'Profil Pengguna — TERRA';
require_once __DIR__ . '/../includes/header.php';
?>

        <!-- Profile Header -->
        <div class="profile-header animate-fadeInUp">
            <div class="profile-avatar"><?= $initials ?></div>
            <h2 class="profile-name"><?= sanitize($user['name']) ?></h2>
            
            <!-- Gelar / Rank Area with Pen Edit Button -->
            <div class="profile-rank-wrapper" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; margin-top: 4px; min-height: 20px;">
                <?php if (!empty($selectedTitle)): ?>
                    <p class="profile-rank" style="font-weight: 700; color: var(--accent); text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; margin: 0; display: inline-flex; align-items: center; gap: 4px;">
                        <?= $selectedTitle ?>
                    </p>
                <?php endif; ?>
                <button type="button" onclick="openGelarModal()" class="edit-gelar-btn" title="Pilih Gelar Pencapaian" style="background: none; border: none; cursor: pointer; padding: 4px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-secondary); transition: all 0.1s ease; border-radius: 50%; width: 22px; height: 22px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width: 13px; height: 13px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </button>
            </div>
            
            <!-- Achievements Stats & Menu Link -->
            <div class="animate-fadeIn" style="display: block; margin: var(--space-md) auto 0 auto; width: 95%; max-width: 380px;">
                <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: var(--space-sm) var(--space-md); box-shadow: var(--shadow-sm); display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; text-align: center; transition: all var(--transition-base);" onmouseover="this.style.borderColor='var(--accent)';" onmouseout="this.style.borderColor='var(--border-color)';">
                    
                    <!-- Tier / Rank Column (Clicks open modal) -->
                    <a href="<?= BASE_URL ?>/pages/tiers.php" style="text-decoration: none; border-right: 1px solid var(--border-color); padding: 4px 0; display: flex; flex-direction: column; justify-content: space-between; align-items: center; min-height: 32px; transition: opacity 0.15s ease;" onmouseover="this.style.opacity='0.7';" onmouseout="this.style.opacity='1';">
                        <div style="font-size: 10px; font-weight: 800; color: var(--text-primary); line-height: 1.1; text-transform: uppercase;">
                            <?= $userTier['title'] ?>
                        </div>
                        <div style="font-size: 9px; font-weight: 700; color: <?= $userTier['color'] ?>; text-transform: uppercase; letter-spacing: 0.04em; line-height: 1; display: inline-flex; align-items: center; gap: 3px; margin-top: auto;">
                            <span><?= $userTier['icon'] ?></span>
                            <span><?= $userTier['tier'] ?></span>
                        </div>
                    </a>
                    
                    <!-- Poin Petualang Column (Links to Achievements) -->
                    <a href="<?= BASE_URL ?>/pages/achievements.php" style="text-decoration: none; border-right: 1px solid var(--border-color); padding: 4px 0; display: flex; flex-direction: column; justify-content: space-between; align-items: center; min-height: 32px; transition: opacity 0.15s ease;" onmouseover="this.style.opacity='0.7';" onmouseout="this.style.opacity='1';">
                        <div style="font-size: var(--font-md); font-weight: 800; color: var(--accent); line-height: 1.1;">
                            <?= $totalPoints ?>
                        </div>
                        <div style="font-size: 9px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.04em; line-height: 1; margin-top: auto;">Poin Petualang</div>
                    </a>
                    
                    <!-- Lencana Column (Links to Achievements) -->
                    <a href="<?= BASE_URL ?>/pages/achievements.php" style="text-decoration: none; padding: 4px 0; display: flex; flex-direction: column; justify-content: space-between; align-items: center; min-height: 32px; transition: opacity 0.15s ease;" onmouseover="this.style.opacity='0.7';" onmouseout="this.style.opacity='1';">
                        <div style="font-size: 10px; font-weight: 800; color: var(--text-primary); line-height: 1.1; display: flex; align-items: center; justify-content: center; gap: 3px;">
                            <span>🥇<?= $achData['goldBadges'] ?></span>
                            <span>🥈<?= $achData['silverBadges'] ?></span>
                            <span>🥉<?= $achData['bronzeBadges'] ?></span>
                        </div>
                        <div style="font-size: 9px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.04em; line-height: 1; margin-top: auto;">Lencana</div>
                    </a>
                    
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="container">
            <div class="quick-stats mb-lg" style="grid-template-columns: repeat(3, 1fr);">
                <div class="quick-stat glass-card-static" style="display:flex;flex-direction:column;align-items:center;">
                    <div class="quick-stat-icon" style="display:inline-flex;align-items:center;justify-content:center;height:24px;color:var(--text-secondary);margin-bottom:4px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <div class="quick-stat-value"><?= $totalBookings ?></div>
                    <div class="quick-stat-label">Booking</div>
                </div>
                <div class="quick-stat glass-card-static" style="display:flex;flex-direction:column;align-items:center;">
                    <div class="quick-stat-icon" style="display:inline-flex;align-items:center;justify-content:center;height:24px;color:var(--text-secondary);margin-bottom:4px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="quick-stat-value"><?= $totalMembers ?></div>
                    <div class="quick-stat-label">Pendaki</div>
                </div>
                <div class="quick-stat glass-card-static" style="display:flex;flex-direction:column;align-items:center;">
                    <div class="quick-stat-icon" style="display:inline-flex;align-items:center;justify-content:center;height:24px;color:var(--text-secondary);margin-bottom:4px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;"><path d="M4 22L12 8L20 22H4Z" /><path d="M9 13.25L12 9.5L15 13.25" /><path d="M12 22L17 14L22 22H12Z" /></svg>
                    </div>
                    <div class="quick-stat-value"><?= $uniqueMountainsCount ?></div>
                    <div class="quick-stat-label">Gunung</div>
                </div>
            </div>
        </div>

        <!-- Profile Menu -->
        <div class="container">
            <div class="glass-card-static" style="overflow:hidden;">
                <div class="profile-menu">
                    <div class="profile-menu-item" style="display:flex;align-items:center;gap:12px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;color:var(--text-secondary);flex-shrink:0;"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <div class="menu-text">
                            <div style="font-weight:500;font-size:var(--font-sm);">Nama</div>
                            <div class="text-secondary text-xs"><?= sanitize($user['name']) ?></div>
                        </div>
                    </div>
                    <div class="profile-menu-item" style="display:flex;align-items:center;gap:12px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;color:var(--text-secondary);flex-shrink:0;"><path d="m22 2-10 11L2 2"/><path d="M22 2v14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2"/></svg>
                        <div class="menu-text">
                            <div style="font-weight:500;font-size:var(--font-sm);">Email</div>
                            <div class="text-secondary text-xs"><?= sanitize($user['email']) ?></div>
                        </div>
                    </div>
                    <div class="profile-menu-item" style="display:flex;align-items:center;gap:12px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;color:var(--text-secondary);flex-shrink:0;"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        <div class="menu-text">
                            <div style="font-weight:500;font-size:var(--font-sm);">Telepon</div>
                            <div class="text-secondary text-xs"><?= sanitize($user['phone'] ?? '-') ?></div>
                        </div>
                    </div>
                    <div class="profile-menu-item" style="display:flex;align-items:center;gap:12px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;color:var(--text-secondary);flex-shrink:0;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <div class="menu-text">
                            <div style="font-weight:500;font-size:var(--font-sm);">Bergabung</div>
                            <div class="text-secondary text-xs"><?= formatDate($user['created_at'] ?? date('Y-m-d')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="container mt-lg">
            <div class="glass-card-static" style="overflow:hidden;">
                <div class="profile-menu">
                    <a href="<?= BASE_URL ?>/pages/my_bookings.php" class="profile-menu-item" style="display:flex;align-items:center;gap:12px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;color:var(--text-secondary);flex-shrink:0;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        <div class="menu-text">Riwayat Booking</div>
                        <span class="menu-arrow">→</span>
                    </a>
                    <a href="<?= BASE_URL ?>/pages/home.php" class="profile-menu-item" style="display:flex;align-items:center;gap:12px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;color:var(--text-secondary);flex-shrink:0;"><path d="M4 22L12 8L20 22H4Z" /><path d="M9 13.25L12 9.5L15 13.25" /><path d="M12 22L17 14L22 22H12Z" /></svg>
                        <div class="menu-text">Jelajahi Gunung</div>
                        <span class="menu-arrow">→</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Logout -->
        <div class="container mt-lg mb-xl">
            <form action="<?= BASE_URL ?>/api/auth.php" method="POST">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-secondary btn-block" style="color:var(--danger);display:inline-flex;align-items:center;justify-content:center;gap:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Keluar
                </button>
            </form>
        </div>

        <!-- App Info -->
        <div class="container mb-xl">
            <div class="text-center text-xs text-secondary" style="padding:var(--space-lg) 0;">
                <p style="display:inline-flex;align-items:center;gap:4px;justify-content:center;">
                    <img src="<?= BASE_URL ?>/logo/logo.png" alt="Logo" style="width:14px; height:14px; object-fit:contain;">
                    TERRA v<?= APP_VERSION ?>
                </p>
                <p style="margin-top:4px;">Sistem Pendakian Gunung Indonesia</p>
            </div>
        </div>

        <!-- Modal Pilih Gelar Pencapaian -->
        <div id="gelarModal" class="gelar-modal">
            <div class="gelar-modal-content">
                <div class="gelar-modal-header">
                    <h3>Pilih Gelar Pencapaian</h3>
                    <button type="button" class="gelar-modal-close" onclick="closeGelarModal()">✕</button>
                </div>
                <div class="gelar-modal-body">
                    <p style="font-size: var(--font-xs); color: var(--text-secondary); margin-bottom: 12px; line-height: 1.4;">
                        Pilih lencana pencapaian yang sudah Anda dapatkan untuk ditampilkan sebagai gelar di profil Anda.
                    </p>
                    <div class="gelar-option-list">
                        <!-- Option Kosongkan Gelar -->
                        <label class="gelar-option">
                            <input type="radio" name="achievement_id" value="" <?= (empty($selectedAchievementId)) ? 'checked' : '' ?>>
                            <span class="gelar-option-card" style="border-style: dashed; border-color: var(--text-tertiary);">
                                <span class="gelar-option-icon">❌</span>
                                <div style="flex-grow: 1;">
                                    <span class="gelar-option-title" style="color: var(--danger);">Kosongkan Gelar</span>
                                    <span class="gelar-option-desc">Sembunyikan gelar pencapaian dari profil Anda</span>
                                </div>
                            </span>
                        </label>

                        <!-- Options Lencana Terbuka -->
                        <?php if (empty($unlockedAchievements)): ?>
                            <div style="text-align: center; padding: 24px 0; color: var(--text-secondary); font-size: var(--font-xs);">
                                <span style="font-size: 2rem; display: block; margin-bottom: 8px;">🏔️</span>
                                Belum ada pencapaian yang terbuka.<br>Ayo lakukan pendakian pertama Anda!
                            </div>
                        <?php else: ?>
                            <?php foreach ($unlockedAchievements as $ach): ?>
                                <label class="gelar-option">
                                    <input type="radio" name="achievement_id" value="<?= $ach['id'] ?>" <?= ($selectedAchievementId === $ach['id']) ? 'checked' : '' ?>>
                                    <span class="gelar-option-card">
                                        <span class="gelar-option-icon">
                                            <?php
                                            $prefix = '';
                                            if ($ach['badge_level'] === 'gold') $prefix = '🥇';
                                            elseif ($ach['badge_level'] === 'silver') $prefix = '🥈';
                                            elseif ($ach['badge_level'] === 'bronze') $prefix = '🥉';
                                            else $prefix = $ach['icon'];
                                            echo $prefix;
                                            ?>
                                        </span>
                                        <div style="flex-grow: 1;">
                                            <span class="gelar-option-title"><?= sanitize($ach['title']) ?></span>
                                            <span class="gelar-option-desc"><?= sanitize($ach['desc']) ?></span>
                                        </div>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="gelar-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeGelarModal()">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="saveSelectedAchievement()">Simpan</button>
                </div>
            </div>
        </div>



        <style>
        .gelar-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 16px;
            animation: fadeInGelarModal 0.2s ease-out;
        }
        .gelar-modal-content {
            background: white;
            width: 100%;
            max-width: 360px;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUpGelarModal 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
        }
        .gelar-modal-header {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .gelar-modal-header h3 {
            margin: 0;
            font-size: var(--font-sm);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 800;
            color: var(--text-primary);
        }
        .gelar-modal-close {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: var(--text-secondary);
            padding: 4px;
        }
        .gelar-modal-body {
            padding: 16px;
            max-height: 280px;
            overflow-y: auto;
        }
        .gelar-option-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .gelar-option {
            cursor: pointer;
            display: block;
            margin: 0;
        }
        .gelar-option input {
            display: none;
        }
        .gelar-option-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            transition: all 0.1s ease;
            background: #FFFFFF;
            text-align: left;
        }
        .gelar-option-card:hover {
            background: var(--bg-tertiary);
            border-color: var(--text-tertiary);
        }
        .gelar-option input:checked + .gelar-option-card {
            border-color: var(--accent);
            background: #FFFBEB;
            box-shadow: 0 0 0 1px var(--accent);
        }
        .gelar-option-icon {
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
        }
        .gelar-option-title {
            font-size: var(--font-xs);
            font-weight: 700;
            color: var(--text-primary);
            display: block;
        }
        .gelar-option-desc {
            font-size: 10px;
            color: var(--text-secondary);
            display: block;
            font-weight: 400;
            margin-top: 1px;
            line-height: 1.2;
        }
        .gelar-modal-footer {
            padding: 12px 16px;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 8px;
        }
        .gelar-modal-footer button {
            flex: 1;
            padding: 10px;
            font-size: var(--font-xs);
            font-weight: 700;
            border-radius: var(--radius-md);
        }
        @keyframes fadeInGelarModal {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUpGelarModal {
            from { transform: translateY(16px); }
            to { transform: translateY(0); }
        }
        
        /* Edit btn hover background */
        .edit-gelar-btn:hover {
            background-color: var(--bg-tertiary);
            color: var(--accent) !important;
        }

        </style>

        <script>
        function openGelarModal() {
            document.getElementById('gelarModal').style.display = 'flex';
        }
        function closeGelarModal() {
            document.getElementById('gelarModal').style.display = 'none';
        }
        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('gelarModal');
            if (e.target === modal) {
                closeGelarModal();
            }
        });
        function saveSelectedAchievement() {
            const selectedRadio = document.querySelector('input[name="achievement_id"]:checked');
            const achievementId = selectedRadio ? selectedRadio.value : '';
            
            const formData = new FormData();
            formData.append('action', 'save_selected_achievement');
            formData.append('achievement_id', achievementId);
            
            fetch('<?= BASE_URL ?>/pages/profile.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Gagal menyimpan gelar');
                }
            })
            .catch(err => {
                console.error('Error saving selected achievement:', err);
            });
        }
        </script>

<?php
$active_page = 'profile';
require_once __DIR__ . '/../includes/footer.php';
?>
