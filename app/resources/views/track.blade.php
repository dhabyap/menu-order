<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    @php
        $storeName = $settings['store_name'] ?? 'Toko';
        $storeInitial = mb_substr($storeName, 0, 1);
        $whatsapp = preg_replace('/\D/', '', $settings['whatsapp'] ?? '');
        $flow = \App\Support\OrderStatus::flow();
        $currentStatus = $status ?? null;
        $currentIndex = $currentStatus ? array_search($currentStatus, $flow, true) : false;
        $isCancelled = $currentStatus === \App\Support\OrderStatus::CANCELLED;
        $stepCopy = [
            \App\Support\OrderStatus::PENDING => 'Pesanan sudah masuk ke toko.',
            \App\Support\OrderStatus::PROCESSING => 'Pesanan sedang disiapkan.',
            \App\Support\OrderStatus::COMPLETED => 'Pesanan sudah selesai.',
        ];
    @endphp
    <title>{{ $code ? 'Lacak '.$code : 'Lacak Pesanan' }} — {{ $storeName }}</title>
    <link rel="stylesheet" href="/assets/catalog/catalog.css">
</head>
<body>
    <div class="shell">
        <header class="top" id="top">
            <div class="brand">
                <span class="mark" id="brandMark">{{ $storeInitial }}</span>
                <div class="brand-text">
                    <span id="brandTitle">{{ $storeName }}</span>
                    <small id="brandTagline">{{ $settings['store_tagline'] ?? '' }}</small>
                </div>
            </div>
            <nav class="site-nav" aria-label="Navigasi utama">
                <a href="/">Menu</a>
                <a href="/track">Cek Pesanan</a>
                <a href="/#storeFooter">Kontak</a>
            </nav>
        </header>

        <main id="viewPublic">
            <section class="hero track-hero">
                <div>
                    <div class="eyebrow" id="heroEyebrow">Menu {{ $storeName }}</div>
                    <h1>Lacak Pesanan</h1>
                    <p>Masukkan kode pesanan untuk melihat status dan rincian.</p>
                </div>
            </section>

            <section id="trackForm" class="track-form">
                <form method="GET" action="/track" class="track-form-inner" id="trackLookupForm">
                    <input type="text" name="code" placeholder="Contoh: RT-261028-ABC12" required class="track-input" value="{{ $code ?? '' }}" aria-label="Kode pesanan">
                    <button type="submit" class="hero-action-primary">Lacak</button>
                </form>

                @if ($code)
                    <div class="track-card">
                        <div class="track-card-head">
                            <div>
                                <span class="track-code">#{{ $code }}</span>
                                <div class="track-meta-inline">Dipesan {{ \Carbon\Carbon::parse($created_at)->format('d M Y H:i') }}</div>
                            </div>
                            <span class="history-badge history-badge-{{ $status }}">{{ $status_label }}</span>
                        </div>

                        @if ($isCancelled)
                            <div class="track-cancelled" role="status">
                                <strong>Pesanan dibatalkan</strong>
                                <p>Hubungi kedai jika kamu memerlukan bantuan atau ingin membuat pesanan baru.</p>
                            </div>
                        @else
                            <ol class="track-timeline" aria-label="Progres pesanan">
                                @foreach ($flow as $index => $step)
                                    <li class="track-step {{ $currentIndex !== false && $index <= $currentIndex ? 'is-done' : '' }} {{ $currentIndex !== false && $index === $currentIndex ? 'is-current' : '' }}">
                                        <span class="track-step-dot" aria-hidden="true">{{ $index + 1 }}</span>
                                        <div>
                                            <strong>{{ \App\Support\OrderStatus::label($step) }}</strong>
                                            <small>{{ $stepCopy[$step] ?? '' }}</small>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif

                        <div class="track-grid">
                            <div>
                                <div class="track-label">Nama</div>
                                <div class="track-value">{{ $name }}</div>

                                <div class="track-label">Layanan</div>
                                <div class="track-value">
                                    {{ $service }}
                                    @if ($service === 'Antar' && $delivery_distance_meters)
                                        <span class="track-muted">· ±{{ number_format($delivery_distance_meters / 1000, 1, ',', '.') }} km</span>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <div class="track-label">Total</div>
                                <div class="track-total">Rp {{ number_format($total, 0, ',', '.') }}</div>
                            </div>
                        </div>

                        @if ($service === 'Antar' && $address)
                            <div class="track-label">Alamat pengantaran</div>
                            <div class="track-value track-address">{{ $address }}</div>
                        @endif

                        @if ($note)
                            <div class="track-label">Catatan pesanan</div>
                            <div class="track-note">{{ $note }}</div>
                        @endif

                        <div class="track-label" style="margin-top:16px">Rincian menu</div>
                        <ul class="track-items">
                            @forelse ($items as $item)
                                <li class="track-item">
                                    <div class="track-item-main">
                                        <span>{{ $item['quantity'] }}x {{ $item['name'] }}</span>
                                        <span class="track-item-price">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                                    </div>
                                    @if (!empty($item['options']))
                                        <div class="track-item-meta">
                                            {{ collect($item['options'])->map(fn ($value, $key) => $key.': '.$value)->implode(' · ') }}
                                        </div>
                                    @endif
                                    @if (!empty($item['note']))
                                        <div class="track-item-meta">Catatan: {{ $item['note'] }}</div>
                                    @endif
                                </li>
                            @empty
                                <li class="track-item"><span>Rincian menu tidak tersedia.</span></li>
                            @endforelse
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
                            Terakhir diperbarui {{ \Carbon\Carbon::parse($updated_at)->format('d M Y H:i') }}
                        </div>

                        <div class="track-actions">
                            <a class="hero-action-primary" href="/#productGrid">Pesan lagi</a>
                            @if ($whatsapp)
                                <a class="btn-sec-action" href="https://wa.me/{{ $whatsapp }}?text={{ urlencode('Halo '.$storeName.', saya ingin menanyakan pesanan #'.$code.'.') }}" target="_blank" rel="noopener noreferrer">Hubungi kedai</a>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="track-help">Kode pesanan ada di pesan WhatsApp yang dikirim setelah checkout. Belum punya kode? Cari pesanan lewat nomor WhatsApp di halaman menu.</p>
                @endif
            </section>
        </main>

        <footer class="site-footer" id="storeFooter">
            <a class="footer-brand" href="#top" aria-label="Kembali ke atas">
                <span class="footer-mark" id="footerMark">{{ $storeInitial }}</span>
                <span>
                    <strong id="footerStoreName">{{ $storeName }}</strong>
                    <small id="footerStoreTagline">{{ $settings['store_tagline'] ?? 'Menu dan pemesanan' }}</small>
                </span>
            </a>
            <div class="footer-links">
                <a href="/">Menu</a>
                <a href="/track">Cek pesanan</a>
            </div>
            <p class="footer-copy">&copy; <span id="footerYear"></span> <span id="footerCopyrightName">{{ $storeName }}</span></p>
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
