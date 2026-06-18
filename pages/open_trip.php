<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$user = getCurrentUser();
$mountains = readJSON(MOUNTAINS_FILE);

// Initialize session state for Open Trips
if (!isset($_SESSION['open_trips'])) {
    $_SESSION['open_trips'] = [
        [
            'id' => 'trip_1',
            'mountain_id' => 'mnt_merbabu',
            'host' => 'Rendi Saputra',
            'date' => date('Y-m-d', strtotime('+3 days')),
            'price' => 250000,
            'max_slots' => 10,
            'filled_slots' => 6,
            'notes' => 'Mencari teman santai, trekking via Selo. Meeting point Terminal Boyolali.'
        ],
        [
            'id' => 'trip_2',
            'mountain_id' => 'mnt_rinjani',
            'host' => 'Siti Aminah',
            'date' => date('Y-m-d', strtotime('+5 days')),
            'price' => 1350000,
            'max_slots' => 8,
            'filled_slots' => 4,
            'notes' => 'Open trip sharing cost 3D2N via Sembalun. Termasuk tenda dan porter logistik.'
        ],
        [
            'id' => 'trip_3',
            'mountain_id' => 'mnt_prau',
            'host' => 'Budi Setiawan',
            'date' => date('Y-m-d', strtotime('+2 days')),
            'price' => 150000,
            'max_slots' => 15,
            'filled_slots' => 12,
            'notes' => 'Camp santai berburu golden sunrise. Cocok untuk pemula. Meeting point Basecamp Patak Banteng.'
        ],
        [
            'id' => 'trip_4',
            'mountain_id' => 'mnt_semeru',
            'host' => 'Agus Hariyadi',
            'date' => date('Y-m-d', strtotime('+8 days')),
            'price' => 950000,
            'max_slots' => 6,
            'filled_slots' => 5,
            'notes' => 'Ekspedisi Mahameru. Wajib melampirkan surat keterangan sehat dan fisik prima.'
        ]
    ];
}

if (!isset($_SESSION['joined_trips'])) {
    $_SESSION['joined_trips'] = [];
}

// Action: Join Trip
if (isset($_GET['action']) && $_GET['action'] === 'join' && isset($_GET['trip_id'])) {
    $tripId = $_GET['trip_id'];
    if (!in_array($tripId, $_SESSION['joined_trips'])) {
        foreach ($_SESSION['open_trips'] as &$trip) {
            if ($trip['id'] === $tripId) {
                if ($trip['filled_slots'] < $trip['max_slots']) {
                    $trip['filled_slots']++;
                    $_SESSION['joined_trips'][] = $tripId;
                    $_SESSION['toast_success'] = 'Berhasil bergabung dengan trip ke ' . getMountainName($trip['mountain_id'], $mountains) . '!';
                } else {
                    $_SESSION['toast_error'] = 'Maaf, kuota trip ini sudah penuh.';
                }
                break;
            }
        }
    } else {
        // Leave trip
        foreach ($_SESSION['open_trips'] as &$trip) {
            if ($trip['id'] === $tripId) {
                $trip['filled_slots'] = max(0, $trip['filled_slots'] - 1);
                $_SESSION['joined_trips'] = array_diff($_SESSION['joined_trips'], [$tripId]);
                $_SESSION['toast_success'] = 'Anda membatalkan keikutsertaan dari trip ke ' . getMountainName($trip['mountain_id'], $mountains) . '.';
                break;
            }
        }
    }
    redirect('pages/open_trip.php');
}

// Action: Create Trip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_trip'])) {
    $mountainId = sanitize($_POST['mountain_id']);
    $date = sanitize($_POST['date']);
    $price = (int)$_POST['price'];
    $maxSlots = (int)$_POST['max_slots'];
    $notes = sanitize($_POST['notes']);
    
    $newTrip = [
        'id' => 'trip_' . uniqid(),
        'mountain_id' => $mountainId,
        'host' => $user['name'],
        'date' => $date,
        'price' => $price,
        'max_slots' => $maxSlots,
        'filled_slots' => 1, // host is automatically inside
        'notes' => $notes
    ];
    
    $_SESSION['open_trips'][] = $newTrip;
    $_SESSION['joined_trips'][] = $newTrip['id']; // host automatically joined
    $_SESSION['toast_success'] = 'Open trip baru ke ' . getMountainName($mountainId, $mountains) . ' berhasil dibuat!';
    redirect('pages/open_trip.php');
}

// Helper to look up mountain name
function getMountainName($id, $mountains) {
    foreach ($mountains as $m) {
        if ($m['id'] === $id) {
            return $m['name'];
        }
    }
    return 'Gunung Tidak Dikenal';
}

