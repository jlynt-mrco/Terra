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
            
            <!-- 1. LANDING WELCOME SCREEN -->
            <div class="auth-welcome-screen">
                <!-- Brand logo & title -->
                <div class="welcome-logo-container">
                    <img src="<?= BASE_URL ?>/logo/logo.png" alt="TERRA Logo" class="welcome-logo-img">
                    <div class="welcome-brand-name">TERRA</div>
                </div>

                <!-- Mountain SVG Illustration (Nature & Mountaineering theme) -->
                <div class="welcome-illustration-container">
                    <svg class="welcome-illustration" viewBox="0 0 200 150" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="mountGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#384252" stop-opacity="0.4"/>
                                <stop offset="100%" stop-color="#0f172a" stop-opacity="0.9"/>
                            </linearGradient>
                            <linearGradient id="sunGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#F97316" stop-opacity="0.85"/>
                                <stop offset="100%" stop-color="#EF4444" stop-opacity="0.3"/>
                            </linearGradient>
                        </defs>
                        <!-- Rising Sun -->
                        <circle cx="100" cy="70" r="24" fill="url(#sunGrad)"/>
                        <!-- Rear mountain -->
                        <polygon points="40,120 95,55 150,120" fill="url(#mountGrad)" />
                        <!-- Front mountains -->
                        <polygon points="10,120 70,72 130,120" fill="url(#mountGrad)" style="transform: translateX(20px); opacity: 0.95;" />
                        <polygon points="80,120 140,65 200,120" fill="url(#mountGrad)" style="transform: translateX(-25px); opacity: 0.85;" />
                        <!-- Ground line -->
                        <path d="M 0 120 Q 50 116 100 120 T 200 120 L 200 130 L 0 130 Z" fill="#0f172a" opacity="0.95"/>
                        
                        <!-- Playful/Abstract shapes like mockup -->
                        <circle cx="140" cy="45" r="6" fill="#10B981" opacity="0.8"/> <!-- green circle -->
                        <polygon points="45,45 47,38 49,45 56,47 49,49 47,56 45,49 38,47" fill="#FFFFFF" opacity="0.7" /> <!-- star -->
                    </svg>
                </div>

                <!-- Welcome Text -->
                <div class="welcome-text-container">
                    <h1 class="welcome-title">Welcome =)</h1>
                    <p class="welcome-subtitle">Mulai petualangan mendaki gunung Anda bersama TERRA. Masuk atau daftarkan akun baru Anda.</p>
                </div>

                <!-- Onboarding Buttons -->
                <div class="welcome-buttons-container">
                    <button class="btn-welcome-create" onclick="openRegisterSheet()">Create Account</button>
                    <button class="btn-welcome-login" onclick="openLoginSheet()">Log In</button>
                </div>
            </div>

            <!-- 2. LOGIN SLIDING BOTTOM SHEET -->
            <div id="loginSheet" class="auth-sheet">
                <!-- Transparent top header area matching mockup -->
                <div class="auth-sheet-header-gradient">
                    <a href="#" class="auth-back-btn" onclick="closeSheets(event)">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Back
                    </a>
                    <h1 class="welcome-title">Welcome Back</h1>
                    <p class="welcome-subtitle">Ready to continue your mountaineering journey?<br>Your path is right here.</p>
                </div>

                <!-- Curved White Sheet for form details -->
                <div class="auth-content-sheet-curved">
                    <?php if ($error && $action !== 'register'): ?>
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

                    <form id="loginForm" action="<?= BASE_URL ?>/api/auth.php" method="POST">
                        <input type="hidden" name="action" value="login">

                        <div class="form-group">
                            <label class="form-label" for="login_email">Enter email</label>
                            <input type="email" id="login_email" name="email" class="form-input" placeholder="nama@email.com" required autocomplete="email">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="login_password">Password</label>
                            <div class="form-input-icon">
                                <input type="password" id="login_password" name="password" class="form-input" placeholder="••••••••" required minlength="6" autocomplete="current-password">
                                <span class="icon icon-right" id="toggleLoginPassword" style="cursor:pointer;">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </span>
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="auth-options">
                            <label class="auth-checkbox">
                                <input type="checkbox" name="remember" id="remember">
                                <span class="checkbox-custom"></span>
                                <span>Remember me</span>
                            </label>
                            <a href="#" class="auth-forgot-link" onclick="alert('Fitur Lupa Password sedang dinonaktifkan')">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn-gradient" id="loginBtn">
                            Log In
                        </button>
                    </form>

                    <!-- Social logins -->
                    <div class="auth-social-group">
                        <div class="auth-social-divider">
                            <span>Sign in with</span>
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
                        Don't have an account? <a href="#" onclick="switchToRegister(event)">Sign Up</a>
                    </div>
                </div>
            </div>

            <!-- 3. REGISTER SLIDING BOTTOM SHEET -->
            <div id="registerSheet" class="auth-sheet">
                <!-- Transparent top header area matching mockup -->
                <div class="auth-sheet-header-gradient">
                    <a href="#" class="auth-back-btn" onclick="closeSheets(event)">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Back
                    </a>
                    <h1 class="welcome-title">Create Your Account</h1>
                    <p class="welcome-subtitle">We're here to help you start your climb.<br>Are you ready?</p>
                </div>

                <!-- Curved White Sheet for form details -->
                <div class="auth-content-sheet-curved">
                    <?php if ($error && $action === 'register'): ?>
                        <div class="alert alert-danger">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <span><?= sanitize($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="registerForm" action="<?= BASE_URL ?>/api/auth.php" method="POST">
                        <input type="hidden" name="action" value="register">

                        <div class="form-group">
                            <label class="form-label" for="reg_name">Enter full name</label>
                            <input type="text" id="reg_name" name="name" class="form-input" placeholder="Nama Lengkap" required autocomplete="name">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="reg_email">Enter email</label>
                            <input type="email" id="reg_email" name="email" class="form-input" placeholder="nama@email.com" required autocomplete="email">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="reg_phone">Enter phone number</label>
                            <input type="tel" id="reg_phone" name="phone" class="form-input" placeholder="08xxxxxxxxxx" required pattern="[0-9]{10,13}" autocomplete="tel">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="reg_password">Enter password</label>
                            <div class="form-input-icon">
                                <input type="password" id="reg_password" name="password" class="form-input" placeholder="Minimal 6 karakter" required minlength="6" autocomplete="new-password">
                                <span class="icon icon-right" id="toggleRegPassword" style="cursor:pointer;">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </span>
                            </div>
                            
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
                            <label class="form-label" for="reg_password_confirm">Confirm password</label>
                            <input type="password" id="reg_password_confirm" name="password_confirm" class="form-input" placeholder="Ulangi password" required minlength="6" autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn-gradient" id="registerBtn">
                            Get Started
                        </button>
                    </form>

                    <!-- Social logins -->
                    <div class="auth-social-group">
                        <div class="auth-social-divider">
                            <span>Sign up with</span>
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
                        Already have an account? <a href="#" onclick="switchToLogin(event)">Log In</a>
                    </div>
                </div>
            </div>

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
