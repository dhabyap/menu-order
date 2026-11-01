# Desain Ongkir: Alamat Tidak Lengkap, Multi-Provider, dan Tarif Dasar

> **Status:** dokumen desain (revisi 2026-11-01). Menggantikan versi sebelumnya.
> **Ruang lingkup:** perhitungan ongkir antar, fallback provider, penanganan alamat tidak lengkap, dan tampilan estimasi di checkout.
> **Prinsip utama:** jangan pernah menebak ongkir tanpa jejak. Tarif dasar adalah **estimasi**, bukan harga final, dan harus tercatat di pesanan.

---

## 0. TL;DR

1. **Alamat tidak lengkap bukan alasan untuk langsung memakai tarif dasar.** Urutannya: validasi input -> autocomplete wajib pilih -> pin lokasi (opsional) -> estimasi tarif dasar dengan peringatan eksplisit.
2. **Titik koordinat tidak wajib untuk customer.** Koordinat adalah jalur akurat, bukan syarat. Customer cukup memilih saran alamat; pin lokasi ditawarkan sebagai opsi.
3. **Fallback harus diputuskan server, bukan client.** Saat ini `fallback: true` dikirim dari browser dan langsung dipercaya server. Ini bisa dipakai untuk melewati pengecekan jarak.
4. **`store_settings` adalah tabel key-value**, bukan tabel berkolom. Konfigurasi baru ditambahkan sebagai baris, bukan kolom.
5. **`delivery_fee` sudah menjadi tarif dasar.** Jangan menambah `delivery_base_fee` yang menduplikasi makna.
6. **Routing juga bisa gagal**, bukan hanya geocoding. Jika Nominatim berhasil tetapi OSRM gagal, saat ini seluruh perhitungan gagal. Perlu fallback routing atau estimasi garis lurus.
7. **Tiga bug P0 jalur koordinat sudah diperbaiki:** import `DB` hilang, pemanggilan method private, dan `quote_id` berupa UUID acak (bukan payload terenkripsi). Retry Nominatim yang tertahan lock sendiri juga sudah diperbaiki. Lihat 2.3-2.4.

---

## 1. Koreksi terhadap dokumen sebelumnya

| Klaim lama | Fakta di kode | Tindakan |
|---|---|---|
| Tambah kolom `geocode_provider_primary`, `delivery_base_fee`, dst. ke `store_settings` | `store_settings` hanya punya `key`, `value`, `created_at`, `updated_at` | Tambah **baris** config, bukan kolom |
| Config `delivery_base_fee` default 5000 | Sudah ada `delivery_fee` yang dipakai sebagai tarif dasar | Pakai `delivery_fee`; jangan duplikasi |
| Config `delivery_base_fee_radius_km` | Tidak ada dan tidak dipakai | Hapus dari desain, atau definisikan ulang dengan jelas |
| Nominatim bisa lebih dari 1 req/s dengan User-Agent custom | Kebijakan Nominatim tetap ~1 req/s; UA custom hanya syarat identitas | Perbaiki klaim; tambah token bucket |
| OpenCage gratis 5000 req/bulan | Kuota berubah-ubah | Jangan hardcode angka; cek dashboard provider |
| Mapbox gratis 100k tiles/bulan | Kuota geocoding berbeda dari tiles | Pisahkan kuota geocoding vs tiles |
| "Cognito Map" | Bukan produk geocoding yang relevan | Hapus; ganti Google Maps/Places jika perlu |
| `distance_km = 0` saat fallback | 0 km menyesatkan di UI | Gunakan `null` untuk jarak tidak diketahui |
| Semua provider gagal -> tarif dasar otomatis | Saat ini fallback hanya dipicu tombol client | Fallback harus diputuskan server + dicatat |
| Peringatan cukup di UI | Pesanan tidak menyimpan status estimasi | Simpan `is_estimate` dan `quote_source` di order |
| Hanya geocoding yang perlu fallback | Routing OSRM juga titik gagal | Tambah fallback routing/estimasi |

---

## 2. Kondisi saat ini (as-is)

### 2.1 Alur yang sudah ada

```
Customer isi alamat
   -> POST /api/delivery-quote { address }
   -> DeliveryQuoteService::quote()
      -> cek cache geocode (7 hari)
      -> Nominatim search (global lock 3 detik)
      -> OSRM route (cache 12 jam)
      -> hitung fee = delivery_fee + ceil(billable_km * delivery_fee_per_km)
   -> response { quote_id, distance_meters, distance_km, fee, destination_label, expires_at, suggestions? }
   -> customer centang konfirmasi
   -> checkout kirim delivery_quote_id
   -> OrderService::validateQuote() cek expiry + hash alamat
   -> simpan order + snapshot ongkir
```

Fallback yang sudah ada:

```
Jika Nominatim tidak menemukan alamat
   -> UI menampilkan tombol "Gunakan tarif dasar"
   -> POST /api/delivery-quote { address, fallback: true }
   -> service mengembalikan fee = delivery_fee, distance = 0
```

