# Spesifikasi: Menu Rekomendasi dan Terlaris

## Tujuan

Bantu pelanggan cepat memilih menu yang banyak dipesan. Dokumen ini jadi pegangan implementasi yang terarah untuk programmer junior atau model coding. Dokumen mencantumkan file yang harus diubah, kontrak data, algoritma, pseudocode, dan batas cakupan agar implementer tidak menebak.

## Konteks aplikasi saat ini

- Aplikasi Laravel; katalog publik ada di `app/resources/views/catalog.blade.php`.
- Katalog sekarang memakai pencarian, navigasi kategori, dan grid produk.
- Data utama di tabel `products`: `id`, `category_id`, `name`, `description`, `price`, `available`, `sort_order`, dan timestamp.
- Pesanan tersimpan di tabel `orders`; item tersimpan sebagai JSON pada `orders.items`.
- Belum ada rating/review atau kolom penanda rekomendasi pada produk.
- Checkout menyimpan pesanan sebelum diarahkan ke WhatsApp. Gunakan order tersimpan sebagai sumber hitung “terlaris”, bukan klik tombol atau pesan WhatsApp yang belum jadi pesanan.

## Istilah dan aturan produk

Pisahkan dua konsep agar label tidak menyesatkan:

1. **Rekomendasi** — pilihan kurasi pemilik toko. Admin dapat menandai produk secara manual. Bukan hasil rating pelanggan.
2. **Terlaris** — dihitung otomatis dari kuantitas produk dalam pesanan valid pada periode yang dipilih.

Definisi pesanan valid untuk perhitungan: status selain `cancelled`. Jika status lain pada aplikasi mengandung status batal/gagal, cek `OrderStatus` sebelum implementasi dan kecualikan status tersebut juga.

### Perhitungan terlaris

- Periode bawaan: 30 hari terakhir, termasuk hari ini berdasarkan zona waktu aplikasi.
- Hitung total kuantitas produk dari `orders.items` untuk setiap produk; jangan menghitung jumlah order saja.
- Batasi produk yang masih tersedia dan aktif ditampilkan.
- Urutkan berdasarkan kuantitas terjual menurun; jika sama, urutkan `products.sort_order`, lalu `products.id`.
- Tampilkan badge “Terlaris” hanya jika kuantitas periode tersebut lebih dari 0.
- Jika belum ada penjualan dalam periode, jangan tampilkan bagian kosong; rekomendasi manual tetap dapat tampil.
- Item order lama mungkin berupa snapshot dan tidak memiliki product ID. Abaikan untuk agregasi jika ID tidak dapat dicocokkan; dokumentasikan batasan ini.

## Pengalaman pelanggan

### Navigasi

Di area katalog, tambahkan kontrol filter sejajar dengan navigasi kategori:

- `Semua`
- `Rekomendasi`
- `Terlaris`
- Kategori produk yang sudah ada

Kontrol harus tetap dapat digunakan di layar sempit, misalnya horizontal scroll. Sertakan status aktif yang terlihat dan atribut aksesibilitas `aria-pressed` atau pola tab yang konsisten.

### Kartu produk

- Produk rekomendasi mendapat badge `Rekomendasi`.
- Produk dalam daftar terlaris mendapat badge `Terlaris`.
- Produk dapat memiliki kedua badge; hindari badge berulang atau menutupi nama/harga.
- Produk tidak tersedia tetap mengikuti aturan katalog saat ini dan tidak boleh bisa ditambahkan ke keranjang.
- Klik produk dan alur tambah ke keranjang tetap memakai mekanisme yang sudah ada.

### Perilaku filter

- `Semua`: daftar produk standar, tetap mengikuti pencarian dan ketersediaan.
- `Rekomendasi`: produk dengan penanda rekomendasi aktif.
- `Terlaris`: urutan ranking terlaris; pencarian dapat mempersempit hasil tanpa mengubah ranking.
- Filter kategori yang ada tetap berjalan. Tentukan komposisi filter sebagai irisan (filter aktif + kategori + pencarian), bukan saling menimpa.
- Kondisi kosong tampilkan pesan singkat dan tombol/aksi kembali ke `Semua`.

## Pengelolaan admin

