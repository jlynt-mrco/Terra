<?php
/**
 * TERRA — Helper Functions
 */

// ============================================================
// JSON File CRUD
// ============================================================

function readJSON($file) {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([]));
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function writeJSON($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ============================================================
// Authentication Helpers
// ============================================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $users = readJSON(USERS_FILE);
    foreach ($users as $user) {
        if ($user['id'] === $_SESSION['user_id']) {
            return $user;
        }
    }
    return null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('pages/login/index.php');
        exit;
    }
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// ============================================================
// Utility Helpers
// ============================================================

function generateId($prefix = 'id') {
    return $prefix . '_' . bin2hex(random_bytes(8));
}

function redirect($path) {
    header('Location: ' . BASE_URL . '/' . $path);
    exit;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

// ============================================================
// Mountain Helpers
// ============================================================

function getMountain($id) {
    $mountains = readJSON(MOUNTAINS_FILE);
    foreach ($mountains as $mountain) {
        if ($mountain['id'] === $id) {
            return $mountain;
        }
    }
    return null;
}

function getMountainQuota($mountainId, $date) {
    $mountain = getMountain($mountainId);
    if (!$mountain) return 0;
    
    $bookings = readJSON(BOOKINGS_FILE);
    $bookedCount = 0;
    
    foreach ($bookings as $booking) {
        if ($booking['mountain_id'] === $mountainId && $booking['date'] === $date && $booking['status'] !== 'cancelled') {
            $bookedCount += count($booking['members']);
        }
    }
    
    return max(0, $mountain['quota_per_day'] - $bookedCount);
}

function getActiveClimbers($mountainId) {
    $bookings = readJSON(BOOKINGS_FILE);
    $today = date('Y-m-d');
    $count = 0;
    
    foreach ($bookings as $booking) {
        if ($booking['mountain_id'] === $mountainId && $booking['date'] === $today && $booking['status'] === 'confirmed') {
            $count += count($booking['members']);
        }
    }
    
    return $count;
}

function getDensityLevel($mountainId) {
    $mountain = getMountain($mountainId);
    if (!$mountain) return ['level' => 'unknown', 'label' => 'Tidak Diketahui', 'color' => '#94A3B8'];
    
    $active = getActiveClimbers($mountainId);
    $quota = $mountain['quota_per_day'];
    $ratio = $quota > 0 ? $active / $quota : 0;
    
    if ($ratio < 0.25) return ['level' => 'sepi', 'label' => 'Sepi', 'color' => '#10B981', 'icon' => '🟢'];
    if ($ratio < 0.50) return ['level' => 'sedang', 'label' => 'Sedang', 'color' => '#F59E0B', 'icon' => '🟡'];
    if ($ratio < 0.75) return ['level' => 'ramai', 'label' => 'Ramai', 'color' => '#F97316', 'icon' => '🟠'];
    return ['level' => 'sangat_ramai', 'label' => 'Sangat Ramai', 'color' => '#EF4444', 'icon' => '🔴'];
}

// ============================================================
// Weather Simulation
// ============================================================

function getSimulatedWeather($mountainId, $date = null) {
    $mountain = getMountain($mountainId);
    if (!$mountain) return null;
    
    $altitude = $mountain['altitude'];
    $seed = crc32($mountainId . ($date ?? date('Y-m-d')));
    mt_srand($seed);
    
    // Temperature based on altitude (roughly -6°C per 1000m)
    $baseTemp = 28 - ($altitude / 1000) * 6;
    $temp = round($baseTemp + mt_rand(-3, 3));
    $tempMin = $temp - mt_rand(2, 5);
    $tempMax = $temp + mt_rand(1, 4);
    
    // Weather conditions
    $conditions = ['Cerah', 'Cerah Berawan', 'Berawan', 'Hujan Ringan', 'Hujan Sedang', 'Hujan Lebat', 'Berkabut'];
    $weights = [20, 25, 20, 15, 10, 5, 5];
    $conditionIndex = weightedRandom($weights);
    $condition = $conditions[$conditionIndex];
    
    // Wind speed (higher at altitude)
    $windBase = 5 + ($altitude / 1000) * 3;
    $windSpeed = round($windBase + mt_rand(-3, 8));
    
    // Humidity
    $humidity = mt_rand(60, 95);
    
    // Visibility
    $visibility = ($condition === 'Berkabut') ? mt_rand(1, 5) : mt_rand(5, 20);
    
    // Warnings
    $warnings = [];
    if ($condition === 'Hujan Lebat') $warnings[] = ['type' => 'rain', 'message' => 'Peringatan Hujan Lebat — Jalur mungkin licin'];
    if ($windSpeed > 15) $warnings[] = ['type' => 'wind', 'message' => 'Peringatan Angin Kencang — Hati-hati di area terbuka'];
    if ($temp < 5) $warnings[] = ['type' => 'cold', 'message' => 'Suhu Sangat Dingin — Siapkan perlengkapan hangat'];
    
    mt_srand(); // Reset seed
    
    return [
        'condition' => $condition,
        'icon' => getWeatherIconSVG($condition),
        'temp' => $temp,
        'temp_min' => $tempMin,
        'temp_max' => $tempMax,
        'humidity' => $humidity,
        'wind_speed' => $windSpeed,
        'visibility' => $visibility,
        'warnings' => $warnings
    ];
}

function getWeatherIconSVG($condition) {
    switch ($condition) {
        case 'Cerah':
            return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
        case 'Cerah Berawan':
            return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v2M4.93 4.93l1.41 1.41M20 12h2M19.07 4.93l-1.41 1.41M15.9 11A5 5 0 1 0 9 16h7a4 4 0 0 0 .9-7.9Z"/></svg>';
        case 'Berawan':
            return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>';
        case 'Hujan Ringan':
            return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m17 18-2 3M20 10A5.5 5.5 0 0 0 9.5 14.5a7 7 0 1 0 7.5 5.5h3a4 4 0 0 0 0-8h-.5ZM12 18l-2 3M8 18l-2 3"/></svg>';
        case 'Hujan Sedang':
        case 'Hujan Lebat':
            return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25M8 19l-2 3M12 19l-2 3M16 19l-2 3"/></svg>';
        case 'Berkabut':
            return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="8" x2="19" y2="8"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="5" y1="16" x2="19" y2="16"/></svg>';
        default:
            return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';
    }
}

function getWeatherForecast($mountainId, $days = 5) {
    $forecast = [];
    for ($i = 0; $i < $days; $i++) {
        $date = date('Y-m-d', strtotime("+{$i} days"));
        $weather = getSimulatedWeather($mountainId, $date);
        $weather['date'] = $date;
        $weather['day_name'] = getIndonesianDay(date('l', strtotime($date)));
        $forecast[] = $weather;
    }
    return $forecast;
}

function weightedRandom($weights) {
    $total = array_sum($weights);
    $rand = mt_rand(1, $total);
    $cumulative = 0;
    foreach ($weights as $i => $weight) {
        $cumulative += $weight;
        if ($rand <= $cumulative) return $i;
    }
    return 0;
}

function getIndonesianDay($englishDay) {
    $days = [
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];
    return $days[$englishDay] ?? $englishDay;
}

// ============================================================
// QR Code SVG Generator (Pure PHP)
// ============================================================

function generateQRCodeSVG($data, $size = 200) {
    // Simple QR-like code generator using a matrix pattern
    // This generates a scannable-looking pattern based on data hash
    $hash = md5($data);
    $moduleCount = 21; // 21x21 modules (Version 1 QR)
    $moduleSize = $size / $moduleCount;
    
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '">';
    $svg .= '<rect width="' . $size . '" height="' . $size . '" fill="white"/>';
    
    // Generate matrix from hash
    $matrix = [];
    $hashBits = '';
    for ($i = 0; $i < strlen($hash); $i++) {
        $hashBits .= str_pad(base_convert($hash[$i], 16, 2), 4, '0', STR_PAD_LEFT);
    }
    // Extend hash bits to fill matrix
    while (strlen($hashBits) < $moduleCount * $moduleCount) {
        $hashBits .= $hashBits;
    }
    
    for ($row = 0; $row < $moduleCount; $row++) {
        for ($col = 0; $col < $moduleCount; $col++) {
            $matrix[$row][$col] = false;
        }
    }
    
    // Finder patterns (top-left, top-right, bottom-left)
    $finderPositions = [[0, 0], [0, $moduleCount - 7], [$moduleCount - 7, 0]];
    foreach ($finderPositions as $pos) {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                $isOuter = ($r === 0 || $r === 6 || $c === 0 || $c === 6);
                $isInner = ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
                $matrix[$pos[0] + $r][$pos[1] + $c] = $isOuter || $isInner;
            }
        }
    }
    
    // Timing patterns
    for ($i = 8; $i < $moduleCount - 8; $i++) {
        $matrix[6][$i] = ($i % 2 === 0);
        $matrix[$i][6] = ($i % 2 === 0);
    }
    
    // Data modules
    $bitIndex = 0;
    for ($row = 0; $row < $moduleCount; $row++) {
        for ($col = 0; $col < $moduleCount; $col++) {
            // Skip finder patterns and timing
            $inFinder = false;
            foreach ($finderPositions as $pos) {
                if ($row >= $pos[0] && $row < $pos[0] + 7 && $col >= $pos[1] && $col < $pos[1] + 7) {
                    $inFinder = true;
                    break;
                }
            }
            if ($inFinder || $row === 6 || $col === 6) continue;
            
            if ($bitIndex < strlen($hashBits)) {
                $matrix[$row][$col] = $hashBits[$bitIndex] === '1';
                $bitIndex++;
            }
        }
    }
    
    // Render SVG modules
    for ($row = 0; $row < $moduleCount; $row++) {
        for ($col = 0; $col < $moduleCount; $col++) {
            if ($matrix[$row][$col]) {
                $x = round($col * $moduleSize, 2);
                $y = round($row * $moduleSize, 2);
                $s = round($moduleSize, 2);
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $s . '" height="' . $s . '" fill="#0F1923"/>';
            }
        }
    }
    
    $svg .= '</svg>';
    return $svg;
}

