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
                <linearGradient id="mountGradRear" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="var(--bg-tertiary)" stop-opacity="0.6"/>
                    <stop offset="100%" stop-color="var(--border-color)" stop-opacity="1"/>
                </linearGradient>
                <linearGradient id="mountGradFront" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="var(--bg-secondary)" stop-opacity="0.9"/>
                    <stop offset="100%" stop-color="var(--bg-tertiary)" stop-opacity="1"/>
                </linearGradient>
            </defs>
            <!-- Rising Sun (Minimalist outlined compass/sun element) -->
            <circle cx="100" cy="70" r="28" fill="none" stroke="var(--border-color)" stroke-width="1" stroke-dasharray="4,4"/>
            <circle cx="100" cy="70" r="12" fill="none" stroke="var(--border-color)" stroke-width="0.75"/>
            <!-- Rear mountain -->
            <polygon points="40,120 95,55 150,120" fill="url(#mountGradRear)" stroke="var(--border-color)" stroke-width="1.5" />
            <!-- Front mountains -->
            <polygon points="10,120 70,72 130,120" fill="url(#mountGradFront)" stroke="var(--accent)" stroke-width="1.5" style="transform: translateX(20px);" />
            <polygon points="80,120 140,65 200,120" fill="url(#mountGradFront)" stroke="var(--accent)" stroke-width="1.5" style="transform: translateX(-25px);" />
            <!-- Ground line -->
            <path d="M 0 120 Q 50 116 100 120 T 200 120 L 200 130 L 0 130 Z" fill="var(--accent)" />
        </svg>
    </div>

    <!-- Welcome Text -->
    <div class="welcome-text-container">
        <h1 class="welcome-title">Welcome</h1>
        <p class="welcome-subtitle">Mulai petualangan mendaki gunung Anda bersama TERRA. Masuk atau daftarkan akun baru Anda.</p>
    </div>

    <!-- Onboarding Buttons -->
    <div class="welcome-buttons-container">
        <button class="btn btn-welcome-create" onclick="openRegisterSheet()">Daftar Akun</button>
        <button class="btn btn-welcome-login" onclick="openLoginSheet()">Masuk</button>
    </div>
</div>
