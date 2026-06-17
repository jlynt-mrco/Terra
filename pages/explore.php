<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$user = getCurrentUser();
$mountains = readJSON(MOUNTAINS_FILE);
$today = date('Y-m-d');
?>
<?php
$page_title = 'Explore';
$page_desc = 'Eksplorasi Gunung Indonesia — TERRA';
$extra_css = '
    <style>
        .filter-tabs {
            display: flex;
            gap: var(--space-xs);
            margin-bottom: var(--space-md);
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .filter-tab {
            padding: 8px 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-full);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            white-space: nowrap;
            transition: all var(--transition-fast);
        }
        .filter-tab.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }
    </style>
';
require_once __DIR__ . '/../includes/header.php';
?>

        <!-- Search Section -->
        <div class="container mt-md">
            <div class="search-bar" style="margin-top:var(--space-sm);margin-bottom:var(--space-md);">
                <span class="search-icon">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" id="searchInput" placeholder="Cari gunung berdasarkan nama atau lokasi..." autocomplete="off">
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">Semua</button>
                <button class="filter-tab" data-filter="easy">Mudah</button>
                <button class="filter-tab" data-filter="medium">Menengah</button>
                <button class="filter-tab" data-filter="hard">Sulit</button>
            </div>
        </div>

        <!-- Mountains Explorer Grid -->
        <div class="container">
            <div class="grid grid-3 stagger-children" id="exploreGrid" style="grid-template-columns: 1fr;">
                <?php foreach ($mountains as $mountain): 
                    $density = getDensityLevel($mountain['id']);
                    $quotaToday = getMountainQuota($mountain['id'], $today);
                    $difficultyLabels = ['easy' => 'Mudah', 'medium' => 'Menengah', 'hard' => 'Sulit'];
                    $difficultyLabel = $difficultyLabels[$mountain['difficulty']] ?? $mountain['difficulty'];
                ?>
                <a href="<?= BASE_URL ?>/pages/mountain.php?id=<?= $mountain['id'] ?>" class="mountain-card" data-name="<?= strtolower($mountain['name']) ?>" data-location="<?= strtolower($mountain['location']) ?>" data-difficulty="<?= $mountain['difficulty'] ?>" style="display:flex;flex-direction:row;align-items:stretch;height:120px;overflow:hidden;">
                    <div class="mountain-card-image-placeholder mountain-bg-<?= $mountain['image'] ?>" style="width:120px;height:100%;min-width:120px;">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:36px;height:36px;opacity:0.3;color:white;"><path d="M4 22L12 8L20 22H4Z" /><path d="M9 13.25L12 9.5L15 13.25" /><path d="M12 22L17 14L22 22H12Z" /></svg>
                    </div>
                    <div class="mountain-card-body" style="flex:1;padding:var(--space-sm);display:flex;flex-direction:column;justify-content:space-between;">
                        <div>
                            <div style="display:flex;justify-content:between;align-items:center;">
                                <h3 class="mountain-card-name" style="font-size:var(--font-sm);margin-bottom:0;flex:1;"><?= sanitize($mountain['name']) ?></h3>
                                <span class="badge badge-difficulty-<?= $mountain['difficulty'] ?>" style="font-size:9px;padding:2px 6px;"><?= $difficultyLabel ?></span>
                            </div>
                            <div class="mountain-card-location" style="margin-bottom:0;margin-top:2px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:10px;height:10px;margin-right:2px;"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <?= sanitize($mountain['location']) ?>
                            </div>
                        </div>

                        <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border-color);padding-top:4px;">
                            <div style="font-size:10px;color:var(--text-secondary);font-weight:700;">
                                <span style="color:var(--text-primary);"><?= number_format($mountain['altitude']) ?></span> mdpl
                            </div>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <span style="background:<?= $density['color'] ?>;width:6px;height:6px;border-radius:50%;"></span>
                                <span style="font-size:9px;font-weight:800;text-transform:uppercase;color:var(--text-secondary);"><?= $density['label'] ?></span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const filterTabs = document.querySelectorAll('.filter-tab');
        const cards = document.querySelectorAll('.mountain-card');
 
        let activeFilter = 'all';
        let searchQuery = '';
 
        function applyFilterAndSearch() {
            cards.forEach(card => {
                const name = card.dataset.name;
                const location = card.dataset.location;
                const difficulty = card.dataset.difficulty;
 
                const matchesSearch = name.includes(searchQuery) || location.includes(searchQuery);
                const matchesFilter = activeFilter === 'all' || difficulty === activeFilter;
 
                if (matchesSearch && matchesFilter) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }
 
        searchInput.addEventListener('input', function() {
            searchQuery = this.value.toLowerCase();
            applyFilterAndSearch();
        });
 
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                activeFilter = this.dataset.filter;
                applyFilterAndSearch();
            });
        });
    </script>
<?php
$active_page = 'explore';
require_once __DIR__ . '/../includes/footer.php';
?>
