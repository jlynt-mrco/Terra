<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$user = getCurrentUser();
$mountains = readJSON(MOUNTAINS_FILE);

// Define coordinates for simulation cities
$cities = [
    'yogyakarta' => [
        'name' => 'Yogyakarta (DIY)',
        'lat' => -7.7955,
        'lon' => 110.3695,
        'icon' => '⛩️'
    ],
    'bandung' => [
        'name' => 'Bandung (Jawa Barat)',
        'lat' => -6.9175,
        'lon' => 107.6191,
        'icon' => '☕'
    ],
    'jakarta' => [
        'name' => 'Jakarta (DKI)',
        'lat' => -6.2088,
        'lon' => 106.8456,
        'icon' => '🏢'
    ],
    'lombok' => [
        'name' => 'Mataram (Lombok / NTB)',
        'lat' => -8.5896,
        'lon' => 116.1293,
        'icon' => '🏖️'
    ]
];

// Define coordinates for mountains (fallback if not in json, but we will attach coordinates)
$mountainCoords = [
    'mnt_semeru' => ['lat' => -8.108, 'lon' => 112.92],
    'mnt_rinjani' => ['lat' => -8.411, 'lon' => 116.457],
    'mnt_merbabu' => ['lat' => -7.453, 'lon' => 110.437],
    'mnt_prau' => ['lat' => -7.185, 'lon' => 109.922],
    'mnt_bromo' => ['lat' => -7.942, 'lon' => 112.953],
    'mnt_gede' => ['lat' => -6.790, 'lon' => 106.979]
];

// Determine active simulated city
$activeCityKey = 'yogyakarta';
if (isset($_GET['city']) && array_key_exists($_GET['city'], $cities)) {
    $activeCityKey = $_GET['city'];
}
$activeCity = $cities[$activeCityKey];

// Haversine distance calculator
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c);
}

// Calculate distance for all mountains
$mountainsWithDistance = [];
foreach ($mountains as $m) {
    $mId = $m['id'];
    $coords = $mountainCoords[$mId] ?? ['lat' => -7.0, 'lon' => 110.0]; // fallback
    $distance = calculateDistance($activeCity['lat'], $activeCity['lon'], $coords['lat'], $coords['lon']);
    
    $m['distance'] = $distance;
    $mountainsWithDistance[] = $m;
}

// Sort by distance ascending
usort($mountainsWithDistance, function($a, $b) {
    return $a['distance'] <=> $b['distance'];
});

$page_title = 'Gunung Terdekat';
$page_desc = 'Gunung Terdekat dari Lokasi Anda — TERRA';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="nearby-mountains-container" style="max-width: 800px; margin: 0 auto; padding: 20px 16px 0px 16px;">

    <!-- Back Navigation -->
    <div style="margin-bottom: 20px;">
        <a href="<?= BASE_URL ?>/pages/home.php" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;font-size:11px;font-weight:700;text-transform:uppercase;border-radius:var(--radius-sm);border:1px solid var(--border-color);background:white;color:var(--text-primary);cursor:pointer;text-decoration:none;transition:all 0.15s ease;" onmouseover="this.style.background='var(--bg-secondary)';" onmouseout="this.style.background='white';">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Kembali ke Home
        </a>
    </div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    #radarMap {
        background: #0B0F19;
        height: 260px;
        width: 100%;
        border-radius: var(--radius-lg);
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
        border: 1.5px solid var(--accent);
        box-shadow: var(--shadow-md);
        z-index: 1;
    }
    
    /* Radar rings animation on Leaflet SVG overlay */
    @keyframes map-radar-ping {
        0% {
            stroke-width: 2;
            fill-opacity: 0.25;
            stroke-opacity: 0.85;
        }
        50% {
            fill-opacity: 0.08;
            stroke-opacity: 0.45;
        }
        100% {
            stroke-width: 0.5;
            fill-opacity: 0;
            stroke-opacity: 0;
        }
    }
    
    .map-radar-ring {
        animation: map-radar-ping 4s infinite linear;
        transform-origin: center;
    }
    
    /* Stagger delay for multiple rings */
    .map-radar-ring-2 {
        animation-delay: 1.3s;
    }
    
    .map-radar-ring-3 {
        animation-delay: 2.6s;
    }
    
    /* Pulse user dot marker */
    .user-pulsing-icon {
        background: #3B82F6;
        border: 2px solid #FFFFFF;
        border-radius: 50%;
        box-shadow: 0 0 8px #3B82F6;
    }
