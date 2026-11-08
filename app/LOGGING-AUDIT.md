# Audit Logging — website-pacar (aplikasi "raciktea")

> **Status dokumen:** IMPLEMENTASI SELESAI (4 Nov 2026). Semua G01–G14 ditutup; lihat `LOGGING-IMPLEMENTATION.md` untuk rincian perubahan & `LOGGING.md` untuk konvensi investigasi.
> **Tanggal audit:** 4 November 2026
> **Cakupan:** seluruh alur aplikasi (katalog publik, checkout, tracking, dashboard admin, backup, command console).
> **Tujuan:** mengidentifikasi apa yang **sudah** tercatat, apa yang **belum**, lalu menyepakati desain logging sebelum implementasi.

---

## 0. Jawaban singkat

**Belum. Logging-nya belum menyeluruh — baru ada 2 lapis, dan keduanya punya lubang besar.**

Yang **sudah** ada:

1. **`storage/logs/laravel.log`** — log bawaan Laravel. Isinya **hanya error/crash**, tidak ada catatan kejadian sukses, tidak ada konteks bisnis, dan tidak berotasi (satu file terus membesar).
2. **Tabel `activity_logs`** — mencatat aksi admin (tambah menu, ubah pesanan, dll) beserta IP dan user agent. Tapi **tidak membedakan sukses vs gagal**, dan tidak mencatat order masuk dari customer.

Yang **belum** ada sama sekali:

- Log pesanan berhasil dibuat (`order.created`) → satu-satunya bukti hanya baris di tabel `orders`.
- Log pesanan **ditolak** (toko tutup, menu habis, ongkir belum dihitung, WhatsApp belum diset) → customer cuma dapat pesan error di layar, tidak ada jejak.
- Log login admin gagal / percobaan brute force.
- Log error sisi browser (JavaScript) — kalau UI pecah di HP customer, kita tidak akan pernah tahu.
- Log panggilan API eksternal (geocoding/OSRM) yang gagal atau lambat.
- Log backup database yang **gagal** — dan justru di sini ada bug: endpoint tetap membalas "Backup selesai."
- Request ID untuk menelusuri satu kejadian dari browser → server → DB.

Ringkasnya: **kalau ada customer komplain "pesanan saya gagal", saat ini kita tidak punya data untuk menjawabnya.**

---

## 1. Metode audit

Yang diperiksa (dibaca langsung, bukan diasumsikan):

| Area | File |
|---|---|
| Konfigurasi log | `config/logging.php`, `.env`, `.env.example`, `config/app.php` |
| Exception handling | `app/Exceptions/Handler.php` |
| Middleware | `app/Http/Kernel.php`, `Http/Middleware/LogAdminActivity.php` |
| Route | `routes/web.php`, `routes/api.php`, `routes/console.php` |
| Controller | `Admin/*` (Auth, Product, Order, Backup, Settings, ActivityLog), `Api/*` (Checkout, DeliveryQuote, OrderLookup, Product, StoreConfig) |
| Service | `OrderService`, `DeliveryQuoteService`, `ProductImageService`, `AdminAuthService` |
| Command | `Console/Commands/BackupDatabase.php` |
| Frontend | `public/assets/catalog/catalog.js` (1462 baris), `public/assets/admin/admin.js` (564 baris) |
| Bukti runtime | `storage/logs/laravel.log` |
| Test | `tests/Feature/*` (13 file) |

Pencarian kunci: `Log::`, `logger(`, `report(`, `catch`, `throw`, `console.error`, `ActivityLog`. Hasil: **`Log::` dan `logger()` nol kemunculan di seluruh kode aplikasi** (`app/app`, `routes`, `resources`, `public/assets`). `report()` hanya dipakai di **satu** tempat.

---

## 2. Matriks kondisi saat ini

Legenda: ✅ ada & memadai · ⚠️ ada tapi cacat · ❌ tidak ada

