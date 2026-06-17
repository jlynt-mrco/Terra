<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$user = getCurrentUser();
$bookings = getUserBookings($user['id']);

// Calculate completed summits and user tier rank
$today = date('Y-m-d');
$completedHikes = 0;
foreach ($bookings as $b) {
    if ($b['date'] <= $today && $b['status'] === 'confirmed') {
        $completedHikes++;
    }
}
$userTier = getUserTier($completedHikes);

// Calculate initials
$initials = '';
$nameParts = explode(' ', $user['name']);
foreach ($nameParts as $part) {
    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    if (strlen($initials) >= 2) break;
}

// Ranks config array
$ranksConfig = [
    [
        'id' => 'beginner',
        'title' => 'Beginner',
        'tier' => 'Trail',
        'emoji' => '🔰',
        'summits' => 0,
        'color' => '#10B981',
        'color_rgb' => '16, 185, 129',
        'rank_level' => 1
    ],
    [
        'id' => 'explorer',
        'title' => 'Explorer',
        'tier' => 'Trail',
        'emoji' => '🧭',
        'summits' => 1,
        'color' => '#10B981',
        'color_rgb' => '16, 185, 129',
        'rank_level' => 2
    ],
    [
        'id' => 'wanderer',
        'title' => 'Wanderer',
        'tier' => 'Trail',
        'emoji' => '🥾',
        'summits' => 2,
        'color' => '#10B981',
        'color_rgb' => '16, 185, 129',
        'rank_level' => 3
    ],
    [
        'id' => 'pathfinder',
        'title' => 'Pathfinder',
        'tier' => 'Explorer',
        'emoji' => '🗺️',
        'summits' => 3,
        'color' => '#1D4ED8',
        'color_rgb' => '29, 78, 216',
        'rank_level' => 1
    ],
    [
        'id' => 'trailblazer',
        'title' => 'Trailblazer',
        'tier' => 'Explorer',
        'emoji' => '🏹',
        'summits' => 4,
        'color' => '#1D4ED8',
        'color_rgb' => '29, 78, 216',
        'rank_level' => 2
    ],
    [
        'id' => 'summit_seeker',
        'title' => 'Summit Seeker',
        'tier' => 'Explorer',
        'emoji' => '🧗',
        'summits' => 5,
        'color' => '#1D4ED8',
        'color_rgb' => '29, 78, 216',
        'rank_level' => 3
    ],
    [
        'id' => 'peak_hunter',
        'title' => 'Peak Hunter',
        'tier' => 'Summit',
        'emoji' => '🦅',
        'summits' => 6,
        'color' => '#7C3AED',
        'color_rgb' => '124, 58, 237',
        'rank_level' => 1
    ],
    [
        'id' => 'trail_master',
        'title' => 'Trail Master',
        'tier' => 'Summit',
        'emoji' => '⚔️',
        'summits' => 8,
        'color' => '#7C3AED',
        'color_rgb' => '124, 58, 237',
        'rank_level' => 2
    ],
    [
        'id' => 'the_ascender',
        'title' => 'The Ascender',
        'tier' => 'Summit',
        'emoji' => '⚡',
        'summits' => 10,
        'color' => '#7C3AED',
        'color_rgb' => '124, 58, 237',
        'rank_level' => 3
    ],
    [
        'id' => 'mountain_sovereign',
        'title' => 'Mountain Sovereign',
        'tier' => 'Legend',
        'emoji' => '🏰',
        'summits' => 12,
        'color' => '#D97706',
        'color_rgb' => '217, 119, 6',
        'rank_level' => 1
    ],
    [
        'id' => 'king_of_peaks',
        'title' => 'King of Peaks',
        'tier' => 'Legend',
        'emoji' => '👑',
        'summits' => 15,
        'color' => '#D97706',
        'color_rgb' => '217, 119, 6',
        'rank_level' => 2
    ],
    [
        'id' => 'mountain_legend',
        'title' => 'Mountain Legend',
        'tier' => 'Legend',
        'emoji' => '🌌',
        'summits' => 20,
        'color' => '#D97706',
        'color_rgb' => '217, 119, 6',
        'rank_level' => 3
    ]
];

$groupedRanks = [];
foreach ($ranksConfig as $rc) {
    $groupedRanks[$rc['tier']][] = $rc;
}