Tambahkan kontrol “Tampilkan sebagai rekomendasi” pada form tambah/edit produk dan kolom/badge status pada tabel menu.

- Nilai default: tidak direkomendasikan.
- Admin dapat mengaktifkan atau menonaktifkan kapan saja.
- Perubahan harus tersimpan dan tampil setelah halaman dimuat ulang.
- Tidak perlu mengubah produk menjadi terlaris secara manual.

## Skema data yang disarankan

### Perubahan tabel `products`

Tambahkan kolom:

| Kolom | Tipe | Default | Kegunaan |
|---|---|---:|---|
| `is_recommended` | boolean | `false` | Kurasi manual admin |

Tambahkan index bila diperlukan setelah mengukur query; jangan menambah index tanpa memeriksa pola akses dan DB yang dipakai.

### Terlaris

**Versi awal, tanpa tabel/kolom tambahan:** hitung agregat dari JSON `orders.items` di backend. Cocok untuk volume kecil dan minim perubahan skema. Pastikan format JSON item dan status order diperiksa terlebih dahulu.

**Skalabilitas:** bila query JSON lambat atau volume bertambah, buat tabel `order_items` dengan foreign key `order_id`, nullable `product_id`, snapshot nama/harga, `quantity`, dan subtotal. Migrasikan data lama hanya bila pemetaan product ID dapat dipercaya. Jangan gunakan skema ini untuk MVP tanpa kebutuhan performa yang terbukti.

### Format response katalog

Pertahankan format produk yang digunakan frontend saat ini, lalu tambahkan properti yang diperlukan, misalnya:

```json
{
  "is_recommended": true,
  "sold_quantity": 24,
  "is_best_seller": true,
  "best_seller_rank": 1
}
```

`sold_quantity` dan `best_seller_rank` boleh bernilai `0`/`null` jika tidak masuk daftar periode. Jangan kirim detail data pelanggan atau order.

## Alur data dan interaksi

### Admin menandai rekomendasi

1. Admin membuka tambah/edit produk.
2. Admin mengaktifkan atau menonaktifkan `Tampilkan sebagai rekomendasi`.
3. Backend memvalidasi boolean dan menyimpan `products.is_recommended`.
4. Daftar menu admin menampilkan status tersimpan.
5. Endpoint katalog publik mengirim flag rekomendasi; frontend memberi badge dan filter.

### Pelanggan melihat terlaris

1. Frontend meminta data katalog seperti alur saat ini.
2. Backend mengambil produk yang dapat ditampilkan dan membaca order valid dalam periode 30 hari.
3. Backend mengurai item order dan menjumlahkan kuantitas per product ID.
4. Backend mengurutkan hasil dan menambahkan ranking/kuantitas secukupnya.
5. Frontend menampilkan badge dan filter `Terlaris`.
6. Pelanggan mencari atau memilih kategori; hasil terlaris tetap berurutan sesuai ranking.
7. Pelanggan memilih produk; alur opsi, keranjang, checkout, penyimpanan order, dan WhatsApp tetap tidak berubah.

## Rancangan visual

### Arah desain

Selaras dengan katalog yang sudah memakai `Fraunces` untuk judul dan `Plus Jakarta Sans` untuk teks. Jangan merombak hero, warna global, kartu produk, atau checkout.

### Susunan

```text
[ Cari menu                                      ]
[ Semua ] [ Rekomendasi ] [ Terlaris ] [ Kategori ... ]

Pilihan pelanggan                         Lihat semua
[ kartu produk ] [ kartu produk ] [ kartu produk ]

Semua menu
[ grid produk mengikuti katalog yang ada             ]
```

Implementasi dapat memakai filter `Rekomendasi` / `Terlaris` sebagai tampilan daftar yang sama, tanpa membuat section rekomendasi terpisah. Pilih satu pola agar produk tidak muncul dua kali.

### Detail komponen