| # | Kejadian | Status | Disimpan di | Bukti |
|---|---|---|---|---|
| 1 | Exception server (crash, 500) | ⚠️ | `laravel.log` | `Handler.php:26` — callback `reportable()` kosong, tanpa konteks |
| 2 | Pesanan berhasil dibuat | ❌ | — | `OrderService.php:148` hanya `return` array |
| 3 | Pesanan ditolak aturan bisnis (toko tutup, menu habis, ongkir, WA) | ❌ | — | `CheckoutController.php:27-29` — `catch` → 422, tanpa log |
| 4 | Gagal simpan order ke DB (`QueryException`) | ❌ | — | `OrderService.php:136-146` — ditelan atau di-rethrow tanpa log |
| 5 | Ongkir gagal karena error tak terduga | ✅ | `laravel.log` | `DeliveryQuoteController.php:33-37` — **satu-satunya `report()`** |
| 6 | Ongkir gagal karena aturan bisnis (alamat tidak ketemu, di luar jangkauan) | ❌ | — | `DeliveryQuoteController.php:31-32` → 422 tanpa log |
| 7 | API eksternal (OpenCage / Mapbox / Google / OSRM) gagal / timeout | ❌ | — | `DeliveryQuoteService.php:425,489,606,693` — `Http::` tanpa `try/catch` + log |
| 8 | Aksi tulis admin (CRUD menu, ubah status order, settings, backup) | ⚠️ | `activity_logs` | `LogAdminActivity.php:44` — dicatat **tanpa cek sukses/gagal** |
| 9 | Aksi baca admin (lihat detail order, export CSV, download backup, buka activity log) | ❌ | — | `LogAdminActivity.php:15` — semua `GET` dikecualikan |
| 10 | Login admin berhasil | ❌ | — | `AuthController.php:34-38` tanpa log |
| 11 | Login admin gagal / brute force | ❌ | — | `AuthController.php:40-42` hanya flash error; `throttle:5,1` (`web.php:35`) tidak mencatat apa pun |
| 12 | Logout admin | ❌ | — | `AuthController.php:48-55` |
| 13 | Backup sukses | ⚠️ | stdout console | `BackupDatabase.php:43` — hilang saat dipanggil dari web |
| 14 | Backup gagal | ⚠️/❌ | stdout console | `BackupDatabase.php:37-40`; route `web.php:46-49` **mengabaikan exit code** |
| 15 | Satu tabel gagal di-dump saat backup | ❌ | komentar di file `.sql` | `BackupDatabase.php:107-109` |
| 16 | Error JavaScript di browser customer | ❌ | — | `catalog.js:1068-1072` — `catch` kosong, quote dibatalkan tanpa pesan |
| 17 | Error JavaScript di dashboard admin | ⚠️ | teks di UI saja | `admin.js:459-461`, `535-537` |
| 18 | 404 / 419 (CSRF) / 429 (throttle) | ❌ | — | Tidak menghasilkan exception → tidak pernah tersentuh logger |
| 19 | Job gagal di queue | ❌ | — | Tabel `failed_jobs` ada, tapi `QUEUE_CONNECTION=sync` |
| 20 | Scheduler / cron | ❌ | — | `routes/console.php` hanya berisi `inspire`; tidak ada jadwal terdaftar |

---

## 3. Temuan detail (untuk diidentifikasi bareng)

### G01 — Tidak ada satu pun log domain/bisnis di kode aplikasi
`Log::` dan `logger()` **nol kemunculan**. Artinya seluruh narasi bisnis ("pesanan RT-261104-A1B2C dibuat senilai Rp 45.000 dari Jl. ...") tidak pernah tercatat. `laravel.log` hanya terisi ketika PHP/Framework meledak.

### G02 — `reportable()` di Exception Handler dibiarkan kosong
`app/Exceptions/Handler.php:26-28`:
```php
$this->reportable(function (Throwable $e) {
    //
});
```
Ini titik paling murah untuk menambahkan konteks global (request ID, user, route, durasi) ke **semua** error. Sekarang setiap error tercatat tanpa identitas request, sehingga sulit dikorelasikan.

### G03 — Kegagalan checkout yang paling sering tidak tercatat
`CheckoutController.php:27-29` menangkap `RuntimeException` lalu mengembalikan 422. Tidak ada log. Padahal di sinilah semua kegagalan nyata terjadi:
- `OrderService.php:58` alamat kosong
- `:68` ongkir belum dihitung
- `:81` menu tidak tersedia
- `:89` nomor WhatsApp toko belum diset ← **kegagalan konfigurasi, bukan kesalahan customer**
- `:102` estimasi ongkir belum dicentang
- `:219,227` opsi racikan tidak valid
- `:263` toko tutup