</style>


    <!-- Location Card -->
    <div class="glass-card-static" style="padding: var(--space-md); border-radius: var(--radius-lg); background: white; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); margin-bottom: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; font-size: 14px;">
                    📍
                </div>
                <div>
                    <div style="font-size: 8.5px; font-weight: 800; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.05em;">Lokasi Terdeteksi</div>
                    <div id="active-location-name" style="font-size: 13.5px; font-weight: 850; color: var(--text-primary);">Mendeteksi lokasi...</div>
                </div>
            </div>
            <!-- Include the Modular Geolocation Widget -->
            <div>
                <?php include __DIR__ . '/location/location.php'; ?>
            </div>
        </div>
    </div>

    <!-- Peta Radar Google Satellite -->
    <div id="radarMap"></div>

    <!-- Mountains List sorted by distance -->
    <h2 style="font-size: 11px; font-weight: 850; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Daftar Gunung Berdasarkan Jarak</h2>
    
    <div id="mountains-list-container" style="display: flex; flex-direction: column; gap: var(--space-sm);">
        <?php foreach ($mountainsWithDistance as $mountain): 
            $density = getDensityLevel($mountain['id']);
            $difficultyLabels = ['easy' => 'Mudah', 'medium' => 'Menengah', 'hard' => 'Sulit'];
            $difficultyLabel = $difficultyLabels[$mountain['difficulty']] ?? $mountain['difficulty'];
            
            $mId = $mountain['id'];
            $coords = $mountainCoords[$mId] ?? ['lat' => -7.0, 'lon' => 110.0];
        ?>
            <a href="<?= BASE_URL ?>/pages/mountain.php?id=<?= $mountain['id'] ?>" class="mountain-card-item" data-id="<?= $mountain['id'] ?>" data-name="<?= sanitize($mountain['name']) ?>" data-lat="<?= $coords['lat'] ?>" data-lon="<?= $coords['lon'] ?>" data-distance="<?= $mountain['distance'] ?>" style="display:flex; flex-direction:row; align-items:stretch; height:106px; overflow:hidden; border-radius: var(--radius-lg); border: 1px solid var(--border-color); background: white;">
                
                <!-- Linear Gradient Placeholder -->
                <div class="mountain-card-image-placeholder mountain-bg-<?= $mountain['image'] ?>" style="width:100px; height:100%; min-width:100px;">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:28px; height:28px; opacity:0.35; color:white;"><path d="M4 22L12 8L20 22H4Z" /><path d="M9 13.25L12 9.5L15 13.25" /><path d="M12 22L17 14L22 22H12Z" /></svg>
                </div>
                
                <!-- Body details -->
                <div class="mountain-card-body" style="flex:1; padding:12px; display:flex; flex-direction:column; justify-content:space-between;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <h3 style="font-size:12.5px; margin-bottom:0; font-weight:800; color:var(--text-primary);"><?= sanitize($mountain['name']) ?></h3>
                            <span class="mountain-distance-badge" style="font-size: 9px; font-weight: 800; color: var(--text-accent); display: inline-flex; align-items: center; gap: 3px; background: var(--bg-tertiary); padding: 2px 8px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                📍 <?= $mountain['distance'] ?> km
                            </span>
                        </div>
                        
                        <div style="display:flex; gap: 8px; align-items:center; margin-top: 2px;">
                            <span class="badge badge-difficulty-<?= $mountain['difficulty'] ?>" style="font-size:8px; padding:1px 5px;"><?= $difficultyLabel ?></span>
                            <span style="font-size: 10px; color: var(--text-secondary);"><?= sanitize($mountain['location']) ?></span>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-color); padding-top:6px;">
                        <div style="font-size:10px; color:var(--text-secondary); font-weight:700;">
                            <span style="color:var(--text-primary); font-weight:800;"><?= number_format($mountain['altitude']) ?></span> mdpl
                        </div>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <span style="background:<?= $density['color'] ?>; width:5px; height:5px; border-radius:50%;"></span>
                            <span style="font-size:8.5px; font-weight:800; text-transform:uppercase; color:var(--text-secondary);"><?= $density['label'] ?></span>
                        </div>
                    </div>
                </div>

            </a>
        <?php endforeach; ?>
    </div>
    <div id="no-mountains-message" style="display: none; padding: var(--space-md); text-align: center; color: var(--text-secondary); font-size: var(--font-sm); border: 1.5px dashed var(--border-color); border-radius: var(--radius-lg); background: white; margin-top: var(--space-sm);">
        Tidak ada gunung terdekat dalam radius 150 km dari posisi Anda.
    </div>