- Filter aktif memakai warna aksen utama yang sudah ada di CSS; filter nonaktif memakai permukaan netral dan border halus.
- Badge rekomendasi: aksen hangat, teks kontras, label teks tetap terlihat (jangan mengandalkan ikon saja).
- Badge terlaris: aksen berbeda namun harmonis, dapat menampilkan nomor ranking untuk tiga teratas bila ruang cukup.
- Hindari emoji sebagai satu-satunya penanda.
- Kartu konsisten tinggi; nama, harga, badge, dan tombol tetap terbaca pada ponsel.
- Gunakan skeleton/loading hanya bila pemuatan katalog memang asynchronous; hindari layout shift.
- Hormati `prefers-reduced-motion`; filter tidak perlu animasi besar.

## Keputusan teknis untuk implementer

Sebelum coding, periksa dan catat:

1. Bentuk objek item di `orders.items` dan apakah memuat `product_id` serta `quantity`.
2. Status order yang tersedia di `app/app/Support/OrderStatus.php`.
3. Endpoint/response katalog publik dan JavaScript yang mengisi `#productGrid`.
4. Form dan validasi produk admin, termasuk create/update.
5. Versi DB dan kemampuan query JSON. Untuk MVP, agregasi di PHP mungkin paling portabel; gunakan batas periode dan batasi kolom yang diambil.
6. Zona waktu aplikasi dan aturan inklusif/exklusif periode.

Jangan mengubah checkout, format payload pesanan, atau perilaku WhatsApp untuk fitur ini.

## Panduan kode wajib

Bagian ini mengikat implementasi. Ikuti file dan kontrak berikut; jangan membuat arsitektur baru, mengganti checkout, atau mengubah skema `orders` untuk MVP.

### File yang boleh/harus disentuh

1. `app/database/migrations/`: migration baru untuk `products.is_recommended`; jangan edit migration lama yang sudah pernah dijalankan.
2. `app/app/Http/Requests/StoreProductRequest.php`: validasi field boolean `is_recommended`.
3. `app/app/Http/Controllers/Admin/ProductController.php`: simpan field pada `store()` dan `update()`; pertahankan proses gambar, kategori, opsi, dan event log.
4. `app/resources/views/admin/partials/add-menu-dialog.blade.php` serta partial/form edit produk yang benar-benar digunakan: checkbox dengan nama field `is_recommended` dan checked state saat edit.
5. `app/app/Http/Controllers/Api/ProductController.php`: properti katalog dan agregasi penjualan.
6. `app/public/assets/catalog/catalog.js`: normalisasi response, filter gabungan, badge, empty state.
7. `app/resources/views/catalog.blade.php` dan stylesheet katalog: hanya markup/style filter dan badge yang perlu.
8. Test di direktori test yang sudah ada.

Jangan mengubah `OrderService`, payload checkout, status order, route WhatsApp, atau format item order.

### Kontrak ID dan item order

- `/api/products` saat ini mengganti `$product->id` dengan `prototype_id`; frontend mengirim ID tersebut ke checkout.
- `OrderService::buildItems()` menyimpan `product_id` dari ID database (`$product->id`) pada `orders.items`, bersama `quantity`, `name`, `unit_price`, dan `subtotal`.
- Karena itu agregasi penjualan wajib mencocokkan `orders.items[*].product_id` dengan `products.id` database, bukan dengan `prototype_id`.
- Jangan mengganti properti `id` API yang sekarang dipakai keranjang/checkout. Jika perlu, pertahankan `id` sebagai `prototype_id`; agregasi backend memakai ID database sebelum response dipetakan.
- Item JSON rusak, bukan array, tanpa `product_id`, atau `quantity` bukan integer positif harus dilewati; jangan gagalkan seluruh endpoint.

### Migration dan validasi

Migration baru:

```php
Schema::table('products', function (Blueprint $table) {
    $table->boolean('is_recommended')->default(false);
});
```

Gunakan nama file timestamp baru sesuai pola Laravel repo. Migration `down()` menghapus kolom. Jangan menambah index MVP.

Di `StoreProductRequest::rules()`, tambah:

```php
'is_recommended' => ['sometimes', 'boolean'],
```

Checkbox HTML tidak mengirim field saat tidak dicentang. Maka di `ProductController`, simpan nilai dengan:

```php
'is_recommended' => $request->boolean('is_recommended'),
```

Pakai `$request` yang sudah ada di kedua handler; simpan `false` bila checkbox kosong. Jangan bergantung pada nilai default database saat update, karena update harus bisa menonaktifkan rekomendasi.

### Algoritma backend yang ditentukan

