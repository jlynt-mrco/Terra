<?php
/**
 * TERRA — Auth API Handler
 */
require_once __DIR__ . '/../config.php';

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        redirect('pages/login/index.php');
}

function handleLogin() {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['auth_error'] = 'Email dan password wajib diisi.';
        redirect('pages/login/index.php');
    }

    $users = readJSON(USERS_FILE);
    $foundUser = null;

    foreach ($users as $user) {
        if (strtolower($user['email']) === strtolower($email)) {
            $foundUser = $user;
            break;
        }
    }

    if (!$foundUser || !verifyPassword($password, $foundUser['password'])) {
        $_SESSION['auth_error'] = 'Email atau password salah.';
        redirect('pages/login/index.php');
    }

    // Set session
    $_SESSION['user_id'] = $foundUser['id'];
    $_SESSION['user_name'] = $foundUser['name'];
    $_SESSION['user_email'] = $foundUser['email'];

    redirect('pages/home.php');
}

function handleRegister() {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $_SESSION['auth_error'] = 'Semua field wajib diisi.';
        redirect('pages/login/index.php?action=register');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['auth_error'] = 'Format email tidak valid.';
        redirect('pages/login/index.php?action=register');
    }

    if (!preg_match('/^[0-9]{10,13}$/', $phone)) {
        $_SESSION['auth_error'] = 'Nomor telepon tidak valid (10-13 digit angka).';
        redirect('pages/login/index.php?action=register');
    }

    if (strlen($password) < 6) {
        $_SESSION['auth_error'] = 'Password minimal 6 karakter.';
        redirect('pages/login/index.php?action=register');
    }

    if ($password !== $passwordConfirm) {
        $_SESSION['auth_error'] = 'Konfirmasi password tidak cocok.';
        redirect('pages/login/index.php?action=register');
    }

    // Check existing email
    $users = readJSON(USERS_FILE);
    foreach ($users as $user) {
        if (strtolower($user['email']) === strtolower($email)) {
            $_SESSION['auth_error'] = 'Email sudah terdaftar. Silakan login.';
            redirect('pages/login/index.php?action=register');
        }
    }

    // Create user
    $newUser = [
        'id' => generateId('usr'),
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'password' => hashPassword($password),
        'created_at' => date('Y-m-d\TH:i:s')
    ];

    $users[] = $newUser;
    writeJSON(USERS_FILE, $users);

    // Auto-login registered user directly
    $_SESSION['user_id'] = $newUser['id'];
    $_SESSION['user_name'] = $newUser['name'];
    $_SESSION['user_email'] = $newUser['email'];

    redirect('pages/home.php');
}

function handleLogout() {
    session_destroy();
    session_start();
    $_SESSION['auth_success'] = 'Anda telah keluar.';
    redirect('pages/login/index.php');
}