### 2.2 Yang sudah benar

- `quote_id` dienkripsi dan berisi `address_hash` + `expires_at`, sehingga quote tidak bisa dipalsukan sembarangan.
- Ada cache geocode dan route untuk mengurangi panggilan provider.
- Ada batas `delivery_max_km` untuk menolak alamat di luar jangkauan.
- Ada snapshot `delivery_distance_meters`, `delivery_base_fee`, `delivery_variable_fee` di order.
- Checkout mewajibkan checkbox konfirmasi ongkir saat layanan antar.
- Ada throttle API `10,1` untuk endpoint quote.

### 2.3 Celah dan bug

| # | Masalah | Dampak | Prioritas | Status |
|---|---|---|---|---|
| 1 | `DB::` tanpa import di `DeliveryQuoteController` | Jalur koordinat selalu 503 | P0 | **Diperbaiki** |
| 2 | `coordinate()`/`route()` private dipanggil controller | Jalur koordinat error 500 | P0 | **Diperbaiki** |
| 3 | Quote koordinat mengembalikan UUID acak | Checkout dengan quote koordinat pasti gagal validasi | P0 | **Diperbaiki** |
| 4 | Retry Nominatim kedua tertahan lock 3 detik | Alamat yang butuh retry selalu "sibuk"; tombol fallback tidak muncul | P0 | **Diperbaiki** |
| 5 | Lock global 3 detik (`Cache::add`) | Dua customer menghitung bersamaan -> "sibuk" | P0 | Belum |
| 6 | `fallback: true` dipercaya mentah dari client | Bisa melewati cek jarak / maksud bisnis | P0 | Belum |
| 7 | Fallback tidak mengembalikan `warning` / `is_base_fee` | UI hanya teks kecil, tidak tegas | P0 | Belum |
| 8 | `distance_km = 0` saat fallback | Terlihat seperti jarak nol | P0 | Belum |
| 9 | Hanya Nominatim; tanpa provider cadangan | Satu gangguan = ongkir mati | P1 | Belum |
| 10 | Routing OSRM tanpa cadangan | Geocode sukses + route gagal = gagal total | P1 | Belum |
| 11 | Suggestions hanya dirender saat error | Alamat ambigu diam-diam memakai hasil pertama | P1 | Belum |
| 12 | Selalu menambahkan ", Kota Bandung" | Alamat di Kabupaten Bandung/Cimahi bisa salah | P1 | Belum |
| 13 | `validateQuote` tidak menghitung ulang harga | Perubahan tarif bisa lolos dengan quote lama | P1 | Belum |
| 14 | Tidak ada `quote_source` / `is_estimate` di order | Admin tidak tahu ongkir final atau estimasi | P1 | Belum |
| 15 | API key provider (jika ditambah) berpotensi plaintext | Risiko kebocoran | P1 | Belum |
| 16 | OSRM demo publik dipakai di produksi | Tidak stabil, melanggar kebijakan penggunaan | P1 | Belum |

### 2.4 Perbaikan yang sudah dilakukan pada revisi ini

- `DeliveryQuoteController` sekarang memanggil `DeliveryQuoteService::quoteForCoordinates()`; tidak lagi menduplikasi logika harga dan tidak memanggil method private.
- `quoteForCoordinates()` mengembalikan `quote_id` terenkripsi yang sama formatnya dengan jalur alamat, sehingga bisa divalidasi saat checkout.
- Perhitungan harga dipusatkan di `priceQuote()` dan dipakai oleh jalur alamat maupun koordinat.
- Retry Nominatim memakai `skipRateLimit: true` agar tidak tertahan lock yang dipasang panggilan pertama.
- Response sukses sekarang menyertakan `is_estimate` dan `quote_source` (jalur koordinat: `coordinates+osrm`, jalur alamat: `nominatim+osrm`).

---

## 3. Keputusan desain

1. **Server adalah sumber kebenaran.** Client hanya mengirim alamat/koordinat; server menentukan apakah quote valid, apakah estimasi, dan berapa fee.
2. **Dua jalur akurasi:**
   - **Akurat:** customer memilih saran alamat (lat/lon) -> geocode -> route.
   - **Estimasi:** tidak ada koordinat valid -> tarif dasar atau estimasi garis lurus, dengan label jelas.
3. **Jangan memaksa koordinat.** Kebanyakan customer tidak akan drop pin. Sediakan, tapi jangan wajibkan.
4. **Semua fallback harus terlihat.** Tidak ada ongkir "diam-diam" memakai tarif dasar.
5. **Simpan jejak.** Setiap order menyimpan sumber quote dan status estimasi.
6. **Fail open terbatas, bukan fail silent.** Jika provider mati, customer tetap bisa memesan dengan estimasi + konfirmasi, bukan diarahkan ke WhatsApp tanpa ongkir.
7. **Hemat biaya.** Provider berbayar hanya dipanggil jika provider gratis gagal, dan hasilnya di-cache.

---

## 4. Alamat tidak lengkap: solusi bertingkat