Di `ProductController::index()`:

1. Ambil produk/kategori seperti query saat ini; pilih field database ID (`products.id`) juga selama agregasi.
2. Tetapkan batas periode dengan `now()->subDays(29)->startOfDay()` sampai `now()` sesuai timezone aplikasi; query rentang timestamp inklusif. Ini mendefinisikan 30 tanggal kalender termasuk hari ini.
3. Ambil hanya `items` untuk order pada rentang itu dengan status `!= cancelled`; jangan ambil data pelanggan.
4. `json_decode($items, true)`; pastikan setiap elemen berupa array, `product_id` integer positif, dan `quantity` integer positif. Kumpulkan jumlah `quantity` per database product ID.
5. Abaikan order items tanpa ID valid. Jumlahkan setiap baris; satu order bisa punya beberapa baris produk sama.
6. Tempelkan data agregat ke setiap produk sebelum mengganti `id` API menjadi `prototype_id`: `sold_quantity` integer, `best_seller_rank` integer atau `null`, `is_best_seller` boolean, `is_recommended` boolean.
7. Ranking hanya untuk produk tersedia dengan jumlah > 0. Urutkan jumlah menurun, `sort_order` menaik, lalu `products.id` menaik. Produk di luar ranking punya rank `null`, jumlah `0`.
8. Query order dibatasi tanggal dan kolom `items`; lakukan agregasi sekali per request, bukan query per produk. Jangan mengirim items/order ke client.

Pseudocode:

```text
salesByProductId = empty map
for each order.items in valid date range and status != cancelled:
    lines = decode JSON safely
    for line in lines:
        if product_id and quantity are positive integers:
            salesByProductId[product_id] += quantity

ranked = available products where salesByProductId[id] > 0
sort ranked by sold desc, sort_order asc, database id asc
assign rank 1..N
return all catalog products with recommendation and sales metadata
```

Catatan: bila status selain `cancelled` menandakan pesanan batal/gagal, programmer wajib periksa `app/app/Support/OrderStatus.php` dan hanya kecualikan status yang benar-benar final batal. Jangan menyimpulkan status tanpa membaca enum/sumber status.

### Kontrak frontend

Di `catalog.js`, pertahankan pemetaan properti lama (`id`, `name`, `price`, `available`, `groups`, dan lainnya), lalu tambahkan:

```js
isRecommended: Boolean(p.is_recommended),
soldQuantity: Number(p.sold_quantity || 0),
isBestSeller: Boolean(p.is_best_seller),
bestSellerRank: p.best_seller_rank == null ? null : Number(p.best_seller_rank),
```

State filter baru hanya punya nilai `all`, `recommended`, `best_seller`. Jangan gunakan teks label sebagai state internal.

Pseudocode filter:

```text
filtered = products
  .filter(matches current special filter)
  .filter(matches selected category)
  .filter(matches search query)

if special filter is best_seller:
    sort by bestSellerRank ascending, null last
```

Pastikan sorting hanya diterapkan untuk hasil filter terlaris; filter lain memakai urutan katalog yang berlaku. `renderCategories()` harus tetap membangun kategori dari data katalog. Jangan memasukkan tombol filter khusus ke array kategori produk.

Kontrol khusus dapat ditambahkan sebagai elemen terpisah dalam `catalog-tools` di `catalog.blade.php`; jangan mengganti `#categoryNav`, `#searchInput`, atau `#productGrid`. Saat klik, set state, update `aria-pressed`, lalu panggil fungsi render yang sudah ada. Pastikan `resetCatalog()` juga mengembalikan filter khusus ke `all`.

Badge dirender sebagai teks escaped di template kartu produk. Badge terlaris hanya jika `isBestSeller`; badge rekomendasi hanya jika `isRecommended`. Jangan interpolasi input tak tepercaya sebagai HTML tanpa escaping/helper escaping yang sudah dipakai katalog.

### Contoh kontrak response

Properti baru ditambahkan pada object produk yang sudah ada, bukan mengganti struktur response:

```json
{
  "id": "prototype-uuid",
  "name": "Teh Melati",
  "price": 12000,
  "available": true,
  "is_recommended": true,
  "sold_quantity": 24,
  "is_best_seller": true,
  "best_seller_rank": 1
}
```

