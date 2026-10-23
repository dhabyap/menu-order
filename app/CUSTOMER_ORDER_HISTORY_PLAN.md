# Plan: Riwayat & Status Pesanan Pembeli (Cek Pesanan)

> Dokumen ini dibuat untuk dikerjakan oleh programmer junior.
> Baca seluruh dokumen sebelum menulis kode.

## 1. Masalah

Saat ini pembeli **tidak bisa mengecek pesanan** setelah checkout.
Pesanan tersimpan di database (tabel `orders`) dan dilihat hanya lewat dashboard `/admin`.

Pembeli perlu tahu:
- **Pesanan yang sedang dipesan/diproses** (status `pending` / `processing`)
- **Riwayat pesanan** (status `completed` / `cancelled`)
- Total, kode, waktu, dan status tiap pesanan

## 2. Keputusan Desain (sudah diputuskan — jangan diganti)

Tidak ada akun/login pembeli. Kunci identitas pembeli = **nomor WhatsApp** (field `phone`
di tabel `orders`, format tersimpan `08…` karena sudah dinormalisasi saat checkout).

Solusi: halaman/drawer **"Cek Pesanan"** — pembeli memasukkan nomor WhatsApp-nya,
sistem menampilkan pesanan milik nomor itu saja.

Trade-off keamanan yang diterima (kedai kecil):
- Tanpa login, siapa pun yang tahu nomor bisa melihat pesanan nomor itu.
- Dibatasi dengan rate limiting (sudah ada pola `throttle:10,1` di project ini)
  dan response hanya memuat field yang perlu (tanpa `address`, tanpa `note`).

## 3. Arsitekturnya sekarang (baca dulu)

| Bagian | Lokasi |
|---|---|
| API publik | `app/routes/api.php` — `GET /api/store-config`, `GET /api/products`, `POST /api/orders` (throttle:10,1) |
| Checkout | `app/app/Http/Controllers/Api/CheckoutController.php` → `app/app/Services/OrderService.php` |
| Validasi telepon | `app/app/Http/Requests/StoreOrderRequest.php` → regex `^(?:\+?62\|0)8[1-9][0-9]{7,11}$` |
| Status pesanan | `app/app/Support/OrderStatus.php` → `pending` (Menunggu), `processing` (Diproses), `completed` (Selesai), `cancelled` (Dibatalkan) |
| Frontend statis | `app/public/index.html`, `app/public/assets/catalog/catalog.js`, `app/public/assets/catalog/catalog.css` |
| Pola drawer | Fungsi `openLayer()` / `closeLayer()` di `catalog.js` — drawer `cartDrawer` adalah contoh kerja |

Aturan frontend yang **tidak boleh dilanggar**:
- Jangan ubah/hapus ID elemen yang sudah ada (banyak kode yang `getElementById` langsung).
- Fungsi helper yang sudah ada: `toast(msg)`, `esc()`, `openLayer()`, `closeLayer()`,
  `normalizeIndonesianPhone(value)`, `fmt(n)` — **pakai, jangan tulis ulang**.
- Cart disimpan di localStorage `racik_cart_v3`, profil di `racik_prof_v2`.

## 4. Tugas

### Tugas A — Endpoint lookup pesanan (backend)

Buat controller baru `app/app/Http/Controllers/Api/OrderLookupController.php`
(jangan taruh di `CheckoutController`, itu khusus checkout).

Daftarkan di `app/routes/api.php`:

```php
Route::post('/orders/lookup', [OrderLookupController::class, 'show'])
    ->middleware('throttle:10,1');
```

Gunakan `POST` (bukan GET) supaya nomor telepon tidak menempel di URL/history browser.

Spesifikasi endpoint `POST /api/orders/lookup`:

Request JSON:
```json
{ "phone": "081234567890" }
```

Validasi: `phone` wajib, regex sama persis dengan `StoreOrderRequest`
(`/^(?:\+?62|0)8[1-9][0-9]{7,11}$/`). Gagal validasi → 422 standar Laravel.

Normalisasi sebelum query (buang `+`/`62` di depan jadi `0`, pakai logika yang sama
dengan `normalizeIndonesianPhone` di frontend). Contoh: `6281234567890` → `081234567890`.

Karena format tersimpan bisa bermacam-macam (tes lama memakai `628…`), query dengan
semua varian:

```php
$variants = [$normalized, '62'.substr($normalized, 1)];
```

Response 200:
```json
{
  "orders": [
    {
      "code": "RT-261022-A1IZW",
      "service": "Ambil Sendiri",
      "status": "processing",
      "status_label": "Diproses",
      "total": 7000,
      "created_at": "2026-10-22 13:45:00",
      "items": [
        { "name": "Teh Melati", "quantity": 2, "options": { "Ukuran": "Large" } }
      ]
    }
  ]
}
```

Aturan response:
- Pesanan **desc** (terbaru dulu), batasi maksimal **50 baris**.
- Field yang boleh: `code`, `service`, `status`, `status_label`, `total`,
  `created_at`, `items` (dari kolom JSON `items` — `name`, `quantity`, `options` saja).