// ============================================================
// Booking Helpers
// ============================================================

function getUserBookings($userId) {
    $bookings = readJSON(BOOKINGS_FILE);
    return array_filter($bookings, function($b) use ($userId) {
        return $b['user_id'] === $userId;
    });
}

function getBooking($bookingId) {
    $bookings = readJSON(BOOKINGS_FILE);
    foreach ($bookings as $booking) {
        if ($booking['id'] === $bookingId) {
            return $booking;
        }
    }
    return null;
}

// ============================================================
// Schedule Helpers  
// ============================================================

function getAvailableDates($mountainId, $days = 14) {
    $dates = [];
    for ($i = 1; $i <= $days; $i++) {
        $date = date('Y-m-d', strtotime("+{$i} days"));
        $quota = getMountainQuota($mountainId, $date);
        $dates[] = [
            'date' => $date,
            'day_name' => getIndonesianDay(date('l', strtotime($date))),
            'formatted' => formatDate($date),
            'quota_remaining' => $quota,
            'available' => $quota > 0
        ];
    }
    return $dates;
}

// ============================================================
// Island/Regional Helpers
// ============================================================

function getMountainIsland($province) {
    $province = strtolower($province);
    if (strpos($province, 'jawa') !== false) {
        return 'Jawa';
    } elseif (strpos($province, 'sumatera') !== false || strpos($province, 'aceh') !== false || strpos($province, 'bengkulu') !== false || strpos($province, 'riau') !== false || strpos($province, 'jambi') !== false || strpos($province, 'lampung') !== false) {
        return 'Sumatera';
    } elseif (strpos($province, 'sulawesi') !== false) {
        return 'Sulawesi';
    } elseif (strpos($province, 'nusa tenggara') !== false || strpos($province, 'bali') !== false || strpos($province, 'ntb') !== false || strpos($province, 'ntt') !== false) {
        return 'Nusa Tenggara & Bali';
    }
    return 'Lainnya';
}

