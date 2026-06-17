<?php
/**
 * TERRA — Common Header
 */
require_once __DIR__ . '/../config.php';

// Safe user query if logged in
if (!isset($user) && isLoggedIn()) {
    $user = getCurrentUser();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= isset($page_desc) ? htmlspecialchars($page_desc) : 'TERRA — Dashboard Pendakian Gunung Indonesia' ?>">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — TERRA' : 'TERRA' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
    <?php if (isset($extra_css)): ?>
        <?= $extra_css ?>
    <?php endif; ?>
</head>
<body>
    <div id="pjax-loading-bar"></div>
    <div class="page-wrapper"<?= isset($page_wrapper_style) ? ' style="' . $page_wrapper_style . '"' : '' ?>>
        <?php if (!isset($hide_header) || !$hide_header): ?>
        <!-- Header -->
        <header class="header">
            <div class="header-inner">
                <a href="<?= BASE_URL ?>/pages/home.php" class="header-logo">
                    <div class="logo-icon" style="background:none;">
                        <img src="<?= BASE_URL ?>/logo/logo.png" alt="Logo" style="width:32px; height:32px; object-fit:contain;">
                    </div>
                    <span>TERRA</span>
                </a>
                <?php if (isLoggedIn() && isset($user)): ?>
                <div class="header-actions">
                    <a href="<?= BASE_URL ?>/pages/profile.php" style="width:32px;height:32px;border-radius:50%;background:var(--accent);color:white;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;border:1.5px solid var(--border-color);box-shadow:var(--shadow-sm);">
                        <?php
                        $initials = '';
                        $nameParts = explode(' ', $user['name']);
                        foreach ($nameParts as $part) {
                            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                            if (strlen($initials) >= 2) break;
                        }
                        echo $initials;
                        ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </header>
        <?php endif; ?>
        <main id="main-content" class="content-fade">

