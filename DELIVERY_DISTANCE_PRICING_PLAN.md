# Fitur Ongkir Berdasarkan Jarak

Prioritas: P2
Untuk: Programmer junior
Project: menu-order (Laravel + Blade + JavaScript)

## Tujuan

Hitung estimasi jarak rute dari toko ke alamat pelanggan dan tampilkan ongkir sebelum pelanggan membuat pesanan. Nilai ongkir yang dikirim browser tidak boleh dipercaya; server harus menghitung ulang atau memverifikasi jarak dan harga ketika pesanan dibuat.

Gunakan API peta/rute gratis tanpa menanam API key di browser. Tarif akhir harus dikendalikan dashboard, bukan angka yang ditentukan frontend.

## Rekomendasi API awal

Gunakan OpenStreetMap Nominatim untuk geocoding alamat dan OSRM public routing service untuk jarak jalan:

- Geocoding: `https://nominatim.openstreetmap.org/search`
- Rute: `https://router.project-osrm.org/route/v1/driving/{longitude},{latitude};{longitude},{latitude}?overview=false`

Keduanya dapat dipakai tanpa API key, tetapi layanan publik punya kebijakan penggunaan, batas kapasitas, dan tidak menyediakan SLA produksi. Implementasikan provider secara terpisah agar endpoint dapat diganti. Jangan mengandalkan layanan publik gratis untuk trafik besar atau menjanjikan selalu tersedia.

Nominatim wajib diberi identitas aplikasi melalui `User-Agent` yang jelas, gunakan maksimal 1 request/detik, cache hasil geocoding, dan jangan kirim request autocomplete per karakter. OSRM public juga harus dipakai hemat dan diberi cache; jika trafik meningkat, pindah ke provider ber-SLA atau host layanan sendiri.

## Keputusan produk dan batasan

- Jarak yang dipakai adalah jarak rute jalan dari koordinat toko ke koordinat tujuan, bukan jarak garis lurus.
- Toko harus punya satu alamat operasional yang lengkap dan tersimpan di server.
- Pelanggan memasukkan alamat lengkap. Geocoding server-side mengubah alamat menjadi kandidat lokasi; UI harus meminta pelanggan memilih/konfirmasi lokasi yang benar sebelum menampilkan ongkir final.
- Bila hasil geocoding ambigu/tidak ditemukan, tampilkan pesan agar pelanggan memperjelas alamat atau hubungi toko. Jangan diam-diam memakai kandidat pertama tanpa konfirmasi.
- Tampilkan jarak sebagai estimasi, misalnya `± 4,2 km`; jelaskan bahwa rute aktual dapat berubah.
- Tarif contoh tidak boleh di-hardcode sebagai keputusan bisnis. Pengelola mengatur tarif dasar, tarif per kilometer, pembulatan, dan batas layanan dari dashboard.
- Jika layanan peta gagal, jangan membuat pesanan dengan ongkir palsu/0. Berikan pilihan fallback yang disetujui pemilik, misalnya `Ongkir dikonfirmasi toko` dengan total belum final, atau hentikan checkout dan tampilkan kontak toko.

## Perhitungan harga

Usulkan konfigurasi berikut (semua nilai dikelola server/dashboard):

- `delivery_base_fee`: ongkir minimum untuk pesanan antar.
- `delivery_included_km`: jarak yang sudah termasuk tarif dasar.
- `delivery_fee_per_km`: biaya tiap km setelah jarak gratis/termasuk.
- `delivery_rounding_km`: langkah pembulatan jarak ke atas, misalnya 0.5 km.
- `delivery_max_km`: radius/limit maksimum layanan; opsional jika toko tidak ingin membatasi.

Rumus awal yang mudah dipahami:

```text
billable_km = max(0, ceil(distance_km / rounding_km) * rounding_km - included_km)
fee = base_fee + billable_km * fee_per_km
```

Sesuaikan urutan pembulatan dengan kebijakan toko dan dokumentasikan contoh harga sebelum implementasi. Simpan jarak yang dipakai (meter integer), komponen tarif, dan total ongkir di order agar riwayat pesanan tidak berubah saat tarif dashboard diubah kemudian.

## Alur pelanggan

1. Pelanggan pilih metode `Antar`.
2. Form menampilkan alamat dan tombol `Hitung ongkir` (hindari request otomatis setiap ketikan).
3. Frontend mengirim alamat ke endpoint Laravel milik aplikasi; browser tidak memanggil API peta langsung.
4. Laravel memvalidasi input, mencari geocoding yang sudah tercache atau memanggil Nominatim, lalu meminta rute OSRM.
5. Server menghitung estimasi jarak dan ongkir dari koordinat toko dan aturan tarif aktif.
6. Frontend menampilkan lokasi kandidat, estimasi jarak, tarif, dan total. Pelanggan mengonfirmasi alamat tersebut.
7. Saat klik pesan, server geocode/validasi ulang melalui cache dan hitung kembali; abaikan ongkir/koordinat/tarif yang dimanipulasi dari browser.
8. Simpan snapshot alamat, koordinat hasil konfirmasi, jarak meter, komponen tarif, dan ongkir final pada order.
9. Masukkan jarak/ongkir dalam rincian pesanan WhatsApp.

## Backend

Buat service khusus, misalnya `DeliveryQuoteService`, dengan tanggung jawab:

- Memanggil geocoder dan routing provider melalui HTTP client Laravel, memakai timeout pendek, retry terbatas, dan penanganan error.
- Memvalidasi format koordinat, batas wilayah yang masuk akal, dan hasil jarak (`distance` meter dari respons OSRM).
- Menghitung harga memakai konfigurasi/database server.
- Menyimpan/cache hasil geocoding berdasarkan alamat ternormalisasi dan wilayah. Jangan cache jawaban error permanen.
- Tidak mengirim API token/credential ke frontend. Jika provider berkey kelak dipakai, simpan credential di `.env` server.
- Melindungi endpoint dari spam dengan throttle dan batas panjang alamat. Jangan membuat proxy bebas untuk URL arbitrer; URL provider harus statis/configured.
- Menangani timeout/provider unavailable dengan respons yang aman dan jelas, tanpa membocorkan stack trace.

Usulkan endpoint, sesuaikan dengan pola routes saat ini:

```text
POST /api/delivery-quote
Request: { "address": "..." }
Success: { "distance_meters": 4200, "distance_km": 4.2, "fee": 12000, "destination_label": "...", "quote_id": "..." }
Failure: status 422 untuk alamat tidak valid/tidak ditemukan; 503 bila provider tidak tersedia.
```

Jangan percaya `destination_label`, `distance`, `fee`, atau `quote_id` dari browser saat membuat order. Server harus memastikan quote valid, terkait alamat, masih berlaku, dan tarif sesuai snapshot/policy. Quote dapat disimpan server-side dengan ID acak berumur pendek atau dihitung ulang saat order.

## Dashboard admin

Tambahkan pengaturan:

- Alamat toko (wajib untuk menghitung rute) dan koordinat toko yang terverifikasi.
- Ongkir dasar.
- Jarak yang termasuk ongkir dasar.
- Tarif per km.
- Pembulatan km.
- Batas jarak layanan (opsional).

Berikan aksi `Validasi lokasi toko` untuk geocode alamat, tampilkan hasil dan minta admin konfirmasi sebelum menyimpan koordinat. Jangan geocode berulang setiap dashboard dibuka.

Jika project belum memiliki kolom/penyimpanan koordinat dan komponen tarif, rencanakan migration aman serta pertahankan konfigurasi/ongkir lama sampai admin menyimpan setup baru. Jangan menimpa nilai ongkir existing tanpa migrasi data dan persetujuan.

## Keamanan, privasi, dan biaya

- Alamat pelanggan adalah data pribadi. Kirim hanya ke provider yang diperlukan untuk menghitung rute; jelaskan di UI bahwa alamat digunakan untuk estimasi pengantaran.
- Batasi logging: jangan mencatat alamat lengkap dalam log biasa. Masking atau log ID quote saja.
- Gunakan HTTPS dan validasi request.
- Terapkan throttle; jangan implementasikan live autocomplete terhadap Nominatim.
- Cache geocoding dan rute secukupnya, dengan expiry yang wajar dan kebijakan penghapusan data.
- Public API gratis bukan jaminan kapasitas/availability. Tambahkan dokumentasi cara mengganti provider tanpa mengubah logika harga.

## Acceptance criteria

- Admin dapat menyimpan dan memvalidasi alamat/koordinat toko serta komponen tarif.
- Pelanggan antar dapat meminta quote setelah memasukkan alamat; UI menunjukkan loading, jarak estimasi, ongkir, total, dan error yang jelas.
- Pelanggan dapat mengoreksi/konfirmasi kandidat lokasi; alamat tidak pasti tidak menghasilkan ongkir final.
- Jarak berasal dari rute jalan OSRM (meter), bukan kalkulasi garis lurus.
- Harga dihitung server-side dengan tarif admin; manipulasi ongkir di browser tidak mengubah total pesanan.
- Order menyimpan snapshot alamat, jarak, tarif, dan ongkir untuk riwayat yang konsisten.
- Checkout pickup tetap tidak dikenakan ongkir dan tidak memanggil API geocoding/routing.
- Timeout, no-result, malformed provider response, dan provider outage ditangani tanpa 500 mentah atau ongkir 0 palsu.
- Endpoint quote diberi throttle; geocoding tidak melakukan request setiap karakter.
- Tes otomatis mencakup formula tarif, alamat tak ditemukan, API timeout, jarak maksimum, manipulasi payload, dan pickup.
- Uji integrasi provider dibuat dengan HTTP fakes; test suite tidak memanggil layanan publik sungguhan.
- Dokumentasikan limit dan ketergantungan pada layanan gratis; tidak menyatakan layanan tanpa batas atau SLA.

## Langkah kerja programmer

1. Audit schema `store_settings`, model/tabel order, checkout API, dan fee calculation existing. Catat kompatibilitas data lama.
2. Konfirmasi kepada pemilik toko soal alamat sumber, tarif dasar, tarif per km, jarak included, rounding, batas layanan, dan kebijakan saat API down. Jangan mengarang angka produksi.
3. Rancang migration/config dan provider interface sebelum menulis kode.
4. Implementasikan unit test kalkulasi ongkir dan HTTP fake provider terlebih dahulu.
5. Implementasikan setting admin dan validasi koordinat toko.
6. Implementasikan quote service + endpoint dengan cache, timeout, throttle, dan respons terstruktur.
7. Tambahkan UI hitung ongkir dengan loading state, konfirmasi lokasi, dan penanganan error.
8. Ubah order creation agar menghitung ulang/memvalidasi quote server-side dan menyimpan snapshot tarif.
9. Uji pickup, alamat delivery valid/tidak valid, provider offline, tarif berubah, dan payload yang dimanipulasi.
10. Jalankan seluruh test suite, cek console/network, baru siapkan PR. Jangan deploy sebelum pemilik mengonfirmasi tarif serta fallback kegagalan provider.

## Di luar scope

- Pelacakan kurir real-time.
- Pembayaran online.
- Menampilkan peta interaktif besar jika belum dibutuhkan; tahap awal cukup kandidat alamat dan estimasi.
- Menjamin waktu tempuh atau tarif pihak ketiga.
