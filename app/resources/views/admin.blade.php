<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>Dashboard Pengelola · Racik Tea</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --paper: #f7f5f0;
            --surface: #fffefa;
            --surface2: #faf8f2;
            --ink: #1c221a;
            --muted: #73796d;
            --line: #e8e4db;
            --line-light: #f0ede4;
            --green: #2e4334;
            --green-hover: #233528;
            --green-light: #e9efe8;
            --gold: #c58738;
            --gold-light: #faeedd;
            --plum: #6f4b63;
            --rose: #a8564a;
            --rose-light: #f7ebe8;
            --shadow: 0 12px 36px rgba(28, 34, 26, .08);
            --radius: 16px;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--ink); font: 14px/1.5 "Plus Jakarta Sans", system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        button, input, textarea { font: inherit; }
        button, summary { cursor: pointer; }
        a { color: inherit; }
        :focus-visible { outline: 3px solid rgba(46, 67, 52, .42); outline-offset: 3px; }
        .shell { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .top { position: sticky; top: 0; z-index: 5; height: 68px; display: flex; align-items: center; justify-content: space-between; gap: 12px; background: rgba(247, 245, 240, .94); backdrop-filter: blur(8px); border-bottom: 1px solid var(--line); }
        .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; font-size: 22px; font-weight: 800; letter-spacing: -.5px; }
        .mark { width: 38px; height: 38px; border-radius: 50%; display: grid; place-items: center; background: var(--green); color: white; font: 700 18px "Fraunces", Georgia, serif; box-shadow: 0 2px 8px rgba(46, 67, 52, .25); }
        .brand-text small { display: block; margin-top: -3px; color: var(--muted); font-size: 11px; font-weight: 500; letter-spacing: 0; }
        .top-actions { display: flex; align-items: center; gap: 8px; }
        .top-link, .logout-button { min-height: 38px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 8px 12px; border: 1px solid var(--line); border-radius: 10px; background: var(--surface); color: var(--ink); text-decoration: none; font-size: 12px; font-weight: 700; transition: border-color .15s, color .15s, background .15s; }
        .top-link:hover { border-color: var(--green); color: var(--green); }
        .logout-button { background: var(--green); border-color: var(--green); color: #fff; }
        .logout-button:hover, .button-primary:hover { background: var(--green-hover); }
        .admin-shell { padding: 28px 0 64px; }
        .intro { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 20px; }
        .eyebrow { display: flex; align-items: center; gap: 7px; color: var(--plum); font-size: 10px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; }
        h1, h2, h3, p { margin-top: 0; }
        h1 { margin: 6px 0 4px; font: 700 clamp(28px, 4vw, 36px)/1.1 "Fraunces", Georgia, serif; letter-spacing: -.7px; }
        .intro p { margin: 0; color: var(--muted); font-size: 13px; }
        .section-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin: 26px 0 12px; }
        .section-head h2, .orders-title { margin: 0; font: 700 19px "Fraunces", Georgia, serif; }
        .section-head p { margin: 3px 0 0; color: var(--muted); font-size: 12px; }
        .stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 20px 0 26px; }
        .stat-card { min-height: 105px; padding: 15px 16px; border: 1px solid var(--line); border-radius: 14px; background: var(--surface); }
        .stat-card span { display: block; color: var(--muted); font-size: 11px; font-weight: 600; }
        .stat-card strong { display: block; margin-top: 3px; font: 700 27px "Fraunces", Georgia, serif; letter-spacing: -.4px; }
        .stat-card em { display: block; margin-top: 2px; color: var(--muted); font-size: 10px; font-style: normal; }
        .panel { margin-top: 22px; overflow: hidden; border: 1px solid var(--line); border-radius: var(--radius); background: var(--surface); }
        .panel-heading { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 17px 18px; border-bottom: 1px solid var(--line-light); }
        .panel-heading h2 { margin: 0; font: 700 18px "Fraunces", Georgia, serif; }
        .panel-heading p { margin: 3px 0 0; color: var(--muted); font-size: 11px; }
        .button { min-height: 38px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 8px 13px; border: 1px solid transparent; border-radius: 9px; text-decoration: none; font-size: 12px; font-weight: 800; transition: background .15s, border-color .15s, color .15s; }
        .button-primary { background: var(--green); color: white; }
        .button-soft { border-color: var(--line); background: var(--surface); color: var(--green); }
        .button-soft:hover { border-color: #b9c2b4; background: var(--green-light); }
        .button-danger { background: var(--rose-light); color: var(--rose); }
        .button-danger:hover { background: #f1dcd7; }
        .notice { margin: 0 0 16px; padding: 12px 15px; border: 1px solid #cdd9cd; border-radius: 12px; background: var(--green-light); color: var(--green); font-size: 12px; }
        .notice.error { border-color: #e8c9c3; background: var(--rose-light); color: #853f35; }
        .notice ul { margin: 7px 0 0; padding-left: 20px; }
        .table-scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .menu-table { min-width: 760px; }
        .orders-table { min-width: 700px; }
        th, td { padding: 12px 14px; border-bottom: 1px solid var(--line-light); text-align: left; vertical-align: middle; }
        th { background: var(--surface2); color: var(--muted); font-size: 10px; font-weight: 800; letter-spacing: .8px; text-transform: uppercase; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover td { background: #fffefa; }
        .menu-cell { display: flex; align-items: center; gap: 10px; min-width: 200px; }
        .menu-art { width: 40px; height: 40px; flex: 0 0 40px; display: grid; place-items: center; border-radius: 11px; background: var(--green-light); color: var(--green); font-size: 17px; }
        .menu-art img { width: 100%; height: 100%; border-radius: inherit; object-fit: cover; }
        .menu-cell strong { display: block; font-size: 12px; }
        .menu-cell small { display: block; max-width: 230px; overflow: hidden; color: var(--muted); font-size: 10px; text-overflow: ellipsis; white-space: nowrap; }
        .category-pill, .status-pill { display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 99px; background: var(--surface2); color: #555d50; font-size: 10px; font-weight: 700; white-space: nowrap; }
        .status-pill.available { background: var(--green-light); color: var(--green); }
        .status-pill.unavailable { background: var(--rose-light); color: var(--rose); }
        .row-actions { display: flex; justify-content: flex-end; align-items: center; gap: 6px; }
        .inline-form { display: inline-flex; }
        .action-button { min-height: 31px; padding: 6px 9px; border: 0; border-radius: 8px; font-size: 10px; font-weight: 800; }
        .action-edit { background: var(--green-light); color: var(--green); }
        .action-stock { background: var(--gold-light); color: #80581f; }
        .action-delete { background: var(--rose-light); color: var(--rose); }
        .edit-row td { padding: 0; background: var(--surface2); }
        .edit-details summary { padding: 0; list-style: none; }
        .edit-details summary::-webkit-details-marker { display: none; }
        .edit-details[open] summary { display: none; }
        .edit-form { padding: 18px; border-top: 1px solid var(--line); }
        .edit-form h3, .form-card h3 { margin: 0 0 14px; font: 700 16px "Fraunces", Georgia, serif; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .field { min-width: 0; }
        .field.full { grid-column: 1 / -1; }
        .field label { display: block; margin-bottom: 5px; color: var(--ink); font-size: 11px; font-weight: 800; }
        .field input, .field textarea { width: 100%; min-height: 40px; padding: 9px 11px; border: 1px solid var(--line); border-radius: 9px; outline: none; background: #fff; color: var(--ink); font-size: 12px; }
        .field textarea { min-height: 78px; resize: vertical; }
        .field input:focus, .field textarea:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(46, 67, 52, .09); }
        .field-hint { display: block; margin-top: 5px; color: var(--muted); font-size: 10px; }
        .form-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
        .form-card { padding: 18px; border-bottom: 1px solid var(--line-light); background: var(--surface2); }
        .add-menu-dialog { width: min(700px, calc(100% - 24px)); max-width: 700px; max-height: min(90dvh, 820px); padding: 0; overflow: hidden; border: 1px solid var(--line); border-radius: 18px; background: var(--surface); color: var(--ink); box-shadow: 0 24px 80px rgba(28, 34, 26, .24); }
        .add-menu-dialog::backdrop { background: rgba(19, 27, 21, .52); backdrop-filter: blur(3px); }
        .dialog-layout { display: flex; flex-direction: column; max-height: min(90dvh, 820px); }
        .dialog-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; padding: 20px 22px 16px; border-bottom: 1px solid var(--line-light); }
        .dialog-kicker { margin-bottom: 4px; color: var(--plum); font-size: 10px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; }
        .dialog-header h2 { margin: 0; font: 700 22px "Fraunces", Georgia, serif; }
        .dialog-header p { margin: 4px 0 0; color: var(--muted); font-size: 11px; }
        .dialog-close { width: 34px; height: 34px; flex: 0 0 34px; border: 0; border-radius: 50%; background: var(--surface2); color: var(--muted); font-size: 19px; }
        .dialog-close:hover { background: var(--rose-light); color: var(--rose); }
        .dialog-form { min-height: 0; display: flex; flex: 1; flex-direction: column; }
        .dialog-body { padding: 18px 22px; overflow-y: auto; overscroll-behavior: contain; }
        .form-section + .form-section { margin-top: 21px; padding-top: 18px; border-top: 1px solid var(--line-light); }
        .form-section-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
        .form-section-heading h3 { margin: 0; font: 700 15px "Fraunces", Georgia, serif; }
        .form-section-heading p { margin: 3px 0 0; color: var(--muted); font-size: 10px; }
        .price-field { position: relative; }
        .price-field > span { position: absolute; top: 50%; left: 11px; color: var(--muted); font-size: 11px; font-weight: 700; transform: translateY(-50%); pointer-events: none; }
        .price-field input { padding-left: 37px; }
        .price-preview { min-height: 40px; display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 8px 11px; border: 1px solid #d5dfd4; border-radius: 9px; background: var(--green-light); color: var(--green); font-size: 10px; font-weight: 700; }
        .price-preview strong { font: 700 15px "Fraunces", Georgia, serif; }
        .image-upload { display: grid; grid-template-columns: 92px minmax(0, 1fr); align-items: center; gap: 13px; }
        .image-preview { width: 92px; height: 92px; display: grid; place-items: center; overflow: hidden; border: 1px dashed #cbd4c6; border-radius: 12px; background: var(--green-light); color: var(--green); font-size: 25px; }
        .image-preview img { width: 100%; height: 100%; object-fit: cover; }
        .image-upload input[type="file"] { min-height: auto; padding: 8px; background: var(--surface); }
        .remove-image { display: inline-flex; align-items: center; gap: 7px; margin-top: 8px; color: var(--rose); font-size: 10px; font-weight: 700; }
        .remove-image input { width: 15px; min-height: 15px; accent-color: var(--rose); }
        .edit-image-preview { width: 52px; height: 52px; overflow: hidden; border-radius: 10px; background: var(--green-light); }
        .edit-image-preview img { width: 100%; height: 100%; object-fit: cover; }
        .option-groups { display: grid; gap: 10px; }
        .option-empty { padding: 14px; border: 1px dashed var(--line); border-radius: 10px; color: var(--muted); font-size: 11px; text-align: center; }
        .option-group { min-width: 0; margin: 0; padding: 13px; border: 1px solid var(--line); border-radius: 11px; background: var(--surface2); }
        .option-group-head { display: flex; align-items: flex-end; gap: 9px; }
        .option-group-head .field { flex: 1; }
        .option-group-head .field label, .option-row .field label { font-size: 10px; }
        .option-rows { display: grid; gap: 8px; margin-top: 10px; }
        .option-row { display: grid; grid-template-columns: minmax(0, 1fr) 150px 36px; align-items: end; gap: 8px; }
        .remove-option { width: 36px; height: 38px; border: 1px solid #ead8d4; border-radius: 9px; background: var(--rose-light); color: var(--rose); font-size: 16px; }
        .option-group-actions { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-top: 10px; }
        .button-small { min-height: 32px; padding: 6px 10px; font-size: 10px; }
        .dialog-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 22px; border-top: 1px solid var(--line-light); background: var(--surface); }
        .dialog-footer-note { color: var(--muted); font-size: 10px; }
        body.dialog-open { overflow: hidden; }
        .settings-panel { padding: 0; }
        .settings-details summary { padding: 17px 18px; color: var(--ink); font: 700 18px "Fraunces", Georgia, serif; list-style: none; cursor: pointer; }
        .settings-details summary::-webkit-details-marker { display: none; }
        .settings-details summary::after { content: "+"; float: right; color: var(--green); font: 700 19px "Plus Jakarta Sans", sans-serif; }
        .settings-details[open] summary::after { content: "−"; }
        .settings-content { padding: 18px; border-top: 1px solid var(--line-light); }
        .settings-content > p { margin: 0 0 16px; color: var(--muted); font-size: 11px; }
        .empty-state { padding: 32px 18px; color: var(--muted); text-align: center; }
        .empty-state strong { display: block; margin-bottom: 4px; color: var(--ink); font: 700 16px "Fraunces", Georgia, serif; }
        .order-code { color: var(--green); font-weight: 800; white-space: nowrap; }
        .order-customer strong { display: block; }
        .order-customer small, .order-time { color: var(--muted); font-size: 10px; }
        .order-items { max-width: 250px; color: #555d50; font-size: 10px; }
        .order-total { font-weight: 800; white-space: nowrap; }
        .order-status { display: inline-flex; padding: 4px 8px; border-radius: 99px; background: var(--gold-light); color: #80581f; font-size: 10px; font-weight: 800; white-space: nowrap; }
        .order-status.cancelled { background: var(--rose-light); color: var(--rose); }
        .footer-note { margin: 22px 0 0; color: var(--muted); font-size: 10px; text-align: center; }
        @media (max-width: 760px) {
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .intro { align-items: flex-start; flex-direction: column; }
        }
        @media (max-width: 520px) {
            .shell { padding: 0 14px; }
            .top { height: 62px; }
            .brand { gap: 7px; font-size: 18px; }
            .mark { width: 34px; height: 34px; }
            .top-link, .logout-button { min-height: 35px; padding: 7px 9px; font-size: 10px; }
            .admin-shell { padding-top: 22px; }
            .stats { gap: 8px; }
            .stat-card { min-height: 94px; padding: 12px; }
            .stat-card strong { font-size: 23px; }
            .panel-heading { align-items: flex-start; padding: 15px 14px; }
            .form-grid { grid-template-columns: 1fr; gap: 10px; }
            .field.full { grid-column: auto; }
            .form-card, .edit-form, .settings-content { padding: 15px; }
            .add-menu-dialog { width: calc(100% - 16px); max-height: 94dvh; border-radius: 17px 17px 12px 12px; }
            .dialog-layout { max-height: 94dvh; }
            .dialog-header { padding: 16px 15px 13px; }
            .dialog-header h2 { font-size: 20px; }
            .dialog-body { padding: 15px; }
            .dialog-footer { align-items: stretch; flex-direction: column; padding: 11px 15px calc(11px + env(safe-area-inset-bottom, 0px)); }
            .dialog-footer .form-actions { margin-top: 0; }
            .dialog-footer .button { flex: 1; }
            .option-row { grid-template-columns: minmax(0, 1fr) 104px 34px; gap: 6px; }
            .remove-option { width: 34px; }
            .form-section-heading { align-items: flex-start; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; } }
    </style>
</head>
<body>
<div class="shell">
    <header class="top">
        <a class="brand" href="/" aria-label="Racik Tea, kembali ke katalog">
            <span class="mark">R</span>
            <span class="brand-text">Racik Tea<small>Artisan &amp; Seduh Segar</small></span>
        </a>
        <div class="top-actions">
            <a class="top-link" href="/">← <span>Lihat katalog</span></a>
            <form method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button class="logout-button" type="submit">Keluar</button>
            </form>
        </div>
    </header>

    <main class="admin-shell">
        <div class="intro">
            <div>
                <div class="eyebrow"><span>✿</span> Panel pengelola</div>
                <h1>Dashboard Pengelola</h1>
                <p>Halo, {{ auth()->user()->name }}. Kelola menu, pesanan, dan informasi kedai.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="notice" role="status">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="notice error" role="alert">
                <strong>Periksa kembali informasi berikut:</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section aria-label="Ringkasan toko">
            <div class="stats">
                <div class="stat-card"><span>Total Pesanan</span><strong>{{ $orders->count() }}</strong><em>Pesanan tersimpan</em></div>
                <div class="stat-card"><span>Menu Tersedia</span><strong>{{ $available }}</strong><em>Siap dipesan pelanggan</em></div>
                <div class="stat-card"><span>Menu Habis</span><strong>{{ $unavailable }}</strong><em>Sedang tidak tersedia</em></div>
                <div class="stat-card"><span>Nilai Pesanan</span><strong>Rp{{ number_format($revenue, 0, ',', '.') }}</strong><em>Di luar pesanan dibatalkan</em></div>
            </div>
        </section>

        <section class="panel" aria-labelledby="menu-title">
            <div class="panel-heading">
                <div><h2 id="menu-title">Daftar Menu &amp; Ketersediaan Stok</h2><p>Atur harga, pilihan racikan, dan ketersediaan menu.</p></div>
                <button class="button button-primary" id="open-add-menu" type="button">＋ Tambah Menu</button>
            </div>

            <div class="table-scroll">
                <table class="menu-table">
                    <thead><tr><th>Menu Minuman</th><th>Kategori</th><th>Harga Dasar</th><th>Status Stok</th><th style="text-align:right">Tindakan</th></tr></thead>
                    <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td><div class="menu-cell"><span class="menu-art">@if($product->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="">@else{{ $product->icon ?: '✿' }}@endif</span><span><strong>{{ $product->name }}</strong><small>{{ $product->description ?: 'Belum ada deskripsi' }}</small></span></div></td>
                            <td><span class="category-pill">{{ $product->category ?: 'Tanpa kategori' }}</span></td>
                            <td><strong>Rp{{ number_format($product->price, 0, ',', '.') }}</strong></td>
                            <td><span class="status-pill {{ $product->available ? 'available' : 'unavailable' }}">{{ $product->available ? 'Tersedia' : 'Habis' }}</span></td>
                            <td>
                                <div class="row-actions">
                                    <button class="action-button action-edit edit-trigger-button" type="button">Edit</button>
                                    <form class="inline-form" method="post" action="{{ route('admin.products.toggle', $product->id) }}">
                                        @csrf @method('PATCH')
                                        <button class="action-button action-stock" type="submit">{{ $product->available ? 'Tandai Habis' : 'Aktifkan' }}</button>
                                    </form>
                                    <form class="inline-form" method="post" action="{{ route('admin.products.delete', $product->id) }}" onsubmit="return confirm('Hapus menu ini? Tindakan ini tidak dapat dibatalkan.')">
                                        @csrf @method('DELETE')
                                        <button class="action-button action-delete" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr class="edit-row">
                            <td colspan="5">
                                <details class="edit-details">
                                    <summary><span class="edit-open-{{ $product->id }}"></span></summary>
                                    <div class="edit-form">
                                        <h3>Edit {{ $product->name }}</h3>
                                        <form method="post" action="{{ route('admin.products.update', $product->id) }}" enctype="multipart/form-data">
                                            @csrf @method('PUT')
                                            <div class="form-grid">
                                                <div class="field"><label>Nama menu</label><input name="name" required maxlength="120" value="{{ $product->name }}"></div>
                                                <div class="field"><label>Kategori</label><input name="category" required maxlength="80" value="{{ $product->category }}"></div>
                                                <div class="field"><label>Harga dasar (Rp)</label><input name="price" type="number" min="0" max="10000000" required value="{{ $product->price }}"></div>
                                                <div class="field"><label>Label</label><input name="labels" maxlength="200" value="{{ implode(', ', json_decode($product->labels ?: '[]', true) ?: []) }}"></div>
                                                <div class="field full"><label>Deskripsi</label><input name="description" maxlength="1000" value="{{ $product->description }}"></div>
                                                <div class="field full"><label>Foto menu (opsional)</label><div class="image-upload"><span class="edit-image-preview">@if($product->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="Foto {{ $product->name }}">@else<span>✿</span>@endif</span><span><input type="file" name="image" accept="image/jpeg,image/png,image/webp" aria-label="Ganti foto {{ $product->name }}"><span class="field-hint">JPEG, PNG, atau WebP · maks. 5 MB. Pilih gambar baru untuk mengganti.</span>@if($product->image_path)<label class="remove-image"><input type="checkbox" name="remove_image" value="1"> Hapus foto saat menyimpan</label>@endif</span></div></div>
                                                <div class="field full"><label>Opsi racikan (JSON)</label><textarea name="options_json">{{ json_encode(json_decode($product->options ?: '[]', true) ?: [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</textarea><span class="field-hint">Array JSON berisi nama kelompok beserta opsi dan harga tambahannya.</span></div>
                                            </div>
                                            <div class="form-actions"><button class="button button-soft" type="button" onclick="this.closest('details').open=false">Batal</button><button class="button button-primary" type="submit">Simpan Perubahan</button></div>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state"><strong>Belum ada menu</strong>Tambahkan menu pertama kedai menggunakan tombol Tambah Menu.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel settings-panel" aria-label="Pengaturan toko">
            <details class="settings-details">
                <summary>Pengaturan Toko</summary>
                <div class="settings-content">
                    <p>Informasi ini digunakan pada halaman katalog dan saat pelanggan membuat pesanan.</p>
                    <form method="post" action="{{ route('admin.settings.update') }}">
                        @csrf
                        <div class="form-grid">
                            <div class="field"><label for="store-name">Nama toko</label><input id="store-name" name="store_name" required maxlength="120" value="{{ old('store_name', $settings['store_name'] ?? 'Racik Tea') }}"></div>
                            <div class="field"><label for="store-tagline">Slogan</label><input id="store-tagline" name="store_tagline" maxlength="120" value="{{ old('store_tagline', $settings['store_tagline'] ?? '') }}" placeholder="Artisan &amp; Seduh Segar"></div>
                            <div class="field"><label for="hero-heading">Judul halaman depan</label><input id="hero-heading" name="hero_heading" required maxlength="160" value="{{ old('hero_heading', $settings['hero_heading'] ?? 'Pilih minuman favoritmu.') }}"></div>
                            <div class="field"><label for="whatsapp">WhatsApp kedai</label><input id="whatsapp" name="whatsapp" inputmode="numeric" pattern="62[1-9][0-9]{7,12}" value="{{ old('whatsapp', $settings['whatsapp'] ?? '') }}" placeholder="6281234567890"><span class="field-hint">Format internasional, angka saja.</span></div>
                            <div class="field"><label for="delivery-fee">Ongkos antar (Rp)</label><input id="delivery-fee" name="delivery_fee" type="number" min="0" max="10000000" required value="{{ old('delivery_fee', $settings['delivery_fee'] ?? 0) }}"></div>
                            <div class="field full"><label for="hero-description">Deskripsi halaman depan</label><textarea id="hero-description" name="hero_description" required maxlength="500">{{ old('hero_description', $settings['hero_description'] ?? 'Telusuri menu yang tersedia dan sesuaikan racikan sebelum memesan.') }}</textarea></div>
                            <div class="field full"><label for="marketing-badges">Informasi singkat</label><textarea id="marketing-badges" name="marketing_badges" maxlength="1000" placeholder="Jam buka, area layanan, atau informasi toko">{{ old('marketing_badges', $settings['marketing_badges'] ?? '') }}</textarea><span class="field-hint">Satu informasi per baris.</span></div>
                        </div>
                        <div class="form-actions"><button class="button button-primary" type="submit">Simpan Pengaturan</button></div>
                    </form>
                </div>
            </details>
        </section>

        <section class="panel" aria-labelledby="orders-title">
            <div class="panel-heading"><div><h2 id="orders-title">Riwayat Pesanan</h2><p>Pesanan yang masuk tersimpan di database toko.</p></div></div>
            <div class="table-scroll">
                <table class="orders-table">
                    <thead><tr><th>Kode</th><th>Pemesan</th><th>Layanan</th><th>Rincian Menu</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($orders as $order)
                        @php($orderItems = is_string($order->items) ? json_decode($order->items, true) : $order->items)
                        <tr>
                            <td><span class="order-code">{{ $order->code }}</span><br><span class="order-time">{{ $order->created_at }}</span></td>
                            <td class="order-customer"><strong>{{ $order->customer_name }}</strong><small>{{ $order->phone }}</small></td>
                            <td>{{ ucfirst($order->service) }}</td>
                            <td class="order-items">{{ collect($orderItems ?: [])->map(fn ($item) => ($item['quantity'] ?? 1).'× '.($item['name'] ?? 'Menu'))->implode(', ') ?: '—' }}</td>
                            <td class="order-total">Rp{{ number_format($order->total, 0, ',', '.') }}</td>
                            <td><span class="order-status {{ $order->status === 'cancelled' ? 'cancelled' : '' }}">{{ ucfirst($order->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><strong>Belum ada pesanan</strong>Pesanan pelanggan akan muncul di sini.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <p class="footer-note">Perubahan menu dan pengaturan tersimpan langsung di database toko.</p>
    </main>
</div>
<dialog class="add-menu-dialog" id="add-menu-dialog" aria-labelledby="add-menu-title">
    <div class="dialog-layout">
        <div class="dialog-header">
            <div><div class="dialog-kicker">Katalog kedai</div><h2 id="add-menu-title">Tambah Menu Baru</h2><p>Isi detail menu dan atur opsi racikan jika tersedia.</p></div>
            <button class="dialog-close" id="close-add-menu" type="button" aria-label="Tutup formulir">×</button>
        </div>
        <form class="dialog-form" id="add-product-form" method="post" action="{{ route('admin.products.create') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="options_json" id="options-json" value="[]">
            <div class="dialog-body">
                <section class="form-section" aria-labelledby="basic-details-title">
                    <div class="form-section-heading"><div><h3 id="basic-details-title">Informasi menu</h3><p>Nama, kategori, dan harga yang akan dilihat pelanggan.</p></div></div>
                    <div class="form-grid">
                        <div class="field"><label for="new-name">Nama menu <span aria-hidden="true">*</span></label><input id="new-name" name="name" required maxlength="120" placeholder="Contoh: Teh Melati"></div>
                        <div class="field"><label for="new-category">Kategori <span aria-hidden="true">*</span></label><input id="new-category" name="category" required maxlength="80" placeholder="Contoh: Teh klasik"></div>
                        <div class="field"><label for="new-price">Harga dasar <span aria-hidden="true">*</span></label><div class="price-field"><span>Rp</span><input id="new-price" name="price" required type="number" min="0" max="10000000" step="500" inputmode="numeric" placeholder="12.000"></div></div>
                        <div class="field"><label>Pratinjau harga</label><div class="price-preview"><span>Harga mulai dari</span><strong id="price-preview">Rp0</strong></div></div>
                        <div class="field full"><label for="new-description">Deskripsi <span style="font-weight:500;color:var(--muted)">(opsional)</span></label><textarea id="new-description" name="description" maxlength="1000" placeholder="Ceritakan rasa atau bahan utama menu ini"></textarea></div>
                        <div class="field full"><label for="new-labels">Label <span style="font-weight:500;color:var(--muted)">(opsional)</span></label><input id="new-labels" name="labels" maxlength="200" placeholder="Contoh: Favorit, Segar"><span class="field-hint">Pisahkan beberapa label dengan koma.</span></div>
                        <div class="field full"><label for="new-image">Foto menu <span style="font-weight:500;color:var(--muted)">(opsional)</span></label><div class="image-upload"><span class="image-preview" id="new-image-preview" aria-label="Pratinjau foto menu">✿</span><span><input id="new-image" name="image" type="file" accept="image/jpeg,image/png,image/webp"><span class="field-hint">Pilih foto produk yang jelas. Format JPEG, PNG, atau WebP, maksimal 5 MB.</span></span></div></div>
                    </div>
                </section>
                <section class="form-section" aria-labelledby="options-title">
                    <div class="form-section-heading">
                        <div><h3 id="options-title">Pilihan racikan <span style="font:500 10px 'Plus Jakarta Sans',sans-serif;color:var(--muted)">(opsional)</span></h3><p>Contoh: ukuran, tingkat gula, atau topping. Harga di bawah adalah tambahan.</p></div>
                        <button class="button button-soft button-small" id="add-option-group" type="button">＋ Tambah Grup</button>
                    </div>
                    <div class="option-groups" id="option-groups"><div class="option-empty">Belum ada pilihan tambahan. Menu tetap bisa dipesan tanpa opsi.</div></div>
                </section>
            </div>
            <div class="dialog-footer">
                <span class="dialog-footer-note">Menu akan langsung tersimpan di katalog.</span>
                <div class="form-actions"><button class="button button-soft" id="cancel-add-menu" type="button">Batal</button><button class="button button-primary" type="submit">Simpan Menu</button></div>
            </div>
        </form>
    </div>
</dialog>
<script>
    document.querySelectorAll('.edit-trigger-button').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('tr');
            const editor = row.nextElementSibling?.querySelector('.edit-details');
            if (editor) editor.open = !editor.open;
            if (editor?.open) editor.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    const addMenuDialog = document.getElementById('add-menu-dialog');
    const addMenuForm = document.getElementById('add-product-form');
    const optionGroups = document.getElementById('option-groups');
    const optionsJson = document.getElementById('options-json');
    const priceInput = document.getElementById('new-price');
    const pricePreview = document.getElementById('price-preview');
    const formatRupiah = (value) => `Rp${Number(value || 0).toLocaleString('id-ID')}`;

    document.getElementById('open-add-menu').addEventListener('click', () => {
        addMenuDialog.showModal();
        document.body.classList.add('dialog-open');
        window.setTimeout(() => document.getElementById('new-name').focus(), 0);
    });

    const closeAddMenu = () => {
        addMenuDialog.close();
        document.body.classList.remove('dialog-open');
    };
    document.getElementById('close-add-menu').addEventListener('click', closeAddMenu);
    document.getElementById('cancel-add-menu').addEventListener('click', closeAddMenu);
    addMenuDialog.addEventListener('close', () => document.body.classList.remove('dialog-open'));
    addMenuDialog.addEventListener('click', (event) => {
        if (event.target === addMenuDialog) closeAddMenu();
    });

    priceInput.addEventListener('input', () => {
        pricePreview.textContent = formatRupiah(priceInput.value);
    });

    const bindImagePreview = (input, preview) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                input.setCustomValidity('Pilih gambar JPEG, PNG, atau WebP.');
                input.reportValidity();
                input.value = '';
                return;
            }
            input.setCustomValidity('');
            const image = document.createElement('img');
            image.alt = 'Pratinjau foto menu';
            image.src = URL.createObjectURL(file);
            image.onload = () => URL.revokeObjectURL(image.src);
            preview.replaceChildren(image);
        });
    };
    bindImagePreview(document.getElementById('new-image'), document.getElementById('new-image-preview'));

    document.querySelectorAll('.edit-form input[type="file"]').forEach((input) => {
        const preview = input.closest('.image-upload').querySelector('.edit-image-preview');
        bindImagePreview(input, preview);
    });

    const makeField = (labelText, input, className = 'field') => {
        const field = document.createElement('div');
        field.className = className;
        const label = document.createElement('label');
        label.textContent = labelText;
        field.append(label, input);
        return field;
    };

    const addOptionRow = (rows) => {
        const row = document.createElement('div');
        row.className = 'option-row';

        const labelInput = document.createElement('input');
        labelInput.type = 'text';
        labelInput.required = true;
        labelInput.maxLength = 80;
        labelInput.placeholder = 'Contoh: Large';
        labelInput.dataset.optionLabel = '';
        labelInput.setAttribute('aria-label', 'Nama pilihan');

        const priceInput = document.createElement('input');
        priceInput.type = 'number';
        priceInput.required = true;
        priceInput.min = '0';
        priceInput.max = '10000000';
        priceInput.step = '1';
        priceInput.inputMode = 'numeric';
        priceInput.placeholder = '0';
        priceInput.dataset.optionPrice = '';
        priceInput.setAttribute('aria-label', 'Harga tambahan dalam rupiah');

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'remove-option';
        remove.textContent = '×';
        remove.setAttribute('aria-label', 'Hapus pilihan');
        remove.addEventListener('click', () => {
            const group = rows.closest('.option-group');
            row.remove();
            if (!rows.querySelector('.option-row')) addOptionRow(rows);
            group.querySelector('[data-option-label]').focus();
        });

        row.append(
            makeField('Nama pilihan', labelInput),
            makeField('Tambah harga (Rp)', priceInput),
            remove
        );
        rows.append(row);
    };

    document.getElementById('add-option-group').addEventListener('click', () => {
        optionGroups.querySelector('.option-empty')?.remove();
        const group = document.createElement('fieldset');
        group.className = 'option-group';

        const groupHead = document.createElement('div');
        groupHead.className = 'option-group-head';
        const groupName = document.createElement('input');
        groupName.type = 'text';
        groupName.required = true;
        groupName.maxLength = 80;
        groupName.placeholder = 'Contoh: Ukuran';
        groupName.dataset.groupName = '';
        groupName.setAttribute('aria-label', 'Nama grup pilihan');
        groupHead.append(makeField('Nama grup', groupName));

        const removeGroup = document.createElement('button');
        removeGroup.type = 'button';
        removeGroup.className = 'button button-danger button-small';
        removeGroup.textContent = 'Hapus grup';
        removeGroup.addEventListener('click', () => {
            group.remove();
            if (!optionGroups.querySelector('.option-group')) {
                const empty = document.createElement('div');
                empty.className = 'option-empty';
                empty.textContent = 'Belum ada pilihan tambahan. Menu tetap bisa dipesan tanpa opsi.';
                optionGroups.append(empty);
            }
        });
        groupHead.append(removeGroup);

        const rows = document.createElement('div');
        rows.className = 'option-rows';
        const actions = document.createElement('div');
        actions.className = 'option-group-actions';
        const hint = document.createElement('span');
        hint.className = 'field-hint';
        hint.textContent = 'Tambahkan pilihan dan harga tambahannya.';
        const addChoice = document.createElement('button');
        addChoice.type = 'button';
        addChoice.className = 'button button-soft button-small';
        addChoice.textContent = '＋ Tambah Pilihan';
        addChoice.addEventListener('click', () => addOptionRow(rows));
        actions.append(hint, addChoice);

        group.append(groupHead, rows, actions);
        optionGroups.append(group);
        addOptionRow(rows);
        groupName.focus();
    });

    addMenuForm.addEventListener('submit', (event) => {
        const groups = [...optionGroups.querySelectorAll('.option-group')].map((group) => ({
            name: group.querySelector('[data-group-name]').value.trim(),
            options: [...group.querySelectorAll('.option-row')].map((row) => ({
                label: row.querySelector('[data-option-label]').value.trim(),
                price: Number(row.querySelector('[data-option-price]').value),
            })),
        }));

        if (groups.some((group) => !group.name || group.options.some((option) => !option.label || !Number.isInteger(option.price) || option.price < 0))) {
            event.preventDefault();
            return;
        }

        optionsJson.value = JSON.stringify(groups);
    });
</script>
</body>
</html>