Konsekuensi: kalau toko tutup padahal jam buka sudah benar, kita tidak punya bukti berapa banyak customer yang ditolak dan kapan.

### G04 — Order yang berhasil tidak punya jejak audit
`OrderService.php:111-146` menyimpan order lalu `return`. Tidak ada `Log::info('order.created', ...)`. Yang tersisa hanya baris DB — cukup untuk *melihat* order, tapi tidak untuk menjawab "apakah order ini dibuat dengan ongkir hasil quote atau fallback?", "apakah harga dihitung ulang server?" (padahal itu justru pengaman utama aplikasi ini).

### G05 — `activity_logs` mencatat aksi yang **gagal** sebagai berhasil
`LogAdminActivity.php:28-52` menjalankan `$next($request)` dulu, lalu **selalu** menulis log — tanpa memeriksa status response.

Contoh nyata: admin submit form menu baru dengan harga kosong → validasi gagal → response 302 redirect back dengan error → **tetap tercatat "Menambahkan menu: Es Teh"**. Log audit yang tidak bisa dipercaya lebih berbahaya daripada tidak punya log, karena bisa dipakai untuk menyalahkan/membela orang.

Perbaikan minimal: periksa `$response->getStatusCode()`, tulis log sukses hanya untuk `< 400`, dan tulis log `failed` untuk `>= 400` (termasuk `302` yang membawa `errors` di session).

### G06 — Aksi baca dan ekspor data customer tidak diaudit
`LogAdminActivity.php:15` (`EXCLUDED_METHODS = ['GET']`) membuat hal-hal ini tidak tercatat:
- `admin.orders.export` (`web.php:44`) → **seluruh data customer (nama, telepon, alamat) diunduh ke CSV**
- `admin.backups.download` (`web.php:50`) → seluruh isi database
- `admin.orders.show` → melihat detail satu customer

Untuk data pribadi, "siapa mengunduh apa kapan" adalah kebutuhan audit dasar, bukan kemewahan.

### G07 — Login gagal tidak tercatat, padahal sudah ada throttle
`web.php:35` memasang `throttle:5,1`, tapi tidak ada log saat limit tercapai maupun saat kredensial salah. Kalau akun admin dibobol, kita tidak punya bukti percobaan sebelumnya. Sebaliknya, kalau admin sah terkunci, kita juga tidak bisa menjelaskan kenapa.

### G08 — Backup bisa gagal sepenuhnya diam-diam (bug fungsional)
`web.php:46-49`:
```php
Route::post('/backups/now', function () {
    \Artisan::call(\App\Console\Commands\BackupDatabase::class);
    return response()->json(['message' => 'Backup selesai.']);
})->name('admin.backups.now');
```
`Artisan::call()` mengembalikan exit code, tapi **dibuang**. `BackupDatabase.php:37-40` melaporkan kegagalan lewat `$this->error()` yang menulis ke output console — yang juga dibuang. Jadi: **dump gagal → admin melihat "Backup selesai." → admin mengira data aman.**

Tambahan: `BackupDatabase.php:107-109` menelan exception per-tabel menjadi komentar di dalam file `.sql`. Backup yang "sukses" bisa saja kehilangan satu tabel utuh tanpa peringatan.

### G09 — Frontend benar-benar bisu
`catalog.js:1068-1072`:
```js
} catch (error) {
    deliveryQuote = null;
    successCard.hidden = true;
    feedback.textContent = "";
    updateCartUI();
}
```
Tidak ada `console.error`, tidak ada pesan ke user, tidak ada laporan ke server. Di `catalog.js:1156,1159,1164,1267` ada `catch {}` kosong total. `admin.js` sedikit lebih baik (menampilkan teks "Gagal memuat…"), tapi tetap tidak melaporkan apa pun ke server.

Artinya: **semua error yang terjadi di browser customer tidak akan pernah kita ketahui** — hanya terlihat sebagai "website-nya nggak bisa checkout".

