<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Pesanan — {{ config('app.name', 'Racik Tea') }}</title>
    <link rel="stylesheet" href="assets/catalog/catalog.css">
</head>
<body>
    <div class="shell">
        <header class="top" style="margin: 0 -20px; padding: 0 20px;">
            <div class="brand">
                <div class="mark" id="brandMark">R</div>
                <div class="brand-text">
                    <span id="brandTitle">Racik Tea</span>
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
                    <div class="eyebrow">Menu Racik Tea</div>
                    <h1>Lacak Pesanan</h1>
                    <p>Masukkan kode pesanan untuk melihat status dan rincian.</p>
                </div>
            </section>

            <section id="trackForm" style="padding: 32px 0; max-width: 520px; margin: auto;">
                <form method="GET" action="/track" style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <input type="text" name="code" placeholder="Contoh: RT-261028-ABC12" required
                           style="flex: 1; min-width: 200px; border: 1px solid var(--line); background: #fff; padding: 12px 14px; border-radius: 10px; font-size: 14px; outline: none;">
                    <button type="submit" class="hero-action-primary" style="border: 0; cursor: pointer;">Lacak</button>
                </form>

                @if ($code)
                    <div style="margin-top: 24px; background: var(--surface); border: 1px solid var(--line); border-radius: 16px; padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 8px; margin-bottom: 12px;">
                            <span style="font: bold 18px 'Fraunces', serif;">#{{ $code }}</span>
                            <span class="history-badge history-badge-{{ $status }}" style="font-size: 12px; font-weight: 700; padding: 5px 10px; border-radius: 99px; white-space: nowrap;">{{ $status_label }}</span>
                        </div>

                        <div style="font-size: 12px; color: var(--muted); margin-bottom: 4px;">Nama</div>
                        <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">{{ $name }}</div>

                        <div style="font-size: 12px; color: var(--muted); margin-bottom: 4px;">Layanan</div>
                        <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">{{ $service }}</div>

                        <div style="font-size: 12px; color: var(--muted); margin-bottom: 4px;">Rincian</div>
                        <ul style="list-style: none; margin: 0 0 12px; padding: 0; display: flex; flex-direction: column; gap: 6px;">
                            @foreach ($items as $item)
                                <li style="display: flex; justify-content: space-between; font-size: 13px; padding: 8px; background: var(--surface2); border-radius: 10px;">
                                    <span>{{ $item['quantity'] }}x {{ $item['name'] }}</span>
                                    <span style="font-weight: 700;">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0; border-top: 1px dashed var(--line-light);">
                            <span>Subtotal</span>
                            <span style="font-weight: 700;">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if ($service_fee > 0)
                            <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0;">
                                <span>Ongkir antar</span>
                                <span style="font-weight: 700;">Rp {{ number_format($service_fee, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div style="display: flex; justify-content: space-between; font-size: 14px; padding: 8px 0; font-weight: 800; color: var(--green);">
                            <span>Total</span>
                            <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>

                        <div style="font-size: 11px; color: var(--muted); margin-top: 8px;">
                            Dipesan {{ \Carbon\Carbon::parse($created_at)->format('d M Y H:i') }} · Diperbarui {{ \Carbon\Carbon::parse($updated_at)->format('d M Y H:i') }}
                        </div>
                    </div>
                @endif
            </section>
        </main>

        <footer class="site-footer" id="storeFooter" style="margin-top: 40px;">
            <div class="footer-brand">
                <div class="footer-mark" id="footerMark">R</div>
                <span>
                    <strong id="footerStoreName">Racik Tea</strong>
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