### Admin dan form edit

- Cari partial edit produk aktual dengan pencarian ID form dan nama route; jangan menganggap add dialog dipakai juga untuk edit.
- Field checkbox harus memakai `name="is_recommended"`, nilai `1`, label terlihat, dan `aria`/label association benar.
- Form edit memuat status database saat ini. Form tambah tidak dicentang secara default.
- `store()` dan `update()` keduanya menyimpan boolean. Update unchecked tetap menyimpan `false`.
- Jangan menambah endpoint toggle baru bila form create/update yang ada sudah cukup.

### Test minimal wajib

Backend:

- migration memberi `false` pada produk lama.
- create dan update menyimpan checkbox true/false.
- dua baris item produk sama menjumlahkan kuantitas.
- jumlah order dihitung berdasarkan kuantitas, bukan jumlah order.
- `cancelled` dan tanggal di luar periode tidak dihitung.
- produk tanpa penjualan tidak mendapat badge/rank.
- peringkat seri stabil menurut `sort_order`, lalu database ID.
- item JSON malformed atau tanpa ID valid tidak merusak response.
- agregasi mencocokkan product ID database, meskipun `id` publik berupa `prototype_id`.

Frontend:

- filter rekomendasi dan terlaris berfungsi.
- filter khusus, kategori, dan pencarian saling beririsan.
- terlaris tetap mengikuti ranking setelah pencarian.
- `resetCatalog()` menghapus semua filter.
- empty state tetap muncul benar saat hasil filter kosong.

## Kriteria penerimaan

- Admin dapat mengatur status rekomendasi; status bertahan setelah reload.
- Hanya produk rekomendasi muncul pada filter `Rekomendasi`.
- Terlaris berdasarkan total kuantitas, bukan jumlah order.
- Pesanan cancelled tidak dihitung.
- Produk dengan penjualan lebih tinggi tampil lebih dahulu dan ranking deterministik saat seri.
- Produk tanpa penjualan tidak mendapat badge terlaris.
- Pencarian dan kategori dapat digabungkan dengan filter.
- Kondisi kosong punya pesan dan jalan kembali ke semua menu.
- Produk tidak tersedia tidak dapat dipesan.
- Tampilan nyaman pada desktop dan ponsel, tombol/label dapat digunakan keyboard dan pembaca layar.
- Ada test backend untuk agregasi/periode/status dan test frontend untuk kombinasi filter serta kondisi kosong.
- Checkout, penyimpanan order, dan rute WhatsApp yang sudah ada tidak mengalami regresi.

## Saran urutan implementasi

1. Verifikasi bentuk item order dan status.
2. Tambah migration `is_recommended`.
3. Perbarui validasi dan create/update admin.
4. Tambah properti katalog publik dan agregasi terlaris.
5. Perbarui kontrol filter, badge, pencarian, kategori, dan empty state.
6. Tambah test.
7. Jalankan migration/test di lingkungan pengembangan; tinjau ponsel dan desktop.

## Batas cakupan MVP

Termasuk: rekomendasi manual, ranking terlaris 30 hari, badge, filter katalog.

Tidak termasuk: rating pelanggan, review, rekomendasi personal, pengaturan periode dari admin, tabel analitik baru, dan penghitungan dari klik/kunjungan.

## Catatan yang perlu disepakati pemilik

Default spesifikasi memakai 30 hari terakhir dan status selain `cancelled` sebagai pesanan valid. Bila bisnis ingin sepanjang masa atau mengecualikan status lain, ubah definisi sebelum implementasi agar angka terlaris konsisten.

## Checklist programmer

- [ ] Baca file proyek terkait; jangan menebak payload order.
- [ ] Pastikan migration aman dan nilai lama default `false`.
- [ ] Hindari N+1 query dan muat hanya data yang diperlukan.
- [ ] Pertahankan pencarian, kategori, keranjang, dan checkout.
- [ ] Tambah test dan ringkas file/endpoint yang diubah.
- [ ] Jangan klaim rating atau “favorit pelanggan”; metrik hanya jumlah item yang dipesan.

---

Dokumen spesifikasi kode awal; ikuti kontrak aktual repository saat implementasi.
