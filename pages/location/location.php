<?php
/**
 * TERRA — Real-Time Geolocation Scanner Component
 * Gaya GoFood / GrabFood Location Indicator yang adaptif terhadap tema (terang/gelap)
 */
?>
<!-- Styles for Geolocation Widget -->
<style>
    .terra-location-widget {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 0;
        font-size: var(--font-xs, 11px);
        font-weight: 150;
        letter-spacing: 0.02em;
        cursor: pointer;
        transition: all var(--transition-base, 200ms ease);
        max-width: max-content;
        box-sizing: border-box;
        background: transparent !important;
        border: none !important;
        color: var(--text-secondary, #4B5563);
    }
    .terra-location-widget:hover {
        color: var(--text-primary, #111827);
        transform: translateY(-0.5px);
    }
    .terra-location-widget:active {
        transform: translateY(0);
    }

    /* Hero section (Dark Mode context override) */
    .hero .terra-location-widget {
        color: #FFFFFF !important;
    }
    .hero .terra-location-widget:hover {
        color: #FFFFFF !important;
        opacity: 0.85;
        transform: translateY(-0.5px);
    }
    .hero .terra-location-widget:active {
        transform: translateY(0);
    }

    /* Pulse radar scanner animation */
    .location-pulse-indicator {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        width: 14px;
        height: 14px;
    }
    .location-pin-icon {
        width: 12px;
        height: 12px;
        transition: color 0.3s ease;
    }

    /* Yellow pin on dark context */
    .hero .location-pin-icon.active {
        color: #FFEB3B;
    }
    /* Blue pin on light context */
    :not(.hero) .location-pin-icon.active {
        color: var(--info, #1D4ED8);
    }
    /* Error state: Red pin */
    .location-pin-icon.error {
        color: var(--danger, #DC2626) !important;
    }

    .location-radar-ping {
        position: absolute;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 1.5px solid currentColor;
        opacity: 0;
        transform: scale(0.5);
        pointer-events: none;
        display: none;
    }
    .location-scanning-ping .location-radar-ping {
        display: block;
        animation: location-widget-pulse 1.5s infinite ease-out;
    }

    @keyframes location-widget-pulse {
        0% {
            transform: scale(0.5);
            opacity: 1;
        }
        100% {
            transform: scale(2.2);
            opacity: 0;
        }
    }

    /* Refresh button styling */
    .location-refresh-btn {
        background: none;
        border: none;
        padding: 2px;
        color: currentColor;
        opacity: 0.65;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s ease;
        outline: none;
        margin-left: 2px;
    }
    .location-refresh-btn:hover {
        opacity: 1;
        background: rgba(255, 255, 255, 0.15);
    }
    :not(.hero) .location-refresh-btn:hover {
        background: rgba(0, 0, 0, 0.05);
    }
    .location-refresh-btn svg {
        width: 11px;
        height: 11px;
        transition: transform 0.25s ease;
    }
    .location-refresh-btn:hover svg {
        transform: scale(1.1);
    }

    .location-spinning svg {
        animation: location-widget-spin 1s infinite linear !important;
    }

    @keyframes location-widget-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div id="location-widget" class="terra-location-widget" style="display: none;" onclick="triggerLocationScan(true)">
    <div id="location-icon-wrapper" class="location-pulse-indicator">
        <svg id="location-svg-pin" class="location-pin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-12a9 9 0 0 1 18 0z"/>
            <circle cx="12" cy="10" r="3"/>
        </svg>
        <span id="location-scanner-ping" class="location-radar-ping"></span>
    </div>
    <span id="location-text" style="font-weight: 150; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px;">Memindai lokasi...</span>
    <button id="refresh-location" class="location-refresh-btn" onclick="event.stopPropagation(); triggerLocationScan(true);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
        </svg>
    </button>
</div>

<script>
{
    // Scoped execution inside block to prevent variable redeclaration crashes in PJAX loads
    const requestWidgetLocation = function(forceRefresh = false) {
        const widget = document.getElementById('location-widget');
        const pinSvg = document.getElementById('location-svg-pin');
        const pingSpan = document.getElementById('location-scanner-ping');
        const textSpan = document.getElementById('location-text');
        const refreshBtn = document.getElementById('refresh-location');
        const wrapper = document.getElementById('location-icon-wrapper');

        if (!widget) return;

        // Check cached location first
        const cachedLocation = localStorage.getItem('terra_user_location');
        const cachedLat = localStorage.getItem('terra_user_lat');
        const cachedLon = localStorage.getItem('terra_user_lon');
        if (cachedLocation && cachedLat && cachedLon && !forceRefresh) {
            textSpan.innerText = cachedLocation;
            pinSvg.classList.add('active');
            pinSvg.classList.remove('error');
            wrapper.classList.remove('location-scanning-ping');
            if (typeof window.onLocationUpdated === 'function') {
                window.onLocationUpdated(parseFloat(cachedLat), parseFloat(cachedLon), cachedLocation);
            }
            return;
        }

        if (!navigator.geolocation) {
            textSpan.innerText = 'GPS tidak didukung';
            pinSvg.classList.add('error');
            pinSvg.classList.remove('active');
            return;
        }

        // Start scanning animation
        textSpan.innerText = 'Memindai lokasi...';
        pinSvg.classList.add('active');
        pinSvg.classList.remove('error');
        wrapper.classList.add('location-scanning-ping');
        if (refreshBtn) {
            refreshBtn.classList.add('location-spinning');
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;

                // Call OpenStreetMap Nominatim reverse geocoding API
                fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Geocoding response error');
                        return response.json();
                    })
                    .then(data => {
                        const addr = data.address || {};
                        let resolvedLocation = '';

                        // Extract descriptive local components
                        const localArea = addr.village || addr.suburb || addr.neighbourhood || addr.quarter || addr.subdistrict || '';
                        const regionalArea = addr.city || addr.town || addr.municipality || addr.city_district || addr.county || addr.state || '';

                        if (localArea && regionalArea) {
                            resolvedLocation = `${localArea}, ${regionalArea}`;
                        } else if (regionalArea) {
                            resolvedLocation = regionalArea;
                        } else if (addr.road) {
                            resolvedLocation = addr.road;
                        } else {
                            resolvedLocation = data.name || `${lat.toFixed(4)}, ${lon.toFixed(4)}`;
                        }

                        // Update UI & Cache
                        textSpan.innerText = resolvedLocation;
                        localStorage.setItem('terra_user_location', resolvedLocation);
                        localStorage.setItem('terra_user_lat', lat);
                        localStorage.setItem('terra_user_lon', lon);
                        pinSvg.classList.add('active');
                        pinSvg.classList.remove('error');

                        if (typeof window.onLocationUpdated === 'function') {
                            window.onLocationUpdated(lat, lon, resolvedLocation);
                        }
                    })
                    .catch(err => {
                        console.error('Reverse Geocoding error:', err);
                        // Fallback to coordinates if geocoding fails
                        const coordsText = `${lat.toFixed(4)}, ${lon.toFixed(4)}`;
                        textSpan.innerText = coordsText;
                        localStorage.setItem('terra_user_location', coordsText);
                        localStorage.setItem('terra_user_lat', lat);
                        localStorage.setItem('terra_user_lon', lon);
                        pinSvg.classList.add('active');
                        pinSvg.classList.remove('error');

                        if (typeof window.onLocationUpdated === 'function') {
                            window.onLocationUpdated(lat, lon, coordsText);
                        }
                    })
                    .finally(() => {
                        // Stop animations
                        wrapper.classList.remove('location-scanning-ping');
                        if (refreshBtn) {
                            refreshBtn.classList.remove('location-spinning');
                        }
                    });
            },
            function(error) {
                console.error('Geolocation error:', error);
                let message = 'Lokasi tidak terdeteksi';
                let hasError = true;

                if (error.code === error.PERMISSION_DENIED) {
                    message = 'Akses lokasi ditolak';
                }

                // Use cached location as fallback if geocoding fails
                const fallback = localStorage.getItem('terra_user_location');
                const fallbackLat = localStorage.getItem('terra_user_lat');
                const fallbackLon = localStorage.getItem('terra_user_lon');

                if (fallback && fallbackLat && fallbackLon) {
                    textSpan.innerText = fallback;
                    pinSvg.classList.add('active');
                    pinSvg.classList.remove('error');
                    if (typeof window.onLocationUpdated === 'function') {
                        window.onLocationUpdated(parseFloat(fallbackLat), parseFloat(fallbackLon), fallback);
                    }
                } else {
                    textSpan.innerText = message;
                    if (error.code === error.PERMISSION_DENIED) {
                        pinSvg.classList.add('error');
                        pinSvg.classList.remove('active');
                    } else {
                        pinSvg.classList.remove('active', 'error');
                    }
                }

                // Stop animations
                wrapper.classList.remove('location-scanning-ping');
                if (refreshBtn) {
                    refreshBtn.classList.remove('location-spinning');
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 8000,
                maximumAge: 60000
            }
        );
    };

    // Expose to global scope under a unique namespace if needed, or bind to window
    window.triggerLocationScan = requestWidgetLocation;

    // Auto run on load
    const widget = document.getElementById('location-widget');
    if (widget) {
        widget.style.display = 'inline-flex';
        requestWidgetLocation(false);
    }
}
</script>