Ini jawaban langsung untuk pertanyaan: **"Kalau client menulis alamat tidak lengkap, bagaimana hitung ongkir? Harus titik koordinat?"**

Jawaban singkat: **tidak harus titik koordinat.** Gunakan tangga berikut, dari yang paling akurat ke paling longgar.

### Level 0 - Validasi input

- Minimal: nama jalan/patokan + kelurahan/kecamatan + kota.
- Tolak input yang hanya "rumah", "dekat masjid", atau satu kata.
- Tampilkan contoh format: `Jl. Karang Layung Dalam No. 11, Cigadung, Bandung`.
- Jangan menambahkan kota secara paksa jika customer sudah menyebut kabupaten/kota lain.

### Level 1 - Autocomplete wajib pilih (disarankan)

- Saat customer mengetik, tampilkan 3-5 saran alamat dari provider.
- **Jika customer memilih saran**, simpan `lat`/`lon` dan label. Ini jalur akurat.
- **Jika customer tidak memilih** dan langsung menekan "Hitung ongkir":
  - Tetap boleh hitung, tetapi tandai `is_estimate = true` dan tampilkan peringatan.
  - Jangan diam-diam memakai saran pertama.
- Jika hasil geocode lebih dari satu dan confidence rendah, **wajib** tampilkan pilihan, jangan pilih otomatis.

### Level 2 - Pin lokasi / lokasi saat ini (opsional)

- Tombol **"Gunakan lokasi saya"** memakai browser Geolocation API.
- Tombol **"Pilih di peta"** membuka peta kecil (Leaflet + OSM) untuk drop pin.
- Setelah pin didapat, lakukan **reverse geocode** untuk mengisi alamat, lalu minta customer mengonfirmasi.
- Simpan `lat`/`lon` + label hasil reverse geocode.
- Ini solusi terbaik untuk alamat informal ("belakang warung", "gang sebelah musala"), tetapi **jangan wajibkan**.

### Level 3 - Estimasi tarif dasar (fallback terakhir)

- Dipakai hanya jika:
  - provider geocoding gagal total, **atau**
  - alamat tidak bisa di-geocode, **atau**
  - routing gagal dan estimasi garis lurus tidak tersedia.
- Tampilkan box peringatan amber yang tegas.
- Wajib centang konfirmasi terpisah: "Saya mengerti ongkir ini estimasi dan bisa dikonfirmasi kurir."
- Tandai order `delivery_is_estimate = true`, `delivery_quote_source = 'base_fee'`.
- Jangan klaim "berlaku jika Anda dalam radius 5 km" jika kita tidak tahu jaraknya. Kalimat yang benar:
  > "Alamat belum terverifikasi. Ongkir Rp5.000 adalah estimasi awal. Kurir akan mengonfirmasi biaya final sebelum pengantaran."

### Level 4 - Jalur manual (tanpa kode)

Untuk kasus ekstrem, sediakan opsi:

- Customer mengirim **location pin WhatsApp** ke toko.
- Admin menghitung/mengonfirmasi manual, lalu memberi tahu total final.
- Pesanan tetap tersimpan dengan status "menunggu konfirmasi ongkir".

### 4.1 Matriks keputusan

| Kondisi input | Aksi server | Label UI | Checkout |
|---|---|---|---|
| Customer memilih saran + ada nomor rumah | Route akurat | `± X km` | Boleh, checkbox normal |
| Customer memilih saran tanpa nomor rumah | Route ke jalan | `± X km (patokan)` | Boleh, minta patokan |
| Free text, geocode confidence tinggi | Route akurat | `± X km` | Boleh |
| Free text, geocode ambigu | Kembalikan `suggestions`, jangan pilih otomatis | "Pilih alamat" | Tahan sampai pilih |
| Free text, tidak ditemukan | Tawarkan tarif dasar | "Estimasi tarif dasar" | Boleh dengan konfirmasi ekstra |
| Geocode sukses, route gagal | Estimasi garis lurus x faktor jalan | `± X km (estimasi)` | Boleh dengan konfirmasi ekstra |
| Semua provider gagal | Tarif dasar + warning | "Estimasi tarif dasar" | Boleh dengan konfirmasi ekstra |
| Di luar `delivery_max_km` | Tolak | "Di luar jangkauan" | Tidak boleh; arahkan WhatsApp |
| Alamat kosong / terlalu pendek | Tolak | "Alamat belum lengkap" | Tidak boleh |

### 4.2 Yang tidak boleh dilakukan

- **Jangan** memakai koordinat mentah tanpa reverse geocode; customer tidak tahu apakah pin-nya benar.
- **Jangan** menganggap semua alamat tidak lengkap berada dalam radius 5 km.
- **Jangan** langsung menyimpan order dengan ongkir final jika masih estimasi.
- **Jangan** membiarkan client mengirim `fallback: true` untuk melewati aturan jarak.
- **Jangan** menambahkan ", Kota Bandung" secara buta; gunakan `viewbox`/`bounded` di sekitar toko.

---

