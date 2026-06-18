<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$user = getCurrentUser();
$mountains = readJSON(MOUNTAINS_FILE);

// Initialize session state for rentals
if (!isset($_SESSION['rentals'])) {
    $_SESSION['rentals'] = [];
}

// Gear Items catalog
$gearCatalog = [
    [
        'id' => 'gear_1',
        'name' => 'Tenda Dome Consina 4 Orang',
        'category' => 'tenda',
        'price_per_day' => 45000,
        'image' => '🎪',
        'store' => 'Terra Outdoor Yogyakarta',
        'description' => 'Tenda double layer tahan badai dan hujan lebat. Termasuk frame fiber & pasak lengkap.'
    ],
    [
        'id' => 'gear_2',
        'name' => 'Tas Carrier Eiger 60 Liter',
        'category' => 'tas',
        'price_per_day' => 30000,
        'image' => '🎒',
        'store' => 'Terra Outdoor Yogyakarta',
        'description' => 'Sistem punggung ergonomis mengurangi kelelahan. Kuat, tahan air, termasuk rain cover.'
    ],
    [
        'id' => 'gear_3',
        'name' => 'Sleeping Bag Thermal Polar',
        'category' => 'tidur',
        'price_per_day' => 12000,
        'image' => '🛌',
        'store' => 'Alat Gunung Sleman',
        'description' => 'Bahan polar hangat tebal. Tahan suhu dingin ekstrim hingga 5°C.'
    ],
    [
        'id' => 'gear_4',
        'name' => 'Cooking Set Nesting & Kompor',
        'category' => 'masak',
        'price_per_day' => 15000,
        'image' => '🍳',
        'store' => 'Sewa Gunung Kaliurang',
        'description' => 'Paket kompor gas portable mini dan nesting alumunium (panci + wajan kecil).'
    ],
    [
        'id' => 'gear_5',
        'name' => 'Headlamp LED Petzl',
        'category' => 'alat',
        'price_per_day' => 8000,
        'image' => '🔦',
        'store' => 'Alat Gunung Sleman',
        'description' => 'Senter kepala LED super terang dengan baterai awet. Cocok untuk summit attack malam hari.'
    ],
    [
        'id' => 'gear_6',
        'name' => 'Jaket Windproof Waterproof',
        'category' => 'pakaian',
        'price_per_day' => 25000,
        'image' => '🧥',
        'store' => 'Terra Outdoor Yogyakarta',
        'description' => 'Jaket penahan angin dingin gunung dan air hujan gerimis. Model sporty modis.'
    ],
    [
        'id' => 'gear_7',
        'name' => 'Trekking Pole Carbon (Sepasang)',
        'category' => 'alat',
        'price_per_day' => 12000,
        'image' => '🦯',
        'store' => 'Sewa Gunung Kaliurang',
        'description' => 'Tongkat bantu pendakian yang ringan dan kokoh untuk menjaga keseimbangan lutut.'
    ],
    [
        'id' => 'gear_8',
        'name' => 'Matras Angin Kembung Otomatis',
        'category' => 'tidur',
        'price_per_day' => 10000,
        'image' => '🛌',
        'store' => 'Alat Gunung Sleman',
        'description' => 'Matras angin nyaman untuk tidur nyenyak di dalam tenda tanpa kerikil tajam.'
    ]
];

// Handle rental submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_rental'])) {
    $cartDataJSON = $_POST['cart_data'];
    $mountainId = sanitize($_POST['mountain_id']);
    $startDate = sanitize($_POST['start_date']);
    $endDate = sanitize($_POST['end_date']);
    
    $cartItems = json_decode($cartDataJSON, true);
    
    // Find mountain
    $mountain = null;
    foreach ($mountains as $m) {
        if ($m['id'] === $mountainId) {
            $mountain = $m;
            break;
        }
    }
    
    if ($cartItems && !empty($cartItems) && $mountain) {
        // Calculate days
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        $diff = $end - $start;
        $days = round($diff / (60 * 60 * 24)) + 1;
        if ($days <= 0) $days = 1;
        
        // Populate items from catalog and compute total
        $detailedItems = [];
        $dailyTotal = 0;
        foreach ($cartItems as $itemId => $qty) {
            foreach ($gearCatalog as $gear) {
                if ($gear['id'] === $itemId) {
                    $gear['qty'] = $qty;
                    $detailedItems[] = $gear;
                    $dailyTotal += $gear['price_per_day'] * $qty;
                    break;
                }
            }
        }
        
        $totalCost = $dailyTotal * $days;
        $rentalId = 'RT_' . strtoupper(bin2hex(random_bytes(4)));
        
        $newRental = [
            'id' => $rentalId,
            'user_id' => $user['id'],
            'items' => $detailedItems,
            'mountain' => $mountain,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $days,
            'daily_total' => $dailyTotal,
            'total_price' => $totalCost,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $_SESSION['rentals'][] = $newRental;
        
        redirect('pages/rental.php?rental_success=1&id=' . $rentalId);
    }
}

