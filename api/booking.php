<?php
/**
 * TERRA — Booking API Handler
 */
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$user = getCurrentUser();
$mountainId = $_POST['mountain_id'] ?? '';
$date = $_POST['date'] ?? '';
$trailId = $_POST['trail_id'] ?? '';
$trailName = $_POST['trail_name'] ?? '';
$membersJson = $_POST['members'] ?? '[]';

// Validate
$mountain = getMountain($mountainId);
if (!$mountain) {
    jsonResponse(['success' => false, 'message' => 'Gunung tidak ditemukan.']);
}

if (empty($date)) {
    jsonResponse(['success' => false, 'message' => 'Tanggal pendakian wajib dipilih.']);
}

// Check date is in the future
if (strtotime($date) <= strtotime('today')) {
    jsonResponse(['success' => false, 'message' => 'Tanggal pendakian harus di masa depan.']);
}

$members = json_decode($membersJson, true);
if (empty($members) || !is_array($members)) {
    jsonResponse(['success' => false, 'message' => 'Minimal 1 anggota pendakian.']);
}

// Validate members
foreach ($members as $i => $member) {
    if (empty($member['name'])) {
        jsonResponse(['success' => false, 'message' => 'Nama anggota ' . ($i + 1) . ' wajib diisi.']);
    }
    if (empty($member['ktp']) || !preg_match('/^[0-9]{16}$/', $member['ktp'])) {
        jsonResponse(['success' => false, 'message' => 'NIK anggota ' . ($i + 1) . ' harus 16 digit angka.']);
    }
}

// Check quota
$quota = getMountainQuota($mountainId, $date);
if ($quota < count($members)) {
    jsonResponse(['success' => false, 'message' => 'Kuota tidak mencukupi. Tersisa ' . $quota . ' slot.']);
}

// Generate booking
$bookingId = generateId('bk');
$mountainCode = strtoupper(substr(str_replace('mnt_', '', $mountainId), 0, 3));
$dateCode = date('Ymd', strtotime($date));

$bookingMembers = [];
foreach ($members as $i => $member) {
    $seq = str_pad($i + 1, 3, '0', STR_PAD_LEFT);
    $barcodeCode = "TERRA-{$mountainCode}-{$dateCode}-{$seq}";
    
    $bookingMembers[] = [
        'id' => generateId('mbr'),
        'name' => trim($member['name']),
        'ktp_number' => $member['ktp'],
        'barcode' => $barcodeCode
    ];
}

$booking = [
    'id' => $bookingId,
    'user_id' => $user['id'],
    'mountain_id' => $mountainId,
    'mountain_name' => $mountain['name'],
    'date' => $date,
    'trail_id' => $trailId,
    'trail_name' => $trailName,
    'status' => 'confirmed',
    'members' => $bookingMembers,
    'created_at' => date('Y-m-d\TH:i:s')
];

// Save
$bookings = readJSON(BOOKINGS_FILE);
$bookings[] = $booking;
writeJSON(BOOKINGS_FILE, $bookings);

jsonResponse([
    'success' => true,
    'message' => 'Pendaftaran berhasil!',
    'booking_id' => $bookingId
]);