## 5. Arsitektur provider

### 5.1 Geocoding

| Provider | Auth | Kelebihan | Keterbatasan | Peran |
|---|---|---|---|---|
| Nominatim (OSM) | Tidak | Gratis, cukup baik untuk jalan utama | ~1 req/s, UA wajib, bukan untuk beban tinggi | Primer |
| OpenCage | API key | Stabil, data lengkap, ada confidence | Kuota gratis berubah; cek dashboard | Fallback 1 |
| Mapbox Geocoding | Access token | Akurat, autocomplete bagus | Kuota & harga berubah | Fallback 2 |
| Google Geocoding/Places | API key + billing | Akurasi alamat Indonesia terbaik | Berbayar | Opsional |
| LocationIQ | API key | API mirip Nominatim | Kuota gratis terbatas | Alternatif |

Catatan:
- **Jangan hardcode kuota** di dokumen/kode. Simpan sebagai catatan operasional dan cek ulang saat implementasi.
- Untuk Indonesia, tambahkan `countrycodes=id`, `accept-language=id`, dan `viewbox` di sekitar toko (mis. ±0.25 derajat) dengan `bounded=1` agar hasil tidak melompat ke kota lain.
- Simpan `confidence`/`relevance`/`importance` dari provider jika tersedia. Jangan menganggap `importance` Nominatim sebagai probabilitas.

### 5.2 Routing

| Provider | Auth | Catatan |
|---|---|---|
| OSRM demo publik | Tidak | **Hanya untuk uji**, jangan produksi |
| OSRM self-hosted | Tidak | Pilihan terbaik jika ada server |
| OpenRouteService | API key | Kuota gratis terbatas |
| GraphHopper | API key | Berbayar/kuota |
| Mapbox Directions | Access token | Satu ekosistem dengan geocoding |

Jika routing gagal tetapi geocode berhasil:
- Hitung **Haversine** (garis lurus) x faktor jalan (mis. 1.3).
- Tandai `is_estimate = true`, `quote_source = 'haversine'`.
- Tampilkan "estimasi" di UI.

### 5.3 Rantai fallback

```
Geocoding:
  Nominatim -> OpenCage (jika key) -> Mapbox (jika token) -> Google (jika diaktifkan)
  -> jika semua gagal: base fee estimate

Routing:
  OSRM -> OpenRouteService -> Mapbox Directions
  -> jika semua gagal: Haversine estimate
  -> jika koordinat tidak ada: base fee estimate
```

Aturan:
- Hentikan rantai saat ada hasil valid.
- Jangan panggil provider berbayar jika provider gratis berhasil.
- Catat provider yang dipakai di `quote_source`.
- Jika provider mengembalikan hasil tetapi confidence rendah, perlakukan sebagai ambigu (tampilkan saran).

### 5.4 Rate limit, cache, dan retry

- Ganti lock global 3 detik dengan **token bucket per provider** (mis. 1 req/s untuk Nominatim).
- Jika token habis, antrekan sebentar (maks. 1-2 detik) atau langsung lanjut ke provider berikutnya; jangan langsung bilang "sibuk".
- Cache geocode berdasarkan alamat ternormalisasi + provider + viewbox; TTL 7-30 hari.
- Cache route berdasarkan origin + destination; TTL 12-24 jam.
- Bedakan error:
  - `rate_limited` -> coba provider lain / antre.
  - `timeout` -> coba provider lain.
  - `not_found` -> tampilkan saran / fallback.
  - `invalid_response` -> log, coba provider lain.
- Gunakan `Http::retry()` hanya untuk error jaringan/5xx, bukan untuk 4xx.

### 5.5 Akurasi alamat Indonesia

- Normalisasi: `Jl.`/`Jalan`, `No.`/`Nomor`, `RT/RW`, `Kel.`/`Kelurahan`, `Kec.`/`Kecamatan`.
- Jangan menghapus angka pertama dengan regex buta; nomor rumah penting.
- Deteksi kota/kabupaten yang sudah disebut; jangan tambahkan "Kota Bandung" jika ada "Kabupaten Bandung", "Cimahi", "Bandung Barat", dll.
- Gunakan `viewbox` + `bounded=1` untuk membatasi area layanan.
- Simpan `display_name` asli provider untuk audit.

---

## 6. Kontrak API

Endpoint: `POST /api/delivery-quote` (throttle `10,1`).

### 6.1 Request

```json
{
  "address": "Jl. Karang Layung Dalam No. 11, Cigadung, Bandung",
  "selected_lat": -6.8825,
  "selected_lon": 107.5935,
  "fallback": false
}
```

Aturan:
- `address` wajib, min. 8 karakter.
- `selected_lat`/`selected_lon` opsional, harus berpasangan.
- `fallback` **tidak boleh** menjadi satu-satunya penentu. Server harus memvalidasi kelayakan fallback.
- Tambahkan `session_id`/`client_token` opsional untuk rate limit per sesi.

### 6.2 Response sukses - jarak pasti

