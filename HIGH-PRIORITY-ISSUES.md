# High-Priority Issues — Racik Tea
Untuk programmer junior. Kerjakan satu per satu sesuai nomor.

---

## Issue #1 — Badge "Habis" di Katalog Pelanggan

### Ringkasan
Saat ini produk dengan `available = false` masih tampil normal di katalog. Pelanggan bisa klik "Pesan" padahal menu habis. Tambah badge "Habis" dan nonaktifkan tombol pesan untuk produk yang tidak tersedia.

### Files yang disentuh
- `app/app/Http/Controllers/Api/ProductController.php` — pastikan endpoint katalog kirim flag `available`
- `app/resources/views/catalog.blade.php` — tampilkan badge + disable button
- `app/tests/Feature/ApiContractTest.php` — tambah assertion `available` ada di response

### Detail implementasi
1. Endpoint katalog sudah include `available` dari DB. Cek apakah JSON response punya field `available`.
2. Di katalog, jika `product.available === false`:
   - tampilkan badge "Habis" di pojok card (bg abu-abu, teks putih)
   - overlay transparan di gambar produk
   - disable tombol "+ Pesan" dan tombol gear/settings
   - tambah class `product-unavailable` di card untuk styling
3. Badge harus terlihat jelas di mobile dan desktop.
4. Tidak ubah layout/spacing/teks lain.

### Acceptance Criteria
- Produk dengan `available = false` menampilkan badge "Habis"
- Tombol "+ Pesan" disabled untuk produk habis
- Produk dengan `available = true` tidak ada badge
- Hover/click pada produk habis tidak membuka drawer opsi racikan

### Tips
- Cek `app/tests/Feature/ApiContractTest.php` untuk lihat struktur response katalog.
- CSS class bisa ditambah di `public/assets/catalog/catalog.css`.

---

## Issue #2 — Konfigurasi Jam Operasional

### Ringkasan
Toko butuh aturan jam buka/tutup. Pelanggan tidak boleh pesan di luar jam operasional. Tambah konfigurasi jam buka-tutup harian di dashboard admin, dan blokir checkout di luar jam tersebut.

### Files yang disentuh
- `app/database/migrations/2026_10_21_000002_create_store_settings_table.php` — lihat struktur tabel store_settings
- `app/app/Http/Controllers/Admin/StoreSettingsController.php` — tambah field jam operasional
- `app/config/store.php` — tambah default jam operasional
- `app/app/Services/OrderService.php` — tambah validasi jam operasional
- `app/resources/views/admin/partials/settings.blade.php` — tambah input jam buka/tutup
- `app/app/Http/Requests/StoreSettingsRequest.php` — validasi input jam
- `app/tests/Feature/` — tambah test

### Detail implementasi
1. Tambah 2 setting di `store_settings`: `open_time` dan `close_time` (format `HH:MM`, 24 jam).
   - Default: `open_time = 08:00`, `close_time = 21:00`.
2. Di dashboard admin (halaman Pengaturan Toko), tambah 2 input:
   - Jam Buka (`type="time"`, name="open_time")
   - Jam Tutup (`type="time"`, name="close_time")
3. Saat checkout (`OrderService::create`), cek jam sekarang:
   - Jika di luar range `open_time` sampai `close_time`, lempar `RuntimeException` dengan message: "Toko sedang tutup. Jam buka: {open_time} - {close_time}."
4. Input dari admin divalidasi: format `HH:MM`, `open_time < close_time`.
5. Jika setting kosong, anggap selalu buka (opsional).

### Acceptance Criteria
- Admin bisa simpan jam buka/tutup dari halaman pengaturan
- Checkout ditolak dengan pesan jelas jika di luar jam operasional
- Test: checkout成功 di dalam jam, checkout ditolak di luar jam
- Setting tersimpan di DB dan bisa dibaca oleh config/store.php

### Tips
- Baca `StoreSettingsController::update` untuk lihat pola simpan setting.
- Baca `OrderService::create` untuk lihat tempat validasi.
- Gunakan `now()->format('H:i')` untuk cek jam sekarang.

---

## Issue #3 — Halaman Tracking Pesanan Publik

### Ringkasan
Pelanggan butuh cek status pesanan tanpa harus login. Buat halaman publik `/{kode_pesanan}` atau `/track/{code}` yang menampilkan detail pesanan dan statusnya.