### G10 — Tidak ada request ID / korelasi
Tidak ada `X-Request-Id`, tidak ada `Log::withContext()`. Satu klik customer bisa menghasilkan 5 baris log dari 5 file berbeda dan tidak ada cara memastikan itu satu kejadian.

### G11 — Format & rotasi log belum siap produksi
- `config/logging.php:55-59` — `stack` → `single` → **satu file `laravel.log` yang tidak pernah dirotasi**.
- Level `debug` di `.env:9` → sangat cerewet di produksi.
- Timezone aplikasi `UTC` (`config/app.php:73`), sedangkan UI menampilkan `id-ID`. Selisih 7 jam saat mencocokkan log dengan laporan customer — sumber kebingungan klasik.
- `.env:2-4` masih `APP_ENV=local`, `APP_DEBUG=true`. Kalau ini ikut ter-deploy, stack trace (termasuk path server dan query SQL) tampil ke publik.

### G12 — Channel tambahan sudah disiapkan tapi belum dipakai
`config/logging.php` sudah punya `slack`, `papertrail`, `stderr`, `daily`. Tidak ada satu pun yang aktif. Untuk notifikasi error kritis, tinggal isi `LOG_SLACK_WEBHOOK_URL`.

### G13 — Tidak ada log performa / HTTP
Tidak ada middleware yang mencatat `method`, `path`, `status`, `durasi`. Jadi tidak bisa menjawab "kenapa checkout terasa lambat?" atau "endpoint mana yang paling sering 500?". Padahal `laravel.log` sudah pernah mencatat `Maximum execution time of 60 seconds exceeded`.

### G14 — Catatan teknis kecil
- `Models/ActivityLog.php:19` — `scopeLatest(int $limit = 50)` **menimpa** `latest()` bawaan Eloquent. Selama dipanggil tanpa argumen aman, tapi `ActivityLog::latest('created_at')` akan melempar `TypeError`.
- `ActivityLogController.php:13` — hanya 50 baris terakhir, tanpa filter/pagination. Tidak mungkin dipakai investigasi.
- Test: 13 file feature test, **tidak ada** yang menguji perilaku logging.

---

## 4. Bukti dari `storage/logs/laravel.log`

| Metrik | Nilai |
|---|---|
| Total entri `ERROR` | **47** |
| Entri level `INFO` / `WARNING` / `NOTICE` | **0** |
| Entri berisi kejadian bisnis (order, login, backup) | **0** |
| Rentang waktu | 21 Okt 2026 → 2 Nov 2026 |
| Entri setelah 2 Nov | **0** (padahal ada pekerjaan setelahnya) |

Semua 47 entri adalah crash/kesalahan teknis, contoh polanya:

- `ParseError` di `routes/api.php:17` (7×) — typo saat development
- `QueryException: no such table: products` — migrasi belum jalan
- `Route [login] not defined` — `Authenticate` middleware salah arah
- `Call to private method DeliveryQuoteService::coordinate()` — bug akses
- `Maximum execution time of 60 seconds exceeded` — masalah performa, tidak pernah ditindaklanjuti
- `The "--columns" option does not exist` — command salah

**Kesimpulan dari bukti:** log yang ada saat ini adalah *crash dump*, bukan *audit trail*. Tidak ada satu pun entri yang bisa menjawab pertanyaan operasional ("berapa order hari ini?", "kenapa pesanan #12 gagal?", "siapa yang menghapus menu ini?").

---

## 5. Kenapa ini penting (risiko konkret)

| Risiko | Skenario nyata |
|---|---|
| Kehilangan penjualan tanpa jejak | Customer gagal checkout karena bug JS → tidak ada order, tidak ada log, kita tidak tahu itu pernah terjadi |
| Salah menyalahkan customer | "Pesanan saya gagal terus" — tanpa log kita tidak bisa bedakan salah input vs bug kita |
| Backup palsu | Backup "sukses" padahal gagal → data hilang saat dibutuhkan (lihat G08) |
| Audit admin tidak sah | Log mencatat aksi yang sebenarnya gagal → tidak bisa dipakai investigasi internal (G05) |
| Kebocoran data tanpa jejak | Ekspor CSV & download backup tidak tercatat (G06) |
| Debug lambat | Tanpa request ID, menelusuri satu komplain = mencari jarum di 47+ baris log tak berkonteks |
| Deployment berisiko | `APP_DEBUG=true` + log `single` tanpa rotasi + timezone UTC (G11) |

