<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Pesanan — {{ $settings['store_name'] ?? 'Toko' }}</title>
    <link rel="stylesheet" href="/assets/catalog/catalog.css">
</head>
<body>
    <div class="shell">
        <header class="top" id="top">
            <div class="brand">
                <span class="mark" id="brandMark">R</span>
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
                    <h1>Lacak Pesanan</h1>
                    <p>Masukkan kode pesanan untuk melihat status dan rincian.</p>
                </div>
            </section>

            <section id="trackForm" class="track-form">
                <form method="GET" action="/track" class="track-form-inner" id="trackLookupForm">
                    <input type="text" name="code" placeholder="Contoh: RT-261028-ABC12" required class="track-input" value="{{ $code ?? '' }}">
                    <button type="submit" class="hero-action-primary">Lacak</button>
                </form>

                @if ($code)
                    <div class="track-card">
                        <div class="track-card-head">
                            <span class="track-code">#{{ $code }}</span>
                            <span class="history-badge history-badge-{{ $status }}">{{ $status_label }}</span>
                        </div>

                        <div class="track-grid">
                            <div>
                                <div class="track-label">Nama</div>
                                <div class="track-value">{{ $name }}</div>

                                <div class="track-label">Layanan</div>
                                <div class="track-value">{{ $service }}</div>
                            </div>
                            <div>
                                <div class="track-label">Total</div>
                                <div class="track-total">Rp {{ number_format($total, 0, ',', '.') }}</div>
                            </div>
                        </div>

                        <div class="track-label" style="margin-top:16px">Rincian</div>
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

        <footer class="site-footer" id="storeFooter">
            <a class="footer-brand" href="#top" aria-label="Kembali ke atas">
                <span class="footer-mark" id="footerMark">R</span>
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
            const url = new URL(window.location.href);
            const code = url.searchParams.get('code');
            if (code) {
                const input = document.querySelector('input[name="code"]');
                if (input) input.value = code;
            }
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
