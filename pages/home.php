<?php
require_once __DIR__ . '/../config.php';
requireLogin();
 
$user = getCurrentUser();
$mountains = readJSON(MOUNTAINS_FILE);
$bookings = readJSON(BOOKINGS_FILE);
 
$today = date('Y-m-d');
?>
<?php
$page_title = 'Beranda';
$page_desc = 'TERRA — Dashboard Pendakian Gunung Indonesia';
require_once __DIR__ . '/../includes/header.php';
?>

        <!-- Hero Section (KAI Access curved theme) -->
        <section class="hero" style="background:var(--accent);color:white;padding-top:var(--space-xl);padding-bottom:56px;border-bottom:none;border-radius:0 0 16px 16px;">
            <div class="container">
                <div class="hero-content" style="color:white;">
                    <p class="hero-greeting" style="display:inline-flex;align-items:center;gap:4px;color:rgba(255,255,255,0.7) !important;">
                        <span>Selamat <?= date('H') < 12 ? 'Pagi' : (date('H') < 15 ? 'Siang' : (date('H') < 18 ? 'Sore' : 'Malam')) ?></span>
                        <?php if (date('H') < 6 || date('H') >= 18): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:rgba(255,255,255,0.7);"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:rgba(255,255,255,0.7);"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                        <?php endif; ?>
                    </p>
                    <h1 class="hero-title" style="color:white;margin-top:4px;font-size:var(--font-xl);"><?= sanitize($user['name']) ?></h1>
                    <?php include __DIR__ . '/location/location.php'; ?>
                </div>
            </div>
        </section>

        <!-- KAI Access style Ticket Reservation Card Overlay -->
        <div class="container" style="margin-top:-38px;position:relative;z-index:10;margin-bottom:var(--space-md);">
            <div class="glass-card-static p-md" style="box-shadow:var(--shadow-lg);border-color:var(--accent);">
                <h3 style="margin-bottom:var(--space-xs);text-transform:uppercase;font-size:10px;letter-spacing:0.05em;color:var(--text-primary);font-weight:800;display:flex;align-items:center;gap:6px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Pesan Tiket Pendakian
                </h3>
                <form action="<?= BASE_URL ?>/pages/booking.php" method="GET" style="display:grid; grid-template-columns: 1fr 1fr; gap: var(--space-xs);">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:9px; margin-bottom:2px;">Gunung Tujuan</label>
                        <div class="form-input-icon">
                            <span class="icon" style="color:var(--text-secondary);display:inline-flex;align-items:center;justify-content:center;left:10px;width:14px;height:14px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M4 22L12 8L20 22H4Z" /><path d="M9 13.25L12 9.5L15 13.25" /><path d="M12 22L17 14L22 22H12Z" /></svg>
                            </span>
                            <select name="mountain" class="form-input" style="padding:0 28px 0 32px; height: 36px; font-size: 11px; line-height: 36px; color: var(--text-primary); background-color: var(--bg-input);" required>
                                <option value="" disabled selected>Pilih gunung...</option>
                                <?php foreach ($mountains as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= sanitize($m['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:9px; margin-bottom:2px;">Tanggal Pendakian</label>
                        <div class="form-input-icon">
                            <span class="icon" style="color:var(--text-secondary);display:inline-flex;align-items:center;justify-content:center;left:10px;width:14px;height:14px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </span>
                            <input type="date" name="date" class="form-input" style="padding:0 8px 0 32px; height: 36px; font-size: 11px;" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" max="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="grid-column: span 2; padding:10px; font-size:11px; height: 36px; margin-top: 4px;">
                        CARI JALUR PENDAKIAN
                    </button>
                </form>
            </div>
        </div>

        <!-- Search -->
        <div class="container">
            <div class="search-bar">
                <span class="search-icon">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" id="searchInput" placeholder="Cari gunung..." autocomplete="off">
            </div>
        </div>

        <!-- Categories Menu -->
        <div class="container" style="margin-top: -10px; margin-bottom: var(--space-md);">
            <div class="glass-card-static p-md" style="box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); background: var(--bg-secondary);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-sm);">
                    <h3 style="font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 6px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;color:var(--text-primary);"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Kategori Layanan
                    </h3>
                    <span style="font-size: 9px; font-weight: 800; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.05em;">TERRA</span>
                </div>
                <div class="categories-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--space-xs); text-align: center;">
                    
                    <a href="<?= BASE_URL ?>/pages/open_trip.php" class="category-item">
                        <div class="category-icon-wrapper">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <span>Open Trip</span>
                    </a>

                    <a href="<?= BASE_URL ?>/pages/nearby_mountains.php" class="category-item">
                        <div class="category-icon-wrapper">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-12a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <span>Gunung Terdekat</span>
                    </a>

                    <a href="<?= BASE_URL ?>/pages/booking_porter.php" class="category-item">
                        <div class="category-icon-wrapper">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <span>Porter & Pemandu</span>
                    </a>

                    <a href="<?= BASE_URL ?>/pages/rental.php" class="category-item">
                        <div class="category-icon-wrapper">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px;"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        </div>
                        <span>Rental Alat Camping</span>
                    </a>
                    
                </div>
            </div>
        </div>


        <!-- Mountain List -->
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Destinasi Populer</h2>
            </div>

            <div class="grid grid-3 stagger-children" id="mountainGrid" style="grid-template-columns: 1fr;">
                <?php foreach ($mountains as $i => $mountain): 
                    $density = getDensityLevel($mountain['id']);
                    $quotaToday = getMountainQuota($mountain['id'], $today);
                    $difficultyLabels = ['easy' => 'Mudah', 'medium' => 'Menengah', 'hard' => 'Sulit'];
                    $difficultyLabel = $difficultyLabels[$mountain['difficulty']] ?? $mountain['difficulty'];
                ?>
                <a href="<?= BASE_URL ?>/pages/mountain.php?id=<?= $mountain['id'] ?>" class="mountain-card" data-name="<?= strtolower($mountain['name']) ?>" data-location="<?= strtolower($mountain['location']) ?>" style="display:flex;flex-direction:row;align-items:stretch;height:120px;overflow:hidden;">
                    <div class="mountain-card-image-placeholder mountain-bg-<?= $mountain['image'] ?>" style="width:120px;height:100%;min-width:120px;">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:36px;height:36px;opacity:0.3;color:white;"><path d="M4 22L12 8L20 22H4Z" /><path d="M9 13.25L12 9.5L15 13.25" /><path d="M12 22L17 14L22 22H12Z" /></svg>
                    </div>
                    <div class="mountain-card-body" style="flex:1;padding:var(--space-sm);display:flex;flex-direction:column;justify-content:space-between;">
                        <div>
                            <div style="display:flex;justify-content:space-between;align-items:center;">
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
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.mountain-card').forEach(card => {
                const name = card.dataset.name;
                const location = card.dataset.location;
                if (name.includes(query) || location.includes(query)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });


    </script>
<?php
$active_page = 'home';
require_once __DIR__ . '/../includes/footer.php';
?>