// ============================================================
// Gamification / Achievements Helpers
// ============================================================

function getUserAchievementsData($user) {
    if (!$user) {
        return [
            'achievements' => [],
            'unlockedCount' => 0,
            'totalPoints' => 0,
            'goldBadges' => 0,
            'silverBadges' => 0,
            'bronzeBadges' => 0
        ];
    }

    if (!isset($_SESSION['demo_achievements'])) {
        $_SESSION['demo_achievements'] = [
            'leave_no_trace' => false,
            'clean_campaign' => false,
            'report_trash' => false,
            'reviews_count' => 2,
            'photos_count' => 12,
            'loyal_consecutive_days' => 8
        ];
    }

    $bookings = getUserBookings($user['id']);
    $totalBookings = count($bookings);
    $totalMembers = 0;
    foreach ($bookings as $b) {
        $totalMembers += count($b['members']);
    }

    $todayStr = date('Y-m-d');
    $userCreatedAt = strtotime($user['created_at'] ?? date('Y-m-d'));
    $accountAgeSeconds = time() - $userCreatedAt;

    // 1. Pendaki Pemula
    $hasFirstTicket = ($totalBookings > 0);
    $hasFirstHike = false;
    foreach ($bookings as $b) {
        if ($b['date'] <= $todayStr) {
            $hasFirstHike = true;
            break;
        }
    }
    $hasFirstCheckIn = $hasFirstHike;

    // 2. Explorer
    $uniqueMountainsClimbed = [];
    foreach ($bookings as $b) {
        $uniqueMountainsClimbed[$b['mountain_id']] = true;
    }
    $uniqueMountainsCount = count($uniqueMountainsClimbed);

    // 3. Pendaki Aktif
    $sixMonthsAgo = strtotime('-6 months');
    $oneYearAgo = strtotime('-1 year');
    $hikesLast6Months = 0;
    $hikesLast1Year = 0;
    foreach ($bookings as $b) {
        $bTime = strtotime($b['date']);
        if ($bTime >= $sixMonthsAgo) {
            $hikesLast6Months++;
        }
        if ($bTime >= $oneYearAgo) {
            $hikesLast1Year++;
        }
    }

    // 4. Penakluk Ketinggian
    $maxAltitude = 0;
    foreach ($bookings as $b) {
        $mnt = getMountain($b['mountain_id']);
        if ($mnt && $mnt['altitude'] > $maxAltitude) {
            $maxAltitude = $mnt['altitude'];
        }
    }

    // 5. Kolektor Puncak
    $completedHikes = 0;
    foreach ($bookings as $b) {
        if ($b['date'] <= $todayStr) {
            $completedHikes++;
        }
    }

    // 6. Pendaki Setia
    $is6MonthsActive = ($accountAgeSeconds >= (6 * 30 * 24 * 60 * 60));
    $is1YearActive = ($accountAgeSeconds >= (365 * 24 * 60 * 60));
    $consecutiveDays = $_SESSION['demo_achievements']['loyal_consecutive_days'] ?? 8;

    // 7. Pendaki Nusantara
    $javaMountainsClimbed = [];
    $sumatraMountainsClimbed = [];
    $sulawesiMountainsClimbed = [];
    $islandsClimbed = [];
    foreach ($bookings as $b) {
        $mnt = getMountain($b['mountain_id']);
        if ($mnt) {
            $island = getMountainIsland($mnt['province']);
            $islandsClimbed[$island] = true;
            if ($island === 'Jawa') {
                $javaMountainsClimbed[$b['mountain_id']] = true;
            } elseif ($island === 'Sumatera') {
                $sumatraMountainsClimbed[$b['mountain_id']] = true;
            } elseif ($island === 'Sulawesi') {
                $sulawesiMountainsClimbed[$b['mountain_id']] = true;
            }
        }
    }
    $javaCount = count($javaMountainsClimbed);
    $sumatraCount = count($sumatraMountainsClimbed);
    $sulawesiCount = count($sulawesiMountainsClimbed);
    $islandsCount = count($islandsClimbed);

    // 8. Pendaki Ramah Lingkungan
    $leaveNoTrace = $_SESSION['demo_achievements']['leave_no_trace'] ?? false;
    $cleanCampaign = $_SESSION['demo_achievements']['clean_campaign'] ?? false;
    $reportTrash = $_SESSION['demo_achievements']['report_trash'] ?? false;

    // 9. Sosial
    $otherMembersCount = 0;
    foreach ($bookings as $b) {
        $otherMembersCount += (count($b['members']) - 1);
    }
    $reviewsCount = $_SESSION['demo_achievements']['reviews_count'] ?? 2;
    $photosCount = $_SESSION['demo_achievements']['photos_count'] ?? 12;

    $hasInvitedFriend = ($otherMembersCount >= 1);
    $hasInvited5Friends = ($otherMembersCount >= 5);

    $achievements = [
        [
            'id' => 'novice_ticket',
            'category' => 'utama',
            'title' => 'Tiket Pertama',
            'desc' => 'Membeli tiket pendakian pertama di aplikasi TERRA',
            'icon' => '🎫',
            'is_unlocked' => $hasFirstTicket,
            'current' => $hasFirstTicket ? 1 : 0,
            'target' => 1,
            'badge_level' => null
        ],
        [
            'id' => 'novice_checkin',
            'category' => 'utama',
            'title' => 'Check-in Pertama',
            'desc' => 'Berhasil melakukan check-in pendakian pertama di pos awal',
            'icon' => '✅',
            'is_unlocked' => $hasFirstCheckIn,
            'current' => $hasFirstCheckIn ? 1 : 0,
            'target' => 1,
            'badge_level' => null
        ],
        [
            'id' => 'novice_hike',
            'category' => 'utama',
            'title' => 'Pendaki Pemula',
            'desc' => 'Menyelesaikan pendakian pertama Anda',
            'icon' => '🏔️',
            'is_unlocked' => $hasFirstHike,
            'current' => $hasFirstHike ? 1 : 0,
            'target' => 1,
            'badge_level' => null
        ],
        [
            'id' => 'explorer_1',
            'category' => 'utama',
            'title' => 'Explorer I',
            'desc' => 'Mendaki 3 gunung berbeda',
            'icon' => '🥉',
            'is_unlocked' => $uniqueMountainsCount >= 3,
            'current' => $uniqueMountainsCount,
            'target' => 3,
            'badge_level' => 'bronze'
        ],
        [
            'id' => 'explorer_2',
            'category' => 'utama',
            'title' => 'Explorer II',
            'desc' => 'Mendaki 5 gunung berbeda',
            'icon' => '🥈',
            'is_unlocked' => $uniqueMountainsCount >= 5,
            'current' => $uniqueMountainsCount,
            'target' => 5,
            'badge_level' => 'silver'
        ],
        [
            'id' => 'explorer_3',
            'category' => 'utama',
            'title' => 'Explorer III',
            'desc' => 'Mendaki 10 gunung berbeda',
            'icon' => '🥇',
            'is_unlocked' => $uniqueMountainsCount >= 10,
            'current' => $uniqueMountainsCount,
            'target' => 10,
            'badge_level' => 'gold'
        ],
        [
            'id' => 'active_hiker_1',
            'category' => 'utama',
            'title' => 'Pendaki Aktif I',
            'desc' => 'Menyelesaikan 3 pendakian dalam 6 bulan',
            'icon' => '⏱️',
            'is_unlocked' => $hikesLast6Months >= 3,
            'current' => $hikesLast6Months,
            'target' => 3,
            'badge_level' => null
        ],
        [
            'id' => 'active_hiker_2',
            'category' => 'utama',
            'title' => 'Pendaki Aktif II',
            'desc' => 'Menyelesaikan 5 pendakian dalam 1 tahun',
            'icon' => '⚡',
            'is_unlocked' => $hikesLast1Year >= 5,
            'current' => $hikesLast1Year,
            'target' => 5,
            'badge_level' => null
        ],
        [
            'id' => 'altitude_2000',
            'category' => 'utama',
            'title' => 'Penakluk 2K',
            'desc' => 'Mendaki gunung dengan ketinggian di atas 2.000 mdpl',
            'icon' => '☁️',
            'is_unlocked' => $maxAltitude >= 2000,
            'current' => $maxAltitude,
            'target' => 2000,
            'unit' => ' mdpl',
            'badge_level' => null
        ],
        [
            'id' => 'altitude_3000',
            'category' => 'utama',
            'title' => 'Penakluk 3K',
            'desc' => 'Mendaki gunung dengan ketinggian di atas 3.000 mdpl',
            'icon' => '🦅',
            'is_unlocked' => $maxAltitude >= 3000,
            'current' => $maxAltitude,
            'target' => 3000,
            'unit' => ' mdpl',
            'badge_level' => null
        ],
        [
            'id' => 'altitude_3500',
            'category' => 'utama',
            'title' => 'Penakluk 3.5K',
            'desc' => 'Mendaki gunung dengan ketinggian di atas 3.500 mdpl',
            'icon' => '👑',
            'is_unlocked' => $maxAltitude >= 3500,
            'current' => $maxAltitude,
            'target' => 3500,
            'unit' => ' mdpl',
            'badge_level' => null
        ],
        [
            'id' => 'summits_5',
            'category' => 'utama',
            'title' => 'Kolektor Puncak I',
            'desc' => 'Menyelesaikan 5 puncak pendakian',
            'icon' => '⛳',
            'is_unlocked' => $completedHikes >= 5,
            'current' => $completedHikes,
            'target' => 5,
            'badge_level' => null
        ],
        [
            'id' => 'summits_10',
            'category' => 'utama',
            'title' => 'Kolektor Puncak II',
            'desc' => 'Menyelesaikan 10 puncak pendakian',
            'icon' => '🚩',
            'is_unlocked' => $completedHikes >= 10,
            'current' => $completedHikes,
            'target' => 10,
            'badge_level' => null
        ],
        [
            'id' => 'loyal_6m',
            'category' => 'spesial',
            'title' => 'Setia 6 Bulan',
            'desc' => 'Memiliki akun TERRA aktif selama minimal 6 bulan',
            'icon' => '🛡️',
            'is_unlocked' => $is6MonthsActive,
            'current' => floor($accountAgeSeconds / (30 * 24 * 60 * 60)),
            'target' => 6,
            'unit' => ' bln',
            'badge_level' => null
        ],
        [
            'id' => 'loyal_streak',
            'category' => 'spesial',
            'title' => 'Login Beruntun',
            'desc' => 'Melakukan login aplikasi selama 30 hari berturut-turut',
            'icon' => '🔥',
            'is_unlocked' => $consecutiveDays >= 30,
            'current' => $consecutiveDays,
            'target' => 30,
            'unit' => ' hari',
            'badge_level' => null,
            'is_demo' => true,
            'demo_key' => 'loyal_consecutive_days'
        ],
        [
            'id' => 'nusantara_java',
            'category' => 'regional',
            'title' => 'Penjelajah Jawa',
            'desc' => 'Menjelajahi 3 gunung berbeda di Pulau Jawa',
            'icon' => '⛩️',
            'is_unlocked' => $javaCount >= 3,
            'current' => $javaCount,
            'target' => 3,
            'badge_level' => null
        ],
        [
            'id' => 'nusantara_sumatra',
            'category' => 'regional',
            'title' => 'Penjelajah Sumatera',
            'desc' => 'Menjelajahi 3 gunung berbeda di Pulau Sumatera (Segera Hadir)',
            'icon' => '🐘',
            'is_unlocked' => $sumatraCount >= 3,
            'current' => $sumatraCount,
            'target' => 3,
            'badge_level' => null
        ],
        [
            'id' => 'nusantara_sulawesi',
            'category' => 'regional',
            'title' => 'Penjelajah Sulawesi',
            'desc' => 'Menjelajahi 3 gunung berbeda di Pulau Sulawesi (Segera Hadir)',
            'icon' => '🐃',
            'is_unlocked' => $sulawesiCount >= 3,
            'current' => $sulawesiCount,
            'target' => 3,
            'badge_level' => null
        ],
        [
            'id' => 'nusantara_islands',
            'category' => 'regional',
            'title' => 'Nusantara Explorer',
            'desc' => 'Melakukan pendakian di 3 pulau berbeda di Indonesia',
            'icon' => '🇮🇩',
            'is_unlocked' => $islandsCount >= 3,
            'current' => $islandsCount,
            'target' => 3,
            'badge_level' => null
        ],
        [
            'id' => 'eco_leave_no_trace',
            'category' => 'spesial',
            'title' => 'Leave No Trace',
            'desc' => 'Menyelesaikan materi edukasi Leave No Trace (Ramah Lingkungan)',
            'icon' => '🌱',
            'is_unlocked' => $leaveNoTrace,
            'current' => $leaveNoTrace ? 1 : 0,
            'target' => 1,
            'badge_level' => null,
            'is_demo' => true,
            'demo_key' => 'leave_no_trace'
        ],
        [
            'id' => 'eco_clean_campaign',
            'category' => 'spesial',
            'title' => 'Bersih Gunung',
            'desc' => 'Mengikuti kampanye sukarela aksi bersih gunung',
            'icon' => '🧹',
            'is_unlocked' => $cleanCampaign,
            'current' => $cleanCampaign ? 1 : 0,
            'target' => 1,
            'badge_level' => null,
            'is_demo' => true,
            'demo_key' => 'clean_campaign'
        ],
        [
            'id' => 'eco_report_trash',
            'category' => 'spesial',
            'title' => 'Pelapor Hijau',
            'desc' => 'Melaporkan titik tumpukan sampah atau kerusakan jalur pendakian',
            'icon' => '📢',
            'is_unlocked' => $reportTrash,
            'current' => $reportTrash ? 1 : 0,
            'target' => 1,
            'badge_level' => null,
            'is_demo' => true,
            'demo_key' => 'report_trash'
        ],
        [
            'id' => 'social_friends_1',
            'category' => 'spesial',
            'title' => 'Sosial: Anggota Baru',
            'desc' => 'Mengajak teman pertama dalam kelompok pendakian Anda',
            'icon' => '🤝',
            'is_unlocked' => $hasInvitedFriend,
            'current' => $otherMembersCount,
            'target' => 1,
            'badge_level' => null
        ],
        [
            'id' => 'social_friends_5',
            'category' => 'spesial',
            'title' => 'Sosial: Tim Solid',
            'desc' => 'Mengajak total 5 teman dalam kelompok pendakian Anda',
            'icon' => '👥',
            'is_unlocked' => $hasInvited5Friends,
            'current' => $otherMembersCount,
            'target' => 5,
            'badge_level' => null
        ],
        [
            'id' => 'social_reviews',
            'category' => 'spesial',
            'title' => 'Kritikus Gunung',
            'desc' => 'Membuat 10 ulasan/review gunung yang telah didaki',
            'icon' => '✍️',
            'is_unlocked' => $reviewsCount >= 10,
            'current' => $reviewsCount,
            'target' => 10,
            'badge_level' => null,
            'is_demo' => true,
            'demo_key' => 'reviews_count'
        ],
        [
            'id' => 'social_photos',
            'category' => 'spesial',
            'title' => 'Fotografer Alam',
            'desc' => 'Mengunggah 50 foto petualangan pendakian Anda',
            'icon' => '📷',
            'is_unlocked' => $photosCount >= 50,
            'current' => $photosCount,
            'target' => 50,
            'badge_level' => null,
            'is_demo' => true,
            'demo_key' => 'photos_count'
        ]
    ];

    $unlockedCount = 0;
    $goldBadges = 0;
    $silverBadges = 0;
    $bronzeBadges = 0;
    $totalPoints = 0;
    foreach ($achievements as $ach) {
        if ($ach['is_unlocked']) {
            $unlockedCount++;
            if ($ach['badge_level'] === 'gold') {
                $goldBadges++;
                $totalPoints += 100;
            } elseif ($ach['badge_level'] === 'silver') {
                $silverBadges++;
                $totalPoints += 50;
            } elseif ($ach['badge_level'] === 'bronze') {
                $bronzeBadges++;
                $totalPoints += 25;
            } else {
                $totalPoints += 10;
            }
        }
    }

    return [
        'achievements' => $achievements,
        'unlockedCount' => $unlockedCount,
        'totalPoints' => $totalPoints,
        'goldBadges' => $goldBadges,
        'silverBadges' => $silverBadges,
        'bronzeBadges' => $bronzeBadges
    ];
}