### Files yang disentuh
- `app/routes/web.php` — tambah route publik `/track/{code}`
- `app/app/Http/Controllers/Api/OrderLookupController.php` — buat controller lookup publik
- `app/resources/views/track.blade.php` — tampilkan detail pesanan
- `app/tests/Feature/OrderLookupTest.php` — lihat test yang sudah ada, tambah test untuk halaman publik

### Detail implementasi
1. Tambah route: `Route::get('/track/{code}', [OrderLookupController::class, 'show'])`
2. Controller `OrderLookupController::show($code)`:
   - Cari pesanan by `code` (unique, format `RT-ymd-XXXXX`)
   - Jika tidak ketemu, tampilkan 404 atau halaman "Kode pesanan tidak ditemukan"
   - Jika ketemu, kirim ke view dengan data: kode, nama pemesan, layanan, total, status, label status, items, created_at
3. View `track.blade.php` (copy style dari catalog):
   - Header kecil: "Lacak Pesanan"
   - Card putih: kode pesanan besar, nama pemesan, layanan, total
   - Tabel item: nama produk, opsi, jumlah, subtotal
   - Badge status dengan warna:
     - Menunggu: kuning
     - Diproses: biru
     - Selesai: hijau
     - Dibatalkan: merah
   - Catatan admin (jika ada)
4. Halaman ini TIDAK perlu login. Akses publik.
5. Link "Cek Pesanan" di nav katalog mengarah ke `/track/{code}`. Saat ini ngaco ke `#`. Ubah jadi form atau link ke halaman tracking dengan input kode.

### Acceptance Criteria
- Buka `/track/RT-261028-ABC12` menampilkan detail pesanan
- Kode salah menampilkan pesan "tidak ditemukan"
- Status punya badge berwarna sesuai state
- Link "Cek Pesanan" di nav mengarah ke halaman tracking
- Tidak perlu login untuk mengakses

### Tips
- Lihat `OrderController::show` untuk format JSON response yang sudah ada, pakai struktur yang sama.
- Lihat `OrderStatus::label()` untuk mapping status ke label Indonesia.
- Test ada di `tests/Feature/OrderLookupTest.php`.

---

## Issue #4 — Rate Limiting Checkout API

### Ringkasan
Endpoint checkout (`POST /api/checkout`) belum dibatasi jumlah request. Pelanggan atau bot bisa spam dan buat ribuan pesanan palsu. Tambah throttle.

### Files yang disentuh
- `app/routes/api.php` — lihat route checkout
- `app/app/Http/Controllers/Api/CheckoutController.php` — tambah middleware throttle
- `app/tests/Feature/ApiContractTest.php` — test throttle

### Detail implementasi
1. Tambah middleware `throttle:10,1` ke route checkout (max 10 request per menit per IP).
2. Jika kena limit, Laravel otomatis return 429 dengan header `Retry-After`.
3. Pesan error yang user-friendly: "Terlalu banyak pesanan. Coba lagi dalam 1 menit."
4. Jangan ubah logic checkout yang lain.

### Acceptance Criteria
- 11 request dalam 1 menit dari IP sama return 429
- Request ke-1 sampai ke-10 return 201 (success)
- Pesan error 429 jelas dan ramah
- Test otomatis lulus

### Tips
- Route API ada di `routes/api.php`.
- Middleware `throttle` sudah ada di Laravel, tinggal tambah di route.
- Test bisa pakai `$this->postJson('/api/checkout', [...])` berkali-kali.

---

## Issue #5 — Export Pesanan ke CSV

### Ringkasan
Admin butuh export data pesanan untuk rekonsiliasi bank atau laporan harian. Tambah tombol "Export CSV" di halaman orders dashboard.

### Files yang disentuh
- `app/app/Http/Controllers/Admin/OrderController.php` — tambah method `exportCsv`
- `app/routes/web.php` — tambah route admin
- `app/resources/views/admin/partials/orders.blade.php` — tambah tombol export
- `app/tests/Feature/AdminOrderProcessingTest.php` — test export

### Detail implementasi
1. Tambah route: `Route::get('/orders/export', [OrderController::class, 'export'])->name('admin.orders.export')`
2. Method `OrderController::export()`:
   - Query semua orders dengan items (join atau ambil JSON)
   - Generate CSV dengan header: `Kode,Nama,Telepon,Alamat,Layanan,Total,Status,Tanggal`
   - Filename: `pesanan-{date}.csv`
   - Return response download dengan header `Content-Type: text/csv`
