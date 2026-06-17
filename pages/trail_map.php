<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$mountainId = $_GET['id'] ?? '';
$mountain = getMountain($mountainId);

if (!$mountain) {
    redirect('pages/home.php');
}
?>
<?php
$page_title = 'Peta Jalur — ' . sanitize($mountain['name']);
$page_desc = 'Peta Jalur Pendakian ' . sanitize($mountain['name']);
$hide_header = true;
require_once __DIR__ . '/../includes/header.php';
?>
        <header class="header">
            <div class="header-inner">
                <a href="<?= BASE_URL ?>/pages/mountain.php?id=<?= $mountainId ?>" class="header-back">← Kembali</a>
                <span class="header-title">Peta Jalur</span>
                <div style="width:80px;"></div>
            </div>
        </header>

        <div class="container mt-md">
            <h2 style="margin-bottom:var(--space-sm);"><?= sanitize($mountain['name']) ?></h2>
            <p class="text-secondary text-sm mb-md">📍 <?= sanitize($mountain['location']) ?></p>
        </div>

        <!-- Full Trail Map -->
        <div class="trail-map-container" style="margin:0 var(--space-md);height:auto;">
            <svg class="trail-map-svg" id="trailMapFull" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet" style="height:500px;">
                <defs>
                    <linearGradient id="bgGrad2" x1="0%" y1="100%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#1a3a2a;stop-opacity:1" />
                        <stop offset="50%" style="stop-color:#0d2137;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#1a1a2e;stop-opacity:1" />
                    </linearGradient>
                    <linearGradient id="trailGrad2" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#10B981;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#0EA5E9;stop-opacity:1" />
                    </linearGradient>
                    <filter id="glow2">
                        <feGaussianBlur stdDeviation="0.8" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>
                <rect width="100" height="100" fill="url(#bgGrad2)"/>

                <!-- Topographic contour lines -->
                <?php for ($i = 15; $i < 95; $i += 8): ?>
                <ellipse cx="<?= 45 + sin($i * 0.1) * 10 ?>" cy="<?= 50 + cos($i * 0.15) * 5 ?>" rx="<?= $i * 0.45 ?>" ry="<?= $i * 0.32 ?>" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="0.12" transform="rotate(<?= -15 + $i * 0.3 ?>, 50, 50)"/>
                <?php endfor; ?>

                <!-- Trail path with glow -->
                <?php
                $pathPoints = [];
                foreach ($mountain['posts'] as $post) {
                    $pathPoints[] = $post['x'] . ',' . $post['y'];
                }
                $pathD = 'M ' . implode(' L ', $pathPoints);
                ?>
                <path d="<?= $pathD ?>" fill="none" stroke="rgba(16,185,129,0.15)" stroke-width="2" stroke-linecap="round"/>
                <path d="<?= $pathD ?>" fill="none" stroke="url(#trailGrad2)" stroke-width="0.7" stroke-dasharray="2,1" stroke-linecap="round" filter="url(#glow2)"/>

                <!-- Water points -->
                <?php foreach ($mountain['water_points'] as $wp): ?>
                <circle cx="<?= $wp['x'] ?>" cy="<?= $wp['y'] ?>" r="2" fill="rgba(59,130,246,0.2)" stroke="none"/>
                <circle cx="<?= $wp['x'] ?>" cy="<?= $wp['y'] ?>" r="1.2" fill="#3B82F6" stroke="#1E40AF" stroke-width="0.2"/>
                <g transform="translate(<?= $wp['x'] - 1.5 ?>, <?= $wp['y'] - 4.5 ?>)">
                    <path d="M1.5 0 C2.5 1.5 3 2.5 3 3.5 A 1.5 1.5 0 0 1 0 3.5 C0 2.5 0.5 1.5 1.5 0 Z" fill="#3B82F6"/>
                </g>
                <text x="<?= $wp['x'] ?>" y="<?= $wp['y'] + 4 ?>" text-anchor="middle" fill="#93C5FD" font-size="1.3" font-weight="400"><?= $wp['name'] ?></text>
                <?php endforeach; ?>

                <!-- Camping areas -->
                <?php foreach ($mountain['camping_areas'] as $camp): ?>
                <circle cx="<?= $camp['x'] ?>" cy="<?= $camp['y'] ?>" r="2" fill="rgba(245,158,11,0.2)" stroke="none"/>
                <circle cx="<?= $camp['x'] ?>" cy="<?= $camp['y'] ?>" r="1.2" fill="#F59E0B" stroke="#B45309" stroke-width="0.2"/>
                <g transform="translate(<?= $camp['x'] - 2 ?>, <?= $camp['y'] - 5 ?>)">
                    <path d="M2 0 L4 4 L0 4 Z" fill="none" stroke="#F59E0B" stroke-width="0.4"/>
                    <path d="M2 0 L2 4" fill="none" stroke="#F59E0B" stroke-width="0.3"/>
                    <path d="M1 4 L2 2 L3 4" fill="none" stroke="#F59E0B" stroke-width="0.3"/>
                </g>
                <text x="<?= $camp['x'] ?>" y="<?= $camp['y'] + 4 ?>" text-anchor="middle" fill="#FCD34D" font-size="1.3" font-weight="400"><?= $camp['name'] ?></text>
                <text x="<?= $camp['x'] ?>" y="<?= $camp['y'] + 6 ?>" text-anchor="middle" fill="#94A3B8" font-size="1" font-weight="400">Kapasitas: <?= $camp['capacity'] ?></text>
                <?php endforeach; ?>

                <!-- Posts -->
                <?php foreach ($mountain['posts'] as $pi => $post): 
                    $isLast = ($pi === count($mountain['posts']) - 1);
                    $color = $isLast ? '#10B981' : '#F1F5F9';
                ?>
                <circle cx="<?= $post['x'] ?>" cy="<?= $post['y'] ?>" r="<?= $isLast ? 2.5 : 1.5 ?>" fill="<?= $isLast ? 'rgba(16,185,129,0.3)' : 'rgba(255,255,255,0.1)' ?>" stroke="none"/>
                <circle cx="<?= $post['x'] ?>" cy="<?= $post['y'] ?>" r="<?= $isLast ? 1.5 : 1 ?>" fill="<?= $color ?>" stroke="<?= $isLast ? '#059669' : 'rgba(255,255,255,0.3)' ?>" stroke-width="0.3"/>
                <?php if ($isLast): ?>
                <g transform="translate(<?= $post['x'] - 3 ?>, <?= $post['y'] - 7.5 ?>)" filter="url(#glow2)">
                    <path d="M3 0 L6 5 L0 5 Z" fill="#10B981"/>
                    <path d="M3 0 L1.5 2.5 L3.5 3 L4.5 2.5 L3 0 Z" fill="white"/>
                </g>
                <text x="<?= $post['x'] ?>" y="<?= $post['y'] + 4 ?>" text-anchor="middle" fill="#34D399" font-size="1.8" font-weight="700"><?= $post['name'] ?></text>
                <text x="<?= $post['x'] ?>" y="<?= $post['y'] + 6.5 ?>" text-anchor="middle" fill="#34D399" font-size="1.3"><?= number_format($post['altitude']) ?> mdpl</text>
                <?php else: ?>
                <text x="<?= $post['x'] ?>" y="<?= $post['y'] + 4 ?>" text-anchor="middle" fill="#CBD5E1" font-size="1.3" font-weight="500"><?= $post['name'] ?></text>
                <text x="<?= $post['x'] ?>" y="<?= $post['y'] + 6 ?>" text-anchor="middle" fill="#64748B" font-size="1.1"><?= number_format($post['altitude']) ?>m</text>
                <?php endif; ?>
                <?php endforeach; ?>
            </svg>

            <div class="trail-map-legend">
                <div class="legend-item"><div class="legend-dot" style="background:#F1F5F9;"></div> Pos Pendakian</div>
                <div class="legend-item"><div class="legend-dot" style="background:#3B82F6;"></div> Titik Air</div>
                <div class="legend-item"><div class="legend-dot" style="background:#F59E0B;"></div> Area Camping</div>
                <div class="legend-item"><div class="legend-dot" style="background:#10B981;"></div> Puncak</div>
                <div class="legend-item"><div style="width:20px;height:2px;background:linear-gradient(90deg,#10B981,#0EA5E9);border-radius:1px;"></div> Jalur</div>
            </div>
        </div>

        <!-- Posts Detail List -->
        <div class="container mt-lg">
            <h3 style="margin-bottom:var(--space-md);">Detail Pos Pendakian</h3>
            <?php foreach ($mountain['posts'] as $pi => $post): 
                $isLast = ($pi === count($mountain['posts']) - 1);
            ?>
            <div class="glass-card-static p-md mb-sm flex items-center gap-md">
                <div style="width:40px;height:40px;border-radius:var(--radius-full);background:<?= $isLast ? 'var(--accent)' : 'var(--bg-tertiary)' ?>;color:<?= $isLast ? 'white' : 'var(--text-primary)' ?>;display:flex;align-items:center;justify-content:center;font-size:0.85rem;font-weight:600;flex-shrink:0;">
                    <?php if ($isLast): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M4 22L12 8L20 22H4Z" /><path d="M9 13.25L12 9.5L15 13.25" /><path d="M12 22L17 14L22 22H12Z" /></svg>
                    <?php else: ?>
                        <?= ($pi + 1) ?>
                    <?php endif; ?>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:500;font-size:var(--font-sm);"><?= sanitize($post['name']) ?></div>
                    <div class="text-secondary text-xs"><?= number_format($post['altitude']) ?> mdpl</div>
                </div>
                <?php if ($pi > 0): 
                    $elevGain = $post['altitude'] - $mountain['posts'][$pi-1]['altitude'];
                ?>
                <span class="badge <?= $elevGain > 0 ? 'badge-success' : 'badge-info' ?>">
                    <?= $elevGain > 0 ? '+' : '' ?><?= $elevGain ?>m
                </span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

<?php
$active_page = 'home';
require_once __DIR__ . '/../includes/footer.php';
?>