// Fetch success rental invoice details
$successRental = null;
if (isset($_GET['rental_success']) && $_GET['rental_success'] === '1' && isset($_GET['id'])) {
    $id = $_GET['id'];
    foreach ($_SESSION['rentals'] as $r) {
        if ($r['id'] === $id) {
            $successRental = $r;
            break;
        }
    }
}

$page_title = 'Rental Perlengkapan';
$page_desc = 'Rental Perlengkapan Pendakian Gunung Indonesia — TERRA';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="rental-container" style="max-width: 800px; margin: 0 auto; padding: 20px 16px; padding-bottom: 90px;">

    <!-- SUCCESS SCREEN -->
    <?php if ($successRental): ?>
        <div class="glass-card-static p-lg animate-fadeInUp" style="background: white; border-radius: var(--radius-xl); border: 2px solid var(--success); box-shadow: var(--shadow-lg); text-align: center; margin-bottom: 24px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--success-bg); color: var(--success); display: inline-flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px; border: 1.5px solid var(--success);">
                ✓
            </div>
            <h2 style="font-size: 16px; font-weight: 850; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin: 0; margin-bottom: 4px;">Penyewaan Berhasil!</h2>
            <p style="font-size: 11px; color: var(--text-secondary); margin: 0; margin-bottom: 18px;">Perlengkapan Anda telah siap untuk diambil di basecamp pos tujuan.</p>
            
            <!-- Rental Invoice Panel -->
            <div style="border: 1px solid var(--border-color); border-radius: var(--radius-lg); background: var(--bg-tertiary); padding: 14px; text-align: left; display: flex; flex-direction: column; gap: var(--space-xs); margin-bottom: var(--space-md);">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 6px;">
                    <span style="font-size: 9px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Nomor Invoice</span>
                    <span style="font-size: 10px; font-weight: 900; color: var(--text-primary); font-family: monospace;"><?= $successRental['id'] ?></span>
                </div>
                
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-xs); margin-bottom: var(--space-xs);">
                    <span style="font-size: 8px; font-weight: 800; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.04em;">Daftar Barang</span>
                    <?php foreach ($successRental['items'] as $item): ?>
                        <div style="display: flex; justify-content: space-between; font-size: 10.5px; margin-top: 4px;">
                            <span style="color: var(--text-secondary);"><?= sanitize($item['name']) ?> <span style="font-weight: 800; color: var(--text-primary);">x<?= $item['qty'] ?></span></span>
                            <span style="font-weight: 700; color: var(--text-primary);">Rp <?= number_format($item['price_per_day'] * $item['qty'], 0, ',', '.') ?>/hr</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <span style="font-size: 9px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Tempat Pengambilan</span>
                    <span style="font-size: 10.5px; font-weight: 800; color: var(--text-primary);">Basecamp <?= sanitize($successRental['mountain']['name']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-size: 9px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Periode Sewa</span>
                    <span style="font-size: 10.5px; font-weight: 800; color: var(--text-primary);"><?= formatDate($successRental['start_date']) ?> s/d <?= formatDate($successRental['end_date']) ?> (<?= $successRental['days'] ?> Hari)</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 6px; margin-top: 2px;">
                    <span style="font-size: 9px; font-weight: 850; color: var(--text-primary); text-transform: uppercase;">Total Biaya Sewa</span>
                    <span style="font-size: 11.5px; font-weight: 900; color: var(--accent);">Rp <?= number_format($successRental['total_price'], 0, ',', '.') ?></span>
                </div>
            </div>

            <!-- QR Verification -->
            <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; margin-bottom: 18px;">
                <div style="border: 1px solid var(--border-color); padding: 8px; background: white; border-radius: var(--radius-md); display: inline-block;">
                    <?= generateQRCodeSVG($successRental['id'], 110) ?>
                </div>
                <span style="font-size: 8.5px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Tunjukkan QR Code ini di Pos Pengambilan Basecamp</span>
            </div>

            <a href="<?= BASE_URL ?>/pages/rental.php" class="btn btn-primary btn-block btn-sm" style="font-size: 10.5px; border-radius: var(--radius-sm);">
                KEMBALI BELANJA
            </a>
        </div>
    <?php endif; ?>

    <!-- MARKETPLACE CATALOG SCREEN -->
    <!-- Header & Back Button -->
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
        <a href="<?= BASE_URL ?>/pages/home.php" style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; border: 1px solid var(--border-color); background: white; color: var(--text-primary); text-decoration: none; transition: all var(--transition-fast);" onmouseover="this.style.background='var(--bg-secondary)';" onmouseout="this.style.background='white';">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-size: 20px; font-weight: 800; color: var(--text-primary); margin: 0; line-height: 1.2;">SEWA PERLENGKAPAN</h1>
            <p style="font-size: 11px; color: var(--text-secondary); margin: 0; margin-top: 2px;">Pesan perlengkapan camping dan ambil langsung di pos pendakian.</p>
        </div>
    </div>

    <!-- Search and Filter Tags -->
    <div style="display: flex; flex-direction: column; gap: var(--space-sm); margin-bottom: var(--space-md);">
        
        <div class="search-bar" style="margin: 0; padding: 10px 14px;">
            <span class="search-icon">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input type="text" id="gearSearchInput" placeholder="Cari nama perlengkapan..." autocomplete="off" style="font-size: 11.5px;">
        </div>

        <div style="display: flex; gap: 4px; overflow-x: auto; padding-bottom: 4px; scrollbar-width: none;">
            <button class="btn btn-primary btn-sm gear-filter-btn active" data-cat="all" style="font-size: 9px; padding: 6px 12px; border-radius: var(--radius-full); text-transform: uppercase;">Semua</button>
            <button class="btn btn-secondary btn-sm gear-filter-btn" data-cat="tenda" style="font-size: 9px; padding: 6px 12px; border-radius: var(--radius-full); text-transform: uppercase;">Tenda</button>
            <button class="btn btn-secondary btn-sm gear-filter-btn" data-cat="tas" style="font-size: 9px; padding: 6px 12px; border-radius: var(--radius-full); text-transform: uppercase;">Tas</button>
            <button class="btn btn-secondary btn-sm gear-filter-btn" data-cat="tidur" style="font-size: 9px; padding: 6px 12px; border-radius: var(--radius-full); text-transform: uppercase;">Tidur</button>
            <button class="btn btn-secondary btn-sm gear-filter-btn" data-cat="masak" style="font-size: 9px; padding: 6px 12px; border-radius: var(--radius-full); text-transform: uppercase;">Masak</button>
            <button class="btn btn-secondary btn-sm gear-filter-btn" data-cat="alat" style="font-size: 9px; padding: 6px 12px; border-radius: var(--radius-full); text-transform: uppercase;">Peralatan</button>
        </div>
    </div>

    <!-- Catalog Catalog -->
    <div id="catalogGrid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--space-xs);">
        <?php foreach ($gearCatalog as $gear): ?>
            <div class="glass-card-static gear-card" data-cat="<?= $gear['category'] ?>" data-name="<?= strtolower($gear['name']) ?>" style="padding: 12px; border-radius: var(--radius-lg); background: white; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between; gap: var(--space-xs);">
                <div>
                    <!-- Visual Icon -->
                    <div style="height: 90px; border-radius: var(--radius-md); background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; font-size: 44px; border: 1px solid var(--border-color); margin-bottom: 8px;">
                        <?= $gear['image'] ?>
                    </div>
                    
                    <span style="font-size: 7.5px; font-weight: 800; text-transform: uppercase; color: var(--text-tertiary); letter-spacing: 0.05em;"><?= $gear['store'] ?></span>
                    <h3 style="font-size: 11.5px; font-weight: 800; color: var(--text-primary); margin: 2px 0 4px 0; line-height: 1.3; height: 2.6em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; text-overflow: ellipsis;"><?= sanitize($gear['name']) ?></h3>
                    
                    <p style="font-size: 9.5px; color: var(--text-secondary); line-height: 1.3; margin: 0; margin-bottom: 6px; height: 3.9em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; text-overflow: ellipsis;"><?= sanitize($gear['description']) ?></p>
                </div>

                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-xs); border-top: 1px solid var(--border-color); padding-top: 6px;">
                        <span style="font-size: 11px; font-weight: 900; color: var(--accent);">Rp <?= number_format($gear['price_per_day'], 0, ',', '.') ?></span>
                        <span style="font-size: 8px; color: var(--text-secondary); font-weight: 700; text-transform: uppercase;">/ Hari</span>
                    </div>

                    <!-- Cart dynamic control -->
                    <div class="cart-control-wrapper" id="control_<?= $gear['id'] ?>">
                        <button class="btn btn-primary btn-block btn-sm add-to-cart-trigger" 
                                data-id="<?= $gear['id'] ?>" 
                                data-name="<?= sanitize($gear['name']) ?>" 
                                data-price="<?= $gear['price_per_day'] ?>" 
                                style="font-size: 9px; padding: 7px; height: 28px; font-weight: 800; border-radius: var(--radius-sm);">
                            TAMBAH
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Dynamic Floating Cart Bar -->
<div id="floatingCartBar" style="display: none; position: fixed; bottom: calc(var(--bottom-nav-height) + 12px); left: 16px; right: 16px; z-index: var(--z-sticky); animation: cartSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
    <div style="background: var(--accent); color: white; border-radius: var(--radius-xl); padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow-lg);">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 20px; height: 20px; border-radius: 50%; background: white; color: var(--accent); font-size: 11px; font-weight: 900; display: flex; align-items: center; justify-content: center;" id="cartCountBadge">0</div>
            <div style="display: flex; flex-direction: column;">
                <span style="font-size: 8px; font-weight: 800; text-transform: uppercase; color: rgba(255,255,255,0.6); letter-spacing: 0.05em;">Keranjang Sewa</span>
                <span style="font-size: 12px; font-weight: 900; color: white;" id="cartTotalText">Rp 0 / Hari</span>
            </div>
        </div>
        
        <button id="openCheckoutModalBtn" class="btn btn-secondary btn-sm" style="background: white; color: var(--accent); border-radius: var(--radius-md); font-size: 10px; font-weight: 900; padding: 8px 14px; border: none; height: 32px;">
            SEWA SEKARANG
        </button>
    </div>