3. Di halaman orders dashboard, tambah tombol "Export CSV" di pojok atas tabel.
4. Tombol hanya muncul jika admin login.
5. CSV encode UTF-8 dengan BOM agar Excel bisa baca karakter Indonesia.

### Acceptance Criteria
- Tombol "Export CSV" muncul di halaman orders
- Download menghasilkan file CSV dengan data semua pesanan
- Buka di Excel, kolom Indonesia benar (tidak error encoding)
- File name include tanggal hari ini

### Tips
- Gunakan `fputcsv()` dengan delimiter `,` dan enclosure `"`.
- Tambah `\u{FEFF}` di awal output untuk UTF-8 BOM.
- Jangan include headers HTML, return response()->streamDownload().

---

## Issue #6 — Backup Database Otomatis (Shared Hosting Friendly)

### Ringkasan
Shared hosting bisa mati kapan saja. Buat backup database harian yang disimpan di storage dan bisa di-download dari dashboard.

### Files yang disentuh
- `app/app/Console/Kernel.php` — lihat scheduled commands
- `app/app/Console/Commands/BackupDatabase.php` — buat command baru
- `app/routes/web.php` — tambah route download backup
- `app/app/Http/Controllers/Admin/BackupController.php` — buat controller
- `app/resources/views/admin/partials/settings.blade.php` — tambah menu backup

### Detail implementasi
1. Buat artisan command `backup:database`:
   - Dump database menggunakan `mysqldump` atau `php artisan db:dump`
   - Simpan file di `storage/app/backups/` dengan nama `backup-{date}.sql`
   - Hapus backup lebih dari 7 hari otomatis
2. Tambah route admin: `GET /admin/backups` (list) dan `GET /admin/backups/download/{filename}`
3. Di dashboard admin, tambah section "Backup Database":
   - Tombol "Backup Sekarang" — jalankan command
   - List backup tersedia dengan tombol download
4. Jika `mysqldump` tidak tersedia di shared hosting, fallback gunakan pure PHP dump:
   - Query semua tabel dan data
   - Write INSERT statements ke file .sql
5. Shared hosting biasanya punya cron. Dokumentasikan cara setup cron di README:
   ```
   php /path/to/artisan backup:database --quiet
   ```

### Acceptance Criteria
- Command `php artisan backup:database` membuat file .sql di storage
- File .sql bisa di-download dari dashboard
- Backup lebih dari 7 hari otomatis terhapus
- Fallback PHP dump bekerja jika `mysqldump` tidak ada
- Dokumentasi cron di README

### Tips
- Cek `app/Console/Kernel.php` untuk lihat scheduled command yang sudah ada.
- Gunakan `Storage::disk('local')->put()` untuk simpan backup.
- Jangan commit file backup ke git (pastikan `.gitignore` include `storage/backups`).

---

## Issue #7 — Activity Log (Audit Trail)

### Ringkasan
Admin butuh tahu siapa yang mengubah menu, menghapus pesanan, atau mengganti setting. Tambah logging aktivitas admin.

### Files yang disentuh
- `app/database/migrations/xxxx_create_activity_log_table.php` — buat migration
- `app/app/Models/ActivityLog.php` — model
- `app/app/Http/Middleware/LogAdminActivity.php` — middleware logging
- `app/app/Http/Controllers/Admin/ActivityLogController.php` — controller
- `app/routes/web.php` — tambah route
- `app/resources/views/admin/partials/header.blade.php` — tambah tab Activity Log

### Detail implementasi
1. Migration `activity_logs`:
   - `id`, `user_email` (email admin yang aksi), `action` (string: "created_product", "deleted_order", dll), `description` (text), `ip_address`, `user_agent`, `created_at`
2. Model `ActivityLog` dengan fillable: `user_email`, `action`, `description`, `ip_address`, `user_agent`
3. Buat trait `LogsActivity` atau middleware yang otomatis log setiap request admin (kecuali GET stats):
   - Ambil user_email dari session/auth
   - Ambil route name sebagai `action`
   - Buat description: "Mengubah menu Cappuccino" atau "Menghapus pesanan RT-261028-ABC12"