// Find next rank
$nextRank = null;
foreach ($ranksConfig as $rc) {
    if ($completedHikes < $rc['summits']) {
        $nextRank = $rc;
        break;
    }
}

$page_title = 'Lencana Pangkat';
$page_desc = 'Galeri Pangkat Lencana Petualangan Anda — TERRA';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="tiers-page-container animate-fadeInUp" style="max-width: 800px; margin: 0 auto; padding: 20px 16px;">
    
    <!-- Back button & Page header -->
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
        <a href="<?= BASE_URL ?>/pages/profile.php" style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; border: 1px solid var(--border-color); background: white; color: var(--text-primary); text-decoration: none; transition: all 0.15s ease;" onmouseover="this.style.background='var(--bg-secondary)';" onmouseout="this.style.background='white';">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-size: 20px; font-weight: 800; color: var(--text-primary); margin: 0; line-height: 1.2;">Lencana Pangkat</h1>
            <p style="font-size: 11px; color: var(--text-secondary); margin: 0; margin-top: 2px;">Tingkatan peringkat Anda berdasarkan pendakian (summit) gunung.</p>
        </div>
    </div>

    <!-- User Status Dashboard Card -->
    <div style="background: white; border: 1px solid var(--border-color); border-radius: 20px; padding: var(--space-md) var(--space-lg); box-shadow: var(--shadow-sm); margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; color: var(--text-primary); border: 2.5px solid <?= $userTier['color'] ?>; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                <?= $initials ?>
            </div>
            <div style="flex: 1;">
                <div style="font-size: 14px; font-weight: 850; color: var(--text-primary);"><?= sanitize($user['name']) ?></div>
                <div style="display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                    <span style="font-size: 11px; font-weight: 800; color: <?= $userTier['color'] ?>; text-transform: uppercase; letter-spacing: 0.04em;">
                        <?= $userTier['icon'] ?> <?= $userTier['title'] ?>
                    </span>
                    <span style="font-size: 10px; color: var(--text-secondary);">•</span>
                    <span style="font-size: 11px; color: var(--text-secondary);"><?= $completedHikes ?> Summit Terkonfirmasi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- The horizontal scroll group display -->
    <h2 style="font-size: 13px; font-weight: 850; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; padding-left: 2px;">Galeri Pangkat</h2>
    <div class="tier-scroll-container" style="margin-bottom: 24px;">
        <?php foreach ($groupedRanks as $tierName => $ranks): 
            $tierColor = $ranks[0]['color'];
            if ($tierName === 'Trail') $tierIcon = '🟢';
            elseif ($tierName === 'Explorer') $tierIcon = '🔵';
            elseif ($tierName === 'Summit') $tierIcon = '🟣';
            elseif ($tierName === 'Legend') $tierIcon = '🟡';
        ?>
            <div class="tier-group" style="--accent-color: <?= $tierColor ?>; --accent-color-rgb: <?= $ranks[0]['color_rgb'] ?>;">
                <div class="tier-group-header">
                    <span class="tier-dot" style="background: <?= $tierColor ?>; box-shadow: 0 0 8px <?= $tierColor ?>;"></span>
                    <?= $tierIcon ?> <?= $tierName ?> Tier
                </div>
                <div class="tier-cards-row">
                    <?php foreach ($ranks as $rank): 
                        $isActive = ($userTier['title'] === $rank['title']);
                        $isUnlocked = ($completedHikes >= $rank['summits']);
                        
                        $rl = $rank['rank_level'];
                        $cardClass = 'tier-card rank-level-' . $rl;
                        if ($isActive) {
                            $cardClass .= ' active';
                        } elseif (!$isUnlocked) {
                            $cardClass .= ' locked';
                        } else {
                            $cardClass .= ' unlocked';
                        }
                        
                        $cardStyle = "--accent-color: " . $rank['color'] . "; --accent-color-rgb: " . $rank['color_rgb'] . ";";
                    ?>
                        <div class="<?= $cardClass ?>" style="<?= $cardStyle ?>">
                            <?php if ($isActive): ?>
                                <span class="active-badge-tag">Aktif</span>
                            <?php endif; ?>
                            
                            <div class="badge-glowing-wrapper">
                                <div class="badge-glow-circle"></div>
                                <div class="badge-frame">
                                    <?php if ($rl >= 2): ?>
                                        <div class="badge-wing wing-left"></div>
                                        <div class="badge-wing wing-right"></div>
                                    <?php endif; ?>
                                    <?php if ($rl === 3): ?>
                                        <div class="badge-crown">👑</div>
                                    <?php endif; ?>
                                    <div class="badge-emoji"><?= $rank['emoji'] ?></div>
                                </div>
                            </div>
                            
                            <div class="rank-card-title"><?= $rank['title'] ?></div>
                            <div class="rank-card-summits"><?= $rank['summits'] ?> Summit</div>
                            
                            <?php if (!$isUnlocked): ?>
                                <div class="lock-indicator">🔒</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Simplified Perks/Benefits Section -->
    <div style="background: white; border: 1px solid var(--border-color); border-radius: 20px; padding: var(--space-md) var(--space-lg); box-shadow: var(--shadow-sm); margin-bottom: 24px;">
        <h3 style="font-size: 12px; font-weight: 850; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px 0;">Keuntungan Tiap Tier</h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <span style="font-size: 14px; line-height: 1.2;">🟢</span>
                <div>
                    <span style="font-size: 11px; font-weight: 800; color: #10B981; text-transform: uppercase; letter-spacing: 0.02em;">Trail Tier</span>
                    <span style="font-size: 10px; color: var(--text-secondary); margin-left: 4px; font-weight: 600;">(0-2 Summit)</span>
                    <p style="font-size: 10.5px; color: var(--text-secondary); margin: 2px 0 0 0; line-height: 1.4;">Akses peta dasar, label profil hijau, & forum komunitas pemula.</p>
                </div>
            </div>
            <div style="display: flex; align-items: flex-start; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 8px;">
                <span style="font-size: 14px; line-height: 1.2;">🔵</span>
                <div>
                    <span style="font-size: 11px; font-weight: 800; color: #1D4ED8; text-transform: uppercase; letter-spacing: 0.02em;">Explorer Tier</span>
                    <span style="font-size: 10px; color: var(--text-secondary); margin-left: 4px; font-weight: 600;">(3-5 Summit)</span>
                    <p style="font-size: 10.5px; color: var(--text-secondary); margin: 2px 0 0 0; line-height: 1.4;">**Diskon booking 5%**, akses jalur menengah, & badge Explorer.</p>
                </div>
            </div>
            <div style="display: flex; align-items: flex-start; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 8px;">
                <span style="font-size: 14px; line-height: 1.2;">🟣</span>
                <div>
                    <span style="font-size: 11px; font-weight: 800; color: #7C3AED; text-transform: uppercase; letter-spacing: 0.02em;">Summit Tier</span>
                    <span style="font-size: 10px; color: var(--text-secondary); margin-left: 4px; font-weight: 600;">(6-11 Summit)</span>
                    <p style="font-size: 10.5px; color: var(--text-secondary); margin: 2px 0 0 0; line-height: 1.4;">**Diskon booking 10%**, verifikasi prioritas, akses jalur alpine, & stiker eksklusif.</p>
                </div>
            </div>
            <div style="display: flex; align-items: flex-start; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 8px;">
                <span style="font-size: 14px; line-height: 1.2;">🟡</span>
                <div>
                    <span style="font-size: 11px; font-weight: 800; color: #D97706; text-transform: uppercase; letter-spacing: 0.02em;">Legend Tier</span>
                    <span style="font-size: 10px; color: var(--text-secondary); margin-left: 4px; font-weight: 600;">(12+ Summit)</span>
                    <p style="font-size: 10.5px; color: var(--text-secondary); margin: 2px 0 0 0; line-height: 1.4;">**Diskon booking 15%**, **layanan guide gratis** (1x/thn), badge emas, & undangan VIP.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ / Rank Guide Section (Accordion style) -->
    <div style="background: white; border: 1px solid var(--border-color); border-radius: 20px; padding: var(--space-md) var(--space-lg); box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 12px; font-weight: 850; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px 0;">Panduan & Ketentuan</h3>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            
            <details class="faq-item" style="border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; background: #F8FAFC; transition: all 0.2s ease;">
                <summary style="list-style: none; display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; font-size: 11px; font-weight: 800; color: var(--text-primary); cursor: pointer; user-select: none;">
                    <span>Bagaimana cara menaikkan pangkat?</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="faq-arrow" style="width: 12px; height: 12px; color: var(--text-secondary); transition: transform 0.2s ease;"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div style="padding: 0 14px 12px 14px; font-size: 10.5px; line-height: 1.5; color: var(--text-secondary); border-top: 1px solid rgba(15,23,42,0.04); background: white; padding-top: 10px;">
                    Pangkat Anda dihitung secara otomatis berdasarkan jumlah **Summit Terkonfirmasi**. Summit dihitung dari tiket booking Anda yang memiliki status `confirmed` dan tanggal pendakiannya telah berlalu (hari ini atau kemarin).
                </div>
            </details>

            <details class="faq-item" style="border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; background: #F8FAFC; transition: all 0.2s ease;">
                <summary style="list-style: none; display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; font-size: 11px; font-weight: 800; color: var(--text-primary); cursor: pointer; user-select: none;">
                    <span>Apakah pangkat saya bisa turun?</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="faq-arrow" style="width: 12px; height: 12px; color: var(--text-secondary); transition: transform 0.2s ease;"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div style="padding: 0 14px 12px 14px; font-size: 10.5px; line-height: 1.5; color: var(--text-secondary); border-top: 1px solid rgba(15,23,42,0.04); background: white; padding-top: 10px;">
                    Tidak. Pangkat yang Anda peroleh bersifat permanen dan dihitung secara akumulatif. Jumlah summit Anda hanya akan terus bertambah setiap kali Anda menyelesaikan pendakian baru.
                </div>
            </details>

        </div>
    </div>
