<?php
require_once __DIR__ . '/../../config.php';

if (isLoggedIn()) {
    redirect('pages/home.php');
}

$error = $_SESSION['auth_error'] ?? null;
$success = $_SESSION['auth_success'] ?? null;
unset($_SESSION['auth_error'], $_SESSION['auth_success']);

$action = $_GET['action'] ?? 'login';

$page_title = 'Masuk / Daftar';
$page_desc = 'TERRA — Sistem Onboarding Pendakian Gunung Indonesia';
$hide_bottom_nav = true;

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Hero Section (Matching home.php style) -->
<section class="hero" style="background:var(--accent);color:white;padding-top:var(--space-xl);padding-bottom:56px;border-bottom:none;border-radius:0 0 16px 16px;">
    <div class="container" style="text-align: center;">
        <div class="hero-content" style="color:white;">
            <p class="hero-greeting" style="display:inline-flex;align-items:center;gap:4px;color:rgba(255,255,255,0.7) !important; text-transform: uppercase; font-size: var(--font-xs); font-weight: 700; letter-spacing: 0.05em;">
                <span><?= APP_NAME ?> — <?= APP_TAGLINE ?></span>
            </p>
            <h1 class="hero-title" style="color:white;margin-top:4px;font-size:var(--font-xl); font-weight: 800; text-transform: uppercase; letter-spacing: 0.02em;">Eksplorasi Gunung Indonesia</h1>
            <p style="color:rgba(255,255,255,0.6);font-size:var(--font-xs);margin-top:2px;">Silakan masuk atau buat akun baru untuk memulai pendakian Anda.</p>
        </div>
    </div>
</section>

