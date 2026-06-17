/**
 * TERRA — Premium Dynamic PJAX Loader
 * Menangani transisi halaman tanpa kedipan (blink) dengan memuat konten secara AJAX (Fetch API)
 */

(function () {
    // Jalankan hanya jika browser mendukung pushState dan fetch
    if (!window.history || !window.history.pushState || !window.fetch) {
        return;
    }

    // Capture DOMContentLoaded listeners registered during dynamic script execution
    let isCollectingListeners = false;
    const capturedListeners = [];
    const originalAddEventListener = document.addEventListener;

    document.addEventListener = function (type, listener, options) {
        if (type === 'DOMContentLoaded' && isCollectingListeners) {
            capturedListeners.push(listener);
        }
        return originalAddEventListener.apply(this, arguments);
    };

    // DOM Elements
    let mainContent = document.getElementById('main-content');
    const loadingBar = document.getElementById('pjax-loading-bar');

    if (!mainContent || !loadingBar) {
        console.warn('TERRA PJAX: Main content wrapper or loading bar not found.');
        return;
    }

    // Loading Bar Controller
    let progressTimer = null;
    let progressWidth = 0;

    function startLoading() {
        if (progressTimer) clearInterval(progressTimer);
        
        progressWidth = 5;
        loadingBar.style.width = progressWidth + '%';
        loadingBar.style.opacity = '1';
        
        // Animasi bar maju secara gradual
        progressTimer = setInterval(() => {
            if (progressWidth < 85) {
                progressWidth += Math.random() * 5 + 1;
                loadingBar.style.width = progressWidth + '%';
            }
        }, 150);

        // Tambahkan efek memudar pada area konten lama
        mainContent.classList.add('is-loading');
    }

    function stopLoading() {
        if (progressTimer) clearInterval(progressTimer);
        
        loadingBar.style.width = '100%';
        setTimeout(() => {
            loadingBar.style.opacity = '0';
            setTimeout(() => {
                loadingBar.style.width = '0%';
            }, 300);
        }, 200);

        // Hapus efek memudar dan picu animasi masuk pada konten baru
        mainContent.classList.remove('is-loading');
    }

    // Mengambil dan menggabungkan aset tambahan di tag head (link, script, style)
    // Mengembalikan Promise yang akan resolve jika semua script eksternal selesai dimuat
    function mergeHead(newDoc) {
        const newHeadElements = newDoc.querySelectorAll('head > link, head > script, head > style');
        const currentHead = document.head;
        const loadPromises = [];

        newHeadElements.forEach(newEl => {
            const tagName = newEl.tagName.toUpperCase();
            const isLink = tagName === 'LINK';
            const isStyle = tagName === 'STYLE';
            const srcAttr = newEl.getAttribute('src');
            const hrefAttr = newEl.getAttribute('href');

            // Cek apakah asset sudah ada
            let isAlreadyLoaded = false;
            if (isLink && hrefAttr) {
                isAlreadyLoaded = !!currentHead.querySelector(`link[href="${hrefAttr}"]`);
            } else if (isStyle) {
                // Bandingkan isi text css-nya agar style tag tidak terduplikasi
                isAlreadyLoaded = Array.from(currentHead.querySelectorAll('style')).some(s => s.innerHTML === newEl.innerHTML);
            } else if (!isLink && !isStyle && srcAttr) {
                isAlreadyLoaded = !!currentHead.querySelector(`script[src="${srcAttr}"]`);
            }

            // Jika belum ada di head, tambahkan
            if (!isAlreadyLoaded) {
                const cloned = document.createElement(newEl.tagName);
                Array.from(newEl.attributes).forEach(attr => cloned.setAttribute(attr.name, attr.value));
                if (newEl.innerHTML) {
                    cloned.innerHTML = newEl.innerHTML;
                }

                if (!isLink && !isStyle && srcAttr) {
                    // Jika ini script eksternal, catat progres pemuatannya
                    const p = new Promise(resolve => {
                        cloned.onload = () => resolve();
                        cloned.onerror = () => {
                            console.error('TERRA Head Loader: Gagal memuat script:', srcAttr);
                            resolve(); // resolve anyway agar navigasi tidak macet
                        };
                    });
                    loadPromises.push(p);
                }

                currentHead.appendChild(cloned);
            }
        });

        return Promise.all(loadPromises);
    }

    // Menjalankan kembali tag script agar fitur JS halaman bekerja (secara sekuensial)
    function executeScripts(container) {
        const scripts = Array.from(container.querySelectorAll('script'));
        
        isCollectingListeners = true;
        capturedListeners.length = 0; // Bersihkan listener lama

        // Jalankan script satu per satu secara berurutan menggunakan Promise chain
        function runScript(oldScript) {
            return new Promise(resolve => {
                if (oldScript.src) {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    
                    newScript.onload = () => resolve();
                    newScript.onerror = () => {
                        console.error('TERRA PJAX: Gagal memuat script:', oldScript.src);
                        resolve();
                    };
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                } else {
                    // Untuk inline script, jalankan menggunakan eval di dalam block scope
                    // agar deklarasi variabel const/let tidak bentrok (SyntaxError) pada reload halaman.
                    try {
                        const code = oldScript.innerHTML;
                        (0, eval)('{\n' + code + '\n}');
                    } catch (err) {
                        console.error('TERRA PJAX: Error mengevaluasi inline script:', err);
                    }
                    resolve();
                }
            });
        }

        // Jalankan sekuensial
        scripts.reduce((promiseChain, script) => {
            return promiseChain.then(() => runScript(script));
        }, Promise.resolve()).then(() => {
            isCollectingListeners = false;
            
            // Panggil event listeners DOMContentLoaded yang tertangkap secara manual
            capturedListeners.forEach(listener => {
                try {
                    listener();
                } catch (err) {
                    console.error('TERRA PJAX: Error in captured DOMContentLoaded listener:', err);
                }
            });
        });
    }

    // Memperbarui navigasi bawah aktif berdasarkan url saat ini
    function updateActiveNav(url) {
        const navItems = document.querySelectorAll('.bottom-nav-item');
        navItems.forEach(item => {
            item.classList.remove('active');
            const href = item.getAttribute('href');
            if (href) {
                const filename = href.split('/').pop();
                if (url.includes(filename)) {
                    item.classList.add('active');
                }
            }
        });
    }

    // Fungsi utama memuat halaman secara AJAX
    function loadPage(url, pushToHistory = true) {
        startLoading();

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal memuat halaman: ' + response.statusText);
                }
                return response.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(html, 'text/html');
                const newMainContent = newDoc.getElementById('main-content');

                if (!newMainContent) {
                    window.location.href = url;
                    return;
                }

                document.title = newDoc.title || 'TERRA';

                // Gabungkan head assets (CSS Leaflet, tag style custom, dsb) & tunggu jika ada script eksternal baru
                mergeHead(newDoc).then(() => {
                    // Ganti konten setelah asset head siap
                    mainContent.innerHTML = newMainContent.innerHTML;

                    if (pushToHistory) {
                        window.history.pushState({ pjaxUrl: url }, '', url);
                    }

                    updateActiveNav(url);
                    window.scrollTo({ top: 0, behavior: 'instant' });

                    // Jalankan Javascript halaman baru
                    executeScripts(mainContent);

                    stopLoading();
                });
            })
            .catch(error => {
                console.error('TERRA PJAX Error:', error);
                stopLoading();
                window.location.href = url;
            });
    }

    // Intersepsi klik pada seluruh dokumen
    document.body.addEventListener('click', function (e) {
        const anchor = e.target.closest('a');

        if (!anchor) return;

        const url = anchor.href;

        if (
            !url ||
            !url.startsWith(window.location.origin) ||
            anchor.getAttribute('target') === '_blank' ||
            anchor.hasAttribute('download') ||
            anchor.hasAttribute('data-no-pjax') ||
            anchor.getAttribute('href').startsWith('#') ||
            url.includes('/api/') || 
            url.includes('logout')    
        ) {
            return;
        }

        e.preventDefault();
        loadPage(url);
    });

    // Tangani navigasi tombol back/forward di browser
    window.addEventListener('popstate', function (e) {
        loadPage(window.location.href, false);
    });

    window.history.replaceState({ pjaxUrl: window.location.href }, '', window.location.href);
})();
