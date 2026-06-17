<?php
require_once __DIR__ . '/../config.php';
requireLogin();

// Initialize demo achievements session
if (!isset($_SESSION['demo_achievements'])) {
    $_SESSION['demo_achievements'] = [
        'leave_no_trace' => false,
        'clean_campaign' => false,
        'report_trash' => false,
        'reviews_count' => 2,
        'photos_count' => 12,
        'loyal_consecutive_days' => 8
    ];
}

// Handle simulation POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_achievement') {
    header('Content-Type: application/json');
    $key = $_POST['key'] ?? '';
    if (isset($_SESSION['demo_achievements'][$key])) {
        if (is_bool($_SESSION['demo_achievements'][$key])) {
            $_SESSION['demo_achievements'][$key] = !$_SESSION['demo_achievements'][$key];
        } elseif ($key === 'reviews_count') {
            $_SESSION['demo_achievements'][$key] = $_SESSION['demo_achievements'][$key] >= 10 ? 2 : 10;
        } elseif ($key === 'photos_count') {
            $_SESSION['demo_achievements'][$key] = $_SESSION['demo_achievements'][$key] >= 50 ? 12 : 50;
        } elseif ($key === 'loyal_consecutive_days') {
            $_SESSION['demo_achievements'][$key] = $_SESSION['demo_achievements'][$key] >= 30 ? 8 : 30;
        }
        echo json_encode([
            'success' => true, 
            'value' => $_SESSION['demo_achievements'][$key]
        ]);
        exit;
    }
}

$user = getCurrentUser();
$achData = getUserAchievementsData($user);
$achievements = $achData['achievements'];
$unlockedCount = $achData['unlockedCount'];
$totalPoints = $achData['totalPoints'];
$goldBadges = $achData['goldBadges'];
$silverBadges = $achData['silverBadges'];
$bronzeBadges = $achData['bronzeBadges'];

$page_title = 'Achievement Pendakian';
$page_desc = 'Pencapaian Petualangan & Lencana Anda — TERRA';
$hide_header = true;
require_once __DIR__ . '/../includes/header.php';
?>

        <!-- Header -->
        <header class="header">
            <div class="header-inner">
                <a href="<?= BASE_URL ?>/pages/profile.php" class="header-back" style="display:inline-flex;align-items:center;gap:6px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Kembali
                </a>
                <span class="header-title">Achievement Saya</span>
                <div style="width:80px;"></div>
            </div>
        </header>

        <!-- Achievements Summary & Dashboard -->
        <div class="container mt-md">
            <!-- Summary Bar -->
            <div class="achievement-summary animate-fadeInUp" style="margin-top:var(--space-sm);">
                <div class="achievement-summary-item">
                    <span class="achievement-summary-value"><?= $unlockedCount ?> / <?= count($achievements) ?></span>
                    <span class="achievement-summary-label">Terbuka</span>
                </div>
                <div class="achievement-summary-item">
                    <span class="achievement-summary-value"><?= $totalPoints ?></span>
                    <span class="achievement-summary-label">Poin Petualang</span>
                </div>
                <div class="achievement-summary-item">
                    <span class="achievement-summary-value" style="font-size: var(--font-base); display: inline-flex; align-items: center; gap: 4px; justify-content: center; height: 24px;">
                        🥇<?= $goldBadges ?> 🥈<?= $silverBadges ?> 🥉<?= $bronzeBadges ?>
                    </span>
                    <span class="achievement-summary-label">Lencana</span>
                </div>
            </div>

            <!-- Category Tabs -->
            <div class="achievement-tabs animate-fadeInUp">
                <button class="achievement-tab active" id="tab-all" onclick="filterAchievements('all')">Semua</button>
                <button class="achievement-tab" id="tab-utama" onclick="filterAchievements('utama')">Utama</button>
                <button class="achievement-tab" id="tab-regional" onclick="filterAchievements('regional')">Regional</button>
                <button class="achievement-tab" id="tab-spesial" onclick="filterAchievements('spesial')">Spesial</button>
            </div>

            <!-- Achievements Cards Grid -->
            <div class="achievements-grid mb-lg animate-fadeInUp" style="margin-bottom: 42px;">
                <?php foreach ($achievements as $ach): 
                    $cardClass = $ach['is_unlocked'] ? 'unlocked' : 'locked';
                    
                    // Determine badge class
                    $badgeClass = '';
                    if ($ach['is_unlocked']) {
                        if ($ach['badge_level'] === 'gold') $badgeClass = 'badge-gold';
                        elseif ($ach['badge_level'] === 'silver') $badgeClass = 'badge-silver';
                        elseif ($ach['badge_level'] === 'bronze') $badgeClass = 'badge-bronze';
                        else $badgeClass = 'badge-unlocked-default';
                    }

                    // Progress calculation
                    $percentage = min(100, round(($ach['current'] / $ach['target']) * 100));
                ?>
                <div class="achievement-card <?= $cardClass ?>" data-category="<?= $ach['category'] ?>">
                    <div class="achievement-badge-container <?= $badgeClass ?>">
                        <?php if ($ach['is_unlocked']): ?>
                            <?= $ach['icon'] ?>
                        <?php else: ?>
                            🔒
                        <?php endif; ?>
                    </div>
                    <div class="achievement-details">
                        <div class="achievement-title-row">
                            <span class="achievement-card-title" style="font-size:var(--font-xs);"><?= sanitize($ach['title']) ?></span>
                            <span class="achievement-status-badge <?= $ach['is_unlocked'] ? 'unlocked-label' : 'locked-label' ?>">
                                <?= $ach['is_unlocked'] ? 'Terbuka' : 'Terkunci' ?>
                            </span>
                        </div>
                        <p class="achievement-card-desc"><?= sanitize($ach['desc']) ?></p>
                        
                        <!-- Progress Bar -->
                        <div class="achievement-progress-wrapper">
                            <div class="achievement-progress-bar">
                                <div class="achievement-progress-fill" style="width: <?= $percentage ?>%;"></div>
                            </div>
                            <div class="achievement-progress-text">
                                <span><?= $percentage ?>% Terpenuhi</span>
                                <span><?= number_format($ach['current']) ?> / <?= number_format($ach['target']) ?><?= $ach['unit'] ?? '' ?></span>
                            </div>
                        </div>

                        <!-- Simulation Button for Demo -->
                        <?php if (isset($ach['is_demo']) && $ach['is_demo']): ?>
                            <button type="button" class="achievement-sim-btn" onclick="toggleDemoAchievement('<?= $ach['demo_key'] ?>')">
                                ⚙️ Simulasi Kontribusi
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

<script>
function filterAchievements(category) {
    // Update active tab styling
    const tabs = document.querySelectorAll('.achievement-tab');
    tabs.forEach(tab => {
        if (tab.id === 'tab-' + category) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });

    // Show/hide achievement cards
    const cards = document.querySelectorAll('.achievement-card');
    cards.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function toggleDemoAchievement(key) {
    const formData = new FormData();
    formData.append('action', 'toggle_achievement');
    formData.append('key', key);

    fetch('<?= BASE_URL ?>/pages/achievements.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Hard reload to refresh calculations and layout
            window.location.reload();
        }
    })
    .catch(err => {
        console.error('Simulation error:', err);
    });
}
</script>

<?php
$active_page = 'profile';
require_once __DIR__ . '/../includes/footer.php';
?>
