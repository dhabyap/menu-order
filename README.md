# Racik Tea — Pemesanan Digital

Aplikasi web pemesanan minuman Racik Tea berbasis Laravel 10 dan MySQL. Pelanggan menjelajahi menu, menyesuaikan racikan, lalu checkout melalui WhatsApp. Dashboard pengelola melindungi kelola menu, pengaturan toko, dan riwayat pesanan di balik login.

## Menjalankan aplikasi

Semua perintah dijalankan dari folder `app/`:

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

Buka `http://127.0.0.1:8000` untuk katalog pelanggan dan `http://127.0.0.1:8000/admin/login` untuk dashboard pengelola.

Database MySQL disesuaikan lewat `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`. Katalog mulai kosong dan diisi melalui dashboard.

### Akun pengelola

Kredensial dashboard dibaca dari `.env` (jangan di-commit ke repository):

```dotenv
ADMIN_EMAIL=admin@raciktea.local
ADMIN_PASSWORD=ganti-dengan-password-kuat
```

`ADMIN_PASSWORD` boleh teks biasa atau hash bcrypt/argon. Setelah mengubah `.env`, jalankan `php artisan config:clear` bila konfigurasi pernah di-cache.

Baris `users` hanya serves sebagai jangkar sesi: baris otomatis dibuat ulang saat login berhasil dan password-nya tidak pernah dibandingkan.

### Pengaturan toko

Nilai opsional di `.env` (dibaca lewat `config/store.php`):

- `STORE_WHATSAPP` — nomor WhatsApp tujuan checkout.
- `STORE_DELIVERY_FEE` — biaya antar default dalam rupiah.

Keduanya juga bisa diubah dari dashboard (Pengaturan Toko) dan tersimpan di database.

## Struktur

Kode aplikasi Laravel ada di `app/app/` (namespace `App\`):

- `app/Http/Controllers/Api/` — endpoint JSON: konfigurasi toko, katalog produk, checkout.
- `app/Http/Controllers/Admin/` — aksi dashboard, produk, pengaturan toko, autentikasi.
- `app/Http/Requests/` — validasi HTTP checkout (`StoreOrderRequest`) dan produk (`StoreProductRequest`).
- `app/Services/` — logika bisnis: `OrderService`, `ProductImageService`, `ProductOptionsValidator`.
- `resources/views/admin/partials/` — potongan Blade dashboard (header, statistik, tabel menu, pengaturan, pesanan, dialog).
- `public/assets/admin/` — CSS/JS dashboard pengelola.
- `public/assets/catalog/` — CSS/JS katalog pelanggan (`public/index.html`).
- `tests/Feature/` — tes kontrak API, katalog admin, autentikasi, dan upload gambar.

Route (`routes/api.php`, `routes/web.php`) hanya memetakan URL ke controller. Halaman katalog pelanggan adalah `public/index.html` statis tanpa proses build.

## Fitur

### Pemesanan pelanggan

- Katalog menu dengan pencarian dan filter kategori.
- Penyesuaian racikan: ukuran, tingkat kemanisan, topping, dan harga tambahan.
- Keranjang dengan jumlah, catatan per menu, serta pilihan ambil di kedai atau antar.
- Data pemesan tersimpan di browser; pesanan disimpan ke database sebelum WhatsApp terbuka.
- Tampilan responsif desktop dan mobile.

### Dashboard pengelola

- Login wajib; halaman setup publik tidak tersedia.
- Ringkasan pesanan, menu tersedia/habis, dan nilai pesanan.
- Tambah, edit, nonaktifkan, dan hapus menu, termasuk opsi racikan dan foto produk.
- Upload foto JPEG/PNG/WebP maksimal 5 MB ke storage publik.
- Pengaturan toko (nama toko, slogan, judul dan deskripsi halaman depan, nomor WhatsApp, ongkos antar, informasi singkat) dan riwayat pesanan.

## Pengujian

```powershell
cd app
php artisan test
vendor\bin\pint --test
```

Tes memakai SQLite in-memory sehingga aman dijalankan tanpa MySQL. Pint dijalankan terhadap file yang disentuh; perbaiki format sebelum commit.

## Dokumen lain

- [`PRD.md`](./PRD.md) — dokumen kebutuhan produk.
- [`menu-order-prototype.html`](./menu-order-prototype.html) — prototype awal satu file, dipertahankan sebagai arsip referensi; bukan frontend aktif.

## Lisensi

Proyek ini ditujukan untuk penggunaan internal dan evaluasi produk.