</div>

<!-- Modal: Checkout Form -->
<div id="checkoutRentalModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: var(--bg-overlay); z-index: var(--z-modal-backdrop); align-items: center; justify-content: center; padding: 16px;">
    <div class="glass-card-static" style="background: white; width: 100%; max-width: 440px; padding: var(--space-lg); border-radius: var(--radius-xl); box-shadow: var(--shadow-xl); border: 1.5px solid var(--accent); position: relative; animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <button id="closeCheckoutModalBtn" style="position: absolute; top: 18px; right: 18px; background: none; border: none; font-size: 16px; cursor: pointer; color: var(--text-secondary);">&times;</button>
        
        <h2 style="font-size: 15px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0; margin-bottom: 12px; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Detail Penyewaan Barang</h2>
        
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: var(--space-sm);">
            <input type="hidden" id="cartDataInput" name="cart_data" value="">
            <input type="hidden" name="checkout_rental" value="1">
            
            <!-- Items Checklist summary in modal -->
            <div style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); max-height: 110px; overflow-y: auto; padding: 8px 10px; background: var(--bg-tertiary); display: flex; flex-direction: column; gap: 4px;" id="modalItemsSummary">
                <!-- Javascript will fill this -->
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 9px; margin-bottom: 4px;">Pilih Pos Pengambilan (Gunung Tujuan)</label>
                <select name="mountain_id" class="form-input" style="height: 38px; padding: 0 10px; font-size: 11px;" required>
                    <option value="" disabled selected>Pilih pos pendakian...</option>
                    <?php foreach ($mountains as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= sanitize($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-xs);">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 9px; margin-bottom: 4px;">Tanggal Ambil</label>
                    <input type="date" id="rentStartDate" name="start_date" class="form-input" style="height: 38px; padding: 0 8px; font-size: 11px;" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 9px; margin-bottom: 4px;">Tanggal Kembali</label>
                    <input type="date" id="rentEndDate" name="end_date" class="form-input" style="height: 38px; padding: 0 8px; font-size: 11px;" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
            </div>

            <!-- Price Calculations panel -->
            <div style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 10px; font-size: 11px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="color: var(--text-secondary);">Total Sewa Harian:</span>
                    <span style="font-weight: 800; color: var(--text-primary);" id="calcDailyTotal">Rp 0</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="color: var(--text-secondary);">Durasi Persewaan:</span>
                    <span style="font-weight: 800; color: var(--text-primary);" id="calcRentDays">0 Hari</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--border-color); padding-top: 4px; margin-top: 4px;">
                    <span style="font-weight: 850; color: var(--text-primary);">Total Biaya:</span>
                    <span style="font-weight: 900; color: var(--accent);" id="calcRentTotalPrice">Rp 0</span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="height: 38px; font-size: 11px; font-weight: 800; border-radius: var(--radius-sm); margin-top: 4px;">
                KONFIRMASI SEWA SEKARANG
            </button>
        </form>
    </div>