4. Route: `GET /admin/activity-log` — tampilkan list 50 terbaru
5. View: tabel dengan kolom Waktu, Admin, Aksi, Deskripsi, IP
6. Log activity untuk:
   - POST /admin/products (create)
   - PUT/PATCH /admin/products/{id} (update)
   - DELETE /admin/products/{id} (delete)
   - PATCH /admin/orders/{id}/status (update status)
   - POST /admin/settings (update settings)

### Acceptance Criteria
- Setiap aksi admin tercatat di tabel `activity_logs`
- Halaman `/admin/activity-log` menampilkan 50 log terbaru
- Log include: waktu, email admin, aksi, deskripsi, IP
- Middleware tidak melog request GET ke stats/dashboard

### Tips
- Gunakan middleware pada route group admin yang sudah ada.
- Untuk deskripsi yang meaningful, baca route name + model yang diubah.
- Test: lakukan aksi admin, cek tabel activity_logs punya record baru.

---

## Issue #8 — Validasi Zona Antar & Minimal Order

### Ringkasan
Saat ini ongkos kirim flat dan tidak ada aturan minimal order. Tambah konfigurasi zona antar dan minimal order per area.

### Files yang disentuh
- `app/database/migrations/2026_10_21_000002_create_store_settings_table.php` — lihat struktur
- `app/app/Http/Controllers/Admin/StoreSettingsController.php` — tambah setting zona
- `app/app/Services/OrderService.php` — validasi zona + minimal
- `app/config/store.php` — tambah default zona
- `app/app/Http/Requests/StoreOrderRequest.php` — validasi input
- `app/tests/Feature/` — test zona

### Detail implementasi
1. Tambah 3 setting baru di `store_settings`:
   - `delivery_zones` (JSON): array of zones `[{"name": "Dalam kota", "fee": 5000, "min_order": 15000}, {"name": "Luar kota", "fee": 10000, "min_order": 25000}]`
   - `pickup_enabled` (boolean): apakah izin ambil sendiri
   - `pickup_address` (string): alamat ambil sendiri
2. Di checkout, tambah dropdown "Pilih zona antar" jika service = "Antar":
   - Isi dari `delivery_zones`
   - Setiap zona punya nama, ongkos, minimal order
3. Validasi:
   - Jika service = "Antar", zona wajib dipilih
   - Total pesanan (subtotal) harus >= `min_order` zona terpilih
   - Jika tidak cukup, return error: "Minimal order untuk zona {name}: Rp {min_order}"
4. Jika service = "Ambil sendiri", minimal order tidak dicek.
5. Di setting admin, tampilkan textarea JSON untuk edit zona, atau buat form dinamis.

### Acceptance Criteria
- Dropdown zona muncul saat pilih "Antar"
- Setiap zona punya nama, ongkir, minimal order yang benar
- Checkout ditolak jika subtotal < minimal order zona
- Ambil sendiri tidak ada minimal order
- Setting bisa diubah dari dashboard admin

### Tips
- `StoreOrderRequest` belum ada, lihat `routes/api.php` untuk checkout.
- `OrderService::create` adalah tempat validasi terbaik.
- JSON `delivery_zones` bisa di-decode dengan `json_decode($value, true)`.

---

## Catatan Umum untuk Semua Issue

### Development
- Semua perintah dijalankan dari folder `app/`.
- Gunakan SQLite in-memory untuk test: `php artisan test` sudah pakai SQLite.
- Jangan ubah `.env.example`, update `.env` lokal saja.
- Jangan force push ke `main`. Buat branch baru untuk setiap issue.

### Coding Standards
- PSR-12 untuk PHP.
- Validasi selalu di server, jangan percaya input browser.
- Gunakan Bahasa Indonesia untuk message/UI.
- Jangan hardcode nomor WhatsApp, ambil dari `config('store.whatsapp')` atau DB.

### Testing
- Setiap issue harus punya minimal 1 test baru.
- Test harus lulus: `cd app && php artisan test`
- Untuk test API, pakai `$this->postJson('/api/checkout', [...])`.
- Untuk test view, pakai `$this->get('/track/RT-261028-ABC12')->assertOk()`.

### Git
- Commit message format: `feat: add {feature}` atau `fix: {bug}`
- Jangan commit file `.env`, `storage/`, `vendor/`.
- Branch dari `main`, buat PR dengan `Closes #N`.

### Questions?
Buka issue di GitHub atau tanyakan di chat. Jangan malu untuk ask, tapi coba dokumentasi dulu.
