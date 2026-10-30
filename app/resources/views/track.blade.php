<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Pesanan — {{ $settings['store_name'] ?? 'Toko' }}</title>
    <link rel="stylesheet" href="assets/catalog/catalog.css">
</head>
<body>
    <div class="shell">
        <header class="top">
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

        <main id="viewPublic" class="track-main">
            <section class="hero track-hero">
                <div>
                    <div class="eyebrow">Menu {{ $settings['store_name'] ?? '' }}</div>
                    <h1>Lacak Pesanan</h1>
                    <p>Masukkan kode pesanan untuk melihat status dan rincian.</p>
                </div>
            </section>

            <section id="trackForm" class="track-form">
                <form method="GET" action="/track" class="track-form-inner">
                    <input type="text" name="code" placeholder="Contoh: RT-261028-ABC12" required class="track-input">
                    <button type="submit" class="hero-action-primary">Lacak</button>
                </form>

                @if ($code)
                    <div class="track-card">
                        <div class="track-card-head">
                            <span class="track-code">#{{ $code }}</span>
                            <span class="history-badge history-badge-{{ $status }}">{{ $status_label }}</span>
                        </div>

                        <div class="track-label">Nama</div>
                        <div class="track-value">{{ $name }}</div>

                        <div class="track-label">Layanan</div>
                        <div class="track-value">{{ $service }}</div>

                        <div class="track-label">Rincian</div>
                        <ul class="track-items">
                            @foreach ($items as $item)
                                <li class="track-item">
                                    <span>{{ $item['quantity'] }}x {{ $item['name'] }}</span>
                                    <span class="track-item-price">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="track-row track-row-divider">
                            <span>Subtotal</span>
                            <span class="track-item-price">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if ($service_fee > 0)
                            <div class="track-row">
                                <span>Ongkir antar</span>
                                <span class="track-item-price">Rp {{ number_format($service_fee, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="track-total">
                            <span>Total</span>
                            <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>

                        <div class="track-meta">
                            Dipesan {{ \Carbon\Carbon::parse($created_at)->format('d M Y H:i') }} · Diperbarui {{ \Carbon\Carbon::parse($updated_at)->format('d M Y H:i') }}
                        </div>
                    </div>
                @endif
            </section>
        </main>

        <footer class="site-footer track-footer" id="storeFooter">
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