- Field yang **dilarang** muncul: `address`, `note`, `phone`, `customer_name`.
- `status_label` wajib dari `OrderStatus::label()` — jangan hardcode string.
- Nomor tidak ditemukan → tetap 200, `orders: []` (bukan error, agar tidak jadi
  alat cek keberadaan nomor).
- Belum ada pesanan untuk nomor itu → 200 `orders: []`.

### Tugas B — UI "Cek Pesanan" (frontend)

1. `app/public/index.html`:
   - Tambah link di nav header (`.site-nav`): `<a href="#" id="navOrderHistory">Cek Pesanan</a>`.
   - Tambah drawer baru setelah `<aside id="cartDrawer">`, ikuti persis pola drawer cart
     (`class="drawer"`, atribut `aria-*`, `inert`, backdrop sendiri `historyBackdrop`).
   - ID elemen wajib: `historyDrawer`, `btnCloseHistory`, `historyBackdrop`,
     `historyPhone` (input tel), `btnFindHistory`, `historyRefresh` (tombol muat ulang),
     `historyList` (wadah daftar), `historyHint` (teks status/error).

2. `app/public/assets/catalog/catalog.js`:
   - Daftarkan drawer ke pola layer: `openLayer(historyDrawer)` / `closeLayer(historyDrawer)`,
     sama seperti `cartDrawer` (lihat objek `closeHandlers` / pemakaian `openLayer`).
   - `navOrderHistory` → buka drawer, isi `historyPhone` dari `profile.phone` bila ada,
     lalu langsung jalankan pencarian bila profil sudah punya nomor.
   - `btnFindHistory` → validasi pakai `normalizeIndonesianPhone()`; kalau null,
     tampilkan pesan di `historyHint` (jangan pakai `alert`).
   - Fetch `POST /api/orders/lookup` dengan header
     `{"Content-Type":"application/json","Accept":"application/json"}`.
   - Render ke `historyList`, **dua kelompok**:
     - **Sedang diproses**: status `pending` + `processing`
     - **Riwayat**: status `completed` + `cancelled`
   - Tiap kartu pesanan tampilkan: kode, waktu, layanan, badge status
     (label dari field `status_label`), daftar item (`quantity`× `name` + opsi), total (`fmt()`).
   - Tombol `historyRefresh` → ulangi pencarian terakhir.
   - Pesan kosong: "Belum ada pesanan untuk nomor ini."
   - Wajib `esc()` untuk semua data server saat dimasukkan ke HTML.

3. `app/public/assets/catalog/catalog.css`:
   - Styling drawer riwayat mengikuti drawer cart yang sudah ada.
   - Badge status — samakan warna dengan admin (`admin.css`):
     - `pending` → emas (`#faeedd` bg / `#80581f` teks)
     - `processing` → biru (`#e8eef2` bg / `#33556b` teks)
     - `completed` → hijau (`var(--green-light)` / `var(--green)`)
     - `cancelled` → merah (`var(--rose-light)` / `var(--rose)`)
   - Wajib lolos di lebar 320px tanpa scroll horizontal (uji di DevTools).

### Tugas C — Test (wajib)

Buat `app/tests/Feature/OrderLookupTest.php`, pola lihat
`app/tests/Feature/AdminOrderProcessingTest.php` (pakai `RefreshDatabase`,
seeding row `orders` langsung via `DB::table('orders')->insertGetId()`).

Test wajib:
1. Nomor ditemukan → 200 dan berisi `code`, `status_label`.
2. Nomor pakai format `628…` tetap menemukan pesanan tersimpan `08…`.
3. Nomor tidak dikenal → 200 dengan `orders: []`.
4. Telepon tidak valid (mis. `12345`) → 422.
5. Field terlarang tidak bocor: `assertJsonMissing` untuk nilai `address` dan `customer_name`.
6. Guest tetap bisa akses (endpoint publik, tanpa login).

Jalankan: `php artisan test` dari folder `app\`. Semua harus hijau,
termasuk 35 test lama (jangan sampai ada yang rusak).

## 5. Acceptance criteria

- [ ] Pembeli klik **Cek Pesanan** di header → drawer terbuka.
- [ ] Masukkan nomor WhatsApp → tampil pesanan milik nomor itu saja.
- [ ] Pesanan `pending`/`processing` muncul di kelompok **Sedang diproses**.
- [ ] Pesanan `completed`/`cancelled` muncul di kelompok **Riwayat**.
- [ ] Nomor salah/kosong → pesan ramah, bukan crash/halusinasi data.
- [ ] Tidak ada data contoh/demo yang ditambahkan.
- [ ] `php artisan test` = semua lolos.
- [ ] Tidak ada scroll horizontal di 320px.

## 6. Batasan

- Jangan sentuh `app/WEBSITE_POLISH_PLAN.md` dan `app/IMAGE_UPLOAD_IMPROVEMENT.md`
  (jika file itu ada) — jangan di-commit.
- Jangan ubah perilaku checkout yang sudah jalan.
- Jangan tambah login pembeli/akun baru.
- **Jangan commit/push tanpa instruksi eksplisit dari user.**

## 7. Urutan kerja yang disarankan

1. Tugas A + Tugas C backend dulu → `php artisan test` hijau.
2. Tugas B frontend → uji manual di browser (`php artisan serve`).
3. Lapor ke user, tunggu aba-aba push.
