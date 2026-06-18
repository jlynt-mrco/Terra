<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$user = getCurrentUser();
$mountains = readJSON(MOUNTAINS_FILE);

// Initialize session state for bookings
if (!isset($_SESSION['porter_bookings'])) {
    $_SESSION['porter_bookings'] = [];
}

// Database of Porter & Guides
$providers = [
    [
        'id' => 'prov_1',
        'name' => 'Wawan Setiawan',
        'role' => 'porter',
        'rating' => 4.8,
        'reviews_count' => 84,
        'price_per_day' => 200000,
        'specialties' => 'Gunung Merapi, Gunung Merbabu, Gunung Prau',
        'avatar' => '👨',
        'status' => 'Tersedia',
        'description' => 'Membawa barang maksimal 20kg. Berpengalaman 5 tahun menyusuri jalur pendakian Jawa Tengah.'
    ],
    [
        'id' => 'prov_2',
        'name' => 'Hasan Sadikin',
        'role' => 'pemandu',
        'rating' => 4.9,
        'reviews_count' => 112,
        'price_per_day' => 450000,
        'specialties' => 'Gunung Rinjani, Gunung Semeru',
        'avatar' => '🧗',
        'status' => 'Tersedia',
        'description' => 'Pemandu bersertifikat APGI (Asosiasi Pemandu Gunung Indonesia). Ahli navigasi darat & P3K.'
    ],
    [
        'id' => 'prov_3',
        'name' => 'Andi Wijaya',
        'role' => 'porter',
        'rating' => 4.7,
        'reviews_count' => 42,
        'price_per_day' => 220000,
        'specialties' => 'Gunung Semeru, Gunung Bromo',
        'avatar' => '🎒',
        'status' => 'Tersedia',
        'description' => 'Porter logistik, pandai memasak di gunung. Siap memikul logistik tim Anda.'
    ],
    [
        'id' => 'prov_4',
        'name' => 'Dewi Kumalasari',
        'role' => 'pemandu',
        'rating' => 5.0,
        'reviews_count' => 38,
        'price_per_day' => 400000,
        'specialties' => 'Gunung Gede, Gunung Prau',
        'avatar' => '👩',
        'status' => 'Tersedia',
        'description' => 'Pemandu ramah lingkungan, fokus edukasi ekosistem hutan & konservasi gunung.'
    ],
];

// Handle Booking Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_provider'])) {
    $providerId = sanitize($_POST['provider_id']);
    $mountainId = sanitize($_POST['mountain_id']);
    $startDate = sanitize($_POST['start_date']);
    $endDate = sanitize($_POST['end_date']);
    
    // Find provider
    $provider = null;
    foreach ($providers as $p) {
        if ($p['id'] === $providerId) {
            $provider = $p;
            break;
        }
    }
    
    // Find mountain
    $mountain = null;
    foreach ($mountains as $m) {
        if ($m['id'] === $mountainId) {
            $mountain = $m;
            break;
        }
    }
    
    if ($provider && $mountain) {
        // Calculate days
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        $diff = $end - $start;
        $days = round($diff / (60 * 60 * 24)) + 1;
        if ($days <= 0) $days = 1;
        
        $totalPrice = $provider['price_per_day'] * $days;
        
        $bookingId = 'BK_PR_' . strtoupper(bin2hex(random_bytes(4)));
        
        $newBooking = [
            'id' => $bookingId,
            'user_id' => $user['id'],
            'provider' => $provider,
            'mountain' => $mountain,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $days,
            'total_price' => $totalPrice,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $_SESSION['porter_bookings'][] = $newBooking;
        
        // Redirect to success screen on this page
        redirect('pages/booking_porter.php?booking_success=1&id=' . $bookingId);
    }
}

