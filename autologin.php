<?php
require_once __DIR__ . '/config.php';
$users = readJSON(USERS_FILE);
foreach ($users as $u) {
    if ($u['email'] === 'tester@test.com') {
        $_SESSION['user'] = $u;
        break;
    }
}
redirect('pages/profile.php');