```json
{
  "quote_id": "encrypted...",
  "fee": 12000,
  "distance_meters": 2450,
  "distance_km": 2.5,
  "is_estimate": false,
  "is_base_fee": false,
  "quote_source": "nominatim+osrm",
  "destination_label": "Jl. Karang Layung Dalam No. 11, Cigadung, Bandung",
  "expires_at": "2026-11-01T10:15:00+07:00",
  "suggestions": []
}
```

### 6.3 Response sukses - estimasi (routing gagal)

```json
{
  "quote_id": "encrypted...",
  "fee": 12000,
  "distance_meters": 3200,
  "distance_km": 3.2,
  "is_estimate": true,
  "is_base_fee": false,
  "quote_source": "nominatim+haversine",
  "warning": "Jarak dihitung sebagai estimasi karena rute jalan tidak tersedia. Ongkir final dikonfirmasi kurir.",
  "destination_label": "...",
  "expires_at": "..."
}
```

### 6.4 Response sukses - tarif dasar

```json
{
  "quote_id": "encrypted...",
  "fee": 5000,
  "distance_meters": null,
  "distance_km": null,
  "is_estimate": true,
  "is_base_fee": true,
  "quote_source": "base_fee",
  "warning": "Alamat belum terverifikasi. Ongkir Rp5.000 adalah estimasi awal. Kurir akan mengonfirmasi biaya final.",
  "destination_label": "Jl. Keadilan Selatan 5 NE18",
  "expires_at": "..."
}
```

### 6.5 Response error + saran

```json
{
  "message": "Alamat belum cukup spesifik. Pilih salah satu alamat di bawah ini.",
  "code": "ambiguous_address",
  "suggestions": [
    { "label": "Jl. Karang Layung Dalam, Cigadung, Bandung", "lat": -6.8825, "lon": 107.5935 }
  ]
}
```

Kode error yang disarankan:
- `address_incomplete`
- `address_not_found`
- `ambiguous_address`
- `out_of_range`
- `provider_unavailable`
- `route_unavailable`
- `quote_expired`
- `quote_invalid`

### 6.6 Payload quote terenkripsi

`quote_id` tetap memakai `Crypt::encryptString(json_encode($payload))` dengan isi:

```json
{
  "id": "uuid",
  "address_hash": "sha256",
  "distance_meters": 2450,
  "fee": 12000,
  "base_fee": 5000,
  "included_km": 0,
  "fee_per_km": 5000,
  "rounding_km": 0.5,
  "billable_km": 2.5,
  "is_estimate": false,
  "quote_source": "nominatim+osrm",
  "expires_at": 1760000000
}
```

### 6.7 Backward compatibility

- Pertahankan field lama: `quote_id`, `fee`, `distance_meters`, `distance_km`, `destination_label`, `expires_at`, `suggestions`.
- Tambahkan field baru tanpa menghapus field lama.
- `distance_km` boleh `null`; UI harus menangani `null`, bukan memformat `0.0`.
- Jika `is_estimate` tidak ada (client lama), anggap `false` hanya untuk quote non-fallback.

---

## 7. Konfigurasi (key-value)

`store_settings` adalah tabel key-value. Jangan menambah kolom.

### 7.1 Config yang sudah ada

| Key | Default | Keterangan |
|---|---|---|
| `delivery_fee` | 5000 | Tarif dasar ongkir |
| `delivery_fee_per_km` | 5000 | Tarif per km setelah included |
| `delivery_included_km` | 0 | Km yang sudah termasuk tarif dasar |
| `delivery_rounding_km` | 0.5 | Pembulatan km |
| `delivery_max_km` | 0 | Batas layanan; 0 = tanpa batas |
| `store_latitude` | - | Koordinat toko |
| `store_longitude` | - | Koordinat toko |
| `store_address` | - | Alamat toko |
| `store_maps_url` | - | Link peta toko |

### 7.2 Config baru yang disarankan

| Key | Default | Keterangan |
|---|---|---|
| `geocode_provider_primary` | `nominatim` | Provider utama |
| `geocode_provider_fallback_1` | `opencage` | Fallback pertama |
| `geocode_provider_fallback_2` | `mapbox` | Fallback kedua |
| `routing_provider_primary` | `osrm` | Provider routing |
| `routing_provider_fallback_1` | `openrouteservice` | Fallback routing |
| `delivery_base_fee_enabled` | `1` | Izinkan tarif dasar sebagai estimasi |
| `delivery_estimate_road_factor` | `1.3` | Faktor jalan untuk Haversine |
| `delivery_quote_ttl_minutes` | `15` | Masa berlaku quote |
| `delivery_max_paid_calls_per_day` | `500` | Pengendali biaya provider berbayar |

Catatan:
- **Tidak perlu** `delivery_base_fee`; gunakan `delivery_fee`.
- **Tidak perlu** `delivery_base_fee_radius_km`; jika ingin batas area, gunakan `delivery_max_km`.
- Jika tetap ingin radius estimasi, definisikan sebagai "radius klaim pemasaran", bukan dasar perhitungan, dan jangan gunakan untuk menolak/menerima secara otomatis.

