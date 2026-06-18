<?php
require_once __DIR__ . '/../config.php';
requireLogin();

// Redirect to integrated Ticket page with Tracking tab selected
$bookingId = $_GET['booking_id'] ?? '';
if (!empty($bookingId)) {
    redirect('pages/my_bookings.php?tab=tracking&booking_id=' . urlencode($bookingId));
} else {
    redirect('pages/my_bookings.php?tab=tracking');
}
exit;