</div>

<script>
    // Shopping Cart, Filters, Search & Modal calculation logic
    (function() {
        const searchInput = document.getElementById('gearSearchInput');
        const filterBtns = document.querySelectorAll('.gear-filter-btn');
        const cards = document.querySelectorAll('.gear-card');
        
        let activeCat = 'all';
        let searchQuery = '';

        function applyFilter() {
            cards.forEach(card => {
                const name = card.dataset.name;
                const cat = card.dataset.cat;

                const matchesSearch = name.includes(searchQuery);
                const matchesCat = activeCat === 'all' || cat === activeCat;

                if (matchesSearch && matchesCat) {
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
                
                activeCat = this.dataset.cat;
                applyFilter();
            });
        });

        // Shopping Cart Logic
        const cart = {}; // maps itemId -> { name, price, qty }
        const floatingCartBar = document.getElementById('floatingCartBar');
        const cartCountBadge = document.getElementById('cartCountBadge');
        const cartTotalText = document.getElementById('cartTotalText');
        const addTriggers = document.querySelectorAll('.add-to-cart-trigger');

        function updateCartUI() {
            let totalQty = 0;
            let dailyTotal = 0;

            for (const id in cart) {
                totalQty += cart[id].qty;
                dailyTotal += cart[id].price * cart[id].qty;
            }

            if (totalQty > 0) {
                cartCountBadge.textContent = totalQty;
                cartTotalText.textContent = 'Rp ' + dailyTotal.toLocaleString('id-ID') + ' / Hari';
                floatingCartBar.style.display = 'block';
            } else {
                floatingCartBar.style.display = 'none';
            }
        }

        function renderCartControls(id, name, price) {
            const container = document.getElementById('control_' + id);
            if (!container) return;

            if (cart[id] && cart[id].qty > 0) {
                container.innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: space-between; border: 1.5px solid var(--accent); border-radius: var(--radius-sm); height: 28px; overflow: hidden; background: white;">
                        <button class="cart-minus" data-id="${id}" style="width: 26px; height: 100%; border: none; background: var(--bg-tertiary); color: var(--text-primary); font-weight: 800; cursor: pointer; font-size: 11px;">-</button>
                        <span style="font-size: 11px; font-weight: 850; color: var(--text-primary);">${cart[id].qty}</span>
                        <button class="cart-plus" data-id="${id}" style="width: 26px; height: 100%; border: none; background: var(--bg-tertiary); color: var(--text-primary); font-weight: 800; cursor: pointer; font-size: 11px;">+</button>
                    </div>
                `;

                // Add event listeners for plus/minus buttons
                container.querySelector('.cart-minus').addEventListener('click', () => {
                    cart[id].qty--;
                    if (cart[id].qty <= 0) {
                        delete cart[id];
                        container.innerHTML = `
                            <button class="btn btn-primary btn-block btn-sm add-to-cart-trigger" 
                                    data-id="${id}" 
                                    data-name="${name}" 
                                    data-price="${price}" 
                                    style="font-size: 9px; padding: 7px; height: 28px; font-weight: 800; border-radius: var(--radius-sm);">
                                TAMBAH
                            </button>
                        `;
                        // Rebind initial add button
                        container.querySelector('.add-to-cart-trigger').addEventListener('click', function() {
                            addToCart(this);
                        });
                    } else {
                        renderCartControls(id, name, price);
                    }
                    updateCartUI();
                });

                container.querySelector('.cart-plus').addEventListener('click', () => {
                    cart[id].qty++;
                    renderCartControls(id, name, price);
                    updateCartUI();
                });

            }
        }

        function addToCart(btnElement) {
            const id = btnElement.dataset.id;
            const name = btnElement.dataset.name;
            const price = parseInt(btnElement.dataset.price);

            cart[id] = { name: name, price: price, qty: 1 };
            renderCartControls(id, name, price);
            updateCartUI();
        }

        addTriggers.forEach(trigger => {
            trigger.addEventListener('click', function() {
                addToCart(this);
            });
        });

        // Checkout Modal & Calculation Logic
        const checkoutModal = document.getElementById('checkoutRentalModal');
        const openCheckoutBtn = document.getElementById('openCheckoutModalBtn');
        const closeCheckoutBtn = document.getElementById('closeCheckoutModalBtn');
        
        const cartDataInput = document.getElementById('cartDataInput');
        const modalItemsSummary = document.getElementById('modalItemsSummary');
        
        const rentStartDate = document.getElementById('rentStartDate');
        const rentEndDate = document.getElementById('rentEndDate');
        
        const calcDailyTotal = document.getElementById('calcDailyTotal');
        const calcRentDays = document.getElementById('calcRentDays');
        const calcRentTotalPrice = document.getElementById('calcRentTotalPrice');
        
        let cartDailyTotal = 0;

        function updateCheckoutCalculation() {
            const startVal = rentStartDate.value;
            const endVal = rentEndDate.value;
            
            if (startVal && endVal) {
                const start = new Date(startVal);
                const end = new Date(endVal);
                const diffTime = end - start;
                let days = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                if (days <= 0) days = 1;
                
                const total = cartDailyTotal * days;
                calcRentDays.textContent = days + ' Hari';
                calcRentTotalPrice.textContent = 'Rp ' + total.toLocaleString('id-ID');
            } else {
                calcRentDays.textContent = '0 Hari';
                calcRentTotalPrice.textContent = 'Rp 0';
            }
        }

        if (openCheckoutBtn && checkoutModal) {
            openCheckoutBtn.addEventListener('click', () => {
                // Populate cart data input
                const submissionCart = {};
                modalItemsSummary.innerHTML = '';
                cartDailyTotal = 0;

                for (const id in cart) {
                    submissionCart[id] = cart[id].qty;
                    cartDailyTotal += cart[id].price * cart[id].qty;

                    // render summary list item
                    const itemDiv = document.createElement('div');
                    itemDiv.style.display = 'flex';
                    itemDiv.style.justifyContent = 'space-between';
                    itemDiv.style.fontSize = '10.5px';
                    itemDiv.innerHTML = `
                        <span style="color: var(--text-secondary);">${cart[id].name} <span style="font-weight: 850; color: var(--text-primary);">x${cart[id].qty}</span></span>
                        <span style="font-weight: 700; color: var(--text-primary);">Rp ${(cart[id].price * cart[id].qty).toLocaleString('id-ID')}</span>
                    `;
                    modalItemsSummary.appendChild(itemDiv);
                }

                cartDataInput.value = JSON.stringify(submissionCart);
                calcDailyTotal.textContent = 'Rp ' + cartDailyTotal.toLocaleString('id-ID') + ' / Hari';

                // Set default dates
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                const tomorrowStr = tomorrow.toISOString().split('T')[0];
                
                rentStartDate.value = tomorrowStr;
                rentStartDate.min = tomorrowStr;
                
                const dayAfter = new Date();
                dayAfter.setDate(dayAfter.getDate() + 3);
                const dayAfterStr = dayAfter.toISOString().split('T')[0];
                
                rentEndDate.value = dayAfterStr;
                rentEndDate.min = tomorrowStr;

                updateCheckoutCalculation();

                checkoutModal.style.display = 'flex';
            });
        }

        if (closeCheckoutBtn && checkoutModal) {
            closeCheckoutBtn.addEventListener('click', () => {
                checkoutModal.style.display = 'none';
            });

            window.addEventListener('click', (e) => {
                if (e.target === checkoutModal) {
                    checkoutModal.style.display = 'none';
                }
            });
        }

        if (rentStartDate && rentEndDate) {
            rentStartDate.addEventListener('change', function() {
                rentEndDate.min = this.value;
                if (rentEndDate.value < this.value) {
                    rentEndDate.value = this.value;
                }
                updateCheckoutCalculation();
            });
            rentEndDate.addEventListener('change', updateCheckoutCalculation);
        }

    })();
</script>

<style>
    @keyframes modalPop {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    @keyframes cartSlideUp {
        from { transform: translateY(100px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>

<?php
$active_page = 'home';
require_once __DIR__ . '/../includes/footer.php';
?>
