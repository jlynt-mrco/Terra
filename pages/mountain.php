<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$mountainId = $_GET['id'] ?? '';
$mountain = getMountain($mountainId);

if (!$mountain) {
    redirect('pages/home.php');
}

$today = date('Y-m-d');
$quotaToday = getMountainQuota($mountainId, $today);
$activeClimbers = getActiveClimbers($mountainId);
$density = getDensityLevel($mountainId);
$weather = getSimulatedWeather($mountainId);
$forecast = getWeatherForecast($mountainId);
$availableDates = getAvailableDates($mountainId);
$selectedDate = $_GET['date'] ?? '';

$quotaPercent = $mountain['quota_per_day'] > 0 
    ? round(($mountain['quota_per_day'] - $quotaToday) / $mountain['quota_per_day'] * 100) 
    : 0;
$quotaClass = $quotaPercent > 75 ? 'danger' : ($quotaPercent > 50 ? 'warning' : '');

$difficultyLabels = ['easy' => 'Mudah', 'medium' => 'Menengah', 'hard' => 'Sulit'];
?>
<?php
$page_title = sanitize($mountain['name']);
$page_desc = sanitize($mountain['name']) . ' — Informasi pendakian, kuota, cuaca, dan peta jalur';
$hide_header = true;
$page_wrapper_style = 'padding-bottom:calc(var(--bottom-nav-height) + 80px);';
$extra_css = '
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
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
/* Leaflet map styles */
#leafletMap {
    box-shadow: var(--shadow-sm);
}
.leaflet-container {
    font-family: var(--font-family) !important;
}
</style>
';
require_once __DIR__ . '/../includes/header.php';
?>
        <!-- Mountain Hero -->
        <div class="mountain-hero mountain-bg-<?= $mountain['image'] ?>">
            <div class="mountain-hero-gradient"></div>
            <a href="<?= BASE_URL ?>/pages/home.php" class="mountain-hero-back">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            </a>
            <div class="mountain-hero-content">
                <span class="badge badge-difficulty-<?= $mountain['difficulty'] ?>" style="margin-bottom:8px;">
                    <?= $difficultyLabels[$mountain['difficulty']] ?? '' ?>
                </span>
                <h1 style="font-size:var(--font-2xl);font-weight:700;margin-bottom:4px;"><?= sanitize($mountain['name']) ?></h1>
                <p class="text-secondary text-sm">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;color:rgba(255,255,255,0.7);margin-right:4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?= sanitize($mountain['location']) ?>
                </p>
            </div>
        </div>

        <!-- Info Bar -->
        <div class="mountain-info-bar">
            <div class="mountain-info-item glass-card-static">
                <div class="mountain-info-value"><?= number_format($mountain['altitude']) ?></div>
                <div class="mountain-info-label">mdpl</div>
            </div>
            <div class="mountain-info-item glass-card-static">
                <div class="mountain-info-value" style="color:var(--accent-light);"><?= $quotaToday ?></div>
                <div class="mountain-info-label">Kuota Tersisa</div>
            </div>
            <div class="mountain-info-item glass-card-static">
                <div class="mountain-info-value"><?= $activeClimbers ?></div>
                <div class="mountain-info-label">Pendaki Aktif</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs" id="tabs">
            <button class="tab active" data-tab="info">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;width:14px;height:14px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> Info
            </button>
            <button class="tab" data-tab="weather">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;width:14px;height:14px;"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg> Cuaca
            </button>
            <button class="tab" data-tab="map">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;width:14px;height:14px;"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg> Peta
            </button>
            <button class="tab" data-tab="schedule">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;width:14px;height:14px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Jadwal
            </button>
        </div>

        <!-- Tab: Info -->
        <div class="tab-content active" id="tab-info">
            <div class="glass-card-static p-lg mb-md">
                <h3 style="margin-bottom:var(--space-sm);">Tentang</h3>
                <p class="text-secondary text-sm" style="line-height:1.7;"><?= sanitize($mountain['description']) ?></p>
            </div>

            <!-- Kuota Display -->
            <div class="glass-card-static p-lg mb-md">
                <h4 style="margin-bottom:var(--space-sm);">Kuota Pendakian Hari Ini</h4>
                <div class="quota-bar">
                    <div class="quota-bar-fill <?= $quotaClass ?>" style="width:<?= $quotaPercent ?>%"></div>
                </div>
                <div class="quota-numbers">
                    <span><?= $activeClimbers ?> terdaftar</span>
                    <span><?= $quotaToday ?> / <?= $mountain['quota_per_day'] ?> tersisa</span>
                </div>

                <!-- Density -->
                <div class="density-indicator" style="background:<?= $density['color'] ?>15;">
                    <div class="density-dot" style="background:<?= $density['color'] ?>"></div>
                    <div>
                        <div class="density-label" style="color:<?= $density['color'] ?>">Kepadatan: <?= $density['label'] ?></div>
                        <div class="density-desc">
                            <?php
                            $densityDescs = [
                                'sepi' => 'Jalur relatif kosong, waktu ideal untuk mendaki.',
                                'sedang' => 'Beberapa pendaki di jalur, masih nyaman.',
                                'ramai' => 'Jalur cukup ramai, persiapkan waktu ekstra.',
                                'sangat_ramai' => 'Jalur sangat padat, pertimbangkan tanggal lain.'
                            ];
                            echo $densityDescs[$density['level']] ?? '';
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trails -->
            <div class="glass-card-static p-lg mb-md">
                <h4 style="margin-bottom:var(--space-md);">Jalur Pendakian</h4>
                <?php foreach ($mountain['trails'] as $trail): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:var(--space-sm) 0;border-bottom:1px solid var(--border-color);">
                    <div>
                        <div style="font-weight:600;font-size:var(--font-sm);">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--text-secondary);margin-right:4px;"><path d="M4 15V9h16v6Z"/><path d="M6 9v2"/><path d="M10 9v4"/><path d="M14 9v2"/><path d="M18 9v4"/></svg>
                            <?= sanitize($trail['name']) ?>
                        </div>
                        <div class="text-secondary text-xs"><?= $trail['distance'] ?> · <?= $trail['duration'] ?></div>
                    </div>
                    <span class="badge badge-difficulty-<?= $trail['difficulty'] ?>"><?= $difficultyLabels[$trail['difficulty']] ?? '' ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tab: Weather -->
        <div class="tab-content" id="tab-weather">
            <!-- Current Weather -->
            <div class="glass-card-static mb-md">
                <div class="weather-current">
                    <div class="weather-icon"><?= $weather['icon'] ?></div>
                    <div>
                        <div class="weather-temp"><?= $weather['temp'] ?>°C</div>
                        <div class="weather-condition"><?= $weather['condition'] ?></div>
                    </div>
                </div>
                <div class="weather-details">
                    <div class="weather-detail">
                        <div class="weather-detail-icon">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:var(--info);"><path d="M12 22a7 7 0 0 0 7-7c0-4.3-7-13-7-13s-7 8.7-7 13a7 7 0 0 0 7 7z"/></svg>
                        </div>
                        <div class="weather-detail-value"><?= $weather['humidity'] ?>%</div>
                        <div class="weather-detail-label">Kelembapan</div>
                    </div>
                    <div class="weather-detail">
                        <div class="weather-detail-icon">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:var(--text-secondary);"><path d="M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2"/></svg>
                        </div>
                        <div class="weather-detail-value"><?= $weather['wind_speed'] ?> km/j</div>
                        <div class="weather-detail-label">Angin</div>
                    </div>
                    <div class="weather-detail">
                        <div class="weather-detail-icon">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:var(--text-secondary);"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </div>
                        <div class="weather-detail-value"><?= $weather['visibility'] ?> km</div>
                        <div class="weather-detail-label">Visibilitas</div>
                    </div>
                </div>
            </div>

            <!-- Warnings -->
            <?php foreach ($weather['warnings'] as $warning): ?>
            <div class="weather-warning <?= $warning['type'] === 'rain' ? 'danger' : '' ?>" style="margin:0 0 var(--space-sm);">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:inherit;margin-right:6px;width:14px;height:14px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span><?= $warning['message'] ?></span>
            </div>
            <?php endforeach; ?>

            <!-- Forecast -->
            <div class="glass-card-static mb-md">
                <div style="padding:var(--space-md) var(--space-lg);border-bottom:1px solid var(--border-color);">
                    <h4>Prediksi 5 Hari</h4>
                </div>
                <div class="weather-forecast">
                    <?php foreach ($forecast as $f): ?>
                    <div class="forecast-item">
                        <span class="forecast-day"><?= $f['day_name'] ?></span>
                        <span class="forecast-icon"><?= $f['icon'] ?></span>
                        <span class="forecast-condition"><?= $f['condition'] ?></span>
                        <span class="forecast-temp"><?= $f['temp_min'] ?>° / <?= $f['temp_max'] ?>°</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Tab: Map -->
        <div class="tab-content" id="tab-map">
            <div class="glass-card-static p-lg mb-md">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-md); flex-wrap:wrap; gap:10px;">
                    <h4 style="margin: 0;">Peta Jalur & Tracker</h4>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="text-xs text-secondary" style="font-weight:700;">JALUR/VIA:</span>
                        <select id="trailSelector" style="padding:6px 12px; border-radius:var(--radius-sm); border:1px solid var(--border-color); font-size:var(--font-xs); font-weight:700; background:var(--bg-secondary); color:var(--text-primary); cursor:pointer;">
                            <?php foreach ($mountain['trails'] as $ti => $trail): ?>
                            <option value="<?= $trail['id'] ?>" <?= $ti === 0 ? 'selected' : '' ?>><?= sanitize($trail['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Leaflet Map Container -->
                <div id="leafletMap" style="height: 380px; width: 100%; border-radius: var(--radius-sm); border: 1px solid var(--border-color); position: relative; z-index: 1; background: var(--bg-tertiary);"></div>

                <!-- Legend -->
                <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-top:var(--space-sm); font-size:var(--font-xs); color:var(--text-secondary); font-weight:600;">
                    <div style="display:flex; align-items:center; gap:4px;"><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#DC2626;"></span> Puncak</div>
                    <div style="display:flex; align-items:center; gap:4px;"><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#B45309;"></span> Pos / Shelter</div>
                    <div style="display:flex; align-items:center; gap:4px;"><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#1D4ED8;"></span> Titik Air</div>
                    <div style="display:flex; align-items:center; gap:4px;"><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#15803D;"></span> Area Camping</div>
                </div>
            </div>

            <!-- Elevation Profile -->
            <div class="glass-card-static p-lg mt-md">
                <h4 style="margin-bottom:var(--space-md);">Profil Elevasi</h4>
                <div style="position:relative;height:120px;background:var(--bg-tertiary);border-radius:var(--radius-sm);overflow:hidden;">
                    <svg width="100%" height="100%" viewBox="0 0 100 60" preserveAspectRatio="none">
                        <?php
                        $posts = $mountain['posts'];
                        $minAlt = min(array_column($posts, 'altitude'));
                        $maxAlt = max(array_column($posts, 'altitude'));
                        $range = max($maxAlt - $minAlt, 1);
                        
                        $elevationPoints = "0,60 ";
                        foreach ($posts as $pi => $post) {
                            $x = ($pi / max(count($posts) - 1, 1)) * 100;
                            $y = 60 - (($post['altitude'] - $minAlt) / $range * 50 + 5);
                            $elevationPoints .= "$x,$y ";
                        }
                        $elevationPoints .= "100,60";
                        
                        $linePoints = "";
                        foreach ($posts as $pi => $post) {
                            $x = ($pi / max(count($posts) - 1, 1)) * 100;
                            $y = 60 - (($post['altitude'] - $minAlt) / $range * 50 + 5);
                            $linePoints .= ($pi === 0 ? "M" : "L") . "$x,$y ";
                        }
                        ?>
                        <defs>
                            <linearGradient id="elevGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#000000;stop-opacity:0.15" />
                                <stop offset="100%" style="stop-color:#000000;stop-opacity:0.0" />
                            </linearGradient>
                        </defs>
                        <polygon points="<?= $elevationPoints ?>" fill="url(#elevGrad)"/>
                        <path d="<?= $linePoints ?>" fill="none" stroke="#000000" stroke-width="1.5" stroke-linecap="round"/>
                        <?php foreach ($posts as $pi => $post):
                            $x = ($pi / max(count($posts) - 1, 1)) * 100;
                            $y = 60 - (($post['altitude'] - $minAlt) / $range * 50 + 5);
                        ?>
                        <circle cx="<?= $x ?>" cy="<?= $y ?>" r="1.5" fill="#000000" stroke="#FFFFFF" stroke-width="0.5"/>
                        <?php endforeach; ?>
                    </svg>
                    <div style="position:absolute;left:8px;top:8px;font-size:var(--font-xs);color:var(--text-tertiary);"><?= number_format($maxAlt) ?>m</div>
                    <div style="position:absolute;left:8px;bottom:8px;font-size:var(--font-xs);color:var(--text-tertiary);"><?= number_format($minAlt) ?>m</div>
                </div>
            </div>
        </div>

        <!-- Tab: Schedule -->
        <div class="tab-content" id="tab-schedule">
            <?php
            // Group available dates by their date string for quick lookup
            $availableLookup = [];
            foreach ($availableDates as $dateInfo) {
                $availableLookup[$dateInfo['date']] = $dateInfo;
            }

            $selectedQuotaInfo = null;
            if (!empty($selectedDate) && isset($availableLookup[$selectedDate])) {
                $selectedQuotaInfo = $availableLookup[$selectedDate];
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
            <div class="glass-card-static p-lg mb-md">
                <h3 style="margin-bottom:var(--space-md);">Pilih Tanggal Pendakian</h3>
                
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

                    <!-- Register Action Link -->
                    <div id="calendar-register-action" style="margin-top: var(--space-md); <?= ($selectedQuotaInfo && $selectedQuotaInfo['available']) ? '' : 'display: none;' ?>">
                        <a href="<?= BASE_URL ?>/pages/booking.php?mountain=<?= $mountainId ?>&date=<?= $selectedDate ?>" id="btnRegisterDate" class="btn btn-primary btn-block btn-lg" style="display: flex; align-items: center; justify-content: center; width: 100%;">
                            Daftar Pendakian pada <?= $selectedQuotaInfo ? $selectedQuotaInfo['formatted'] : '' ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <div class="sticky-cta">
        <a href="<?= BASE_URL ?>/pages/booking.php?mountain=<?= $mountainId ?>" class="btn btn-primary btn-block btn-lg">
            Daftar Pendakian
        </a>
    </div>
 
    <script>
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active from all
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Activate clicked
                this.classList.add('active');
                document.getElementById('tab-' + this.dataset.tab).classList.add('active');

                // Initialize Leaflet map when switching to map tab
                if (this.dataset.tab === 'map') {
                    setTimeout(() => {
                        initLeafletMap();
                        if (map) map.invalidateSize();
                    }, 100);
                }
            });
        });

        // Schedule Calendar interaction on Mountain Detail page
        document.querySelectorAll('#tab-schedule .calendar-day-cell').forEach(opt => {
            const radio = opt.querySelector('input[type="radio"]');
            if (radio && !radio.disabled) {
                opt.addEventListener('click', function(e) {
                    if (e.target === radio) return;
                    
                    radio.checked = true;
                    
                    // Remove selected class from all cells in schedule tab
                    document.querySelectorAll('#tab-schedule .calendar-day-cell').forEach(o => {
                        o.classList.remove('selected');
                    });
                    
                    // Add selected class to the clicked cell
                    this.classList.add('selected');

                    const quota = this.dataset.quota;
                    const date = this.dataset.date;
                    const day = this.dataset.day;
                    const dateVal = this.dataset.dateVal;
                    const quotaDisplay = document.getElementById('calendar-quota-display');
                    const registerAction = document.getElementById('calendar-register-action');
                    const btnRegister = document.getElementById('btnRegisterDate');
                    
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
                            
                            // Show register action button
                            if (registerAction && btnRegister) {
                                btnRegister.href = `<?= BASE_URL ?>/pages/booking.php?mountain=<?= $mountainId ?>&date=${dateVal}`;
                                btnRegister.innerHTML = `Daftar Pendakian pada ${date}`;
                                registerAction.style.display = 'block';
                            }
                        } else {
                            quotaDisplay.innerHTML = `
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; width: 100%;">
                                    <span>Kuota pada <strong>${dateText}</strong>:</span>
                                    <span style="font-size: var(--font-md); font-weight: 800; color: var(--danger);">Penuh</span>
                                </div>
                            `;
                            if (registerAction) registerAction.style.display = 'none';
                        }
                    }
                });
            }
        });

        // Initialize Map Tab
        let map;
        let activeLayers = [];

        function initLeafletMap() {
            if (map) return; // already initialized
            
            const mountainData = <?= json_encode($mountain) ?>;
            const mountainId = "<?= $mountainId ?>";
            
            // Map configuration for each mountain center
            const configs = {
                'mnt_semeru': { center: [-8.1077, 112.9224], zoom: 12 },
                'mnt_rinjani': { center: [-8.411, 116.457], zoom: 12 },
                'mnt_merbabu': { center: [-7.45, 110.43], zoom: 13 },
                'mnt_prau': { center: [-7.18, 109.92], zoom: 13 },
                'mnt_bromo': { center: [-7.94, 112.95], zoom: 13 },
                'mnt_gede': { center: [-6.79, 106.98], zoom: 13 }
            };
            
            const config = configs[mountainId] || { center: [-8.0, 110.0], zoom: 12 };
            
            // Create map
            map = L.map('leafletMap', {
                center: config.center,
                zoom: config.zoom,
                scrollWheelZoom: false
            });
            
            // Add Satellite tile layer (Esri World Imagery)
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP'
            }).addTo(map);

            // Add street labels
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}{r}.png').addTo(map);

            // Redraw trails based on selector
            updateMapTrail();

            // Bind change listener
            const selector = document.getElementById('trailSelector');
            if (selector) {
                selector.addEventListener('change', updateMapTrail);
            }
        }

        function updateMapTrail() {
            if (!map) return;

            // Clear old layers
            activeLayers.forEach(layer => map.removeLayer(layer));
            activeLayers = [];

            const mountainData = <?= json_encode($mountain) ?>;
            const mountainId = "<?= $mountainId ?>";
            
            const configs = {
                'mnt_semeru': { center: [-8.1077, 112.9224] },
                'mnt_rinjani': { center: [-8.411, 116.457] },
                'mnt_merbabu': { center: [-7.45, 110.43] },
                'mnt_prau': { center: [-7.18, 109.92] },
                'mnt_bromo': { center: [-7.94, 112.95] },
                'mnt_gede': { center: [-6.79, 106.98] }
            };
            const center = configs[mountainId]?.center || [-8.0, 110.0];

            function scaleCoord(x, y) {
                const latScale = 0.0012; 
                const lngScale = 0.0012;
                const lat = center[0] - (y - 50) * latScale;
                const lng = center[1] + (x - 50) * lngScale;
                return [lat, lng];
            }

            const selector = document.getElementById('trailSelector');
            const selectedTrailId = selector ? selector.value : mountainData.trails[0].id;
            
            let pathPoints = [];
            
            // Map-specific coordinates list
            const trailCoords = {
                'trail_sembalun': [
                    scaleCoord(12, 88), 
                    scaleCoord(28, 68), 
                    scaleCoord(48, 45), 
                    scaleCoord(55, 55), 
                    scaleCoord(82, 8)   
                ],
                'trail_senaru': [
                    scaleCoord(20, 20), 
                    scaleCoord(32, 35), 
                    scaleCoord(42, 48), 
                    scaleCoord(55, 55), 
                    scaleCoord(82, 8)   
                ],
                'trail_cibodas': [
                    scaleCoord(10, 88), 
                    scaleCoord(22, 72), 
                    scaleCoord(40, 52), 
                    scaleCoord(82, 12)  
                ],
                'trail_gunung_putri': [
                    scaleCoord(60, 80), 
                    scaleCoord(55, 60), 
                    scaleCoord(60, 32), 
                    scaleCoord(82, 12)  
                ],
                'trail_selo': [
                    scaleCoord(10, 85), 
                    scaleCoord(30, 62), 
                    scaleCoord(52, 40), 
                    scaleCoord(88, 10)  
                ],
                'trail_wekas': [
                    scaleCoord(20, 30), 
                    scaleCoord(45, 35), 
                    scaleCoord(70, 25), 
                    scaleCoord(88, 10)  
                ],
                'trail_dieng': [
                    scaleCoord(10, 82),
                    scaleCoord(32, 58),
                    scaleCoord(58, 35),
                    scaleCoord(85, 12)
                ],
                'trail_kalilembu': [
                    scaleCoord(25, 90),
                    scaleCoord(40, 70),
                    scaleCoord(58, 35),
                    scaleCoord(85, 12)
                ]
            };

            const points = trailCoords[selectedTrailId];
            if (points) {
                pathPoints = points;
            } else {
                mountainData.posts.forEach(post => {
                    pathPoints.push(scaleCoord(post.x, post.y));
                });
            }

            // Draw shadow path
            const outline = L.polyline(pathPoints, {
                color: '#000000',
                weight: 6,
                opacity: 0.5
            }).addTo(map);
            activeLayers.push(outline);

            // Draw primary path
            const polyline = L.polyline(pathPoints, {
                color: '#FFFFFF',
                weight: 4,
                opacity: 0.9,
                dashArray: '5, 5'
            }).addTo(map);
            activeLayers.push(polyline);
            
            // Draw markers based on trail
            const trailMarkers = {
                'trail_sembalun': [
                    { name: 'Pos 1 — Sembalun Lawang', alt: 1150, coord: scaleCoord(12, 88), isPeak: false },
                    { name: 'Pos 2 — Tengengean', alt: 1800, coord: scaleCoord(28, 68), isPeak: false },
                    { name: 'Pos 3 — Plawangan Sembalun', alt: 2639, coord: scaleCoord(48, 45), isPeak: false },
                    { name: 'Danau Segara Anak', alt: 2010, coord: scaleCoord(55, 55), isPeak: false },
                    { name: 'Puncak Rinjani', alt: 3726, coord: scaleCoord(82, 8), isPeak: true }
                ],
                'trail_senaru': [
                    { name: 'Pos 1 — Senaru Basecamp', alt: 601, coord: scaleCoord(20, 20), isPeak: false },
                    { name: 'Pos 2 — Mondokon Lolon', alt: 1500, coord: scaleCoord(32, 35), isPeak: false },
                    { name: 'Pos 3 — Plawangan Senaru', alt: 2641, coord: scaleCoord(42, 48), isPeak: false },
                    { name: 'Danau Segara Anak', alt: 2010, coord: scaleCoord(55, 55), isPeak: false },
                    { name: 'Puncak Rinjani', alt: 3726, coord: scaleCoord(82, 8), isPeak: true }
                ],
                'trail_cibodas': [
                    { name: 'Pos 1 — Cibodas', alt: 1500, coord: scaleCoord(10, 88), isPeak: false },
                    { name: 'Pos 2 — Air Terjun Cibeureum', alt: 1800, coord: scaleCoord(22, 72), isPeak: false },
                    { name: 'Pos 3 — Kandang Batu', alt: 2200, coord: scaleCoord(40, 52), isPeak: false },
                    { name: 'Puncak Gede', alt: 2958, coord: scaleCoord(82, 12), isPeak: true }
                ],
                'trail_gunung_putri': [
                    { name: 'Pos 1 — Gunung Putri', alt: 1450, coord: scaleCoord(60, 80), isPeak: false },
                    { name: 'Pos 2 — Legok Leunca', alt: 1850, coord: scaleCoord(55, 60), isPeak: false },
                    { name: 'Alun-alun Surya Kencana', alt: 2750, coord: scaleCoord(60, 32), isPeak: false },
                    { name: 'Puncak Gede', alt: 2958, coord: scaleCoord(82, 12), isPeak: true }
                ],
                'trail_selo': [
                    { name: 'Pos 1 — Selo', alt: 1600, coord: scaleCoord(10, 85), isPeak: false },
                    { name: 'Pos 2 — Sabana 1', alt: 2200, coord: scaleCoord(30, 62), isPeak: false },
                    { name: 'Pos 3 — Sabana 2', alt: 2700, coord: scaleCoord(52, 40), isPeak: false },
                    { name: 'Puncak Kenteng Songo', alt: 3145, coord: scaleCoord(88, 10), isPeak: true }
                ],
                'trail_wekas': [
                    { name: 'Pos 1 — Wekas Basecamp', alt: 1400, coord: scaleCoord(20, 30), isPeak: false },
                    { name: 'Pos 2 — Wekas Shelter', alt: 1900, coord: scaleCoord(45, 35), isPeak: false },
                    { name: 'Pos 3 — Watu Tulis', alt: 2950, coord: scaleCoord(70, 25), isPeak: false },
                    { name: 'Puncak Kenteng Songo', alt: 3145, coord: scaleCoord(88, 10), isPeak: true }
                ],
                'trail_dieng': [
                    { name: 'Pos 1 — Patak Banteng Basecamp', alt: 1800, coord: scaleCoord(10, 82), isPeak: false },
                    { name: 'Pos 2 — Hutan Pinus', alt: 2100, coord: scaleCoord(32, 58), isPeak: false },
                    { name: 'Pos 3 — Padang Rumput', alt: 2400, coord: scaleCoord(58, 35), isPeak: false },
                    { name: 'Puncak Prau', alt: 2565, coord: scaleCoord(85, 12), isPeak: true }
                ],
                'trail_kalilembu': [
                    { name: 'Pos 1 — Kalilembu Basecamp', alt: 1850, coord: scaleCoord(25, 90), isPeak: false },
                    { name: 'Pos 2 — Pertigaan Trail', alt: 2150, coord: scaleCoord(40, 70), isPeak: false },
                    { name: 'Pos 3 — Padang Rumput', alt: 2400, coord: scaleCoord(58, 35), isPeak: false },
                    { name: 'Puncak Prau', alt: 2565, coord: scaleCoord(85, 12), isPeak: true }
                ]
            };
            
            const activePoints = trailMarkers[selectedTrailId] || [];
            
            if (activePoints.length > 0) {
                activePoints.forEach(m => {
                    drawPostMarker(m.coord, m.name, m.alt, m.isPeak);
                });
            } else {
                mountainData.posts.forEach((post, i) => {
                    const coord = scaleCoord(post.x, post.y);
                    const isLast = (i === mountainData.posts.length - 1);
                    drawPostMarker(coord, post.name, post.altitude, isLast);
                });
            }

            function drawPostMarker(coord, name, alt, isPeak) {
                const marker = L.circleMarker(coord, {
                    radius: isPeak ? 8 : 5,
                    fillColor: isPeak ? '#DC2626' : '#B45309',
                    color: '#FFFFFF',
                    weight: 2,
                    fillOpacity: 1
                }).addTo(map);

                marker.bindPopup(`<strong>${name}</strong><br>Elevasi: ${alt} mdpl`);
                activeLayers.push(marker);
            }

            // Draw water points
            if (mountainData.water_points) {
                mountainData.water_points.forEach(wp => {
                    const coord = scaleCoord(wp.x, wp.y);
                    const marker = L.circleMarker(coord, {
                        radius: 5,
                        fillColor: '#1D4ED8',
                        color: '#FFFFFF',
                        weight: 1.5,
                        fillOpacity: 0.8
                    }).addTo(map);
                    marker.bindPopup(`<strong>Titik Air: ${wp.name}</strong>`);
                    activeLayers.push(marker);
                });
            }

            // Draw camping areas
            if (mountainData.camping_areas) {
                mountainData.camping_areas.forEach(camp => {
                    const coord = scaleCoord(camp.x, camp.y);
                    const marker = L.circleMarker(coord, {
                        radius: 6,
                        fillColor: '#15803D',
                        color: '#FFFFFF',
                        weight: 1.5,
                        fillOpacity: 0.8
                    }).addTo(map);
                    marker.bindPopup(`<strong>Area Camping: ${camp.name}</strong><br>Kapasitas: ${camp.capacity} tenda`);
                    activeLayers.push(marker);
                });
            }

            map.fitBounds(polyline.getBounds(), { padding: [30, 30] });
        }
    </script>
<?php
$active_page = 'home';
require_once __DIR__ . '/../includes/footer.php';
?>
