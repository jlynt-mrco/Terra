<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$bookingId = $_GET['id'] ?? '';
$booking = getBooking($bookingId);

if (!$booking || $booking['user_id'] !== $_SESSION['user_id']) {
    redirect('pages/home.php');
}

$mountain = getMountain($booking['mountain_id']);
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
$estTrack = ($distance && $duration) ? $distance . ' / ' . $duration : '';
?>
<?php
$page_title = 'Pendaftaran Berhasil';
$page_desc = 'Pendaftaran Berhasil — TERRA';
$page_wrapper_style = 'padding-bottom: 32px;';
$extra_css = '<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>';
require_once __DIR__ . '/../includes/header.php';
?>

        <!-- Back Navigation -->
        <div class="container mt-md" style="margin-bottom: var(--space-md);">
            <a href="<?= BASE_URL ?>/pages/my_bookings.php" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;font-size:11px;font-weight:700;text-transform:uppercase;border-radius:var(--radius-sm);border:1px solid var(--border-color);background:white;color:var(--text-primary);cursor:pointer;text-decoration:none;transition:all 0.15s ease;" onmouseover="this.style.background='var(--bg-secondary)';" onmouseout="this.style.background='white';">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Kembali ke Ticket
            </a>
        </div>

        <!-- Success Animation -->
        <div class="success-animation animate-fadeInUp">
            <div class="success-checkmark" style="display:inline-flex;align-items:center;justify-content:center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:30px;height:30px;"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h2 class="success-title">Booking Berhasil!</h2>
            <p class="success-subtitle">Tunjukkan barcode check-in di bawah ini di pos awal pendakian</p>
        </div>

        <!-- Representative Barcode (Top) -->
        <div class="container">
            <?php 
            $leader = $booking['members'][0]; 
            ?>
            <div class="barcode-card animate-fadeInUp" style="margin-bottom: var(--space-lg);">
                <!-- TERRA watermark -->
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; margin-bottom:14px;">
                    <img src="<?= BASE_URL ?>/logo/logo.png" alt="Logo" style="width:36px; height:36px; object-fit:contain;">
                    <span style="font-weight:800;color:var(--text-primary);font-size:var(--font-sm);text-transform:uppercase;letter-spacing:0.1em;">TERRA TICKET</span>
                </div>
                
                <div class="barcode-card-mountain"><?= sanitize($booking['mountain_name']) ?> (<?= $mountain ? $mountain['altitude'] . ' MDPL' : '' ?>)</div>
                <div style="font-size:var(--font-xs);color:var(--text-secondary);margin-bottom:12px;text-transform:uppercase;font-weight:700;"><?= formatDate($booking['date'], 'd M Y') ?> · VIA <?= sanitize($booking['trail_name']) ?> · <?= $mountain ? sanitize($mountain['location']) : '' ?></div>
                
                <div style="border-top:1.5px dashed var(--border-color);padding-top:12px;margin-bottom:12px;"></div>
                
                <div class="barcode-card-name"><?= sanitize($leader['name']) ?> (Ketua Kelompok)</div>
                <div class="barcode-card-code" style="letter-spacing: 0.1em; font-family: monospace;"><?= $leader['barcode'] ?></div>
                
                <div class="barcode-card-qr">
                    <div id="qr-group" data-code="<?= $leader['barcode'] ?>"></div>
                </div>
                
                <div style="border-top:1.5px dashed var(--border-color);padding-top:8px;margin-bottom:8px;"></div>
                <div class="barcode-card-info" style="font-size: var(--font-xs); font-weight: 700; color: var(--success);">Tunjukkan barcode ini untuk check-in seluruh anggota kelompok</div>
            </div>
        </div>

        <!-- Booking Summary -->
        <div class="container">
            <div class="glass-card-static p-lg mb-lg">
                <div class="review-section" style="background:transparent;border:none;padding:0;">
                    <div class="review-row">
                        <span class="review-label">Gunung</span>
                        <span class="review-value"><?= sanitize($booking['mountain_name']) ?> (<?= $mountain ? $mountain['altitude'] . ' MDPL' : '' ?>)</span>
                    </div>
                    <div class="review-row">
                        <span class="review-label">Lokasi</span>
                        <span class="review-value"><?= $mountain ? sanitize($mountain['location']) : '' ?></span>
                    </div>
                    <div class="review-row">
                        <span class="review-label">Tanggal</span>
                        <span class="review-value"><?= formatDate($booking['date'], 'l, d M Y') ?></span>
                    </div>
                    <div class="review-row">
                        <span class="review-label">Jalur</span>
                        <span class="review-value">Via <?= sanitize($booking['trail_name']) ?></span>
                    </div>
                    <?php if ($estTrack): ?>
                    <div class="review-row">
                        <span class="review-label">Estimasi Trek</span>
                        <span class="review-value"><?= sanitize($estTrack) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="review-row">
                        <span class="review-label">Status</span>
                        <span class="badge badge-success" style="display:inline-flex;align-items:center;gap:4px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px;"><polyline points="20 6 9 17 4 12"/></svg>
                            Terkonfirmasi
                        </span>
                    </div>
                    <div class="review-row">
                        <span class="review-label">Jumlah Anggota</span>
                        <span class="review-value"><?= count($booking['members']) ?> orang</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Member List -->
        <div class="container mb-lg">
            <div class="glass-card-static p-lg">
                <h4 style="margin-bottom:var(--space-md); font-weight: 700;">Daftar Anggota Kelompok</h4>
                <div style="display:flex; flex-direction:column; gap: 8px;">
                    <?php foreach ($booking['members'] as $i => $member): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--space-sm);background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:var(--radius-sm);">
                        <div style="display:flex;align-items:center;gap:var(--space-sm);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;color:var(--text-secondary);"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <div>
                                <div style="font-weight:700;font-size:var(--font-sm);"><?= sanitize($member['name']) ?> <?php if ($i === 0): ?><span class="badge badge-success" style="font-size:8px;padding:2px 4px;margin-left:4px;">Ketua</span><?php endif; ?></div>
                                <div class="text-secondary text-xs">NIK: <?= sanitize($member['ktp_number']) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="container mt-lg" style="margin-bottom: 12px !important;">
            <a href="<?= BASE_URL ?>/pages/home.php" class="btn btn-primary btn-block btn-lg" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Kembali ke Beranda
            </a>
        </div>

    <script>
        // Generate QR codes
        document.querySelectorAll('[id^="qr-"]').forEach(el => {
            const code = el.dataset.code;
            try {
                const qr = qrcode(0, 'M');
                qr.addData(code);
                qr.make();
                el.innerHTML = qr.createSvgTag(5, 0);
                // Style the SVG
                const svg = el.querySelector('svg');
                if (svg) {
                    svg.style.width = '160px';
                    svg.style.height = '160px';
                }
            } catch(e) {
                // Fallback to text
                el.innerHTML = '<div style="padding:20px;background:#f0f0f0;border-radius:8px;font-family:monospace;font-size:12px;">' + code + '</div>';
            }
        });
    </script>
<?php
$active_page = 'bookings';
require_once __DIR__ . '/../includes/footer.php';
?>