### 7.3 Rahasia

- API key **jangan** disimpan plaintext di `store_settings`.
- Prioritaskan `.env` + `config/services.php`:

```php
// config/services.php
'geocoding' => [
    'opencage_key' => env('OPENCAGE_API_KEY'),
    'mapbox_token' => env('MAPBOX_ACCESS_TOKEN'),
    'google_key' => env('GOOGLE_MAPS_API_KEY'),
],
```

- Jika harus di DB (multi-tenant), enkripsi dengan `Crypt::encryptString()` dan jangan pernah kirim balik ke client.
- Di form admin, tampilkan sebagai `••••` dan sediakan tombol "Ganti key", bukan value asli.

### 7.4 Migrasi data

Karena key-value, "migrasi" cukup `updateOrInsert` baris baru:

```php
DB::table('store_settings')->updateOrInsert(
    ['key' => 'geocode_provider_primary'],
    ['value' => 'nominatim', 'created_at' => now(), 'updated_at' => now()]
);
```

Jangan membuat migration `Schema::table('store_settings', ...)` untuk menambah kolom.

---

## 8. Checkout dan snapshot pesanan

### 8.1 Validasi quote di server

`OrderService::validateQuote()` harus memeriksa:

1. Token bisa didekripsi.
2. `expires_at` belum lewat.
3. `address_hash` cocok dengan alamat yang dikirim.
4. **Hitung ulang fee** dari setting saat ini; jika berbeda dari payload, tolak dan minta hitung ulang.
5. `quote_source` valid.
6. Jika `is_base_fee`, pastikan `delivery_base_fee_enabled` masih aktif.
7. Jika `is_estimate`, pastikan customer mengirim `delivery_quote_accepted = true`.
8. Jika `service != Antar`, abaikan quote.

### 8.2 Kolom snapshot di `orders`

Sudah ada:
- `delivery_distance_meters`
- `delivery_base_fee`
- `delivery_variable_fee`

Tambahan yang disarankan:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `delivery_quote_source` | varchar(40) nullable | `nominatim+osrm`, `haversine`, `base_fee`, dll. |
| `delivery_is_estimate` | boolean default false | True jika jarak/fee estimasi |
| `delivery_lat` | decimal(10,7) nullable | Titik tujuan jika ada |
| `delivery_lon` | decimal(10,7) nullable | Titik tujuan jika ada |
| `delivery_quote_expires_at` | timestamp nullable | Audit |
| `delivery_confirmed_at` | timestamp nullable | Kapan customer menyetujui estimasi |

### 8.3 Status pesanan dan WhatsApp

- Jika `is_estimate = true`, tambahkan baris di pesan WhatsApp:
  `Ongkir: Rp5.000 (estimasi, menunggu konfirmasi kurir)`
- Jangan menulis "Total final" jika ongkir masih estimasi; tulis "Total sementara".
- Admin melihat badge **"Ongkir estimasi"** di daftar dan detail pesanan.
- Status awal tetap `pending`, tetapi tambahkan catatan internal/flag agar admin tahu perlu konfirmasi.

### 8.4 Admin

- Filter pesanan: "Ongkir estimasi".
- Detail pesanan menampilkan:
  - sumber quote,
  - jarak (atau "tidak diketahui"),
  - tarif dasar vs variabel,
  - status estimasi.
- Aksi: "Konfirmasi ongkir final" yang mengubah total dan mencatat log.

---

## 9. UI/UX checkout

### 9.1 Saat quote akurat

- Tampilkan `± X km` dan fee.
- Checkbox: "Saya sudah memeriksa alamat dan ongkir."
- Tombol checkout aktif setelah checkbox.

### 9.2 Saat estimasi / tarif dasar

- Box amber di `#deliveryQuoteCard`:
  > **Alamat belum terverifikasi.**
  > Ongkir Rp5.000 adalah estimasi awal. Kurir akan mengonfirmasi biaya final sebelum pengantaran.
- Label jarak: **"Estimasi tarif dasar"**, bukan `± 0 km`.
- Checkbox terpisah: "Saya mengerti ongkir ini masih estimasi."
- Tombol checkout aktif hanya setelah checkbox estimasi dicentang.
- Jangan sembunyikan peringatan di teks kecil; gunakan `role="alert"`/`aria-live`.

### 9.3 Saat alamat ambigu

- Tampilkan daftar saran sebagai tombol.
- Teks: "Pilih alamat yang paling sesuai."
- Jangan mengisi otomatis saran pertama.
- Setelah dipilih, hitung ulang quote.

### 9.4 Saat alamat tidak ditemukan

- Tampilkan error yang actionable:
  > "Alamat belum ditemukan. Tambahkan kelurahan/kecamatan/kota, atau gunakan lokasi saat ini."
- Sediakan:
  - tombol "Gunakan lokasi saya",
  - tombol "Pilih di peta",
  - tombol "Gunakan tarif dasar" (dengan konsekuensi jelas).