---

## 6. Usulan desain (target state)

Tiga lapis, semuanya saling terhubung lewat **satu `request_id`**.

```
Browser ──[X-Request-Id]──► Middleware RequestId ──► Log context
   │                              │
   │                              ├─► Layer 1: file log terstruktur (JSON, harian)
   └──[POST /api/client-logs]─────┤
                                  ├─► Layer 2: tabel event_logs (audit, bisa difilter di dashboard)
                                  └─► Layer 3: notifikasi (Slack) untuk level critical
```

### Layer 1 — File log terstruktur
- Channel baru `app` (driver `daily`, `days: 30`, format JSON) supaya bisa di-`grep`/di-parse.
- `stack` → `['app']` di produksi; `single` hanya untuk lokal.
- Semua log menyertakan konteks otomatis: `request_id`, `route`, `method`, `actor`, `ip`.
- Timezone log disamakan dengan `Asia/Jakarta` **atau** tetap UTC tapi setiap baris menyebut offset eksplisit.

### Layer 2 — Tabel `event_logs` (audit yang bisa dipercaya)
Tabel baru, bukan menumpang `activity_logs` (supaya data lama tetap utuh):

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | |
| `event` | string(80), index | `order.created`, `order.rejected`, `admin.login.failed`, `backup.failed`, `client.error` |
| `level` | string(10), index | `debug`/`info`/`warning`/`error`/`critical` |
| `status` | string(10), index | `success` / `failed` |
| `actor_type` | string(10) | `admin` / `customer` / `system` / `client` |
| `actor_id` | string(120), nullable | email admin, atau `phone_hash` customer |
| `subject_type` / `subject_id` | string / bigint, nullable | mis. `order` / `12` |
| `message` | text | ringkasan manusiawi (bahasa Indonesia) |
| `context` | json | payload terstruktur |
| `request_id` | uuid(36), index | penghubung ke Layer 1 |
| `ip_address` / `user_agent` | string / text | |
| `created_at` | timestamp, index | |

Dibaca lewat halaman admin **Log Sistem** dengan filter: level, event, tanggal, `request_id`, `subject_id`.

### Layer 3 — Error dari browser
- Endpoint `POST /api/client-logs` (`throttle:30,1`, payload divalidasi, maks 8 KB).
- Hook global di `catalog.js` & `admin.js`: `window.onerror`, `unhandledrejection`, dan pembungkus `fetch` untuk response non-OK.
- Yang dikirim: `message`, `stack` (dipotong), `url`, `ua`, `request_id` (dari header response terakhir), `app_version`.

### Konvensi penamaan event
`<domain>.<subjek>.<aksi>` — huruf kecil, titik sebagai pemisah:

`order.created` · `order.rejected` · `order.duplicate` · `quote.calculated` · `quote.failed` · `quote.fallback_used` · `product.created` · `product.updated` · `product.deleted` · `product.unavailable` · `settings.updated` · `admin.login.success` · `admin.login.failed` · `admin.login.throttled` · `admin.logout` · `data.exported` · `backup.started` · `backup.completed` · `backup.failed` · `backup.table_failed` · `client.error` · `http.server_error`

### Aturan level
| Level | Kapan |
|---|---|
| `debug` | detail teknis, hanya lokal |
| `info` | kejadian normal yang perlu jejak: order dibuat, login sukses, backup selesai |
| `warning` | ditolak aturan bisnis / anomali yang wajar: toko tutup, menu habis, ongkir gagal, login salah |
| `error` | kesalahan sistem: exception, API eksternal down, backup gagal |
| `critical` | butuh tindakan segera: DB mati, backup gagal berulang, error rate melonjak |

### Privasi (penting)
Jangan simpan mentah: nomor telepon, alamat lengkap, nama customer, isi catatan pesanan.
Gunakan: `phone_hash` (SHA-256 + salt dari `APP_KEY`), alamat dipotong ke `kecamatan/kota` saja, nama di-mask (`A***`). `password` dan `Authorization` header **tidak boleh** masuk log sama sekali.