</div>

<script>
{
    // Pass PHP mountains coordinates data into JS
    const mountainsData = <?= json_encode(array_map(function($m) use ($mountainCoords) {
        return [
            'id' => $m['id'],
            'name' => $m['name'],
            'altitude' => $m['altitude'],
            'lat' => $mountainCoords[$m['id']]['lat'] ?? -7.0,
            'lon' => $mountainCoords[$m['id']]['lon'] ?? 110.0
        ];
    }, $mountains)) ?>;



    let map = null;
    let userMarker = null;
    let mountainMarkers = {};
    let radarCircles = [];

    // Haversine formula to calculate distance in km
    function calculateHaversine(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth's radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return Math.round(R * c);
    }

    // Initialize Map
    function initMap(lat, lon) {
        if (map) return;

        // Create Leaflet Map centered on user
        map = L.map('radarMap', {
            zoomControl: true,
            scrollWheelZoom: false
        }).setView([lat, lon], 8);

        // Add Google Satellite Hybrid tiles (Satellite view with labels & roads)
        L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: '© Google Satellites'
        }).addTo(map);

        // Add custom pulsing blue marker for the user
        const userIcon = L.divIcon({
            className: 'user-pulsing-icon',
            iconSize: [12, 12]
        });
        userMarker = L.marker([lat, lon], { icon: userIcon }).addTo(map);
        userMarker.bindPopup('<b>Posisi Anda</b>').openPopup();

        // Add pulsing radar circles centered at the user's location
        const radiusRanges = [30000, 60000, 120000]; // 30km, 60km, 120km
        radiusRanges.forEach((radius, index) => {
            const circle = L.circle([lat, lon], {
                radius: radius,
                color: '#3B82F6',
                weight: 1,
                fillColor: '#3B82F6',
                fillOpacity: 0.1,
                className: `map-radar-ring map-radar-ring-${index + 1}`
            }).addTo(map);
            radarCircles.push(circle);
        });

        // Add markers for all mountains
        mountainsData.forEach(m => {
            const mMarker = L.marker([m.lat, m.lon]).addTo(map);
            mMarker.bindPopup(`
                <div style="font-family: var(--font-family); font-size:11px;">
                    <b style="color:var(--text-primary);">${m.name}</b><br>
                    <span>Ketinggian: ${m.altitude.toLocaleString()} mdpl</span><br>
                    <a href="<?= BASE_URL ?>/pages/mountain.php?id=${m.id}" style="color:var(--info); font-weight:700; font-size:9px; text-transform:uppercase; display:inline-block; margin-top:4px;">Detail Gunung →</a>
                </div>
            `);
            mountainMarkers[m.id] = mMarker;
        });
    }

    // Update Map Elements
    function updateMapLocation(lat, lon, locationName) {
        if (!map) {
            initMap(lat, lon);
            return;
        }

        // Center map to new coordinates
        map.setView([lat, lon], 8);

        // Move user marker
        if (userMarker) {
            userMarker.setLatLng([lat, lon]);
            userMarker.bindPopup(`<b>${locationName}</b>`).openPopup();
        }

        // Move radar circles
        radarCircles.forEach(circle => {
            circle.setLatLng([lat, lon]);
        });

        // Update mountain marker popups with real-time distance
        mountainsData.forEach(m => {
            const distance = calculateHaversine(lat, lon, m.lat, m.lon);
            const marker = mountainMarkers[m.id];
            if (marker) {
                marker.setPopupContent(`
                    <div style="font-family: var(--font-family); font-size:11px;">
                        <b style="color:var(--text-primary);">${m.name}</b><br>
                        <span>Ketinggian: ${m.altitude.toLocaleString()} mdpl</span><br>
                        <span style="font-weight:700; color:var(--text-accent);">📍 Jarak: ${distance} km</span><br>
                        <a href="<?= BASE_URL ?>/pages/mountain.php?id=${m.id}" style="color:var(--info); font-weight:700; font-size:9px; text-transform:uppercase; display:inline-block; margin-top:4px;">Detail Gunung →</a>
                    </div>
                `);
            }
        });
    }

    // Update list cards and dynamically re-sort them
    function updateListAndDistances(userLat, userLon) {
        const container = document.getElementById('mountains-list-container');
        if (!container) return;

        const cards = Array.from(container.querySelectorAll('.mountain-card-item'));
        let visibleCount = 0;

        cards.forEach(card => {
            const mLat = parseFloat(card.dataset.lat);
            const mLon = parseFloat(card.dataset.lon);
            const distance = calculateHaversine(userLat, userLon, mLat, mLon);

            // Update distance badge text
            const badge = card.querySelector('.mountain-distance-badge');
            if (badge) {
                badge.innerHTML = `📍 ${distance} km`;
            }
            card.dataset.distance = distance;

            // Filter: Limit to nearby mountains within 150 km radius
            if (distance <= 150) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Sort HTML cards by distance ascending
        cards.sort((a, b) => parseFloat(a.dataset.distance) - parseFloat(b.dataset.distance));
        
        // Re-append in sorted order to the DOM
        cards.forEach(card => container.appendChild(card));

        // Show empty state message if no mountains found in radius
        const noMsg = document.getElementById('no-mountains-message');
        if (noMsg) {
            noMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    // Geolocation Callback hook
    window.onLocationUpdated = function(lat, lon, locationName) {
        const activeNameEl = document.getElementById('active-location-name');
        if (!activeNameEl) return; // Prevent executing callback if user navigated away from this page

        activeNameEl.innerText = locationName;
        updateMapLocation(lat, lon, 'Lokasi Anda');
        updateListAndDistances(lat, lon);
    };

    // Fallback Initializer (load standard default center if no cache found)
    setTimeout(() => {
        const cachedLat = localStorage.getItem('terra_user_lat');
        const cachedLon = localStorage.getItem('terra_user_lon');
        const cachedLocation = localStorage.getItem('terra_user_location');

        if (!cachedLat || !cachedLon) {
            // Default center: Yogyakarta coordinates
            initMap(-7.7955, 110.3695);
            updateMapLocation(-7.7955, 110.3695, 'Yogyakarta (Default)');
            updateListAndDistances(-7.7955, 110.3695);
        }
    }, 300);

    // Auto run location scan on load
    if (typeof window.triggerLocationScan === 'function') {
        // First, if we have cached coordinates, initialize the map with them immediately so the page is not blank while scanning
        const cachedLat = localStorage.getItem('terra_user_lat');
        const cachedLon = localStorage.getItem('terra_user_lon');
        const cachedLocation = localStorage.getItem('terra_user_location');
        if (cachedLat && cachedLon) {
            const lat = parseFloat(cachedLat);
            const lon = parseFloat(cachedLon);
            initMap(lat, lon);
            updateMapLocation(lat, lon, cachedLocation || 'Lokasi Terdeteksi');
            updateListAndDistances(lat, lon);
        }
        // Force refresh location scan automatically when page opens
        window.triggerLocationScan(true);
    }
}
</script>

<?php
$active_page = 'home';
require_once __DIR__ . '/../includes/footer.php';
?>
