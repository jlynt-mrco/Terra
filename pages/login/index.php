<?php
require_once __DIR__ . '/../../config.php';

if (isLoggedIn()) {
    redirect('pages/home.php');
}

$error = $_SESSION['auth_error'] ?? null;
$success = $_SESSION['auth_success'] ?? null;
unset($_SESSION['auth_error'], $_SESSION['auth_success']);

$action = $_GET['action'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TERRA — Sistem Onboarding Pendakian Gunung Indonesia">
    <title>Masuk — TERRA</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            
            <?php include __DIR__ . '/welcome.php'; ?>
            <?php include __DIR__ . '/masuk.php'; ?>
            <?php include __DIR__ . '/daftar.php'; ?>

        </div>
    </div>

    <!-- JS Form Validation, Visibility Toggles, and Sliding sheets actions -->
    <script>
        // DOM Sheets Elements
        const loginSheet = document.getElementById('loginSheet');
        const registerSheet = document.getElementById('registerSheet');

        function openLoginSheet() {
            loginSheet.classList.add('sheet-open');
            registerSheet.classList.remove('sheet-open');
        }

        function openRegisterSheet() {
            registerSheet.classList.add('sheet-open');
            loginSheet.classList.remove('sheet-open');
        }

        function closeSheets(e) {
            if (e) e.preventDefault();
            loginSheet.classList.remove('sheet-open');
            registerSheet.classList.remove('sheet-open');
        }

        function switchToRegister(e) {
            if (e) e.preventDefault();
            loginSheet.classList.remove('sheet-open');
            setTimeout(openRegisterSheet, 200);
        }

        function switchToLogin(e) {
            if (e) e.preventDefault();
            registerSheet.classList.remove('sheet-open');
            setTimeout(openLoginSheet, 200);
        }

        // Toggle Password Visibilities
        const eyeOpen = `<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
        const eyeClosed = `<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;

        document.getElementById('toggleLoginPassword').addEventListener('click', function() {
            const pwd = document.getElementById('login_password');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                this.innerHTML = eyeClosed;
            } else {
                pwd.type = 'password';
                this.innerHTML = eyeOpen;
            }
        });

        document.getElementById('toggleRegPassword').addEventListener('click', function() {
            const pwd = document.getElementById('reg_password');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                this.innerHTML = eyeClosed;
            } else {
                pwd.type = 'password';
                this.innerHTML = eyeOpen;
            }
        });

        // Password Strength Checker
        document.getElementById('reg_password').addEventListener('input', function() {
            const val = this.value;
            const container = document.getElementById('passwordStrength');
            const bars = container.querySelectorAll('.strength-bar');
            const text = container.querySelector('.strength-text');
            
            container.style.display = val.length > 0 ? 'block' : 'none';
            
            let strength = 0;
            if (val.length >= 6) strength++;
            if (val.length >= 8) strength++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) strength++;
            if (/[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val)) strength++;

            const colors = ['#EF4444', '#F59E0B', '#10B981', '#10B981'];
            const labels = ['Lemah', 'Cukup', 'Kuat', 'Sangat Kuat'];

            bars.forEach((bar, i) => {
                bar.style.background = i < strength ? colors[Math.min(strength - 1, 3)] : 'var(--bg-tertiary)';
            });
            text.textContent = strength > 0 ? labels[strength - 1] : '';
            text.style.color = strength > 0 ? colors[Math.min(strength - 1, 3)] : 'var(--text-tertiary)';
        });

        // Form login validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('login_email');
            const password = document.getElementById('login_password');
            let valid = true;

            if (!email.value || !email.validity.valid) {
                email.closest('.form-group').classList.add('error');
                valid = false;
            } else {
                email.closest('.form-group').classList.remove('error');
            }

            if (!password.value || password.value.length < 6) {
                password.closest('.form-group').classList.add('error');
                valid = false;
            } else {
                password.closest('.form-group').classList.remove('error');
            }

            if (!valid) {
                e.preventDefault();
            } else {
                document.getElementById('loginBtn').innerHTML = '<span class="spinner" style="width:20px;height:20px;border-width:2px;"></span> Memproses...';
                document.getElementById('loginBtn').disabled = true;
            }
        });

        // Form register validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let valid = true;
            const fields = [
                { el: document.getElementById('reg_name'), check: v => v.length > 0 },
                { el: document.getElementById('reg_email'), check: (v, el) => el.validity.valid },
                { el: document.getElementById('reg_phone'), check: v => /^[0-9]{10,13}$/.test(v) },
                { el: document.getElementById('reg_password'), check: v => v.length >= 6 },
                { el: document.getElementById('reg_password_confirm'), check: v => v === document.getElementById('reg_password').value && v.length >= 6 }
            ];

            fields.forEach(f => {
                if (!f.check(f.el.value, f.el)) {
                    f.el.closest('.form-group').classList.add('error');
                    valid = false;
                } else {
                    f.el.closest('.form-group').classList.remove('error');
                }
            });

            if (!valid) {
                e.preventDefault();
            } else {
                document.getElementById('registerBtn').innerHTML = '<span class="spinner" style="width:20px;height:20px;border-width:2px;"></span> Memproses...';
                document.getElementById('registerBtn').disabled = true;
            }
        });

        // Auto-open sheet if there are alerts or action parameters
        const hasError = <?= $error ? 'true' : 'false' ?>;
        const hasSuccess = <?= $success ? 'true' : 'false' ?>;
        const action = '<?= sanitize($action) ?>';

        if (action === 'register') {
            openRegisterSheet();
        } else if (action === 'login' || hasError || hasSuccess) {
            openLoginSheet();
        }
    </script>
</body>
</html>