---

## 7. Daftar event yang wajib dilog

| Event | Level | Status | Dipicu di | Konteks minimal |
|---|---|---|---|---|
| `order.created` | info | success | `OrderService::create()` setelah `insertGetId` | `order_id`, `code`, `total`, `service`, `items_count`, `quote_source`, `is_estimate` |
| `order.duplicate` | info | success | `OrderService.php:52` | `client_token`, `order_id` |
| `order.rejected` | warning | failed | `CheckoutController.php:27` | `reason_code`, `reason_message`, `service`, `items_count` (bukan PII) |
| `order.db_failed` | error | failed | `OrderService.php:145` | `sql_state`, `client_token` |
| `quote.calculated` | info | success | `DeliveryQuoteController` sukses | `fee`, `distance_m`, `source`, `is_estimate`, `duration_ms` |
| `quote.failed` | warning | failed | `DeliveryQuoteController.php:31` | `reason` |
| `quote.provider_error` | error | failed | `DeliveryQuoteService` (4 titik `Http::`) | `provider`, `status`, `duration_ms` |
| `quote.fallback_used` | warning | success | `DeliveryQuoteService` fallback | `reason` |
| `product.created/updated/deleted` | info | success | `ProductController` | `product_id`, `name`, `changed_fields` |
| `order.status_changed` | info | success | `OrderController::update` | `order_id`, `from`, `to` |
| `settings.updated` | info | success | `StoreSettingsController` | `changed_keys` |
| `admin.login.success` | info | success | `AuthController.php:35` | `email`, `ip` |
| `admin.login.failed` | warning | failed | `AuthController.php:41` | `email_attempted`, `ip` |
| `admin.login.throttled` | warning | failed | `throttle:5,1` / 429 | `ip`, `attempts` |
| `admin.logout` | info | success | `AuthController.php:50` | `email` |
| `data.exported` | info | success | `OrderController::export` | `rows`, `format` |
| `backup.started` | info | success | `BackupDatabase::handle()` awal | `trigger` (`web`/`cli`), `user` |
| `backup.completed` | info | success | `BackupDatabase.php:42` | `filename`, `size`, `tables`, `duration_ms` |
| `backup.failed` | **error** | failed | `BackupDatabase.php:38` + `web.php:46` | `reason`, `trigger` |
| `backup.table_failed` | error | failed | `BackupDatabase.php:107` | `table`, `exception` |
| `backup.purged` | info | success | `BackupDatabase.php:121` | `filename`, `age_days` |
| `client.error` | warning/error | failed | `POST /api/client-logs` | `message`, `stack` (dipotong), `url`, `ua`, `app_version` |
| `http.server_error` | error | failed | middleware HTTP | `method`, `path`, `status`, `duration_ms` |

---

## 8. Rencana implementasi (bertahap, bisa berhenti di fase mana pun)

### Fase 0 — Perbaikan cepat & bernilai tinggi · ±0,5 hari
Tidak butuh arsitektur baru, langsung menutup lubang paling berbahaya.

| # | Perubahan | File |
|---|---|---|
| 0.1 | Channel `app` (daily, JSON, 30 hari) + `stack` → `['app']`; `LOG_LEVEL=info` untuk produksi | `config/logging.php`, `.env`, `.env.example` |
| 0.2 | Isi `reportable()` dengan konteks global (route, method, user, IP) | `app/Exceptions/Handler.php` |
| 0.3 | Cek exit code backup; balas 500 + log `backup.failed` bila gagal | `routes/web.php` |
| 0.4 | Log `backup.failed` & `backup.table_failed` (jangan hanya komentar di `.sql`) | `Console/Commands/BackupDatabase.php` |
| 0.5 | Log `order.created` (info) & `order.rejected` (warning) | `Api/CheckoutController.php`, `Services/OrderService.php` |
| 0.6 | Log `admin.login.success/failed` | `Admin/AuthController.php` |
| 0.7 | Perbaiki `LogAdminActivity` agar mencatat status sukses/gagal | `Http/Middleware/LogAdminActivity.php` |

