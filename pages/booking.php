<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$user = getCurrentUser();
$mountainId = $_GET['mountain'] ?? '';
$selectedDate = $_GET['date'] ?? '';
$mountain = getMountain($mountainId);

if (!$mountain) {
    redirect('pages/home.php');
}

$trails = $mountain['trails'];
$availableDates = getAvailableDates($mountainId);
?>
<?php
$page_title = 'Daftar Pendakian — ' . sanitize($mountain['name']);
$page_desc = 'Pendaftaran Pendakian — ' . sanitize($mountain['name']);
$hide_header = true;
$page_wrapper_style = 'padding-bottom:100px;';
$extra_css = '
<style>
.calendar-day-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 42px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all var(--transition-fast);
    background: var(--bg-card);
    color: var(--text-primary);
}
.calendar-day-cell:hover:not(.disabled):not(.empty) {
    border-color: var(--accent);
}
.calendar-day-cell.selected {
    background: var(--accent) !important;
    border-color: var(--accent) !important;
    color: #FFFFFF !important;
}
.calendar-day-cell.disabled {
    opacity: 0.35;
    cursor: not-allowed;
}
.calendar-day-cell.empty {
    background: var(--bg-tertiary);
    border: 1px solid transparent;
    opacity: 0.25;
    color: var(--text-tertiary);
    cursor: default;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 42px;
    border-radius: var(--radius-sm);
}
</style>
';
require_once __DIR__ . '/../includes/header.php';
?>
        <!-- Header -->
        <header class="header">
            <div class="header-inner">
                <a href="<?= BASE_URL ?>/pages/mountain.php?id=<?= $mountainId ?>" class="header-back" style="display:inline-flex;align-items:center;gap:6px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Kembali
                </a>
                <span class="header-title">Pendaftaran</span>
                <div style="width:80px;"></div>
            </div>
        </header>

        <!-- Mountain Info Mini -->
        <div class="container container-sm mt-md">
            <div class="glass-card-static p-md flex items-center gap-md mb-lg">
                <div class="mountain-card-image-placeholder mountain-bg-<?= $mountain['image'] ?>" style="width:60px;height:60px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;color:white;min-width:60px;background:var(--accent);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:28px;height:28px;"><path d="M4 22L12 8L20 22H4Z" /><path d="M9 13.25L12 9.5L15 13.25" /><path d="M12 22L17 14L22 22H12Z" /></svg>
                </div>
                <div>
                    <div style="font-weight:600;"><?= sanitize($mountain['name']) ?></div>
                    <div class="text-secondary text-sm" style="display:inline-flex;align-items:center;gap:4px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.0" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--text-secondary);"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?= sanitize($mountain['location']) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="container container-sm">
            <!-- Wizard Progress -->
            <div class="wizard-progress" style="padding: 0;">
                <div class="wizard-step active" id="wizStep1">
                    <div class="wizard-step-circle">1</div>
                    <span class="wizard-step-label">Jadwal</span>
                </div>
                <div class="wizard-connector" id="wizConn1"></div>
                <div class="wizard-step" id="wizStep2">
                    <div class="wizard-step-circle">2</div>
                    <span class="wizard-step-label">Anggota</span>
                </div>
                <div class="wizard-connector" id="wizConn2"></div>
                <div class="wizard-step" id="wizStep3">
                    <div class="wizard-step-circle">3</div>
                    <span class="wizard-step-label">Konfirmasi</span>
                </div>
            </div>

        <!-- Step 1: Date & Trail -->
        <div class="wizard-content" id="step1">
            <div class="glass-card-static p-lg mb-md">
                <h3 style="margin-bottom:var(--space-md);">Pilih Jalur</h3>
                <?php foreach ($trails as $ti => $trail): ?>
                <label style="display:flex;align-items:center;gap:var(--space-md);padding:var(--space-sm) var(--space-md);border-radius:var(--radius-sm);cursor:pointer;margin-bottom:6px;transition:all var(--transition-fast);border:1px solid <?= $ti === 0 ? 'var(--accent)' : 'var(--border-color)' ?>;<?= $ti === 0 ? 'background:var(--bg-tertiary);' : 'background:transparent;' ?>">
                    <input type="radio" name="booking_trail" value="<?= $trail['id'] ?>" data-trail-name="<?= sanitize($trail['name']) ?>" style="accent-color:var(--accent);width:18px;height:18px;" <?= $ti === 0 ? 'checked' : '' ?>>
                    <div style="flex:1;">
                        <div style="font-weight:700;font-size:var(--font-sm);display:inline-flex;align-items:center;gap:6px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.0" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:var(--text-primary);"><path d="M9 20 3 17V4l6 3 6-3 6 3v13l-6-3-6 3Z"/><path d="M9 7v13"/><path d="M15 4v13"/></svg>
                            <?= sanitize($trail['name']) ?>
                        </div>
                        <div class="text-secondary text-xs" style="margin-top:2px;"><?= $trail['distance'] ?> · <?= $trail['duration'] ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="glass-card-static p-lg mb-md">
                <h3 style="margin-bottom:var(--space-md);">Pilih Tanggal Pendakian</h3>
                <?php
                // Group available dates by their date string for quick lookup
                $availableLookup = [];
                foreach ($availableDates as $dateInfo) {
                    $availableLookup[$dateInfo['date']] = $dateInfo;
                }

                // Find the start date and end date
                $firstDateStr = $availableDates[0]['date']; // tomorrow
                $lastDateStr = end($availableDates)['date']; // 14 days from now

                $firstTimestamp = strtotime($firstDateStr);
                $lastTimestamp = strtotime($lastDateStr);

                // Sunday of the first week:
                $firstDayOfWeek = date('w', $firstTimestamp);
                $startCalendarTimestamp = strtotime("-$firstDayOfWeek days", $firstTimestamp);

                // Saturday of the last week:
                $lastDayOfWeek = date('w', $lastTimestamp);
                $daysToSaturday = 6 - $lastDayOfWeek;
                $endCalendarTimestamp = strtotime("+$daysToSaturday days", $lastTimestamp);

                $monthsEng = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                $monthsInd = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                $firstMonth = str_replace($monthsEng, $monthsInd, date('F Y', $firstTimestamp));
                $lastMonth = str_replace($monthsEng, $monthsInd, date('F Y', $lastTimestamp));
                $headerText = ($firstMonth === $lastMonth) ? $firstMonth : $firstMonth . ' / ' . $lastMonth;
                ?>
                <div class="calendar-wrapper">
                    <!-- Month Header -->
                    <div class="calendar-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-md);">
                        <span style="font-weight: 700; font-size: var(--font-sm); text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); border-bottom: 2px solid var(--accent); padding-bottom: 2px;">
                            <?= $headerText ?>
                        </span>
                    </div>

                    <!-- Weekday Labels -->
                    <div class="calendar-grid-weekdays" style="display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: 800; font-size: 10px; color: var(--text-tertiary); margin-bottom: 8px;">
                        <div>MIN</div>
                        <div>SEN</div>
                        <div>SEL</div>
                        <div>RAB</div>
                        <div>KAM</div>
                        <div>JUM</div>
                        <div>SAB</div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="calendar-grid-days" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px;">
                        <?php
                        $selectedQuotaInfo = null;
                        if (!empty($selectedDate) && isset($availableLookup[$selectedDate])) {
                            $selectedQuotaInfo = $availableLookup[$selectedDate];
                        }

                        $curr = $startCalendarTimestamp;
                        while ($curr <= $endCalendarTimestamp):
                            $dateStr = date('Y-m-d', $curr);
                            $dayNum = date('j', $curr);
                            $isAvailable = isset($availableLookup[$dateStr]);
                            $info = $isAvailable ? $availableLookup[$dateStr] : null;
                            $isSelected = ($dateStr === $selectedDate);
                            
                            if ($isAvailable):
                                $activeClass = $isSelected ? 'selected' : '';
                                $disabledClass = !$info['available'] ? 'disabled' : '';
                                ?>
                                <label class="calendar-day-cell <?= $activeClass ?> <?= $disabledClass ?>" 
                                       data-quota="<?= $info['quota_remaining'] ?>" 
                                       data-date="<?= $info['formatted'] ?>" 
                                       data-day="<?= $info['day_name'] ?>"
                                       data-date-val="<?= $dateStr ?>">
                                    <input type="radio" name="booking_date" value="<?= $dateStr ?>" style="display: none;" <?= $isSelected ? 'checked' : '' ?> <?= !$info['available'] ? 'disabled' : '' ?>>
                                    <span style="font-weight: 700; font-size: var(--font-sm);"><?= $dayNum ?></span>
                                </label>
                                <?php
                            else:
                                ?>
                                <div class="calendar-day-cell empty">
                                    <span style="font-weight: 500; font-size: var(--font-xs);"><?= $dayNum ?></span>
                                </div>
                                <?php
                            endif;
                            
                            $curr = strtotime("+1 day", $curr);
                        endwhile;
                        ?>
                    </div>

                    <!-- Quota Info Display -->
                    <div id="calendar-quota-display" style="margin-top: var(--space-md); padding: var(--space-md); background: var(--bg-tertiary); border-radius: var(--radius-sm); border: 1px solid var(--border-color); text-align: center; font-size: var(--font-sm); font-weight: 600; color: var(--text-secondary); min-height: 60px; display: flex; align-items: center; justify-content: center; transition: all var(--transition-fast);">
                        <?php if ($selectedQuotaInfo): 
                            $dateText = $selectedQuotaInfo['day_name'] . ', ' . $selectedQuotaInfo['formatted'];
                            if ($selectedQuotaInfo['available']): ?>
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; width: 100%;">
                                    <span>Kuota tersedia pada <strong><?= $dateText ?></strong>:</span>
                                    <span style="font-size: var(--font-md); font-weight: 800; color: var(--success);"><?= $selectedQuotaInfo['quota_remaining'] ?> slot</span>
                                </div>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; width: 100%;">
                                    <span>Kuota pada <strong><?= $dateText ?></strong>:</span>
                                    <span style="font-size: var(--font-md); font-weight: 800; color: var(--danger);">Penuh</span>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:var(--text-tertiary);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                <span>Pilih tanggal untuk melihat sisa kuota</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Members -->
        <div class="wizard-content hidden" id="step2">
            <div class="glass-card-static p-lg mb-md">
                <h3 style="margin-bottom:var(--space-xs);">Data Anggota Pendakian</h3>
                <p class="text-secondary text-sm mb-md">Masukkan data setiap orang yang akan mendaki. Setiap anggota akan mendapat barcode masing-masing.</p>

                <div id="membersContainer">
                    <!-- Member 1 (always present) -->
                    <div class="member-card" data-member="1">
                        <div class="member-card-header">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.0" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:var(--text-primary);"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span class="member-number">Anggota 1</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap (sesuai KTP)</label>
                            <input type="text" class="form-input member-name" placeholder="Masukkan nama lengkap" value="<?= sanitize($user['name']) ?>" required>
                            <div class="form-error">Nama wajib diisi</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nomor KTP (NIK)</label>
                            <input type="text" class="form-input member-ktp" placeholder="16 digit nomor KTP" maxlength="16" pattern="[0-9]{16}" required>
                            <div class="form-error">NIK harus 16 digit angka</div>
                        </div>
                    </div>
                </div>

                <button type="button" class="add-member-btn" id="addMemberBtn" style="display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah Anggota
                </button>
            </div>
        </div>

        <!-- Step 3: Review -->
        <div class="wizard-content hidden" id="step3">
            <div class="glass-card-static p-lg mb-md">
                <h3 style="margin-bottom:var(--space-md);">Ringkasan Pendaftaran</h3>
                
                <div class="review-section">
                    <div class="review-row">
                        <span class="review-label">Gunung</span>
                        <span class="review-value" id="reviewMountain"><?= sanitize($mountain['name']) ?></span>
                    </div>
                    <div class="review-row">
                        <span class="review-label">Tanggal</span>
                        <span class="review-value" id="reviewDate">—</span>
                    </div>
                    <div class="review-row">
                        <span class="review-label">Jalur</span>
                        <span class="review-value" id="reviewTrail">—</span>
                    </div>
                    <div class="review-row">
                        <span class="review-label">Jumlah Anggota</span>
                        <span class="review-value" id="reviewMembers">—</span>
                    </div>
                </div>

                <h4 style="margin:var(--space-md) 0 var(--space-sm);">Daftar Anggota</h4>
                <div id="reviewMemberList"></div>
            </div>

            <div class="alert alert-info mb-md" style="display:flex;align-items:flex-start;gap:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;color:var(--info);"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Setelah konfirmasi, setiap anggota akan mendapatkan barcode QR yang harus ditunjukkan di pos awal pendakian.</span>
            </div>
        </div>
        </div>

        <!-- Wizard Actions -->
        <div class="wizard-actions">
            <button class="btn btn-secondary hidden" id="prevBtn" style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Kembali
            </button>
            <button class="btn btn-primary" id="nextBtn" style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                <span>Lanjut</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </button>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 3;
        let memberCount = 1;

        const steps = {
            1: document.getElementById('step1'),
            2: document.getElementById('step2'),
            3: document.getElementById('step3')
        };

        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        // Date option highlight in Calendar
        document.querySelectorAll('.calendar-day-cell').forEach(opt => {
            const radio = opt.querySelector('input[type="radio"]');
            if (radio && !radio.disabled) {
                opt.addEventListener('click', function(e) {
                    if (e.target === radio) return;
                    
                    radio.checked = true;
                    
                    document.querySelectorAll('.calendar-day-cell').forEach(o => {
                        o.classList.remove('selected');
                    });
                    this.classList.add('selected');

                    const quota = this.dataset.quota;
                    const date = this.dataset.date;
                    const day = this.dataset.day;
                    const quotaDisplay = document.getElementById('calendar-quota-display');
                    
                    if (quotaDisplay) {
                        const dateText = day + ', ' + date;
                        const remaining = parseInt(quota);
                        if (remaining > 0) {
                            quotaDisplay.innerHTML = `
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; width: 100%;">
                                    <span>Kuota tersedia pada <strong>${dateText}</strong>:</span>
                                    <span style="font-size: var(--font-md); font-weight: 800; color: var(--success);">${remaining} slot</span>
                                </div>
                            `;
                        } else {
                            quotaDisplay.innerHTML = `
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; width: 100%;">
                                    <span>Kuota pada <strong>${dateText}</strong>:</span>
                                    <span style="font-size: var(--font-md); font-weight: 800; color: var(--danger);">Penuh</span>
                                </div>
                            `;
                        }
                    }
                });
            }
        });

        // Trail option highlight
        document.querySelectorAll('input[name="booking_trail"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('input[name="booking_trail"]').forEach(r => {
                    r.closest('label').style.borderColor = 'var(--border-color)';
                    r.closest('label').style.background = 'transparent';
                });
                this.closest('label').style.borderColor = 'var(--accent)';
                this.closest('label').style.background = 'var(--bg-tertiary)';
            });
        });

        // Add Member
        document.getElementById('addMemberBtn').addEventListener('click', function() {
            memberCount++;
            const card = document.createElement('div');
            card.className = 'member-card';
            card.dataset.member = memberCount;
            card.innerHTML = `
                <div class="member-card-header" style="display:flex;align-items:center;gap:6px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:var(--text-primary);"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span class="member-number" style="flex:1;">Anggota ${memberCount}</span>
                    <button type="button" class="member-remove" onclick="removeMember(this)" style="display:inline-flex;align-items:center;justify-content:center;">✕</button>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Lengkap (sesuai KTP)</label>
                    <input type="text" class="form-input member-name" placeholder="Masukkan nama lengkap" required>
                    <div class="form-error">Nama wajib diisi</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor KTP (NIK)</label>
                    <input type="text" class="form-input member-ktp" placeholder="16 digit nomor KTP" maxlength="16" pattern="[0-9]{16}" required>
                    <div class="form-error">NIK harus 16 digit angka</div>
                </div>
            `;
            document.getElementById('membersContainer').appendChild(card);
        });

        function removeMember(btn) {
            btn.closest('.member-card').remove();
            // Renumber
            document.querySelectorAll('.member-card').forEach((card, i) => {
                card.querySelector('.member-number').textContent = `Anggota ${i + 1}`;
                card.dataset.member = i + 1;
            });
            memberCount = document.querySelectorAll('.member-card').length;
        }

        // KTP validation - numbers only
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('member-ktp')) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            }
        });

        // Navigation
        function goToStep(step) {
            // Validate current step before advancing
            if (step > currentStep && !validateStep(currentStep)) {
                return;
            }

            // Hide all steps
            Object.values(steps).forEach(s => s.classList.add('hidden'));
            steps[step].classList.remove('hidden');

            // Update wizard progress
            for (let i = 1; i <= totalSteps; i++) {
                const wizStep = document.getElementById('wizStep' + i);
                const wizConn = document.getElementById('wizConn' + (i - 1));
                
                wizStep.classList.remove('active', 'completed');
                if (i < step) {
                    wizStep.classList.add('completed');
                    wizStep.querySelector('.wizard-step-circle').innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;display:block;"><polyline points="20 6 9 17 4 12"/></svg>';
                } else if (i === step) {
                    wizStep.classList.add('active');
                    wizStep.querySelector('.wizard-step-circle').innerHTML = i;
                } else {
                    wizStep.querySelector('.wizard-step-circle').innerHTML = i;
                }

                if (wizConn) {
                    wizConn.classList.toggle('completed', i < step);
                }
            }

            // Update buttons
            prevBtn.classList.toggle('hidden', step === 1);
            
            const wrapper = document.querySelector('.page-wrapper');
            const actions = document.querySelector('.wizard-actions');
            
            if (step === totalSteps) {
                if (wrapper) wrapper.style.paddingBottom = '100px';
                if (actions) {
                    actions.style.flexDirection = 'row';
                    actions.style.gap = 'var(--space-sm)';
                }
                nextBtn.style.flex = '1.2';
                prevBtn.style.flex = '1';
                nextBtn.style.width = 'auto';
                prevBtn.style.width = 'auto';
                nextBtn.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Konfirmasi</span>
                `;
                nextBtn.classList.add('btn-primary');
                populateReview();
            } else {
                if (wrapper) wrapper.style.paddingBottom = '100px';
                if (actions) {
                    actions.style.flexDirection = 'row';
                    actions.style.gap = 'var(--space-sm)';
                }
                nextBtn.style.flex = '1';
                prevBtn.style.flex = '1';
                nextBtn.style.width = 'auto';
                prevBtn.style.width = 'auto';
                nextBtn.innerHTML = `
                    <span>Lanjut</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                `;
            }

            currentStep = step;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateStep(step) {
            if (step === 1) {
                const dateSelected = document.querySelector('input[name="booking_date"]:checked');
                const trailSelected = document.querySelector('input[name="booking_trail"]:checked');
                
                if (!dateSelected) {
                    showToast('Pilih tanggal pendakian terlebih dahulu', 'error');
                    return false;
                }
                if (!trailSelected) {
                    showToast('Pilih jalur pendakian', 'error');
                    return false;
                }
                return true;
            }

            if (step === 2) {
                const names = document.querySelectorAll('.member-name');
                const ktps = document.querySelectorAll('.member-ktp');
                let valid = true;

                names.forEach((input, i) => {
                    if (!input.value.trim()) {
                        input.closest('.form-group').classList.add('error');
                        valid = false;
                    } else {
                        input.closest('.form-group').classList.remove('error');
                    }
                });

                ktps.forEach((input, i) => {
                    if (!input.value.trim() || !/^[0-9]{16}$/.test(input.value)) {
                        input.closest('.form-group').classList.add('error');
                        valid = false;
                    } else {
                        input.closest('.form-group').classList.remove('error');
                    }
                });

                if (!valid) {
                    showToast('Lengkapi semua data anggota', 'error');
                }
                return valid;
            }

            return true;
        }

        function populateReview() {
            const date = document.querySelector('input[name="booking_date"]:checked');
            const trail = document.querySelector('input[name="booking_trail"]:checked');
            
            document.getElementById('reviewDate').textContent = date ? formatDateStr(date.value) : '—';
            document.getElementById('reviewTrail').textContent = trail ? trail.dataset.trailName : '—';
            
            const members = document.querySelectorAll('.member-card');
            document.getElementById('reviewMembers').textContent = members.length + ' orang';

            const list = document.getElementById('reviewMemberList');
            list.innerHTML = '';
            members.forEach((card, i) => {
                const name = card.querySelector('.member-name').value;
                const ktp = card.querySelector('.member-ktp').value;
                list.innerHTML += `
                    <div style="display:flex;align-items:center;gap:var(--space-sm);padding:var(--space-sm);background:var(--bg-secondary);border-radius:var(--radius-sm);margin-bottom:4px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;color:var(--text-secondary);flex-shrink:0;"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <div style="flex:1;">
                            <div style="font-weight:500;font-size:var(--font-sm);">${escapeHtml(name)}</div>
                            <div class="text-secondary text-xs">NIK: ${ktp.substring(0,4)}****${ktp.substring(12)}</div>
                        </div>
                    </div>
                `;
            });
        }

        function formatDateStr(dateStr) {
            const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            const d = new Date(dateStr + 'T00:00:00');
            return days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        prevBtn.addEventListener('click', () => goToStep(currentStep - 1));
        
        nextBtn.addEventListener('click', () => {
            if (currentStep < totalSteps) {
                goToStep(currentStep + 1);
            } else {
                submitBooking();
            }
        });

        function submitBooking() {
            const date = document.querySelector('input[name="booking_date"]:checked').value;
            const trailInput = document.querySelector('input[name="booking_trail"]:checked');
            const trailId = trailInput.value;
            const trailName = trailInput.dataset.trailName;
            
            const members = [];
            document.querySelectorAll('.member-card').forEach(card => {
                members.push({
                    name: card.querySelector('.member-name').value.trim(),
                    ktp: card.querySelector('.member-ktp').value.trim()
                });
            });

            nextBtn.innerHTML = '<span class="spinner" style="width:20px;height:20px;border-width:2px;"></span> Memproses...';
            nextBtn.disabled = true;

            // Submit via fetch
            const formData = new FormData();
            formData.append('mountain_id', '<?= $mountainId ?>');
            formData.append('date', date);
            formData.append('trail_id', trailId);
            formData.append('trail_name', trailName);
            formData.append('members', JSON.stringify(members));

            fetch('<?= BASE_URL ?>/api/booking.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '<?= BASE_URL ?>/pages/booking_success.php?id=' + data.booking_id;
                } else {
                    showToast(data.message || 'Terjadi kesalahan', 'error');
                    nextBtn.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Konfirmasi</span>
                    `;
                    nextBtn.disabled = false;
                }
            })
            .catch(err => {
                showToast('Terjadi kesalahan jaringan', 'error');
                nextBtn.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Konfirmasi</span>
                `;
                nextBtn.disabled = false;
            });
        }

        // Toast
        function showToast(message, type = 'success') {
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container';
                document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            
            const iconSvg = type === 'error' 
                ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon" style="width:16px;height:16px;margin-right:8px;flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>' 
                : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon" style="width:16px;height:16px;margin-right:8px;flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>';
            
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.innerHTML = iconSvg + '<span>' + message + '</span>';
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
<?php
$hide_bottom_nav = true;
require_once __DIR__ . '/../includes/footer.php';
?>