<!-- Consolidated Login/Register Form Card (Matching reservation form card in home.php) -->
<div class="container" style="margin-top:-38px; position:relative; z-index:10; margin-bottom:var(--space-md); max-width: var(--container-sm);">
    <div class="glass-card-static p-md" style="box-shadow:var(--shadow-lg); border-color:var(--accent); background: var(--bg-card); border-radius: var(--radius-md);">
        
        <!-- Tab Switched Header -->
        <div style="display: flex; border-bottom: 1px solid var(--border-color); margin-bottom: var(--space-md);">
            <a href="#" id="tab-login" onclick="switchTab('login', event)" style="flex: 1; text-align: center; padding: var(--space-sm) 0; font-size: var(--font-xs); font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); border-bottom: 2px solid var(--accent); transition: all var(--transition-fast);">
                Masuk
            </a>
            <a href="#" id="tab-register" onclick="switchTab('register', event)" style="flex: 1; text-align: center; padding: var(--space-sm) 0; font-size: var(--font-xs); font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); border-bottom: 2px solid transparent; transition: all var(--transition-fast);">
                Daftar Akun
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span><?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?= sanitize($success) ?></span>
            </div>
        <?php endif; ?>

        <!-- LOGIN FORM CONTAINER -->
        <div id="form-login-container">
            <form id="loginForm" action="<?= BASE_URL ?>/api/auth.php" method="POST">
                <input type="hidden" name="action" value="login">

                <div class="form-group">
                    <label class="form-label" for="login_email">Email</label>
                    <div class="form-input-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <input type="email" id="login_email" name="email" class="form-input" placeholder="nama@email.com" required autocomplete="email">
                    </div>
                    <div class="form-error">Email tidak valid</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="login_password">Password</label>
                    <div class="form-input-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="login_password" name="password" class="form-input" placeholder="Masukkan password" required minlength="6" autocomplete="current-password">
                        <span class="icon icon-right" id="toggleLoginPassword" style="cursor:pointer;">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </span>
                    </div>
                    <div class="form-error">Password minimal 6 karakter</div>
                </div>

                <div class="auth-options">
                    <label class="auth-checkbox">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkbox-custom"></span>
                        <span>Ingat Saya</span>
                    </label>
                    <a href="#" class="auth-forgot-link" onclick="alert('Fitur Lupa Password sedang dinonaktifkan')">Lupa Password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-md" id="loginBtn">
                    MASUK KE AKUN
                </button>
            </form>
        </div>

        <!-- REGISTER FORM CONTAINER -->
        <div id="form-register-container" style="display: none;">
            <form id="registerForm" action="<?= BASE_URL ?>/api/auth.php" method="POST">
                <input type="hidden" name="action" value="register">

                <div class="form-group">
                    <label class="form-label" for="reg_name">Nama Lengkap</label>
                    <div class="form-input-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="reg_name" name="name" class="form-input" placeholder="Nama Lengkap" required autocomplete="name">
                    </div>
                    <div class="form-error">Nama wajib diisi</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reg_email">Email</label>
                    <div class="form-input-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <input type="email" id="reg_email" name="email" class="form-input" placeholder="nama@email.com" required autocomplete="email">
                    </div>
                    <div class="form-error">Email tidak valid</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reg_phone">Nomor Telepon</label>
                    <div class="form-input-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <input type="tel" id="reg_phone" name="phone" class="form-input" placeholder="08xxxxxxxxxx" required pattern="[0-9]{10,13}" autocomplete="tel">
                    </div>
                    <div class="form-error">Nomor telepon tidak valid (10-13 digit)</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reg_password">Password</label>
                    <div class="form-input-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="reg_password" name="password" class="form-input" placeholder="Minimal 6 karakter" required minlength="6" autocomplete="new-password">
                        <span class="icon icon-right" id="toggleRegPassword" style="cursor:pointer;">
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
                    <label class="form-label" for="reg_password_confirm">Konfirmasi Password</label>
                    <div class="form-input-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="reg_password_confirm" name="password_confirm" class="form-input" placeholder="Ulangi password" required minlength="6" autocomplete="new-password">
                    </div>
                    <div class="form-error">Password tidak cocok</div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-md" id="registerBtn">
                    DAFTAR AKUN BARU
                </button>
            </form>
        </div>

        <!-- Social logins -->
        <div class="auth-social-group" style="margin-top:var(--space-md);">
            <div class="auth-social-divider">
                <span>atau gunakan</span>
            </div>
            <div class="auth-social-buttons">
                <a href="#" class="social-btn facebook" onclick="alert('Login dengan Facebook sedang dinonaktifkan')">
                    <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <a href="#" class="social-btn google" onclick="alert('Login dengan Google sedang dinonaktifkan')">
                    <svg class="icon" viewBox="0 0 24 24"><path fill="#EA4335" d="M5.266 9.765A7.077 7.077 0 0 1 12 4.909c1.69 0 3.218.6 4.418 1.582l3.51-3.51C17.827 1.109 15.082 0 12 0 7.354 0 3.307 2.68 1.285 6.6L5.266 9.765z"/><path fill="#34A853" d="M16.04 14.545c-1.036.696-2.347 1.09-4.04 1.09a7.08 7.08 0 0 1-6.733-4.856L1.267 13.94C3.284 17.89 7.34 20.571 12 20.571c3.155 0 6.009-1.05 8.164-2.88l-4.124-3.146z"/><path fill="#4285F4" d="M23.51 12.273c0-.828-.076-1.625-.218-2.39H12v4.545h6.458a5.522 5.522 0 0 1-2.396 3.618l4.124 3.146c2.408-2.22 3.8-5.502 3.8-9.282z"/><path fill="#FBBC05" d="M5.266 10.81c-.135-.41-.212-.846-.212-1.31s.077-.9.212-1.31L1.285 4.22A11.968 11.968 0 0 0 0 9.5c0 1.9.444 3.704 1.233 5.318l4.033-3.008z"/></svg>
                </a>
                <a href="#" class="social-btn apple" onclick="alert('Login dengan Apple sedang dinonaktifkan')">
                    <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 4.17c.66-.81 1.11-1.93.99-3.06-.96.04-2.13.64-2.82 1.45-.6.69-1.12 1.83-1 2.94.97.08 2.06-.52 2.83-1.33z"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function switchTab(tab, event) {
        if (event) event.preventDefault();
        
        const tabLogin = document.getElementById('tab-login');
        const tabRegister = document.getElementById('tab-register');
        const formLogin = document.getElementById('form-login-container');
        const formRegister = document.getElementById('form-register-container');
        
        if (tab === 'login') {
            tabLogin.style.borderColor = 'var(--accent)';
            tabLogin.style.color = 'var(--text-primary)';
            tabRegister.style.borderColor = 'transparent';
            tabRegister.style.color = 'var(--text-secondary)';
            formLogin.style.display = 'block';
            formRegister.style.display = 'none';
        } else {
            tabRegister.style.borderColor = 'var(--accent)';
            tabRegister.style.color = 'var(--text-primary)';
            tabLogin.style.borderColor = 'transparent';
            tabLogin.style.color = 'var(--text-secondary)';
            formLogin.style.display = 'none';
            formRegister.style.display = 'block';
        }
    }

    // Toggle Passwords
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

    // Password Strength
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

    // Form validation
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

    // Auto-select tab based on action URL query parameter
    const action = '<?= sanitize($action) ?>';
    if (action === 'register') {
        switchTab('register');
    } else {
        switchTab('login');
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