**Kriteria selesai:** buat order sukses & gagal → dua entri berbeda muncul di `storage/logs/app-YYYY-MM-DD.log`; matikan MySQL lalu klik "Backup Sekarang" → muncul pesan gagal + entri `backup.failed`.

### Fase 1 — Request ID & log HTTP · ±0,5 hari
Middleware `AssignRequestId` (header `X-Request-Id`, `Log::withContext`), middleware `LogHttpRequest` (method, path, status, durasi; 4xx → warning, 5xx → error; aset statis dikecualikan). **Kriteria selesai:** satu request menghasilkan beberapa baris log dengan `request_id` identik, dan ID itu terlihat di response header.

### Fase 2 — Tabel `event_logs` & audit yang benar · ±1 hari
Migrasi tabel, service `EventLogger` (`EventLogger::success('order.created', $order, [...])`), ganti `LogAdminActivity` menjadi pencatat berbasis status response, audit aksi GET sensitif (export, download backup, lihat detail order), halaman admin **Log Sistem** dengan filter. **Kriteria selesai:** admin gagal validasi → tercatat `status=failed`; admin berhasil → `status=success`; ekspor CSV tercatat.

### Fase 3 — Error dari browser · ±1 hari
Endpoint `POST /api/client-logs`, hook `window.onerror` + `unhandledrejection` + pembungkus `fetch` di `catalog.js` dan `admin.js`, kirim `request_id` terakhir. **Kriteria selesai:** `throw new Error('tes')` di console browser → muncul entri `client.error` di server dalam < 5 detik.

### Fase 4 — Operasional · ±0,5 hari
Notifikasi Slack untuk `critical`/`error` (channel `slack` sudah ada di config), retensi otomatis (`event_logs` > 90 hari dipangkas via command harian), dokumentasi `LOGGING.md` berisi konvensi event & cara investigasi.

**Total ±3,5 hari kerja.** Saran: kerjakan **Fase 0 lebih dulu** — 80% manfaat dengan 15% usaha.

---

## 9. Checklist verifikasi (dipakai setelah implementasi)

| # | Aksi | Yang harus muncul |
|---|---|---|
| 1 | Buka katalog | Log HTTP `200`, tidak ada error |
| 2 | Checkout tanpa alamat (layanan Antar) | `order.rejected` warning, `reason_code=address_required` |
| 3 | Set jam tutup toko ke masa lalu, checkout | `order.rejected` warning, `reason_code=store_closed` |
| 4 | Checkout normal | `order.created` info + `quote.calculated` info, `request_id` sama |
| 5 | Klik checkout 2× cepat | `order.duplicate` info, **tidak** ada 2 baris `order.created` |
| 6 | Kosongkan `ADMIN_PASSWORD`, login | `admin.login.failed` warning |
| 7 | Login 6× salah | `admin.login.throttled` warning + response 429 |
| 8 | Tambah menu dengan harga kosong | `product.created` **failed** (bukan success) |
| 9 | Tambah menu valid | `product.created` success + `activity_logs` terisi |
| 10 | Ekspor CSV pesanan | `data.exported` info dengan jumlah baris |
| 11 | Backup normal | `backup.completed` info berisi ukuran file |
| 12 | Rename folder `storage/app/backups` jadi read-only, backup | HTTP 500 + `backup.failed` error + **tidak** ada pesan "Backup selesai." |
| 13 | Matikan koneksi internet, hitung ongkir | `quote.provider_error` error, customer dapat fallback |
| 14 | `throw new Error('x')` di console | `client.error` warning di server |
| 15 | Akses `/halaman-tidak-ada` | Log HTTP `404` (warning) |

---

## 10. Yang perlu kamu putuskan sebelum saya mulai

1. **Tujuan penyimpanan** — cukup file + MySQL seperti usulan, atau langsung ke layanan eksternal (Sentry / Papertrail / Slack)? Config `slack` dan `papertrail` sudah tersedia tinggal diisi.
2. **Retensi** — usul saya: file 30 hari, `event_logs` 90 hari. Cukup?
3. **Privasi** — setuju nomor telepon & alamat disamarkan (hash/mask) di log? Kalau kamu butuh data mentah untuk investigasi, harus ada alasan kuat dan akses terbatas.
4. **Halaman admin "Log Sistem"** — perlu dibuat, atau cukup baca file log langsung?
5. **Notifikasi** — mau dikirimi Slack/email saat ada error `critical`?
6. **Mulai dari mana** — setuju kerjakan **Fase 0** dulu, lalu review sebelum lanjut?