</div>

<style>
/* Horizontal Scroll Track Layout */
.tier-scroll-container {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding: 12px 16px 20px 16px;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    width: 100vw;
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    box-sizing: border-box;
}
.tier-scroll-container::-webkit-scrollbar {
    height: 6px;
}
.tier-scroll-container::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.03);
    border-radius: 99px;
}
.tier-scroll-container::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.12);
    border-radius: 99px;
}
.tier-scroll-container::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.25);
}

.tier-group {
    flex-shrink: 0;
    width: calc(100vw - 32px);
    max-width: 550px;
    background: #F8FAFC;
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 16px;
    padding: 14px 12px 12px 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    scroll-snap-align: center;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.02);
    box-sizing: border-box;
}
.tier-group-header {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    display: flex;
    align-items: center;
    gap: 6px;
    padding-left: 2px;
    color: var(--accent-color);
}
.tier-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.tier-cards-row {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    width: 100%;
}

/* Card Styling with High Contrast */
.tier-card {
    flex: 1;
    width: calc((100% - 16px) / 3);
    min-width: 0;
    border-radius: 12px;
    padding: 14px 4px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    overflow: hidden;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    background: #FFFFFF;
    border: 1.5px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.03);
    box-sizing: border-box;
}

/* Progressive heights for staircase look */
.tier-card.rank-level-1 {
    height: 130px;
}
.tier-card.rank-level-2 {
    height: 155px;
}
.tier-card.rank-level-3 {
    height: 180px;
}