### 9.5 Salinan (copy) yang disarankan

- Error alamat kosong: "Masukkan alamat pengantaran terlebih dahulu."
- Error terlalu pendek: "Alamat terlalu singkat. Sertakan nama jalan dan kelurahan."
- Error ambigu: "Ada beberapa alamat yang cocok. Pilih salah satu."
- Error di luar jangkauan: "Alamat di luar jangkauan antar. Hubungi kedai via WhatsApp."
- Error provider: "Layanan alamat sedang gangguan. Coba lagi atau gunakan tarif dasar."
- Estimasi: "Ongkir ini estimasi. Biaya final dikonfirmasi kurir."

---

## 10. Keamanan, privasi, dan biaya

### 10.1 Keamanan

- Jangan pernah mengirim API key ke browser.
- Validasi `selected_lat`/`selected_lon` di server (range, pasangan, jarak wajar dari toko).
- Jangan percaya `fallback` dari client; server menentukan.
- Rate limit per IP **dan** per sesi; saat ini hanya `10,1` per IP.
- Cegah abuse: batasi jumlah quote per sesi/jam, dan tolak koordinat yang tidak masuk akal (mis. di luar Indonesia).
- Log tanpa PII: hash alamat, jangan log nomor telepon.

### 10.2 Privasi

- Alamat customer dikirim ke provider pihak ketiga. Cantumkan di kebijakan privasi:
  - data apa yang dikirim (alamat, bukan nama/telepon),
  - provider mana,
  - tujuan (geocoding/routing),
  - retensi.
- Sediakan opsi "hitung manual via WhatsApp" bagi yang tidak ingin alamatnya diproses pihak ketiga.

### 10.3 Biaya

- Prioritaskan provider gratis.
- Cache agresif untuk alamat yang sering dipakai.
- Batas harian panggilan provider berbayar.
- Circuit breaker: jika provider error > threshold, hentikan sementara.
- Dashboard biaya sederhana: jumlah panggilan per provider per hari.

---

## 11. Observability

Log setiap quote:

```json
{
  "event": "delivery_quote",
  "provider": "nominatim",
  "routing": "osrm",
  "latency_ms": 420,
  "result": "success",
  "is_estimate": false,
  "distance_bucket": "2-5km",
  "address_hash": "sha256..."
}
```

Metrik yang dipantau:
- Quote success rate.
- Fallback rate (`base_fee` / `haversine`).
- Provider error rate dan latency p95.
- Persentase order dengan `delivery_is_estimate`.
- Jumlah panggilan provider berbayar per hari.

Alert:
- Fallback rate > 10% selama 15 menit.
- Provider error rate > 5%.
- Latency p95 > 5 detik.
- Biaya provider mendekati batas.

---

## 12. Testing

### 12.1 Unit

- Urutan provider: Nominatim gagal -> OpenCage dipanggil.
- Provider tanpa key dilewati.
- Timeout/5xx -> provider berikutnya.
- 4xx `not_found` -> suggestions/fallback, bukan retry membabi buta.
- Cache hit tidak memanggil provider.
- Token bucket tidak menyebabkan "sibuk" untuk request bersamaan.
- Haversine menghitung jarak dengan benar.
- Fee dihitung konsisten di satu tempat.

### 12.2 Feature

- Quote akurat -> order tersimpan dengan snapshot benar.
- Quote estimasi -> `delivery_is_estimate = true` dan warning tampil.
- Fallback tanpa konfirmasi -> ditolak.
- `fallback: true` dari client tidak bisa melewati aturan.
- Quote kedaluwarsa -> ditolak.
- Alamat berubah -> ditolak.
- Quote dipalsukan -> ditolak.
- Tarif berubah setelah quote -> ditolak, minta hitung ulang.
- Di luar `delivery_max_km` -> 422.
- `selected_lat/lon` valid -> 200 (regresi bug `DB::`).
- Semua provider gagal -> base fee + warning.

### 12.3 UI

- Warning amber muncul saat estimasi.
- Label "Estimasi tarif dasar", bukan `± 0 km`.
- Checkbox estimasi wajib.
- Suggestions tampil saat ambigu.
- Tombol "Gunakan lokasi saya" meminta izin dan mengisi alamat.
- Error copy sesuai kasus.

### 12.4 Aturan pengujian

- Gunakan `Http::fake()`; jangan memanggil provider nyata di CI.
- Simpan fixture respons provider untuk contract test.
- Uji manual di staging dengan alamat nyata, termasuk alamat informal.
- Uji beban ringan untuk memastikan tidak ada lock global.

---

## 13. Rollout

### Fase 0 - Perbaikan correctness (P0)

Sudah selesai:
- [x] Perbaiki import `DB` dan pemanggilan method private di jalur koordinat.
- [x] Pusatkan perhitungan harga di `priceQuote()`.
- [x] Kembalikan `quote_id` terenkripsi dari jalur koordinat agar bisa dipakai checkout.
- [x] Perbaiki retry Nominatim yang tertahan lock sendiri.
- [x] Tambahkan `is_estimate` dan `quote_source` di response sukses.