$page_title = 'Cari Teman';
$page_desc = 'Cari Teman Pendakian / Open Trip Finder — TERRA';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="open-trip-container" style="max-width: 800px; margin: 0 auto; padding: 20px 16px 0px 16px;">
    
    <!-- Page Header & Back Button -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="<?= BASE_URL ?>/pages/home.php" style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; border: 1px solid var(--border-color); background: white; color: var(--text-primary); text-decoration: none; transition: all var(--transition-fast);" onmouseover="this.style.background='var(--bg-secondary)';" onmouseout="this.style.background='white';">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            <div>
                <h1 style="font-size: 20px; font-weight: 800; color: var(--text-primary); margin: 0; line-height: 1.2;">TEMAN PENDAKIAN</h1>
                <p style="font-size: 11px; color: var(--text-secondary); margin: 0; margin-top: 2px;">Gabung pendakian berkelompok atau buat agenda trip Anda sendiri.</p>
            </div>
        </div>
        <button id="openCreateModalBtn" class="btn btn-primary btn-sm" style="border-radius: var(--radius-lg); font-size: 10px; font-weight: 800; padding: 8px 14px;">
            + BUAT TRIP
        </button>
    </div>

    <!-- Toast Notification Banner -->
    <?php if (isset($_SESSION['toast_success'])): ?>
        <div class="toast-banner success-toast" style="background: var(--success-bg); border: 1px solid var(--success); color: var(--success); padding: 12px 16px; border-radius: var(--radius-md); font-size: 11.5px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span><?= $_SESSION['toast_success'] ?></span>
        </div>
        <?php unset($_SESSION['toast_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['toast_error'])): ?>
        <div class="toast-banner error-toast" style="background: var(--danger-bg); border: 1px solid var(--danger); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-md); font-size: 11.5px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <span><?= $_SESSION['toast_error'] ?></span>
        </div>
        <?php unset($_SESSION['toast_error']); ?>
    <?php endif; ?>

    <!-- Trips Grid -->
    <div style="display: flex; flex-direction: column; gap: var(--space-md);">
        <?php foreach ($_SESSION['open_trips'] as $trip): 
            $mountain = null;
            foreach ($mountains as $m) {
                if ($m['id'] === $trip['mountain_id']) {
                    $mountain = $m;
                    break;
                }
            }
            $isJoined = in_array($trip['id'], $_SESSION['joined_trips']);
            $progress = round(($trip['filled_slots'] / $trip['max_slots']) * 100);
            $isFull = $trip['filled_slots'] >= $trip['max_slots'];
            
            // Format dates
            $formattedDate = formatDate($trip['date'], 'd M Y');
        ?>
            <div class="glass-card-static" style="padding: var(--space-md); border-radius: var(--radius-lg); background: white; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: var(--space-sm); border: 1px solid var(--border-color); position: relative; transition: all 0.2s ease;" onmouseover="this.style.borderColor='var(--accent)';" onmouseout="this.style.borderColor='var(--border-color)';">
                
                <?php if ($trip['host'] === $user['name']): ?>
                    <span style="position: absolute; top: 12px; right: 12px; font-size: 8.5px; font-weight: 900; background: var(--bg-tertiary); border: 1px solid var(--border-color); color: var(--text-primary); padding: 3px 8px; border-radius: var(--radius-sm); text-transform: uppercase; letter-spacing: 0.05em;">Trip Anda</span>
                <?php endif; ?>

                <div>
                    <!-- Mountain name and Location -->
                    <span style="font-size: 9.5px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.08em; color: var(--accent);"><?= $mountain ? $mountain['location'] : 'Lokasi' ?></span>
                    <h2 style="font-size: 15px; font-weight: 800; color: var(--text-primary); margin: 2px 0 6px 0; text-transform: none; letter-spacing: normal;">
                        <?= $mountain ? $mountain['name'] : 'Gunung' ?>
                    </h2>
                    
                    <!-- Trip notes -->
                    <p style="font-size: 11px; color: var(--text-secondary); line-height: 1.4; margin: 0; margin-bottom: 8px;">
                        "<?= sanitize($trip['notes']) ?>"
                    </p>
                </div>

                <!-- Info Grid -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-xs); background: var(--bg-tertiary); padding: 8px var(--space-sm); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <div>
                        <div style="font-size: 8px; font-weight: 700; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.02em;">Host</div>
                        <div style="font-size: 10px; font-weight: 800; color: var(--text-primary); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= sanitize($trip['host']) ?></div>
                    </div>
                    <div>
                        <div style="font-size: 8px; font-weight: 700; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.02em;">Tanggal</div>
                        <div style="font-size: 10px; font-weight: 800; color: var(--text-primary);"><?= $formattedDate ?></div>
                    </div>
                    <div>
                        <div style="font-size: 8px; font-weight: 700; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.02em;">Harga (Sharing)</div>
                        <div style="font-size: 10px; font-weight: 800; color: var(--text-primary);">Rp <?= number_format($trip['price'], 0, ',', '.') ?></div>
                    </div>
                </div>

                <!-- Slots Progress -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <span style="font-size: 9px; font-weight: 700; color: var(--text-secondary);">Sisa Slot: <?= ($trip['max_slots'] - $trip['filled_slots']) ?> dari <?= $trip['max_slots'] ?></span>
                        <span style="font-size: 9px; font-weight: 800; color: var(--text-primary);"><?= $trip['filled_slots'] ?> / <?= $trip['max_slots'] ?> Terisi</span>
                    </div>
                    <div style="height: 6px; width: 100%; background: var(--bg-tertiary); border-radius: var(--radius-full); overflow: hidden; border: 1px solid var(--border-color);">
                        <div style="height: 100%; width: <?= $progress ?>%; background: <?= $isFull ? 'var(--danger)' : 'var(--accent)' ?>; border-radius: var(--radius-full);"></div>
                    </div>
                </div>

                <!-- Action Button -->
                <div style="display: flex; justify-content: flex-end; margin-top: 4px;">
                    <?php if ($trip['host'] === $user['name']): ?>
                        <button class="btn btn-secondary btn-sm btn-block" style="font-size: 10px; padding: 10px; border-radius: var(--radius-sm); opacity: 0.7; cursor: default;" disabled>
                            ANDA ADALAH HOST
                        </button>
                    <?php elseif ($isJoined): ?>
                        <a href="?action=join&trip_id=<?= $trip['id'] ?>" class="btn btn-outline btn-sm btn-block" style="font-size: 10px; padding: 10px; border-radius: var(--radius-sm); background: var(--accent); color: white; display: flex; align-items: center; justify-content: center; gap: 4px; border-color: var(--accent);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:12px;height:12px;color:white;"><polyline points="20 6 9 17 4 12"/></svg>
                            TERDAFTAR (BATALKAN)
                        </a>
                    <?php else: ?>
                        <a href="?action=join&trip_id=<?= $trip['id'] ?>" class="btn btn-primary btn-sm btn-block" style="font-size: 10px; padding: 10px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;" <?= $isFull ? 'style="background: var(--text-tertiary); border-color: var(--text-tertiary); cursor: not-allowed;" pointer-events: none;' : '' ?>>
                            <?= $isFull ? 'KUOTA HABIS' : 'GABUNG PENDAKIAN' ?>
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal: Create Trip -->
<div id="createTripModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: var(--bg-overlay); z-index: var(--z-modal-backdrop); align-items: center; justify-content: center; padding: 16px;">
    <div class="glass-card-static" style="background: white; width: 100%; max-width: 460px; padding: var(--space-lg); border-radius: var(--radius-xl); box-shadow: var(--shadow-xl); border: 1.5px solid var(--accent); position: relative; animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <button id="closeModalBtn" style="position: absolute; top: 18px; right: 18px; background: none; border: none; font-size: 16px; cursor: pointer; color: var(--text-secondary);">&times;</button>
        
        <h2 style="font-size: 16px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0; margin-bottom: var(--space-md); color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">Buat Open Trip Baru</h2>
        
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: var(--space-sm);">
            <input type="hidden" name="create_trip" value="1">
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 9px; margin-bottom: 4px;">Gunung Tujuan</label>
                <select name="mountain_id" class="form-input" style="height: 38px; padding: 0 10px; font-size: 11px;" required>
                    <option value="" disabled selected>Pilih destinasi gunung...</option>
                    <?php foreach ($mountains as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= sanitize($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-xs);">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 9px; margin-bottom: 4px;">Tanggal Mulai</label>
                    <input type="date" name="date" class="form-input" style="height: 38px; padding: 0 8px; font-size: 11px;" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 9px; margin-bottom: 4px;">Maksimal Anggota</label>
                    <input type="number" name="max_slots" class="form-input" style="height: 38px; padding: 0 8px; font-size: 11px;" min="2" max="25" placeholder="Contoh: 10" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 9px; margin-bottom: 4px;">Harga (Cost Sharing / Orang)</label>
                <input type="number" name="price" class="form-input" style="height: 38px; padding: 0 8px; font-size: 11px;" min="0" step="1000" placeholder="Rp. Contoh: 150000" required>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 9px; margin-bottom: 4px;">Catatan Pendakian / Meeting Point</label>
                <textarea name="notes" class="form-input" style="height: 68px; padding: 8px 10px; font-size: 11px; resize: none;" placeholder="Sebutkan meeting point, rute via mana, barang yang harus dibawa..." required></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="height: 38px; font-size: 11px; font-weight: 800; border-radius: var(--radius-sm); margin-top: 8px;">
                BUAT & GABUNG TRIP
            </button>
        </form>
    </div>
</div>

<script>
    // Modal controls
    (function() {
        const modal = document.getElementById('createTripModal');
        const openBtn = document.getElementById('openCreateModalBtn');
        const closeBtn = document.getElementById('closeModalBtn');

        if (openBtn && modal && closeBtn) {
            openBtn.addEventListener('click', () => {
                modal.style.display = 'flex';
            });

            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });

            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    })();
</script>

<style>
    @keyframes modalPop {
        from {
            transform: scale(0.95);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
</style>

<?php
$active_page = 'home';
require_once __DIR__ . '/../includes/footer.php';
?>
