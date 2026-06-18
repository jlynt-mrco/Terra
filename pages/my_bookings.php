<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$user = getCurrentUser();
$bookings = getUserBookings($user['id']);

$tab = $_GET['tab'] ?? 'ticket';
$today = date('Y-m-d');

if ($tab === 'tracking') {
    // Sort bookings by hike date descending (latest first) for tracking
    usort($bookings, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Determine if a booking is selected
    $selectedBookingId = $_GET['booking_id'] ?? null;
    $selectedBooking = null;
    $mountain = null;
    
    if ($selectedBookingId) {
        foreach ($bookings as $b) {
            if ($b['id'] === $selectedBookingId) {
                $selectedBooking = $b;
                break;
            }
        }
        if ($selectedBooking) {
            $mountain = getMountain($selectedBooking['mountain_id']);
        }
    }
    
    // Geographic coordinates center for Leaflet map scaling
    $mountainCoords = [
        'mnt_semeru' => ['lat' => -8.108, 'lng' => 112.922, 'scale' => 0.0006],
        'mnt_rinjani' => ['lat' => -8.411, 'lng' => 116.457, 'scale' => 0.0006],
        'mnt_merbabu' => ['lat' => -7.452, 'lng' => 110.438, 'scale' => 0.0006],
        'mnt_prau' => ['lat' => -7.179, 'lng' => 109.923, 'scale' => 0.0006],
        'mnt_bromo' => ['lat' => -7.942, 'lng' => 112.953, 'scale' => 0.0006],
        'mnt_gede' => ['lat' => -6.789, 'lng' => 106.984, 'scale' => 0.0006],
    ];
    
    $coords = $selectedBooking ? ($mountainCoords[$selectedBooking['mountain_id']] ?? ['lat' => -8.0, 'lng' => 112.0, 'scale' => 0.0006]) : null;
    
    $page_title = $selectedBooking ? 'Tracking — ' . sanitize($mountain['name']) : 'Tracking Jalur Saya';
    $page_desc = 'Pelacakan Rute & Pendakian Aktif — TERRA';
    $page_wrapper_style = 'padding-bottom: 32px;';
    
    $extra_css = '
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <style>
            .checkpoint-timeline {
                display: flex;
                flex-direction: column;
                gap: var(--space-md);
                position: relative;
                padding-left: 24px;
                margin-top: var(--space-md);
            }
            .checkpoint-timeline::before {
                content: \'\';
                position: absolute;
                left: 7px;
                top: 4px;
                bottom: 4px;
                width: 2px;
                background: var(--border-color);
            }
            .checkpoint-item {
                position: relative;
            }
            .checkpoint-dot {
                position: absolute;
                left: -24px;
                top: 4px;
                width: 16px;
                height: 16px;
                border-radius: 50%;
                background: white;
                border: 2.5px solid var(--border-color);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1;
            }
            .checkpoint-item.completed .checkpoint-dot {
                border-color: var(--accent);
                background: var(--accent);
            }
            .checkpoint-item.current .checkpoint-dot {
                border-color: var(--accent);
                background: white;
                box-shadow: 0 0 0 4px rgba(0,0,0,0.1);
            }
            .checkpoint-item.current .checkpoint-dot::after {
                content: \'\';
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: var(--accent);
            }
            .sos-modal {
                position: fixed;
                inset: 0;
                background: var(--bg-overlay);
                z-index: var(--z-modal);
                display: none;
                align-items: center;
                justify-content: center;
                padding: var(--space-lg);
            }
            .sos-modal-content {
                background: white;
                border: 1px solid var(--border-color);
                border-radius: var(--radius-sm);
                padding: var(--space-xl) var(--space-lg);
                width: 100%;
                max-width: 400px;
                text-align: center;
            }
            .tracking-header {
                margin-bottom: var(--space-lg);
            }
            .tracking-title {
                font-size: var(--font-lg);
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: -0.02em;
            }
            .tracking-desc {
                color: var(--text-secondary);
                font-size: var(--font-xs);
                margin-top: 4px;
            }
            .track-select-card {
                transition: all var(--transition-base) !important;
                border: 1px solid var(--border-color) !important;
            }
            .track-select-card:hover {
                transform: translateY(-4px);
                box-shadow: var(--shadow-lg) !important;
                border-color: var(--accent) !important;
            }
            .leaflet-container {
                z-index: 1 !important;
            }
            .leaflet-pane {
                z-index: 1 !important;
            }
            .leaflet-top, .leaflet-bottom {
                z-index: 2 !important;
            }
            
            /* Custom tabs styling */
            .tabs-container {
                display: flex;
                gap: 8px;
                margin-bottom: 20px;
                border-bottom: 1.5px solid var(--border-color);
                padding-bottom: 10px;
            }
            .tab-item {
                flex: 1;
                text-align: center;
                padding: 10px;
                font-weight: 800;
                font-size: 11px;
                text-transform: uppercase;
                border-radius: var(--radius-sm);
                transition: all 0.2s;
                text-decoration: none;
            }
        </style>
    ';
} else {
    // Default: Sort by booking creation date desc
    usort($bookings, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    $page_title = 'Booking Saya';
    $page_desc = 'Riwayat Booking Pendakian — TERRA';
    $extra_css = '
        <style>
            /* Custom tabs styling */
            .tabs-container {
                display: flex;
                gap: 8px;
                margin-bottom: 20px;
                border-bottom: 1.5px solid var(--border-color);
                padding-bottom: 10px;
            }
            .tab-item {
                flex: 1;
                text-align: center;
                padding: 10px;
                font-weight: 800;
                font-size: 11px;
                text-transform: uppercase;
                border-radius: var(--radius-sm);
                transition: all 0.2s;
                text-decoration: none;
            }
        </style>
    ';
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* Airline Boarding Pass style overrides for tickets */
.booking-ticket-card {
    display: flex;
    position: relative;
    background: #FFFFFF;
    border: 1px solid #475569;
    border-radius: 16px;
    margin-bottom: 16px;
    height: 125px;
    text-decoration: none;
    color: var(--text-primary);
    overflow: visible; /* Crucial to let cutout circles float outside the border slightly! */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.02);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-sizing: border-box;
}

.booking-ticket-card:hover {
    transform: translateY(-2px);
    border-color: var(--accent);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.05);
}
.booking-ticket-card:hover .ticket-cutout-top,
.booking-ticket-card:hover .ticket-cutout-bottom {
    border-color: var(--accent);
}

/* Punch Holes */
.ticket-cutout-top, .ticket-cutout-bottom {
    position: absolute;
    width: 14px;
    height: 14px;
    background: var(--bg-primary); /* Matches the page background color to look like a cutout! */
    border: 1px solid #475569;
    border-radius: 50%;
    z-index: 10;
    box-sizing: border-box;
    right: 75px; /* Stub width is 82px, half cutout center is 75px */
}
.ticket-cutout-top {
    top: -8px;
    clip-path: circle(50% at 50% 100%); /* Keeps border look clean */
    border-top: none;
}
.ticket-cutout-bottom {
    bottom: -8px;
    clip-path: circle(50% at 50% 0%);
    border-bottom: none;
}

/* Main Ticket Layout */
.ticket-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 12px 14px;
    overflow: hidden;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.ticket-brand {
    font-size: 8px;
    font-weight: 800;
    color: #475569;
    background: #E2E8F0;
    padding: 3px 8px;
    border-radius: 6px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
}

/* Route pass styling */
.ticket-route {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.route-point {
    display: flex;
    flex-direction: column;
}

.route-code {
    font-size: 14px;
    font-weight: 900;
    color: var(--text-primary);
    line-height: 1.1;
    letter-spacing: -0.01em;
}

.route-label {
    font-size: 8px;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    margin-top: 1px;
}

.route-connector {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 0 16px;
}

.route-line {
    width: 100%;
    height: 1.5px;
    border-top: 1.5px dotted var(--border-color);
    position: absolute;
    top: 50%;
    z-index: 1;
}

.route-icon {
    position: relative;
    z-index: 2;
    background: white;
    padding: 0 4px;
}

/* Details column */
.ticket-details {
    display: flex;
    gap: var(--space-md);
    margin-top: auto;
}

.detail-col {
    display: flex;
    flex-direction: column;
}

.detail-label {
    font-size: 7px;
    font-weight: 800;
    color: var(--text-secondary);
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.detail-val {
    font-size: 9.5px;
    font-weight: 800;
    color: var(--text-primary);
    margin-top: 1px;
    text-transform: uppercase;
}

/* Perforation Line */
.ticket-perforation {
    width: 1px;
    border-left: 1.2px dashed #475569;
    margin: 8px 0;
    height: calc(100% - 16px);
}

/* Stub Section */
.ticket-stub {
    width: 82px;
    display: flex;
    flex-direction: column;
    padding: 12px 10px;
    background: #E2E8F0;
    border-top-right-radius: 16px;
    border-bottom-right-radius: 16px;
    box-sizing: border-box;
}

.stub-header {
    font-size: 8px;
    font-weight: 900;
    color: var(--text-secondary);
    letter-spacing: 0.08em;
    text-align: center;
    margin-bottom: 8px;
    text-transform: uppercase;
}

.stub-body {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.stub-row {
    display: flex;
    flex-direction: column;
    text-align: center;
}

.stub-label {
    font-size: 6px;
    font-weight: 800;
    color: var(--text-secondary);
    text-transform: uppercase;
}

.stub-val {
    font-size: 9px;
    font-weight: 800;
    color: var(--text-primary);
    text-transform: uppercase;
}
</style>


        <div class="container mt-lg">
            <!-- Tabs Switcher -->
            <div class="tabs-container">
                <a href="?tab=ticket" class="tab-item" style="border: 1.5px solid <?= ($tab === 'ticket') ? 'var(--accent)' : 'var(--border-color)' ?>; background: <?= ($tab === 'ticket') ? 'var(--accent)' : 'white' ?>; color: <?= ($tab === 'ticket') ? 'white' : 'var(--text-primary)' ?>;">
                    🎫 Ticket
                </a>
                <a href="?tab=tracking" class="tab-item" style="border: 1.5px solid <?= ($tab === 'tracking') ? 'var(--accent)' : 'var(--border-color)' ?>; background: <?= ($tab === 'tracking') ? 'var(--accent)' : 'white' ?>; color: <?= ($tab === 'tracking') ? 'white' : 'var(--text-primary)' ?>;">
                    📍 Tracking
                </a>
            </div>

            <?php if ($tab === 'ticket'): ?>
                <!-- ============================================================ -->
                <!-- TICKET TAB -->
                <!-- ============================================================ -->
                <?php if (empty($bookings)): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon" style="opacity:0.3;display:inline-flex;align-items:center;justify-content:center;margin-bottom:var(--space-md);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:64px;height:64px;color:var(--text-secondary);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <h3 class="empty-state-title">Belum Ada Booking</h3>
                    <p class="empty-state-desc">Anda belum mendaftar pendakian. Mulai jelajahi gunung-gunung Indonesia!</p>
                    <a href="<?= BASE_URL ?>/pages/home.php" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M4 22L12 8L20 22H4Z" /><path d="M9 13.25L12 9.5L15 13.25" /><path d="M12 22L17 14L22 22H12Z" /></svg>
                        Jelajahi Gunung
                    </a>
                </div>
                <?php else: ?>
                
                <!-- Stats -->
                <div class="quick-stats mb-lg" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="quick-stat glass-card-static">
                        <div class="quick-stat-value"><?= count($bookings) ?></div>
                        <div class="quick-stat-label">Total Booking</div>
                    </div>
                    <div class="quick-stat glass-card-static">
                        <?php 
                        $totalMembers = 0;
                        foreach ($bookings as $b) $totalMembers += count($b['members']);
                        ?>
                        <div class="quick-stat-value"><?= $totalMembers ?></div>
                        <div class="quick-stat-label">Total Pendaki</div>
                    </div>
                </div>

                <!-- Booking List -->
                <div class="stagger-children">
                    <?php foreach ($bookings as $booking):
                        $isUpcoming = strtotime($booking['date']) >= strtotime($today);
                        $isPast = strtotime($booking['date']) < strtotime($today);
                        $statusLabel = $isUpcoming ? 'Akan Datang' : 'Selesai';
                        $statusClass = $isUpcoming ? 'badge-info' : 'badge-neutral';
                        
                        $day = date('d', strtotime($booking['date']));
                        $month = date('M', strtotime($booking['date']));
                        $year = date('Y', strtotime($booking['date']));
                    ?>
                    <a href="<?= BASE_URL ?>/pages/booking_success.php?id=<?= $booking['id'] ?>" class="booking-ticket-card animate-fadeIn">
                        
                        <!-- Cutout Holes -->
                        <div class="ticket-cutout-top"></div>
                        <div class="ticket-cutout-bottom"></div>

                        <!-- Main Ticket Section -->
                        <div class="ticket-main">
                            <!-- Top header info -->
                            <div class="ticket-header">
                                <span class="ticket-brand">
                                    <img src="<?= BASE_URL ?>/logo/logo.png" alt="Logo" style="width:11px; height:11px; object-fit:contain; vertical-align:middle; margin-right:4px;">
                                    TERRA TICKET
                                </span>
                                <span class="badge <?= $statusClass ?>" style="font-size: 8px; font-weight: 800; padding: 2px 6px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.05em;"><?= $statusLabel ?></span>
                            </div>

                            <!-- Route Section (Boarding pass style!) -->
                            <div class="ticket-route">
                                <div class="route-point">
                                    <span class="route-code">BASECAMP</span>
                                    <span class="route-label">VIA <?= strtoupper(sanitize($booking['trail_name'])) ?></span>
                                </div>
                                <div class="route-connector">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="route-icon" style="width: 14px; height: 14px; color: var(--accent);"><path d="M4 22L12 8L20 22H4Z" /></svg>
                                    <div class="route-line"></div>
                                </div>
                                <div class="route-point" style="text-align: right;">
                                    <?php 
                                    $mountain = getMountain($booking['mountain_id']);
                                    $displayMName = str_replace('Gunung ', 'Gn. ', $booking['mountain_name']);
                                    $altitude = $mountain ? ' · ' . $mountain['altitude'] . ' MDPL' : '';
                                    ?>
                                    <span class="route-code"><?= strtoupper(sanitize($displayMName)) ?></span>
                                    <span class="route-label"><?= $mountain ? strtoupper(sanitize($mountain['province'])) . $altitude : 'MOUNTAIN' ?></span>
                                </div>
                            </div>

                            <!-- Details Row -->
                            <div class="ticket-details">
                                <div class="detail-col">
                                    <span class="detail-label">PASSENGER / KETUA</span>
                                    <span class="detail-val"><?= sanitize(explode(' ', $booking['members'][0]['name'])[0]) ?></span>
                                </div>
                                <div class="detail-col">
                                    <span class="detail-label">DATE OF HIKE</span>
                                    <span class="detail-val"><?= date('d M Y', strtotime($booking['date'])) ?></span>
                                </div>
                                <?php
                                $trailInfo = null;
                                if ($mountain) {
                                    foreach ($mountain['trails'] as $tr) {
                                        if ($tr['id'] === $booking['trail_id']) {
                                            $trailInfo = $tr;
                                            break;
                                        }
                                    }
                                }
                                $distance = $trailInfo ? $trailInfo['distance'] : '';
                                $duration = $trailInfo ? $trailInfo['duration'] : '';
                                $estTrack = ($distance && $duration) ? $distance . ' / ' . $duration : '1-2 HARI';
                                ?>
                                <div class="detail-col">
                                    <span class="detail-label">EST. TRACK</span>
                                    <span class="detail-val" style="max-width: 95px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block;"><?= strtoupper(sanitize($estTrack)) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Perforation line -->
                        <div class="ticket-perforation"></div>

                        <!-- Stub Section -->
                        <div class="ticket-stub">
                            <div class="stub-header">
                                <span>STUB</span>
                            </div>
                            <div class="stub-body">
                                <div class="stub-row">
                                    <span class="stub-label">PAX</span>
                                    <span class="stub-val"><?= count($booking['members']) ?> CLMB</span>
                                </div>
                                <div class="stub-row" style="margin-top: 6px;">
                                    <span class="stub-label">CLASS</span>
                                    <span class="stub-val"><?= $isUpcoming ? 'UPCM' : 'DONE' ?></span>
                                </div>
                                
                                <div class="stub-barcode" style="margin-top: auto; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1px;">
                                    <!-- Mini visual barcode lines! -->
                                    <div style="display: flex; gap: 1.5px; height: 16px; width: 100%; justify-content: center;">
                                        <div style="width: 1px; background: #1E293B; height: 100%;"></div>
                                        <div style="width: 2px; background: #1E293B; height: 100%;"></div>
                                        <div style="width: 1px; background: #1E293B; height: 100%;"></div>
                                        <div style="width: 3px; background: #1E293B; height: 100%;"></div>
                                        <div style="width: 1px; background: #1E293B; height: 100%;"></div>
                                        <div style="width: 2px; background: #1E293B; height: 100%;"></div>
                                        <div style="width: 1px; background: #1E293B; height: 100%;"></div>
                                        <div style="width: 3px; background: #1E293B; height: 100%;"></div>
                                        <div style="width: 1px; background: #1E293B; height: 100%;"></div>
                                        <div style="width: 2px; background: #1E293B; height: 100%;"></div>
                                    </div>
                                    <span style="font-size: 6px; font-family: monospace; color: var(--text-secondary); font-weight: 700; transform: scale(0.95);"><?= $booking['members'][0]['barcode'] ? substr($booking['members'][0]['barcode'], -6) : 'TICKET' ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- ============================================================ -->
                <!-- TRACKING TAB -->
                <!-- ============================================================ -->
                <?php if (!$selectedBooking): ?>

                    <?php if (empty($bookings)): ?>
                        <!-- Empty State -->
                        <div class="empty-state">
                            <div class="empty-state-icon" style="opacity:0.3;display:inline-flex;align-items:center;justify-content:center;margin-bottom:var(--space-md);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:64px;height:64px;color:var(--text-secondary);"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>
                            </div>
                            <h3 class="empty-state-title">Tidak Ada Jalur Pendakian</h3>
                            <p class="empty-state-desc">Anda belum melakukan pendaftaran pendakian gunung. Silakan lakukan pemesanan tiket terlebih dahulu.</p>
                            <a href="<?= BASE_URL ?>/pages/home.php" class="btn btn-primary">Pesan Tiket Sekarang</a>
                        </div>
                    <?php else: ?>
                        <!-- Booking List style - light theme distinct from standard ticket list -->
                        <div class="stagger-children">
                            <?php foreach ($bookings as $booking):
                                $mnt = getMountain($booking['mountain_id']);
                                if (!$mnt) continue;

                                $trailInfo = null;
                                foreach ($mnt['trails'] as $tr) {
                                    if ($tr['id'] === $booking['trail_id']) {
                                        $trailInfo = $tr;
                                        break;
                                    }
                                }

                                $isUpcoming = strtotime($booking['date']) >= strtotime($today);
                            ?>
                            <a href="<?= BASE_URL ?>/pages/my_bookings.php?tab=tracking&booking_id=<?= $booking['id'] ?>" class="track-select-card" style="text-decoration:none; display:block; margin-bottom:var(--space-md); border-radius:var(--radius-md); overflow:hidden; background:white; color:var(--text-primary); position:relative;">
                                
                                <div style="padding:var(--space-md);">
                                    
                                    <!-- Header: Mountain Name & Difficulty Badge -->
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:var(--space-xs);">
                                        <div>
                                            <span style="font-size:9px; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.06em;">Pelacakan Jalur</span>
                                            <h3 style="font-size:var(--font-lg); font-weight:800; text-transform:uppercase; color:var(--text-primary); margin-top:2px; letter-spacing:-0.01em;"><?= sanitize($booking['mountain_name']) ?></h3>
                                        </div>
                                        <?php 
                                        $diffColor = '#15803D'; // Easy
                                        $diffBg = '#DCFCE7';
                                        $diffLabel = 'EASY';
                                        if ($mnt['difficulty'] === 'hard') {
                                            $diffColor = '#DC2626';
                                            $diffBg = '#FEE2E2';
                                            $diffLabel = 'HARD';
                                        } elseif ($mnt['difficulty'] === 'medium') {
                                            $diffColor = '#B45309';
                                            $diffBg = '#FEF3C7';
                                            $diffLabel = 'MEDIUM';
                                        }
                                        ?>
                                        <span style="font-size:9px; font-weight:800; background:<?= $diffBg ?>; color:<?= $diffColor ?>; padding:4px 9px; border-radius:var(--radius-full); letter-spacing:0.05em;"><?= $diffLabel ?></span>
                                    </div>

                                    <!-- Route Path details -->
                                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:14px;">
                                        <div style="width:18px; height:18px; border-radius:50%; background:var(--bg-tertiary); display:inline-flex; align-items:center; justify-content:center;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:10px; height:10px; color:var(--text-primary);"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>
                                        </div>
                                        <span style="font-size:var(--font-sm); font-weight:600; color:var(--text-primary);">Jalur: <?= sanitize($booking['trail_name']) ?></span>
                                    </div>

                                    <!-- Sporty Telemetry Grid for Route -->
                                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:1px; background:var(--border-color); border-radius:var(--radius-sm); overflow:hidden; margin-bottom:12px;">
                                        <div style="background:var(--bg-tertiary); padding:8px 6px; text-align:center;">
                                            <div style="font-size:8px; color:var(--text-secondary); font-weight:700; text-transform:uppercase; letter-spacing:0.02em;">Ketinggian</div>
                                            <div style="font-size:var(--font-sm); font-weight:800; color:var(--text-primary); margin-top:2px;"><?= number_format($mnt['altitude']) ?><span style="font-size:9px; font-weight:500;">m</span></div>
                                        </div>
                                        <div style="background:var(--bg-tertiary); padding:8px 6px; text-align:center;">
                                            <div style="font-size:8px; color:var(--text-secondary); font-weight:700; text-transform:uppercase; letter-spacing:0.02em;">Jarak Jalur</div>
                                            <div style="font-size:var(--font-sm); font-weight:800; color:var(--text-primary); margin-top:2px;"><?= $trailInfo ? sanitize($trailInfo['distance']) : '-' ?></div>
                                        </div>
                                        <div style="background:var(--bg-tertiary); padding:8px 6px; text-align:center;">
                                            <div style="font-size:8px; color:var(--text-secondary); font-weight:700; text-transform:uppercase; letter-spacing:0.02em;">Estimasi</div>
                                            <div style="font-size:var(--font-sm); font-weight:800; color:var(--text-primary); margin-top:2px;"><?= $trailInfo ? sanitize($trailInfo['duration']) : '-' ?></div>
                                        </div>
                                    </div>

                                    <!-- Anggota Terdaftar -->
                                    <div style="margin-bottom: 12px; font-size: 11px;">
                                        <div style="font-size: 8px; color: var(--text-secondary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em; margin-bottom: 6px; display: flex; align-items: center; gap: 4px;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 10px; height: 10px; color: var(--text-secondary);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                            Anggota Terdaftar (<?= count($booking['members']) ?>)
                                        </div>
                                        <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                                            <?php foreach ($booking['members'] as $idx => $mbr): ?>
                                                <span class="member-tag-<?= $booking['id'] ?>" style="background: var(--bg-tertiary); border: 1px solid var(--border-color); padding: 3px 8px; border-radius: var(--radius-sm); font-size: 10px; font-weight: 600; color: var(--text-primary); display: inline-flex; align-items: center; gap: 4px; <?= $idx >= 4 ? 'display: none !important;' : '' ?>">
                                                    <span style="font-size: 10px; opacity: 0.8;">👤</span>
                                                    <?= sanitize($mbr['name']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                            
                                            <?php if (count($booking['members']) > 4): ?>
                                                <button type="button" class="toggle-members-btn" data-booking-id="<?= $booking['id'] ?>" style="background: white; border: 1px solid var(--border-color); padding: 3px 8px; border-radius: var(--radius-sm); font-size: 10px; font-weight: 800; color: var(--accent); cursor: pointer; display: inline-flex; align-items: center; gap: 2px; transition: all var(--transition-fast); outline: none;">
                                                    <span>+<?= count($booking['members']) - 4 ?> lainnya</span>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width: 10px; height: 10px; transition: transform 0.2s;" class="arrow-icon"><polyline points="6 9 12 15 18 9"/></svg>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Footer details: Date of hike -->
                                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:10px; color:var(--text-secondary); font-weight:700; border-top:1px solid var(--border-color); padding-top:10px; margin-top:4px;">
                                        <span style="display:inline-flex; align-items:center; gap:4px;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:11px; height:11px; opacity:0.8;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                            <?= formatDate($booking['date'], 'd M Y') ?>
                                        </span>
                                        <span style="text-transform:uppercase; letter-spacing:0.05em; color:var(--accent); font-weight:800; display:inline-flex; align-items:center; gap:4px;">
                                            Mulai Tracking 
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:10px; height:10px; color:var(--accent);"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                        </span>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Detail Tracking View -->

                    <!-- Back Navigation & Date Badge -->
                    <div class="flex items-center justify-between mb-sm" style="margin-top:-8px;">
                        <a href="<?= BASE_URL ?>/pages/my_bookings.php?tab=tracking" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;font-size:11px;font-weight:700;text-transform:uppercase;border-radius:var(--radius-sm);border:1px solid var(--border-color);background:white;color:var(--text-primary);cursor:pointer;text-decoration:none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            Kembali ke Daftar
                        </a>
                        <span class="badge badge-info" style="font-weight:700;font-size:10px;text-transform:uppercase;"><?= formatDate($selectedBooking['date'], 'd M Y') ?></span>
                    </div>

                    <!-- Active Header -->
                    <div class="glass-card-static p-md mb-md">
                        <div style="font-size:10px;font-weight:800;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Pelacakan Aktif</div>
                        <h2 style="font-size:var(--font-lg);text-transform:uppercase;font-weight:800;line-height:1.2;"><?= sanitize($mountain['name']) ?></h2>
                        <div class="text-secondary text-xs" style="margin-top:2px;">Via Jalur: <?= sanitize($selectedBooking['trail_name']) ?></div>
                    </div>

                    <!-- Leaflet Interactive Satellite Map with Custom Tracker -->
                    <div class="glass-card-static p-md mb-md">
                        <h3 style="font-size:var(--font-md); font-weight:800; text-transform:uppercase; letter-spacing:-0.01em; margin-bottom:var(--space-xs);">Peta Jalur & Tracker</h3>
                        
                        <!-- Dropdown JALUR/VIA -->
                        <div class="flex items-center gap-xs mb-sm">
                            <span style="font-size: 10px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing:0.02em;">Jalur/Via:</span>
                            <select id="trailSelector" style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: white; color: var(--text-primary); cursor: pointer; outline: none;">
                                <option value="<?= sanitize($selectedBooking['trail_id']) ?>" selected><?= sanitize($selectedBooking['trail_name']) ?></option>
                                <?php foreach ($mountain['trails'] as $tr): ?>
                                    <?php if ($tr['id'] !== $selectedBooking['trail_id']): ?>
                                        <option value="<?= sanitize($tr['id']) ?>"><?= sanitize($tr['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Map container -->
                        <div id="map" style="height: 330px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); margin-bottom: var(--space-sm);"></div>

                        <!-- Custom Marker Legend -->
                        <div class="flex flex-wrap gap-sm justify-center mt-sm" style="font-size: 9px; font-weight: 700; border-top: 1px solid var(--border-color); padding-top: 12px;">
                            <span class="flex items-center gap-xs" style="color: var(--text-secondary);">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #dc2626; display: inline-block; border: 1.5px solid white; box-shadow: 0 0 0 1px #dc2626;"></span>
                                Puncak
                            </span>
                            <span class="flex items-center gap-xs" style="color: var(--text-secondary);">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #d97706; display: inline-block; border: 1.5px solid white; box-shadow: 0 0 0 1px #d97706;"></span>
                                Pos / Shelter
                            </span>
                            <span class="flex items-center gap-xs" style="color: var(--text-secondary);">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #1d4ed8; display: inline-block; border: 1.5px solid white; box-shadow: 0 0 0 1px #1d4ed8;"></span>
                                Titik Air
                            </span>
                            <span class="flex items-center gap-xs" style="color: var(--text-secondary);">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #15803d; display: inline-block; border: 1.5px solid white; box-shadow: 0 0 0 1px #15803d;"></span>
                                Area Camping
                            </span>
                        </div>
                    </div>

                    <!-- Elevation Profile Card -->
                    <?php
                    $posts = $mountain['posts'];
                    $count = count($posts);

                    $altitudes = array_column($posts, 'altitude');
                    $minAlt = min($altitudes);
                    $maxAlt = max($altitudes);
                    $chartMin = max(0, $minAlt - 200);
                    $chartMax = $maxAlt + 200;
                    $altRange = $chartMax - $chartMin;

                    $points = [];
                    $fillPoints = [];
                    for ($i = 0; $i < $count; $i++) {
                        $x = 10 + ($i / ($count - 1)) * 80;
                        $y = 35 - (($posts[$i]['altitude'] - $chartMin) / $altRange) * 25;
                        $points[] = "$x,$y";
                        if ($i === 0) {
                            $fillPoints[] = "10,35";
                        }
                        $fillPoints[] = "$x,$y";
                        if ($i === $count - 1) {
                            $fillPoints[] = "$x,35";
                        }
                    }
                    $pathLine = "M " . implode(" L ", $points);
                    $pathFill = "M " . implode(" L ", $fillPoints) . " Z";
                    ?>
                    <div class="glass-card-static p-md mb-md">
                        <h3 style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-primary);border-bottom:1px solid var(--border-color);padding-bottom:var(--space-xs);margin-bottom:var(--space-md);">Profil Elevasi Jalur</h3>
                        <div style="background:var(--bg-tertiary); border-radius:var(--radius-sm); padding:var(--space-sm); border:1px solid var(--border-color); overflow:visible;">
                            <svg viewBox="0 0 100 44" style="width:100%; display:block; overflow:visible;">
                                <defs>
                                    <linearGradient id="elevGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" style="stop-color:var(--accent); stop-opacity:0.15" />
                                        <stop offset="100%" style="stop-color:var(--accent); stop-opacity:0.0" />
                                    </linearGradient>
                                </defs>
                                <line x1="10" y1="10" x2="90" y2="10" stroke="var(--border-color)" stroke-width="0.1" stroke-dasharray="0.5" />
                                <line x1="10" y1="22.5" x2="90" y2="22.5" stroke="var(--border-color)" stroke-width="0.1" stroke-dasharray="0.5" />
                                <line x1="10" y1="35" x2="90" y2="35" stroke="var(--border-color)" stroke-width="0.1" />

                                <path d="<?= $pathFill ?>" fill="url(#elevGrad)" />
                                <path d="<?= $pathLine ?>" fill="none" stroke="var(--accent)" stroke-width="0.5" />

                                <?php foreach ($posts as $i => $post): 
                                    $x = 10 + ($i / ($count - 1)) * 80;
                                    $y = 35 - (($post['altitude'] - $chartMin) / $altRange) * 25;
                                    
                                    $shortName = sanitize(explode(' — ', $post['name'])[0]);
                                    if (strlen($shortName) > 10) {
                                        $shortName = substr($shortName, 0, 8) . '..';
                                    }
                                ?>
                                    <circle cx="<?= $x ?>" cy="<?= $y ?>" r="0.8" fill="white" stroke="var(--accent)" stroke-width="0.3" />
                                    <text x="<?= $x ?>" y="<?= $y - 2 ?>" text-anchor="middle" font-size="1.3" font-weight="800" fill="var(--text-primary)"><?= number_format($post['altitude']) ?>m</text>
                                    <text x="<?= $x ?>" y="39.5" text-anchor="middle" font-size="1.2" font-weight="700" fill="var(--text-secondary)"><?= $shortName ?></text>
                                <?php endforeach; ?>
                            </svg>
                        </div>
                    </div>

                    <!-- Telemetry Metrics Grid -->
                    <div class="grid grid-3 mb-md" style="grid-template-columns: repeat(3, 1fr);">
                        <div class="quick-stat glass-card-static">
                            <div style="font-size:9px;color:var(--text-secondary);font-weight:700;text-transform:uppercase;">Ketinggian</div>
                            <div class="quick-stat-value" style="font-size:var(--font-md);margin-top:4px;">1,840<span style="font-size:10px;font-weight:500;">m</span></div>
                        </div>
                        <div class="quick-stat glass-card-static">
                            <div style="font-size:9px;color:var(--text-secondary);font-weight:700;text-transform:uppercase;">Kecepatan</div>
                            <div class="quick-stat-value" style="font-size:var(--font-md);margin-top:4px;">2.4<span style="font-size:10px;font-weight:500;">km/h</span></div>
                        </div>
                        <div class="quick-stat glass-card-static">
                            <div style="font-size:9px;color:var(--text-secondary);font-weight:700;text-transform:uppercase;">Jarak</div>
                            <div class="quick-stat-value" style="font-size:var(--font-md);margin-top:4px;">4.2<span style="font-size:10px;font-weight:500;">km</span></div>
                        </div>
                    </div>

                    <!-- Checkpoints Timeline -->
                    <div class="glass-card-static p-lg mb-md">
                        <h3 style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-primary);border-bottom:1px solid var(--border-color);padding-bottom:var(--space-xs);margin-bottom:var(--space-md);">Progress Checkpoint</h3>
                        
                        <div class="checkpoint-timeline">
                            <?php 
                            $posts = $mountain['posts'];
                            $totalPosts = count($posts);
                            foreach ($posts as $index => $post): 
                                $statusClass = '';
                                $statusLabel = '';
                                if ($index < 2) {
                                    $statusClass = 'completed';
                                    $statusLabel = 'Telah Dilewati';
                                } elseif ($index === 2) {
                                    $statusClass = 'current';
                                    $statusLabel = 'Posisi Anda Saat Ini';
                                } else {
                                    $statusClass = 'future';
                                    $statusLabel = 'Berikutnya';
                                }
                            ?>
                            <div class="checkpoint-item <?= $statusClass ?>">
                                <div class="checkpoint-dot"></div>
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                                    <div>
                                        <div style="font-size:var(--font-sm);font-weight:700;color:<?= $statusClass === 'future' ? 'var(--text-secondary)' : 'var(--text-primary)' ?>;">
                                            <?= sanitize($post['name']) ?>
                                        </div>
                                        <div style="font-size:10px;color:var(--text-secondary);"><?= $statusLabel ?></div>
                                    </div>
                                    <div style="font-size:11px;font-weight:700;color:var(--text-secondary);">
                                        <?= number_format($post['altitude']) ?> mdpl
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Anggota Terdaftar (Registered Members) Section -->
                    <div class="glass-card-static p-lg mb-md">
                        <h3 style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-primary);border-bottom:1px solid var(--border-color);padding-bottom:var(--space-xs);margin-bottom:var(--space-md);display:flex;align-items:center;gap:6px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;color:var(--accent);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            Anggota Terdaftar (<?= count($selectedBooking['members']) ?>)
                        </h3>
                        <div style="display:flex;flex-direction:column;gap:var(--space-xs);">
                            <?php foreach ($selectedBooking['members'] as $mbr): ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;background:var(--bg-tertiary);border:1px solid var(--border-color);padding:var(--space-sm);border-radius:var(--radius-sm);">
                                <div style="display:flex;align-items:center;gap:var(--space-sm);">
                                    <div style="width:32px;height:32px;border-radius:50%;background:white;border:1.5px solid var(--border-color);display:flex;align-items:center;justify-content:center;font-weight:800;color:var(--accent);font-size:12px;box-shadow:var(--shadow-sm);">
                                        <?= strtoupper(substr($mbr['name'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div style="font-size:var(--font-sm);font-weight:700;color:var(--text-primary);"><?= sanitize($mbr['name']) ?></div>
                                        <div style="font-size:10px;color:var(--text-secondary);">NIK: <?= sanitize($mbr['ktp_number']) ?></div>
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:9px;font-weight:800;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.02em;">Tiket QR</div>
                                    <div style="font-size:10px;font-weight:700;color:var(--text-primary);font-family:monospace;margin-top:2px;"><?= sanitize($mbr['barcode']) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Map & SOS Action Buttons -->
                    <div class="flex flex-col gap-sm" style="margin-bottom: 12px !important;">
                        <a href="<?= BASE_URL ?>/pages/trail_map.php?id=<?= $mountain['id'] ?>" class="btn btn-primary btn-block" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>
                            Buka Peta Offline
                        </a>
                        <button type="button" class="btn btn-outline btn-block" id="sosBtn" style="border-color:var(--danger);color:var(--danger);display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            DARURAT — SOS
                        </button>
                    </div>

                    <!-- SOS Modal -->
                    <div class="sos-modal" id="sosModal">
                        <div class="sos-modal-content">
                            <div style="width:56px;height:56px;border-radius:50%;background:var(--danger-bg);color:var(--danger);display:inline-flex;align-items:center;justify-content:center;margin-bottom:var(--space-md);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:28px;height:28px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            </div>
                            <h3 style="text-transform:uppercase;font-weight:800;margin-bottom:var(--space-xs);color:var(--text-primary);">Kirim Sinyal SOS?</h3>
                            <p class="text-secondary text-xs mb-lg">Sinyal darurat beserta koordinat GPS terakhir Anda akan segera dikirimkan ke pos ranger terdekat.</p>
                            <div class="flex gap-sm">
                                <button class="btn btn-secondary" id="cancelSos" style="flex:1;">Batal</button>
                                <button class="btn btn-danger" id="confirmSos" style="flex:1;">Kirim</button>
                            </div>
                        </div>
                    </div>

                    <!-- Leaflet Javascript Execution -->
                    <?php
                    $postsData = [];
                    foreach ($mountain['posts'] as $index => $post) {
                        $lat = $coords['lat'] + (50 - $post['y']) * $coords['scale'];
                        $lng = $coords['lng'] + ($post['x'] - 50) * $coords['scale'];
                        $postsData[] = [
                            'name' => $post['name'],
                            'lat' => $lat,
                            'lng' => $lng,
                            'altitude' => $post['altitude'],
                            'type' => ($index === count($mountain['posts']) - 1) ? 'peak' : 'pos'
                        ];
                    }

                    $waterPointsData = [];
                    foreach ($mountain['water_points'] as $wp) {
                        $lat = $coords['lat'] + (50 - $wp['y']) * $coords['scale'];
                        $lng = $coords['lng'] + ($wp['x'] - 50) * $coords['scale'];
                        $waterPointsData[] = [
                            'name' => $wp['name'],
                            'lat' => $lat,
                            'lng' => $lng,
                            'type' => 'water'
                        ];
                    }

                    $campingAreasData = [];
                    foreach ($mountain['camping_areas'] as $camp) {
                        $lat = $coords['lat'] + (50 - $camp['y']) * $coords['scale'];
                        $lng = $coords['lng'] + ($camp['x'] - 50) * $coords['scale'];
                        $campingAreasData[] = [
                            'name' => $camp['name'],
                            'lat' => $lat,
                            'lng' => $lng,
                            'capacity' => $camp['capacity'] ?? '',
                            'type' => 'camping'
                        ];
                    }
                    ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const mapCenter = [<?= $coords['lat'] ?>, <?= $coords['lng'] ?>];
                            const map = L.map('map', {
                                center: mapCenter,
                                zoom: 14,
                                zoomControl: true
                            });

                            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP',
                                maxZoom: 18
                            }).addTo(map);

                            const trailCoords = [];
                            const posts = <?= json_encode($postsData) ?>;
                            const waterPoints = <?= json_encode($waterPointsData) ?>;
                            const campingAreas = <?= json_encode($campingAreasData) ?>;

                            posts.forEach(p => {
                                trailCoords.push([p.lat, p.lng]);
                            });

                            L.polyline(trailCoords, {
                                color: '#000000',
                                weight: 6,
                                opacity: 0.4
                            }).addTo(map);

                            L.polyline(trailCoords, {
                                color: '#ffffff',
                                weight: 3,
                                dashArray: '8, 8',
                                opacity: 1
                            }).addTo(map);

                            waterPoints.forEach(wp => {
                                L.circleMarker([wp.lat, wp.lng], {
                                    radius: 6,
                                    fillColor: '#1d4ed8',
                                    color: '#ffffff',
                                    weight: 1.5,
                                    fillOpacity: 1
                                }).addTo(map).bindPopup('<b>' + wp.name + '</b><br>Titik Air');
                            });

                            campingAreas.forEach(c => {
                                L.circleMarker([c.lat, c.lng], {
                                    radius: 6,
                                    fillColor: '#15803d',
                                    color: '#ffffff',
                                    weight: 1.5,
                                    fillOpacity: 1
                                }).addTo(map).bindPopup('<b>' + c.name + '</b><br>Camping Area (Kapasitas: ' + c.capacity + ')');
                            });

                            posts.forEach((p, idx) => {
                                const isPeak = p.type === 'peak';
                                const markerColor = isPeak ? '#dc2626' : '#d97706';
                                const markerRadius = isPeak ? 8 : 6;
                                
                                L.circleMarker([p.lat, p.lng], {
                                    radius: markerRadius,
                                    fillColor: markerColor,
                                    color: '#ffffff',
                                    weight: 1.5,
                                    fillOpacity: 1
                                }).addTo(map).bindPopup('<b>' + p.name + '</b><br>' + (isPeak ? 'Puncak' : 'Pos Pendakian') + ' (' + p.altitude + ' m)');
                            });

                            if (trailCoords.length > 0) {
                                const bounds = L.latLngBounds(trailCoords);
                                map.fitBounds(bounds, { padding: [25, 25] });
                            }

                            const trailSelector = document.getElementById('trailSelector');
                            if (trailSelector) {
                                trailSelector.addEventListener('change', function() {
                                    alert('Jalur pelacakan berhasil diubah ke: ' + this.options[this.selectedIndex].text);
                                });
                            }

                            // SOS Modal logic
                            const sosBtn = document.getElementById('sosBtn');
                            const sosModal = document.getElementById('sosModal');
                            const cancelSos = document.getElementById('cancelSos');
                            const confirmSos = document.getElementById('confirmSos');

                            if (sosBtn) {
                                sosBtn.addEventListener('click', () => {
                                    sosModal.style.display = 'flex';
                                });
                            }
                            if (cancelSos) {
                                cancelSos.addEventListener('click', () => {
                                    sosModal.style.display = 'none';
                                });
                            }
                            if (confirmSos) {
                                confirmSos.addEventListener('click', () => {
                                    sosModal.style.display = 'none';
                                    alert('🚨 Sinyal darurat SOS berhasil dikirimkan! Harap tetap tenang di lokasi Anda.');
                                });
                            }
                        });
                    </script>
                <?php endif; ?>

                <script>
                    // Toggle registered members list in choice card
                    document.addEventListener('DOMContentLoaded', () => {
                        const toggleButtons = document.querySelectorAll('.toggle-members-btn');
                        toggleButtons.forEach(btn => {
                            btn.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                
                                const bookingId = this.dataset.bookingId;
                                const hiddenTags = document.querySelectorAll('.member-tag-' + bookingId);
                                const arrowIcon = this.querySelector('.arrow-icon');
                                const btnText = this.querySelector('span');
                                
                                let isExpanded = this.dataset.expanded === 'true';
                                
                                if (isExpanded) {
                                    hiddenTags.forEach((tag, idx) => {
                                        if (idx >= 4) {
                                            tag.style.setProperty('display', 'none', 'important');
                                        }
                                    });
                                    btnText.textContent = '+' + (hiddenTags.length - 4) + ' lainnya';
                                    if (arrowIcon) arrowIcon.style.transform = 'rotate(0deg)';
                                    this.dataset.expanded = 'false';
                                } else {
                                    hiddenTags.forEach(tag => {
                                        tag.style.setProperty('display', 'inline-flex', 'important');
                                    });
                                    btnText.textContent = 'Sembunyikan';
                                    if (arrowIcon) arrowIcon.style.transform = 'rotate(180deg)';
                                    this.dataset.expanded = 'true';
                                }
                            });
                        });
                    });
                </script>
            <?php endif; ?>
        </div>
        
<?php
$active_page = 'bookings';
require_once __DIR__ . '/../includes/footer.php';
?>
