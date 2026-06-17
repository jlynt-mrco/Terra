<!-- 2. LOGIN SLIDING BOTTOM SHEET -->
<div id="loginSheet" class="auth-sheet">
    <!-- Banner header inside sheet -->
    <div class="auth-header-banner">
        <a href="#" class="auth-back-btn" onclick="closeSheets(event)">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Kembali
        </a>
        <div class="auth-banner-pattern">
            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                <circle cx="200" cy="0" r="40" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                <circle cx="200" cy="0" r="60" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                <circle cx="200" cy="0" r="80" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                <circle cx="200" cy="0" r="100" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                <circle cx="200" cy="0" r="120" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                <circle cx="200" cy="0" r="140" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                <circle cx="200" cy="0" r="160" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                <circle cx="200" cy="0" r="180" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                <circle cx="200" cy="0" r="200" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
            </svg>
        </div>
        <div class="auth-banner-content">
            <h1 class="auth-banner-title">Selamat Datang</h1>
            <p class="auth-banner-subtitle">Masuk ke akun TERRA Anda</p>
        </div>
    </div>

    <!-- Form Area inside sheet -->
    <div class="auth-content-sheet">
        <?php if ($error && $action !== 'register'): ?>
            <div class="alert alert-danger mb-md">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--danger);margin-right:6px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span><?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success mb-md">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--success);margin-right:6px;"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?= sanitize($success) ?></span>
            </div>
        <?php endif; ?>

        <form id="loginForm" action="<?= BASE_URL ?>/api/auth.php" method="POST">
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label class="form-label" for="login_email">Email</label>
                <div class="form-input-icon">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <input
                        type="email"
                        id="login_email"
                        name="email"
                        class="form-input"
                        placeholder="nama@email.com"
                        required
                        autocomplete="email"
                    >
                </div>
                <div class="form-error">Email tidak valid</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="login_password">Password</label>
                <div class="form-input-icon">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input
                        type="password"
                        id="login_password"
                        name="password"
                        class="form-input"
                        placeholder="Masukkan password"
                        required
                        minlength="6"
                        autocomplete="current-password"
                    >
                    <span class="icon icon-right" id="toggleLoginPassword" style="cursor:pointer;">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                </div>
                <div class="form-error">Password minimal 6 karakter</div>
            </div>

            <!-- Options -->
            <div class="auth-options">
                <label class="auth-checkbox">
                    <input type="checkbox" name="remember" id="remember">
                    <span class="checkbox-custom"></span>
                    <span>Ingat Saya</span>
                </label>
                <a href="#" class="auth-forgot-link" onclick="alert('Fitur Lupa Password sedang dinonaktifkan')">Lupa Password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg mt-lg" id="loginBtn">
                Masuk
            </button>
        </form>

        <!-- Social logins -->
        <div class="auth-social-group">
            <div class="auth-social-divider">
                <span>atau masuk dengan</span>
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

        <div class="auth-footer">
            Belum punya akun? <a href="#" onclick="switchToRegister(event)">Daftar Sekarang</a>
        </div>
    </div>
</div>