/* Card state overrides */
.tier-card.unlocked {
    opacity: 1;
    border: 1.5px solid rgba(var(--accent-color-rgb), 0.25);
    box-shadow: 0 4px 10px rgba(var(--accent-color-rgb), 0.06);
}
.tier-card.active {
    opacity: 1;
    border: 2px solid var(--accent-color);
    box-shadow: 0 4px 15px rgba(var(--accent-color-rgb), 0.22);
    animation: activePulse 3s infinite ease-in-out;
}
.tier-card.locked {
    opacity: 0.5;
    background: rgba(15, 23, 42, 0.02);
    border: 1.5px dashed rgba(15, 23, 42, 0.1);
    box-shadow: none;
}
.tier-card.locked .badge-emoji {
    filter: grayscale(1) opacity(0.35);
}

/* Hover states */
.tier-card:not(.locked):hover {
    transform: translateY(-4px);
    border-color: rgba(var(--accent-color-rgb), 0.6);
    box-shadow: 0 6px 15px rgba(var(--accent-color-rgb), 0.15);
}
.tier-card.active:hover {
    border-color: var(--accent-color);
    box-shadow: 0 8px 20px rgba(var(--accent-color-rgb), 0.3);
}
.tier-card.locked:hover {
    opacity: 0.75;
    transform: translateY(-2px);
}

