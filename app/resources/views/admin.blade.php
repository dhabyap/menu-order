<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Pengelola · {{ $storeName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/admin.css') }}">
</head>
<body>
<div class="shell">
    @include('admin.partials.header')

    <nav class="tabs" aria-label="Tabs dashboard">
        <button class="tab-link" data-tab="ringkasan">Ringkasan</button>
        <button class="tab-link" data-tab="menu">Menu</button>
        <button class="tab-link" data-tab="pesanan">Pesanan</button>
        <button class="tab-link" data-tab="pengaturan">Pengaturan</button>
    </nav>

    <main class="admin-shell">
        @include('admin.partials.intro')
        @include('admin.partials.stats')

        <section class="panel tab-panel active" data-tab="ringkasan" aria-labelledby="tab-summary-title">
            <div class="panel-heading" id="tab-summary-head">
                <div>
                    <h2 id="tab-summary-title">Ringkasan</h2>
                    <p>Stat cepat, pesanan terbaru, dan riwayat aktivitas.</p>
                </div>
                <div class="tab-actions">
                    <button class="button button-soft button-small" type="button" id="quick-add-menu">Tambah Menu</button>
                    <button class="button button-soft button-small" type="button" id="quick-export">Export CSV</button>
                    <span class="orders-live" id="quick-orders-live" aria-live="polite"></span>
                </div>
            </div>

            <div class="table-scroll">
                <table class="orders-table" id="recent-orders-table">
                    <thead><tr><th>Kode</th><th>Pemesan</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody id="recent-orders-body"></tbody>
                </table>
            </div>
            <p class="footer-note">Klik kode pada kolom untuk lihat detail pesanan.</p>

            <div class="table-scroll">
                <table class="orders-table" id="activity-log-table">
                    <thead><tr><th>Waktu</th><th>Aksi</th><th>Deskripsi</th></tr></thead>
                    <tbody id="recent-activity-body"></tbody>
                </table>
            </div>
        </section>

        <section class="panel tab-panel" data-tab="menu" aria-labelledby="tab-menu-title">
            <div class="panel-heading" id="tab-menu-head">
                <div>
                    <h2 id="tab-menu-title">Daftar Menu &amp; Ketersediaan Stok</h2>
                    <p>Atur harga, pilihan racikan, dan ketersediaan menu.</p>
                </div>
                <button class="button button-primary" type="button" id="open-add-menu">＋ Tambah Menu</button>
            </div>
            <input id="products-search" type="text" placeholder="Cari nama menu atau kategori…" aria-label="Filter menu" style="width:100%; margin-bottom:12px;">
            <div class="table-scroll">
                @include('admin.partials.menu-table', ['products' => $products])
            </div>
        </section>

        <section class="panel tab-panel" data-tab="pesanan" aria-labelledby="tab-orders-title">
            <div class="panel-heading" id="tab-orders-head">
                <div>
                    <h2 id="tab-orders-title">Riwayat Pesanan</h2>
                    <p>Pesanan yang masuk tersimpan di database toko. Ubah status untuk memproses pesanan.</p>
                </div>
                <div class="tab-actions">
                    <select id="orders-status-filter" class="order-status-select" aria-label="Filter status pesanan">
                        <option value="">Semua status</option>
                        <option value="pending">Menunggu</option>
                        <option value="processing">Sedang Diproses</option>
                        <option value="completed">Selesai</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                    <span class="orders-live" id="orders-live" aria-live="polite">Memuat…</span>
                    <button class="button button-soft button-small" type="button" id="orders-refresh">⟳ Muat ulang</button>
                    <a class="button button-soft button-small" href="{{ route('admin.orders.export') }}" download>Export CSV</a>
                </div>
            </div>
            <div class="table-scroll">
                <table class="orders-table">
                    <thead><tr><th>Kode</th><th>Pemesan</th><th>Layanan</th><th>Rincian Menu</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody id="orders-body">
                        @include('admin.partials.orders-rows', ['orders' => $orders])
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel tab-panel" data-tab="pengaturan" aria-labelledby="tab-settings-title">
            <div class="panel-heading" id="tab-settings-head">
                <div>
                    <h2 id="tab-settings-title">Pengaturan Toko</h2>
                    <p>Informasi ini digunakan pada halaman katalog dan saat pelanggan membuat pesanan.</p>
                </div>
                <div class="orders-heading-actions">
                    <button class="button button-soft button-small" type="button" id="backup-now">Backup Sekarang</button>
                    <button class="button button-soft button-small" type="button" id="backup-refresh">⟳ Muat ulang</button>
                </div>
            </div>
            <div class="settings-panel">
                @include('admin.partials.settings')
            </div>
        </section>
    </main>
</div>
@include('admin.partials.add-menu-dialog')
<script src="{{ asset('assets/admin/admin.js') }}"></script>
</body>
</html>
