<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$user = getCurrentUser();
$bookings = getUserBookings($user['id']);

// Sort by date desc
usort($bookings, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$today = date('Y-m-d');
?>
<?php
$page_title = 'Booking Saya';
$page_desc = 'Riwayat Booking Pendakian — TERRA';
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
        </div>
        
<?php
$active_page = 'bookings';
require_once __DIR__ . '/../includes/footer.php';
?>