Belum selesai:
- [ ] Ganti lock global 3 detik dengan token bucket per provider.
- [ ] Tambahkan `warning` dan `is_base_fee` di response fallback.
- [ ] Ubah `distance_km` fallback menjadi `null`.
- [ ] Validasi fallback di server; jangan percaya client.
- [ ] Simpan `quote_source` dan `is_estimate` di order.
- [ ] Hitung ulang fee saat `validateQuote`.

### Fase 1 - Multi-provider (P1)

- Ekstrak interface `GeocodeProvider` dan `RoutingProvider`.
- Implementasi Nominatim + OpenCage + Mapbox.
- Implementasi OSRM + OpenRouteService/Mapbox Directions.
- Fallback Haversine.
- Cache dan rate limit per provider.
- Suggestions selalu tersedia saat ambigu.

### Fase 2 - UX alamat (P1/P2)

- Autocomplete wajib pilih.
- Tombol "Gunakan lokasi saya".
- Peta drop pin (Leaflet + OSM).
- Reverse geocode + konfirmasi.
- Copy error yang actionable.

### Fase 3 - Operasional (P2)

- Form admin provider + key (masked).
- Tombol "Test koneksi".
- Dashboard metrik fallback.
- Batas biaya dan circuit breaker.
- Filter "Ongkir estimasi" di admin.

### Feature flag & kill switch

- `delivery_provider_chain_enabled`
- `delivery_base_fee_enabled`
- `delivery_map_pin_enabled`
- `delivery_estimate_road_factor`
- Kill switch: paksa tarif dasar atau nonaktifkan antar jika provider bermasalah.

---

## 14. File yang akan disentuh

| File | Perubahan |
|---|---|
| `app/Services/DeliveryQuoteService.php` | Provider chain, pricing terpusat, fallback, warning |
| `app/Http/Controllers/Api/DeliveryQuoteController.php` | Hapus duplikasi pricing, perbaiki `DB::`, validasi fallback |
| `app/Http/Requests/DeliveryQuoteRequest.php` | Aturan koordinat, fallback, session |
| `app/Services/OrderService.php` | Validasi quote, snapshot estimasi, pesan WhatsApp |
| `app/Http/Requests/StoreOrderRequest.php` | `delivery_quote_accepted`, koordinat |
| `app/Http/Controllers/Admin/StoreSettingsController.php` | Simpan config provider (masked) |
| `app/Http/Requests/StoreSettingsRequest.php` | Validasi config baru |
| `app/resources/views/admin/partials/settings.blade.php` | Form provider & key |
| `app/resources/views/catalog.blade.php` | Box warning, tombol lokasi/peta |
| `app/public/assets/catalog/catalog.js` | State estimasi, suggestions, geolocation |
| `app/public/assets/catalog/catalog.css` | Styling warning/estimasi |
| `app/database/migrations/*_add_delivery_estimate_fields_to_orders.php` | Kolom snapshot baru |
| `app/config/services.php`, `.env.example` | API key provider |
| `app/tests/Feature/DeliveryQuoteTest.php` | Test provider chain & fallback |
| `app/tests/Feature/CheckoutTest.php` | Test quote validation & snapshot |

---

## 15. Pertanyaan terbuka (perlu keputusan pemilik toko)

1. Apakah tarif dasar boleh dipakai sama sekali? Jika ya, siapa yang mengonfirmasi dan berapa lama?
2. Apakah ongkir estimasi boleh langsung checkout, atau harus menunggu admin?
3. Berapa batas jarak layanan (`delivery_max_km`) yang sebenarnya?
4. Apakah ada budget untuk Google Maps/Places atau provider berbayar lain?
5. Apakah tersedia server untuk OSRM self-hosted?
6. Apakah customer boleh memilih "hitung manual via WhatsApp"?
7. Jika ongkir final berbeda dari estimasi, siapa yang menanggung selisihnya?

---

## 16. Rekap perubahan dari dokumen lama

- Skema config dikoreksi dari "tambah kolom" menjadi "tambah baris key-value".
- `delivery_base_fee` dihapus; memakai `delivery_fee` yang sudah ada.
- `delivery_base_fee_radius_km` dihapus/didefinisikan ulang; memakai `delivery_max_km`.
- Klaim rate limit Nominatim dan kuota provider dikoreksi.
- "Cognito Map" dihapus.
- Ditambahkan fallback routing dan estimasi Haversine.
- Ditambahkan kontrak response yang lengkap (`is_estimate`, `quote_source`, `warning`).
- Ditambahkan aturan validasi quote dan snapshot order.
- Ditambahkan solusi bertingkat untuk alamat tidak lengkap.
- Ditambahkan keamanan, privasi, biaya, observability, dan rollout.
- Bug `DB::`, method private, UUID acak, dan retry-lock didokumentasikan lalu diperbaiki.
- Lock global antar-request tetap tercatat sebagai P0 yang belum diselesaikan.