// Fetch success booking details
$successBooking = null;
if (isset($_GET['booking_success']) && $_GET['booking_success'] === '1' && isset($_GET['id'])) {
    $id = $_GET['id'];
    foreach ($_SESSION['porter_bookings'] as $b) {
        if ($b['id'] === $id) {
            $successBooking = $b;
            break;
        }
    }
}

$page_title = 'Porter & Pemandu';
$page_desc = 'Booking Porter dan Pemandu Pendakian Gunung — TERRA';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="booking-porter-container" style="max-width: 800px; margin: 0 auto; padding: 20px 16px;">

    <!-- SUCCESS SCREEN -->
    <?php if ($successBooking): ?>
        <div class="glass-card-static p-lg animate-fadeInUp" style="background: white; border-radius: var(--radius-xl); border: 2px solid var(--success); box-shadow: var(--shadow-lg); text-align: center; margin-bottom: 24px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--success-bg); color: var(--success); display: inline-flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px; border: 1.5px solid var(--success);">
                ✓
            </div>
            <h2 style="font-size: 16px; font-weight: 850; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0; margin-bottom: 4px;">Pemesanan Berhasil!</h2>
            <p style="font-size: 11px; color: var(--text-secondary); margin: 0; margin-bottom: 18px;">Layanan porter/pemandu Anda telah dikonfirmasi dan terdaftar di pos pendakian.</p>
            
            <!-- Ticket Info Panel -->
            <div style="border: 1px solid var(--border-color); border-radius: var(--radius-lg); background: var(--bg-tertiary); padding: 14px; text-align: left; display: flex; flex-direction: column; gap: var(--space-xs); margin-bottom: var(--space-md);">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 6px;">
                    <span style="font-size: 9px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Kode Booking</span>
                    <span style="font-size: 10px; font-weight: 900; color: var(--text-primary); font-family: monospace;"><?= $successBooking['id'] ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-size: 9px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Nama Layanan</span>
                    <span style="font-size: 10.5px; font-weight: 800; color: var(--text-primary);"><?= sanitize($successBooking['provider']['name']) ?> (<?= ucfirst($successBooking['provider']['role']) ?>)</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-size: 9px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Tujuan Gunung</span>
                    <span style="font-size: 10.5px; font-weight: 800; color: var(--text-primary);"><?= sanitize($successBooking['mountain']['name']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-size: 9px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Durasi Sewa</span>
                    <span style="font-size: 10.5px; font-weight: 800; color: var(--text-primary);"><?= formatDate($successBooking['start_date']) ?> s/d <?= formatDate($successBooking['end_date']) ?> (<?= $successBooking['days'] ?> Hari)</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 6px; margin-top: 2px;">
                    <span style="font-size: 9px; font-weight: 850; color: var(--text-primary); text-transform: uppercase;">Total Pembayaran</span>
                    <span style="font-size: 11.5px; font-weight: 900; color: var(--accent);">Rp <?= number_format($successBooking['total_price'], 0, ',', '.') ?></span>
                </div>
            </div>

            <!-- E-Ticket QR Code -->
            <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; margin-bottom: 18px;">
                <div style="border: 1px solid var(--border-color); padding: 8px; background: white; border-radius: var(--radius-md); display: inline-block;">
                    <?= generateQRCodeSVG($successBooking['id'], 110) ?>
                </div>
                <span style="font-size: 8.5px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Tunjukkan QR Code ini di Basecamp Pos Awal</span>
            </div>

            <a href="<?= BASE_URL ?>/pages/booking_porter.php" class="btn btn-primary btn-block btn-sm" style="font-size: 10.5px; border-radius: var(--radius-sm);">
                KEMBALI KE DIREKTORI
            </a>
        </div>
    <?php endif; ?>

    <!-- MAIN DIRECTORY SCREEN -->
    <!-- Header & Back Button -->
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
        <a href="<?= BASE_URL ?>/pages/home.php" style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; border: 1px solid var(--border-color); background: white; color: var(--text-primary); text-decoration: none; transition: all var(--transition-fast);" onmouseover="this.style.background='var(--bg-secondary)';" onmouseout="this.style.background='white';">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-size: 20px; font-weight: 800; color: var(--text-primary); margin: 0; line-height: 1.2;">PORTER & PEMANDU</h1>
            <p style="font-size: 11px; color: var(--text-secondary); margin: 0; margin-top: 2px;">Sewa porter angkut logistik atau pemandu lokal bersertifikat.</p>
        </div>
    </div>

    <!-- Filters and Search -->
    <div style="display: flex; flex-direction: column; gap: var(--space-sm); margin-bottom: var(--space-md);">
        
        <div class="search-bar" style="margin: 0; padding: 10px 14px;">
            <span class="search-icon">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input type="text" id="providerSearchInput" placeholder="Cari nama atau spesialisasi gunung..." autocomplete="off" style="font-size: 11.5px;">
        </div>

        <div style="display: flex; gap: 6px;">
            <button class="btn btn-primary btn-sm role-filter-btn active" data-role="all" style="flex:1; font-size: 9.5px; padding: 8px; border-radius: var(--radius-sm); text-transform: uppercase;">Semua</button>
            <button class="btn btn-secondary btn-sm role-filter-btn" data-role="porter" style="flex:1; font-size: 9.5px; padding: 8px; border-radius: var(--radius-sm); text-transform: uppercase;">Porter Saja</button>
            <button class="btn btn-secondary btn-sm role-filter-btn" data-role="pemandu" style="flex:1; font-size: 9.5px; padding: 8px; border-radius: var(--radius-sm); text-transform: uppercase;">Pemandu Saja</button>
        </div>
    </div>

    <!-- Providers List -->
    <div id="providersGrid" style="display: flex; flex-direction: column; gap: var(--space-sm);">
        <?php foreach ($providers as $prov): ?>
            <div class="glass-card-static provider-card" data-role="<?= $prov['role'] ?>" data-name="<?= strtolower($prov['name']) ?>" data-specialties="<?= strtolower($prov['specialties']) ?>" style="padding: var(--space-md); border-radius: var(--radius-lg); background: white; border: 1px solid var(--border-color); display: flex; gap: 14px;">
                
                <!-- Avatar column -->
                <div style="width: 52px; height: 52px; border-radius: 50%; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; font-size: 24px; border: 1.5px solid var(--border-color); flex-shrink: 0;">
                    <?= $prov['avatar'] ?>
                </div>

                <!-- Info column -->
                <div style="flex: 1; display: flex; flex-direction: column; gap: 4px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <span style="font-size: 8.5px; font-weight: 900; text-transform: uppercase; background: <?= $prov['role'] === 'porter' ? 'var(--info-bg)' : 'var(--warning-bg)' ?>; color: <?= $prov['role'] === 'porter' ? 'var(--info)' : 'var(--warning)' ?>; padding: 2px 6px; border-radius: var(--radius-sm); letter-spacing: 0.04em;">
                                <?= $prov['role'] ?>
                            </span>
                            <h3 style="font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin: 4px 0 0 0;"><?= sanitize($prov['name']) ?></h3>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 12px; font-weight: 900; color: var(--accent);">Rp <?= number_format($prov['price_per_day'], 0, ',', '.') ?></div>
                            <div style="font-size: 8px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Per Hari</div>
                        </div>
                    </div>

                    <!-- Description -->
                    <p style="font-size: 11px; color: var(--text-secondary); line-height: 1.4; margin: 2px 0 6px 0;">
                        <?= sanitize($prov['description']) ?>
                    </p>

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: var(--space-xs); margin-top: 2px;">
                        <!-- Ratings -->
                        <div style="display: flex; align-items: center; gap: 4px;">
                            <span style="color: #F59E0B; font-size: 12px;">★</span>
                            <span style="font-size: 10.5px; font-weight: 800; color: var(--text-primary);"><?= $prov['rating'] ?></span>
                            <span style="font-size: 9px; color: var(--text-secondary);">(<?= $prov['reviews_count'] ?> trip)</span>
                        </div>
                        
                        <!-- Specialized mountains tags -->
                        <div style="font-size: 9px; color: var(--text-secondary); font-weight: 700; max-width: 170px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                            Spesialis: <span style="color: var(--text-primary); font-weight: 800;"><?= sanitize($prov['specialties']) ?></span>
                        </div>
                    </div>

                    <!-- Book trigger button -->
                    <button class="btn btn-primary btn-sm open-booking-modal-trigger" 
                            data-provider-id="<?= $prov['id'] ?>" 
                            data-provider-name="<?= sanitize($prov['name']) ?>" 
                            data-provider-role="<?= ucfirst($prov['role']) ?>" 
                            data-price="<?= $prov['price_per_day'] ?>" 
                            style="margin-top: 8px; border-radius: var(--radius-sm); font-size: 9.5px; padding: 8px; height: 32px; font-weight: 800; width: 100%;">
                        PESAN SEKARANG
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal: Booking Form -->
<div id="bookingProviderModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: var(--bg-overlay); z-index: var(--z-modal-backdrop); align-items: center; justify-content: center; padding: 16px;">
    <div class="glass-card-static" style="background: white; width: 100%; max-width: 440px; padding: var(--space-lg); border-radius: var(--radius-xl); box-shadow: var(--shadow-xl); border: 1.5px solid var(--accent); position: relative; animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <button id="closeBookingModalBtn" style="position: absolute; top: 18px; right: 18px; background: none; border: none; font-size: 16px; cursor: pointer; color: var(--text-secondary);">&times;</button>
        
        <h2 style="font-size: 15px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0; margin-bottom: 2px; color: var(--text-primary);">Form Reservasi Layanan</h2>
        <p id="modalSubtitle" style="font-size: 10.5px; color: var(--text-secondary); margin: 0; margin-bottom: var(--space-md); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Penyewaan porter</p>
        
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: var(--space-sm);">
            <input type="hidden" id="formProviderId" name="provider_id" value="">
            <input type="hidden" name="book_provider" value="1">
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 9px; margin-bottom: 4px;">Pilih Gunung Tujuan</label>
                <select name="mountain_id" class="form-input" style="height: 38px; padding: 0 10px; font-size: 11px;" required>
                    <option value="" disabled selected>Pilih gunung tujuan...</option>
                    <?php foreach ($mountains as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= sanitize($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-xs);">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 9px; margin-bottom: 4px;">Tanggal Mulai</label>
                    <input type="date" id="formStartDate" name="start_date" class="form-input" style="height: 38px; padding: 0 8px; font-size: 11px;" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 9px; margin-bottom: 4px;">Tanggal Selesai</label>
                    <input type="date" id="formEndDate" name="end_date" class="form-input" style="height: 38px; padding: 0 8px; font-size: 11px;" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
            </div>

            <!-- Price Breakdown Calculation Panel -->
            <div style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 10px; font-size: 11px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="color: var(--text-secondary);">Tarif Harian:</span>
                    <span style="font-weight: 800; color: var(--text-primary);" id="calcDailyPrice">Rp 0</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="color: var(--text-secondary);">Durasi Pendakian:</span>
                    <span style="font-weight: 800; color: var(--text-primary);" id="calcDays">0 Hari</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--border-color); padding-top: 4px; margin-top: 4px;">
                    <span style="font-weight: 850; color: var(--text-primary);">Estimasi Total:</span>
                    <span style="font-weight: 900; color: var(--accent);" id="calcTotalPrice">Rp 0</span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="height: 38px; font-size: 11px; font-weight: 800; border-radius: var(--radius-sm); margin-top: 4px;">
                KONFIRMASI RESERVASI
            </button>
        </form>
    </div>
</div>

<script>
    // Directory Filters, Search & Modal calculation logic
    (function() {
        const searchInput = document.getElementById('providerSearchInput');
        const filterBtns = document.querySelectorAll('.role-filter-btn');
        const cards = document.querySelectorAll('.provider-card');
        
        let activeRole = 'all';
        let searchQuery = '';

        function applyFilter() {
            cards.forEach(card => {
                const name = card.dataset.name;
                const specialties = card.dataset.specialties;
                const role = card.dataset.role;

                const matchesSearch = name.includes(searchQuery) || specialties.includes(searchQuery);
                const matchesRole = activeRole === 'all' || role === activeRole;

                if (matchesSearch && matchesRole) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                searchQuery = this.value.toLowerCase();
                applyFilter();
            });
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => {
                    b.classList.remove('active');
                    b.classList.remove('btn-primary');
                    b.classList.add('btn-secondary');
                });
                this.classList.add('active');
                this.classList.remove('btn-secondary');
                this.classList.add('btn-primary');
                
                activeRole = this.dataset.role;
                applyFilter();
            });
        });

        // Booking Modal Calculator
        const modal = document.getElementById('bookingProviderModal');
        const closeBtn = document.getElementById('closeBookingModalBtn');
        const triggers = document.querySelectorAll('.open-booking-modal-trigger');
        
        const formProviderIdInput = document.getElementById('formProviderId');
        const modalSubtitle = document.getElementById('modalSubtitle');
        const calcDailyPrice = document.getElementById('calcDailyPrice');
        const calcDays = document.getElementById('calcDays');
        const calcTotalPrice = document.getElementById('calcTotalPrice');
        
        const startDateInput = document.getElementById('formStartDate');
        const endDateInput = document.getElementById('formEndDate');
        
        let currentProviderPrice = 0;

        function updateModalCalculation() {
            const startVal = startDateInput.value;
            const endVal = endDateInput.value;
            
            if (startVal && endVal) {
                const start = new Date(startVal);
                const end = new Date(endVal);
                const diffTime = end - start;
                let days = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                if (days <= 0) days = 1;
                
                const total = currentProviderPrice * days;
                calcDays.textContent = days + ' Hari';
                calcTotalPrice.textContent = 'Rp ' + total.toLocaleString('id-ID');
            } else {
                calcDays.textContent = '0 Hari';
                calcTotalPrice.textContent = 'Rp 0';
            }
        }

        triggers.forEach(trigger => {
            trigger.addEventListener('click', function() {
                const id = this.dataset.providerId;
                const name = this.dataset.providerName;
                const role = this.dataset.providerRole;
                const price = parseInt(this.dataset.price);
                
                currentProviderPrice = price;
                
                formProviderIdInput.value = id;
                modalSubtitle.textContent = role + ': ' + name;
                calcDailyPrice.textContent = 'Rp ' + price.toLocaleString('id-ID');
                
                // Set default dates
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                const tomorrowStr = tomorrow.toISOString().split('T')[0];
                
                startDateInput.value = tomorrowStr;
                startDateInput.min = tomorrowStr;
                
                const dayAfter = new Date();
                dayAfter.setDate(dayAfter.getDate() + 3);
                const dayAfterStr = dayAfter.toISOString().split('T')[0];
                
                endDateInput.value = dayAfterStr;
                endDateInput.min = tomorrowStr;
                
                updateModalCalculation();
                
                modal.style.display = 'flex';
            });
        });

        if (closeBtn && modal) {
            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });

            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function() {
                endDateInput.min = this.value;
                if (endDateInput.value < this.value) {
                    endDateInput.value = this.value;
                }
                updateModalCalculation();
            });
            endDateInput.addEventListener('change', updateModalCalculation);
        }
    })();
</script>

<style>
    @keyframes modalPop {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>

<?php
$active_page = 'home';
require_once __DIR__ . '/../includes/footer.php';
?>
