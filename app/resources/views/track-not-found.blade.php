<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Tidak Ditemukan — {{ $settings['store_name'] ?? 'Toko' }}</title>
    <link rel="stylesheet" href="assets/catalog/catalog.css">
</head>
<body>
    <div class="shell">
        <header class="top" style="margin: 0 -20px; padding: 0 20px;">
            <div class="brand">
                <div class="mark" id="brandMark">R</div>
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

        <main id="viewPublic" style="padding-top: 24px;">
            <section class="hero" style="border-bottom: 1px solid var(--line);">
                <div>
                    <div class="eyebrow">Menu {{ $settings['store_name'] ?? '' }}</div>
                    <h1>Kode Tidak Ditemukan</h1>
                    <p>Kode pesanan yang Anda masukkan tidak terdaftar. Periksa kembali kode dan coba lagi.</p>
                </div>
            </section>

            <section id="trackForm" style="padding: 32px 0; max-width: 520px; margin: auto;">
                <form method="GET" action="/track" class="track-retry-form">
                    <input type="text" name="code" placeholder="Contoh: RT-261028-ABC12" required
                           style="flex: 1; min-width: 200px; border: 1px solid var(--line); background: #fff; padding: 12px 14px; border-radius: 10px; font-size: 14px; outline: none;">
                    <button type="submit" class="hero-action-primary" style="border: 0; cursor: pointer;">Coba Lagi</button>
                </form>
                <p style="margin-top: 12px; color: var(--muted); font-size: 13px;">
                    Tidak menemukan kode? Cek pesan WhatsApp yang dikirim saat checkout.
                </p>
            </section>
        </main>

        <footer class="site-footer" id="storeFooter" style="margin-top: 40px;">
            <div class="footer-brand">
                <div class="footer-mark" id="footerMark">R</div>
                <span>
                    <strong id="footerStoreName">{{ $settings['store_name'] ?? 'Toko' }}</strong>
                    <small id="footerCopyrightName"></small>
                </span>
            </div>
            <div class="footer-links">
                <button type="button" onclick="window.scrollTo({top:0,behavior:'smooth'})">Kembali ke atas</button>
            </div>
        </footer>
    </div>

    <script>
        (function() {
            const url = new URL(window.location.href);
            const code = url.searchParams.get('code');
            if (code) {
                const input = document.querySelector('input[name="code"]');
                if (input) input.value = code;
            }
        })();
    </script>
</body>
</html>
