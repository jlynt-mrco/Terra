<?php
require_once __DIR__ . '/../config.php';

if (isLoggedIn()) {
    redirect('pages/home.php');
}

$error = $_SESSION['auth_error'] ?? null;
unset($_SESSION['auth_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TERRA — Buat akun pendakian baru">
    <title>Daftar — TERRA</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <img src="<?= BASE_URL ?>/logo/logo.png" alt="TERRA Logo" style="width:56px; height:56px; object-fit:contain; margin: 0 auto 8px auto; display: block;">
                </div>
                <h1 class="auth-title">Buat Akun</h1>
                <p class="auth-subtitle">Daftar untuk mulai pendakian Anda</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger mb-md">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--danger);margin-right:6px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span><?= sanitize($error) ?></span>
                </div>
            <?php endif; ?>

            <form id="registerForm" action="<?= BASE_URL ?>/api/auth.php" method="POST">
                <input type="hidden" name="action" value="register">

                <div class="form-group">
                    <label class="form-label" for="name">Nama Lengkap</label>
                    <div class="form-input-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="name" name="name" class="form-input" placeholder="Masukkan nama lengkap" required autocomplete="name">
                    </div>
                    <div class="form-error">Nama wajib diisi</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <div class="form-input-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <input type="email" id="email" name="email" class="form-input" placeholder="nama@email.com" required autocomplete="email">
                    </div>
                    <div class="form-error">Email tidak valid</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Nomor Telepon</label>
                    <div class="form-input-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <input type="tel" id="phone" name="phone" class="form-input" placeholder="08xxxxxxxxxx" required pattern="[0-9]{10,13}" autocomplete="tel">
                    </div>
                    <div class="form-error">Nomor telepon tidak valid (10-13 digit)</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="form-input-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Minimal 6 karakter" required minlength="6" autocomplete="new-password">
                        <span class="icon icon-right" id="togglePassword" style="cursor:pointer;">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </span>
                    </div>
                    <div class="form-error">Password minimal 6 karakter</div>
                    <!-- Password strength -->
                    <div class="password-strength mt-sm" id="passwordStrength" style="display:none;">
                        <div style="display:flex;gap:4px;margin-bottom:4px;">
                            <div class="strength-bar" style="flex:1;height:3px;border-radius:2px;background:var(--bg-tertiary);transition:background 0.3s;"></div>
                            <div class="strength-bar" style="flex:1;height:3px;border-radius:2px;background:var(--bg-tertiary);transition:background 0.3s;"></div>
                            <div class="strength-bar" style="flex:1;height:3px;border-radius:2px;background:var(--bg-tertiary);transition:background 0.3s;"></div>
                            <div class="strength-bar" style="flex:1;height:3px;border-radius:2px;background:var(--bg-tertiary);transition:background 0.3s;"></div>
                        </div>
                        <span class="strength-text" style="font-size:var(--font-xs);color:var(--text-tertiary);"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirm">Konfirmasi Password</label>
                    <div class="form-input-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-input" placeholder="Ulangi password" required minlength="6" autocomplete="new-password">
                    </div>
                    <div class="form-error">Password tidak cocok</div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-lg" id="registerBtn">
                    Daftar
                </button>
            </form>

            <div class="auth-footer">
                Sudah punya akun? <a href="<?= BASE_URL ?>/pages/login.php">Masuk</a>
            </div>
        </div>
    </div>


    <script>

        // Toggle password
        const eyeOpen = `<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
        const eyeClosed = `<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;

        document.getElementById('togglePassword').addEventListener('click', function() {
            const pwd = document.getElementById('password');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                this.innerHTML = eyeClosed;
            } else {
                pwd.type = 'password';
                this.innerHTML = eyeOpen;
            }
        });

        // Password strength
        document.getElementById('password').addEventListener('input', function() {
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

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let valid = true;
            const fields = [
                { el: document.getElementById('name'), check: v => v.length > 0 },
                { el: document.getElementById('email'), check: (v, el) => el.validity.valid },
                { el: document.getElementById('phone'), check: v => /^[0-9]{10,13}$/.test(v) },
                { el: document.getElementById('password'), check: v => v.length >= 6 },
                { el: document.getElementById('password_confirm'), check: v => v === document.getElementById('password').value && v.length >= 6 }
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
    </script>
</body>
</html>