function updateSelectedAchievement($userId, $achievementId) {
    $users = readJSON(USERS_FILE);
    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            $user['selected_achievement'] = $achievementId;
            writeJSON(USERS_FILE, $users);
            return true;
        }
    }
    return false;
}

function getUserTier($completedHikes) {
    if ($completedHikes <= 0) {
        return [
            'tier' => 'Trail',
            'title' => 'Beginner',
            'icon' => '🟢',
            'color' => '#10B981'
        ];
    } elseif ($completedHikes === 1) {
        return [
            'tier' => 'Trail',
            'title' => 'Explorer',
            'icon' => '🟢',
            'color' => '#10B981'
        ];
    } elseif ($completedHikes === 2) {
        return [
            'tier' => 'Trail',
            'title' => 'Wanderer',
            'icon' => '🟢',
            'color' => '#10B981'
        ];
    } elseif ($completedHikes === 3) {
        return [
            'tier' => 'Explorer',
            'title' => 'Pathfinder',
            'icon' => '🔵',
            'color' => '#1D4ED8'
        ];
    } elseif ($completedHikes === 4) {
        return [
            'tier' => 'Explorer',
            'title' => 'Trailblazer',
            'icon' => '🔵',
            'color' => '#1D4ED8'
        ];
    } elseif ($completedHikes === 5) {
        return [
            'tier' => 'Explorer',
            'title' => 'Summit Seeker',
            'icon' => '🔵',
            'color' => '#1D4ED8'
        ];
    } elseif ($completedHikes === 6 || $completedHikes === 7) {
        return [
            'tier' => 'Summit',
            'title' => 'Peak Hunter',
            'icon' => '🟣',
            'color' => '#7C3AED'
        ];
    } elseif ($completedHikes === 8 || $completedHikes === 9) {
        return [
            'tier' => 'Summit',
            'title' => 'Trail Master',
            'icon' => '🟣',
            'color' => '#7C3AED'
        ];
    } elseif ($completedHikes === 10 || $completedHikes === 11) {
        return [
            'tier' => 'Summit',
            'title' => 'The Ascender',
            'icon' => '🟣',
            'color' => '#7C3AED'
        ];
    } elseif ($completedHikes >= 12 && $completedHikes <= 14) {
        return [
            'tier' => 'Legend',
            'title' => 'Mountain Sovereign',
            'icon' => '🟡',
            'color' => '#D97706'
        ];
    } elseif ($completedHikes >= 15 && $completedHikes <= 19) {
        return [
            'tier' => 'Legend',
            'title' => 'King of Peaks',
            'icon' => '🟡',
            'color' => '#D97706'
        ];
    } else {
        return [
            'tier' => 'Legend',
            'title' => 'Mountain Legend',
            'icon' => '🟡',
            'color' => '#D97706'
        ];
    }
}