## Status Implementasi

Selesai: **Fase 0–4** (±3,5 hari kerja).

**Gaps tertutup (G01–G14):**

| G## | Kejadian (audit) | Status |
|---|---|---|
| G01 | Semua log domain terstruktur (`event_logs`) | ✅ |
| G02 | `reportable()` handler terisi | ✅ |
| G03 | Gagal checkout, pembatalan toko, WA tidak diset → tercatat | ✅ |
| G04 | `order.created` tercatat saat sukses | ✅ |
| G05 | `activity_logs` mencatat status sukses/gagal | ✅ |
| G06 | Akses GET sensitif (`.export`, `show`, backup) → tercatat `admin.action.success` | ✅ |
| G07 | Login admin gagal/brute-force → `admin.login.failed/throttled` | ✅ |
| G08 | Backup gagal → `backup.failed` + HTTP 500 | ✅ |
| G09 | Error JavaScript browser → `client.error` | ✅ |
| G10 | Request ID & korelasi log (event.log + DB) | ✅ |
| G11 | Rotasi log (30 hari harian + `event_logs` > 90 hari) + timezone `Asia/Jakarta` | ✅ |
| G12 | Channel tambahan (`app` JSON) disiapkan & dipakai | ✅ |
| G13 | Error HTTP (≥400) → `http.client_error` / `http.server_error` | ✅ |
| G14 | Aktivitas admin `scopeLatest` diselesaikan | ✅ |

**Fitur logging yang aktif:**

- Dual-write (`app.log` + `event_logs`)
- PII masker/hashed (`EventLogger.filterContext`)
- `AssignRequestId` + `LogHttpRequest` middleware
- Mengatur halaman admin **Log Sistem** dengan filter
- Penjadwal log: `log:purge` (hapus > 90 hari)
- Dokumen teknis: `LOGGING-IMPLEMENTATION.md` + `LOGGING.md`

**Pengujian:**

- Ditambahkan `EventLogTest` + `AdminActivityLogTest`
- Dummy attribute `updated_at = null` pada `EventLog` dan `ActivityLog` ✅

Semua event yang diwajibkan (§7) ditutup.

---

## 11. Lampiran — file yang diaudit

```
config/logging.php          config/app.php            .env / .env.example
app/Exceptions/Handler.php  app/Http/Kernel.php
Http/Middleware/LogAdminActivity.php
routes/web.php  routes/api.php  routes/console.php
Admin/AuthController.php    Admin/ProductController.php
Admin/OrderController.php   Admin/BackupController.php
Admin/ActivityLogController.php  Admin/StoreSettingsController.php
Api/CheckoutController.php  Api/DeliveryQuoteController.php
Api/OrderLookupController.php
Services/OrderService.php   Services/DeliveryQuoteService.php
Services/AdminAuthService.php  Services/ProductImageService.php
Console/Commands/BackupDatabase.php
Models/ActivityLog.php
public/assets/catalog/catalog.js   public/assets/admin/admin.js
database/migrations/2026_10_30_000001_create_activity_logs_table.php
storage/logs/laravel.log
tests/Feature/*.php (13 file)
```

---

### Ringkasan satu paragraf

Logging aplikasi ini masih di tahap "kalau error, Laravel yang nulis". Tidak ada log sukses, tidak ada log kegagalan bisnis, tidak ada log dari browser, tidak ada korelasi antar-kejadian, dan tabel `activity_logs` yang ada pun mencatat aksi gagal sebagai berhasil. Dokumen ini memetakan 20 titik (G01–G14 plus matriks §2) dan mengusulkan perbaikan bertahap ±3,5 hari, dimulai dari Fase 0 (±0,5 hari) yang sudah menutup lubang paling berbahaya termasuk bug backup yang bisa membuat kamu kehilangan data tanpa sadar.
