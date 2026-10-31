<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Kode Tidak Ditemukan — {{ $settings['store_name'] ?? 'Toko' }}</title>
    <link rel="stylesheet" href="/assets/catalog/catalog.css">
</head>
<body>
    <div class="shell">
        <header class="top" id="top">
            <div class="brand">
                <span class="mark" id="brandMark">{{ mb_substr($settings['store_name'] ?? 'Toko', 0, 1) }}</span>
                <div class="brand-text">
                    <span id="brandTitle">{{ $settings['store_name'] ?? 'Toko' }}</span>
                    <small id="brandTagline"></small>
                </div>
            </div>
            <nav class="site-nav" aria-label="Navigasi utama">
                <a href="/">Menu</a>
                <a href="#trackForm">Cek Pesanan</a>
                <a href="#storeFooter">Kontak</a>
            </nav>
        </header>

        <main id="viewPublic">
            <section class="hero track-hero">
                <div>
                    <div class="eyebrow" id="heroEyebrow">Menu {{ $settings['store_name'] ?? '' }}</div>
                    <h1>Kode Tidak Ditemukan</h1>
                    <p>Kode pesanan yang Anda masukkan tidak terdaftar. Periksa kembali kode dan coba lagi.</p>
                </div>
            </section>

            <section id="trackForm" class="track-form">
                <form method="GET" action="/track" class="track-form-inner" id="trackLookupForm">
                    <input type="text" name="code" placeholder="Contoh: RT-261028-ABC12" required class="track-input" value="{{ $code ?? '' }}">
                    <button type="submit" class="hero-action-primary">Coba Lagi</button>
                </form>
                <p style="margin-top:12px;color:var(--muted);font-size:13px">Tidak menemukan kode? Cek pesan WhatsApp yang dikirim saat checkout.</p>
            </section>
        </main>

        <footer class="site-footer" id="storeFooter">
            <a class="footer-brand" href="#top" aria-label="Kembali ke atas">
                <span class="footer-mark" id="footerMark">{{ mb_substr($settings['store_name'] ?? 'Toko', 0, 1) }}</span>
                <span>
                    <strong id="footerStoreName">{{ $settings['store_name'] ?? 'Toko' }}</strong>
                    <small id="footerStoreTagline">{{ $settings['store_tagline'] ?? 'Menu dan pemesanan' }}</small>
                </span>
            </a>
            <div class="footer-links">
                <a href="/">Menu</a>
                <a href="#trackForm">Cek pesanan</a>
            </div>
            <p class="footer-copy">&copy; <span id="footerYear"></span> <span id="footerCopyrightName">{{ $settings['store_name'] ?? 'Toko' }}</span></p>
            <p class="footer-address" id="footerStoreAddress" style="display:none;"></p>
        </footer>
    </div>

    <script>
        (function() {
            document.getElementById('footerYear').textContent = new Date().getFullYear();
            const form = document.getElementById('trackLookupForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const input = form.querySelector('input[name="code"]');
                    const val = (input.value || '').trim();
                    if (!val) return;
                    window.location.href = '/track/' + encodeURIComponent(val);
                });
            }
        })();
    </script>
</body>
</html>
