# Delivery Address Autocomplete Plan
Dibuat: 2026-10-30  
Kontekst: Nominatim OSM `limit=1` gagal pada alamat hampir lengkap. User stop menulis di tengah jalan.

## Problem Statement
Saat ini geocoder cuma ambil **1 hasil terbaik** dari Nominatim. Jika alamat kurang tepat (typo, format setengah, nama jalan pendek), Nominatim return `[]` → service lempar `Alamat tidak ditemukan`. User Bingung, checkout drop-off tinggi.

Bukti kasus: `"Jalan Keadilan selatan 5 NE 18 bandung"` — kurang lengkap, typo case, Nominatim gagal match.

## Target
1. Saat geocode gagal, tampilkan **suggestion list** (dropdown/accordion) berisi 3-5 alamat yang *mirip* dari Nominatim.
2. User pilih salah satu → sistem lanjut hitung ongkir dengan alamat terpilih.
3. Jika tetap tidak ada, tampilkan error yang lebih helpful (contoh format alamat yang benar).

## Opsi Geocoder (banding)

| Opsi | Biaya | Rate limit | Kelebihan | Kekurangan |
|------|-------|-----------|-----------|-----------|
| **Nominatim OSM** (saat ini) | Gratis | 1 req/detik, policy open | Gratis, tidak perlu API key | Rate limit ketat, kadang gagal untuk alamat ID, butuh identitas app |
| **Nominatim OSM dengan `limit=5` + fuzzy** | Gratis | Sama | Cuma ganti `limit` + normalisasi query | Masih rate limit, tidak ada autocomplete while typing |
| **OpenCage Geocoder** | Free tier 2.500 req/hari | 25 req/detik | Mix OSM + data lain, lebih stabil untuk ID | Butuh API key, free tier ada limit |
| **Mapbox Geocoding** | Free tier 100.000 req/bulan | Tinggi | Akurat di ID, fast, autocomplete API khusus | Butuh API key + kartu, berbayar melebihi limit |
| **Google Places API** | $200 credit/bulan | Tinggi | Autocomplete class, akurat, reverse geocode | Butuh API key + kartu, rumit setup, overkill untuk skala kecil |
| **Database alamat lokal** | Gratis (jika punya data) | Unlimited | Offline, cepat, bisa custom | Perlu import data jalan Bandung, maintenance |

## Rekomendasi: Hybrid Approach (tanpa API key baru)

### Phase 1 — Quick Fix (0 cost, 1-2 jam)
**Ubah `limit=1` jadi `limit=5` + normalisasi query sebelum kirim ke Nominatim.**

Normalisasi yang diterapkan:
- Lowercase + squish (sudah ada).
- Hapus kata sambung yang ambigu: `"selatan"`, `"utara"`, `"timur"`, `"barat"` kecuali setelah nama jalan.
- Map typo umum: `"Keadilan"` → `"Keadilan"` (tidak diubah, tapi trim).
- Prioritaskan query tanpa RT/RW/WORDA jika hasil < 2: retry tanpa nomor rumah.

Flow:
1. Geocode gagal (`limit=5` return `[]`):
   - Retry 1x dengan query tanpa nomor rumah.
   - Jika masih gagal, lempar error dengan **saran format**.
2. Geocode sukses (`limit=5` return array):
   - Jika `count === 1` → lanjut hitung ongkir (existing behavior).
   - Jika `count > 1` → return list suggestion ke frontend.

### Phase 2 — Suggestion UI (frontend)
Tempat: `catalog.blade.php` + `catalog.js`

- Tambah container `#dq-suggestions` di bawah form alamat.
- Saat API return `suggestions` (array max 5), render `<ul>` dengan `display_name` dari Nominatim.
- Setiap item punya button `Pilih alamat ini`.
- Klik → isi textarea alamat dengan `display_name` yang dipilih + trigger hitung ongkir ulang.
- Jika user ketik manual & suggestion muncul, keyboard bisa navigate (arrow keys + enter) — nice-to-have.

### Phase 3 — Fallback Helpful Error
Jika Nominatim return `[]` setelah retry:
```
Alamat tidak ditemukan. Coba:
- Tulis nama jalan + kelurahan + kecamatan + kota
- Hindari singkatan (contoh: "Bandung" bukan "Bdg")
- Gunakan nomor rumah jika ada
```

## Backend Changes

### `DeliveryQuoteService::quote()`
- Ubah `limit` dari `1` jadi `5`.
- Tambah method `normalizeAddress(string): string` untuk bersih query.
- Tambah retry logic: jika hasil `[]`, retry tanpa nomor rumah (regex hapus `\b\d+\b` di awal).
- Return structure baru:
  ```php
  return [
      'quote_id' => ...,
      'fee' => ...,
      'destination_label' => ...,
      'expires_at' => ...,
      'suggestions' => [ // baru, opsional
          ['label' => 'Jalan ...', 'lat' => ..., 'lon' => ...],
          ...
      ],
  ];
  ```

### `DeliveryQuoteController::__invoke()`
- Terima field `selected_lat/lon` opsional dari frontend.
- Jika user pilih suggestion, pakai `lat/lon` itu untuk route (skip geocode ulang).

### `routes/api.php`
- Tidak perlu tambah route, pakai endpoint existing `POST /api/delivery-quote`.

## Frontend Changes

### `catalog.blade.php`
- Tambah `#dq-suggestions` container.

### `catalog.js`
- Ubah handler `handleDeliveryQuote`:
  - Jika `data.suggestions?.length`, render suggestion UI.
  - Tambah `selectSuggestion(lat, lon, label)` function.
- Tambah helper `normalizeAddressInput()` sebelum submit (trim, lowercase opsional untuk UX).

### `catalog.css`
- Tambah style `.dq-suggestions`, `.dq-suggestion-item`, `.dq-suggestion-active`.

## Edge Cases
- **Nominatim rate limit**: throttle 1 detik sudah ada (`Cache::add`), tetap pertahankan. Jika limit tercapai, lempar error "Layanan pencarian alamat sedang sibuk".
- **User pilih suggestion yang ternyata salah**: reset deliveryQuote + sembunyikan kartu, sama seperti close cart.
- **Alamat di luar jangkauan**: tetap lempar error existing, suggestion tidak perlu.
- **Offline / Nominatim down**: error message yang jelas, tanpa suggestion.

## Rollback / Future Upgrade
- Jangan hapus Nominatim dulu. Phase 1 ini reversible.
- Jika nanti butuh akurasi lebih tinggi, tinggal ganti method `geocode()` jadi panggil Mapbox/Google, tanpa ubah frontend (karena frontend cuma consume `suggestions` array).

## File Terkait
- `app/Services/DeliveryQuoteService.php` — geocode, normalize, suggestion list
- `app/Http/Controllers/Api/DeliveryQuoteController.php` — handle suggestion selection
- `public/assets/catalog/catalog.js` — render suggestion UI, selectSuggestion()
- `resources/views/catalog.blade.php` — container #dq-suggestions
- `public/assets/catalog/catalog.css` — style suggestion dropdown