/* Badge and Ornaments */
.active-badge-tag {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(90deg, #F59E0B 0%, #D97706 100%);
    color: #FFFFFF;
    font-size: 8px;
    font-weight: 950;
    padding: 3px 8px;
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    box-shadow: 0 2px 8px rgba(217, 119, 6, 0.25);
    z-index: 5;
    line-height: 1;
}
.badge-glowing-wrapper {
    position: relative;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 8px;
    margin-bottom: 10px;
}
.badge-glow-circle {
    position: absolute;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(var(--accent-color-rgb), 0.22) 0%, rgba(255,255,255,0) 70%);
    z-index: 1;
    pointer-events: none;
}
.badge-frame {
    position: relative;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #FFFFFF;
    border: 2px solid rgba(var(--accent-color-rgb), 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    transition: all 0.2s;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}
.active .badge-frame {
    border-color: var(--accent-color);
    background: rgba(var(--accent-color-rgb), 0.06);
    box-shadow: 0 0 10px rgba(var(--accent-color-rgb), 0.15);
}
.badge-emoji {
    font-size: 16px;
    z-index: 3;
    line-height: 1;
    display: inline-block;
    transition: transform 0.2s;
}
.tier-card:not(.locked):hover .badge-emoji {
    transform: scale(1.1);
}

/* Wing Ornaments */
.badge-wing {
    position: absolute;
    width: 12px;
    height: 18px;
    border-radius: 4px 10px 4px 10px;
    border: 2px solid rgba(var(--accent-color-rgb), 0.45);
    border-top: none;
    border-left: none;
    z-index: 1;
    transition: all 0.2s;
}
.active .badge-wing {
    border-color: var(--accent-color);
    box-shadow: 1px 1px 4px rgba(var(--accent-color-rgb), 0.1);
}
.wing-left {
    left: -8px;
    transform: rotate(-40deg) scaleX(-1);
}
.wing-right {
    right: -8px;
    transform: rotate(-40deg);
}

/* Crown Ornament */
.badge-crown {
    position: absolute;
    top: -10px;
    font-size: 9px;
    z-index: 4;
    animation: crownFloat 3s ease-in-out infinite;
}

/* Titles and Texts inside card */
.rank-card-title {
    font-size: 8.5px;
    font-weight: 800;
    color: var(--text-primary);
    text-transform: uppercase;
    margin-top: 6px;
    line-height: 1.2;
    letter-spacing: 0.02em;
    padding: 0 2px;
}
.active .rank-card-title {
    color: var(--text-primary);
    font-weight: 900;
}
.rank-card-summits {
    font-size: 8px;
    font-weight: 700;
    color: var(--text-secondary);
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}
.active .rank-card-summits {
    color: var(--accent-color);
}

/* Lock placement */
.lock-indicator {
    position: absolute;
    bottom: 8px;
    right: 8px;
    font-size: 9px;
    opacity: 0.5;
    color: #94A3B8;
    z-index: 3;
}

/* Animations */
@keyframes activePulse {
    0%, 100% {
        box-shadow: 0 4px 15px rgba(var(--accent-color-rgb), 0.2), inset 0 0 10px rgba(var(--accent-color-rgb), 0.05);
        border-color: var(--accent-color);
    }
    50% {
        box-shadow: 0 4px 22px rgba(var(--accent-color-rgb), 0.35), inset 0 0 14px rgba(var(--accent-color-rgb), 0.1);
        border-color: rgba(var(--accent-color-rgb), 0.7);
    }
}
@keyframes crownFloat {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-3px) rotate(3deg);
    }
}

/* FAQ Accordion Styling */
summary::-webkit-details-marker {
    display: none;
}
details[open] {
    border-color: var(--accent) !important;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
}
details[open] .faq-arrow {
    transform: rotate(180deg);
    color: var(--accent) !important;
}
</style>

<?php
$active_page = 'profile';
require_once __DIR__ . '/../includes/footer.php';
?>
