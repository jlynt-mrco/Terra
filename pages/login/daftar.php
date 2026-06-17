<!-- 3. REGISTER SLIDING BOTTOM SHEET -->
<div id="registerSheet" class="auth-sheet">
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
            <h1 class="auth-banner-title">Daftar Akun</h1>
            <p class="auth-banner-subtitle">Buat akun mendaki gunung Anda</p>
        </div>
    </div>

    <!-- Form Area inside sheet -->
    <div class="auth-content-sheet">
        <?php if ($error && $action === 'register'): ?>
            <div class="alert alert-danger mb-md">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--danger);margin-right:6px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span><?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>

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

            <button type="submit" class="btn btn-primary btn-block btn-lg mt-lg" id="registerBtn">
                Daftar
            </button>
        </form>

        <div class="auth-footer">
            Sudah punya akun? <a href="#" onclick="switchToLogin(event)">Masuk</a>
        </div>
    </div>
</div>
