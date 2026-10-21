# Rencana Refactor Clean Code — Racik Tea

Dokumen kerja untuk programmer junior. Tujuan: membuat kode lebih mudah dibaca, diuji, dan dirawat tanpa mengubah perilaku aplikasi.

## Tujuan

- Pisahkan routing, HTTP handling, validasi, logika bisnis, akses data, dan tampilan secara bertahap.
- Pertahankan kontrak API, aturan harga dan pesanan, autentikasi admin, UI, upload gambar, dan konfigurasi MySQL.
- Pastikan setiap perubahan kecil punya tes regresi dan mudah dibatalkan.

## Batasan wajib

Jangan mengubah URL/HTTP method, struktur payload, pesan/error yang terlihat pengguna, perhitungan harga, aturan opsi racikan, status pesanan, alur login, tampilan, data produksi, kredensial, atau konfigurasi deploy sebagai bagian refactor ini. Jangan membuat ulang aplikasi atau mengganti database. Perubahan perilaku hanya boleh dilakukan sebagai tugas terpisah dengan persetujuan pemilik.

Sebelum mulai, periksa status Git. Jangan menimpa perubahan lokal yang bukan bagian tugas. Jangan pernah membuka atau menyalin nilai rahasia dari `.env` ke dokumen, log, tes, atau commit.

## Gambaran kondisi saat ini

- `routes/api.php`: endpoint konfigurasi toko dan produk, serta validasi, validasi opsi racikan, kalkulasi harga, penyimpanan pesanan, dan respons checkout berada dalam route closure.
- `routes/web.php`: autentikasi, dashboard, pengaturan toko, CRUD produk, validasi opsi, dan pengelolaan file gambar berada dalam route closure.
- `resources/views/admin.blade.php`: halaman admin menggabungkan tampilan dengan CSS/JavaScript inline dan beberapa alur UI.
- `public/index.html`: katalog pelanggan memuat markup, styling, dan logika UI dalam satu file. Perlakukan sebagai frontend aktif, bukan berkas demo yang boleh dihapus.
- `tests/Feature/`: sudah ada tes fitur admin, autentikasi, katalog, checkout, dan gambar. Gunakan tes yang ada sebagai baseline sebelum menambah cakupan.

## Urutan kerja

### Tahap 0 — Amankan baseline

1. Baca `README.md`, `routes/api.php`, `routes/web.php`, migration, model, view, dan tes fitur terkait.
2. Jalankan tes lengkap: `php artisan test` dari folder `app/`.
3. Catat hasil, jumlah tes/assertion, dan kegagalan yang sudah ada. Jangan memperbaiki masalah lama yang tidak terkait.
4. Catat kontrak endpoint dari tes dan kode: method, URL, middleware, format respons, status HTTP, validasi, serta batas throttle.
5. Catat skenario UI yang harus dipertahankan: login, dashboard, tambah/edit/nonaktifkan/hapus menu, upload/ganti/hapus gambar, pengaturan, dan checkout.

**Selesai jika:** baseline tercatat dan dapat dijalankan kembali; tidak ada perubahan kode pada tahap ini.

### Tahap 1 — Tambah tes karakterisasi yang kurang

Tambahkan tes fitur terlebih dahulu, sebelum memindahkan implementasi:

- `GET /api/store-config` dan `GET /api/products`: field penting, tipe nilai, gambar, opsi, label, serta data kosong.
- `POST /api/orders`: sukses, produk tidak tersedia, jumlah/opsi tidak valid, alamat antar kosong, harga opsi, biaya antar, dan throttle.
- Admin: akses tanpa login ditolak/diarahkan; create/update/toggle/delete menu dan pengaturan tetap berfungsi.
- Gambar: tipe/ukuran diterima atau ditolak, upload baru, penggantian, penghapusan eksplisit, dan penghapusan produk.

Gunakan `Storage::fake('public')` untuk tes file. Gunakan data uji/database tes yang tersedia; jangan arahkan tes ke MySQL lokal/produksi. Hindari assertion yang mengunci detail markup yang tidak perlu.

**Selesai jika:** tes mencakup kontrak penting di atas dan lulus pada implementasi sekarang. Jika ditemukan bug, dokumentasikan terpisah; jangan diam-diam mengubah hasil bisnis dalam refactor.

### Tahap 2 — Pindahkan endpoint baca API

1. Buat controller di `app/Http/Controllers/Api/` untuk konfigurasi toko dan katalog, misalnya `StoreConfigController` dan `ProductController`.
2. Pindahkan closure GET satu per satu; route file hanya menunjuk ke controller.
3. Pertahankan nama field dan aturan transformasi katalog (`image_url`, `groups`, `labels`, serta ID frontend) persis seperti sebelumnya.
4. Untuk tahap awal, pertahankan query builder jika paling aman. Jangan menambah repository/abstraksi hanya demi pola.
5. Jalankan tes API terkait setelah setiap endpoint dipindah.

**Selesai jika:** URL dan respons tidak berubah, closure logika sudah hilang dari endpoint GET, tes terkait lulus.

### Tahap 3 — Pindahkan checkout dengan batas yang jelas

1. Buat `StoreOrderRequest` di `app/Http/Requests/` untuk validasi HTTP. Pertahankan rules, status 422, dan pesan yang diuji.
2. Buat `OrderController` dengan method `store` yang hanya mengoordinasikan request, proses, dan response.
3. Pindahkan perhitungan/validasi opsi dan total ke satu kelas berisi logika bisnis, misalnya `CreateOrder` di `app/Actions/` atau `OrderService` di `app/Services/`.
4. Simpan order dan item secara konsisten. Gunakan transaction bila perubahan implementasi mendukungnya tanpa mengubah kontrak.
5. Kalkulasikan harga dari data produk server, bukan angka harga dari browser. Pertahankan validasi produk tersedia dan kecocokan opsi.
6. Pertahankan throttle route, format respons sukses, kode order, dan semua error yang telah ditetapkan.

**Selesai jika:** tes checkout lulus untuk seluruh kasus; controller tidak berisi kalkulasi rincian bisnis; endpoint tetap kompatibel dengan frontend.

### Tahap 4 — Pindahkan aksi admin

Kerjakan satu aksi per perubahan: login/logout, dashboard, pengaturan, create produk, update produk, toggle ketersediaan, delete produk.

1. Buat controller di `app/Http/Controllers/Admin/`, misalnya `DashboardController`, `ProductController`, dan `StoreSettingsController`; autentikasi tetap memakai middleware yang berlaku.
2. Untuk validasi form, buat Form Request terpisah bila rules cukup kompleks atau digunakan berulang. Tempatkan validasi struktur opsi racikan pada kelas khusus yang mudah dites, bukan closure di dalam route group.
3. Pindahkan setiap route closure ke method controller. Jangan mengubah nama route yang dipakai Blade/JavaScript.
4. Pertahankan query dan urutan hasil pada tahap ekstraksi. Optimasi query boleh menjadi perubahan terpisah dengan tes.
5. Jalankan tes terkait setelah setiap aksi dipindahkan.

**Selesai jika:** route file deklaratif, middleware tetap aktif, dan semua aksi admin lulus tes lama serta baru.

### Tahap 5 — Rapikan siklus hidup gambar

1. Setelah perilaku baseline terlindungi, isolasi penyimpanan/penggantian/penghapusan gambar dalam kelas kecil khusus, misalnya `ProductImageService`.
2. Periksa kasus update: gambar lama hanya dihapus setelah gambar baru berhasil disimpan dan perubahan database berhasil.
3. Periksa kasus `remove_image`: gambar dihapus hanya setelah pembaruan database berhasil.
4. Pastikan penghapusan produk tidak meninggalkan file yang tidak terpakai; pertimbangkan kegagalan DB/storage dan buat perilakunya eksplisit.
5. Uji disk public palsu, termasuk kegagalan validasi. Jangan menghapus atau memindahkan file nyata saat menguji.

**Selesai jika:** tes membuktikan DB dan storage konsisten pada tambah, ganti, hapus gambar, dan hapus produk.

### Tahap 6 — Pecah view admin tanpa mengubah desain

1. Pindahkan satu bagian pada satu waktu dari `resources/views/admin.blade.php` ke Blade partial di `resources/views/admin/partials/` (misalnya header, statistik, menu, pesanan, pengaturan, dialog menu).
2. Pertahankan nama field, ID/class yang dipakai JavaScript, route, accessibility label, teks, dan urutan elemen.
3. Setelah setiap pemindahan, muat halaman admin, periksa console browser, dan uji ukuran desktop serta mobile.
4. Pindahkan CSS/JavaScript inline ke berkas asset terpisah hanya bila alur pemuatan proyek mendukungnya. Jangan menambah bundler/dependensi dalam tahap ini tanpa alasan dan persetujuan.

**Selesai jika:** Blade utama mudah dibaca, semua partial ter-render, tidak ada perubahan visual/fungsional yang tidak diminta.

### Tahap 7 — Rapikan katalog pelanggan secara konservatif

`public/index.html` merupakan frontend aktif. Jangan menghapusnya atau melakukan rewrite besar.

1. Tambahkan/pastikan tes kontrak API sebelum mengubah frontend.
2. Bila pemisahan asset diperlukan, pindahkan CSS atau JavaScript dalam potongan kecil ke `public/` dan perbarui referensi.
3. Pertahankan alur katalog, filter, pilihan racikan, keranjang, checkout, gambar, dan WhatsApp.
4. Verifikasi desktop dan viewport mobile; periksa Network/Console untuk file asset atau request yang gagal.

**Selesai jika:** katalog berperilaku sama di browser dan semua request API tetap memakai kontrak yang sama.

### Tahap 8 — Konsistensi dan dokumentasi

1. Jalankan `vendor/bin/pint --test` dari `app/`. Jika menemukan masalah format pada file yang disentuh, jalankan Pint hanya pada file tersebut dan tinjau diff.
2. Jangan menambah linter, framework frontend, package, atau abstraksi generik tanpa kebutuhan yang disetujui.
3. Perbarui `README.md` hanya bila struktur atau instruksi pengembangan benar-benar berubah. Jangan tulis credential.
4. Jalankan `php artisan test` dari `app/`, lalu periksa `git diff --check` dan tinjau diff lengkap.
5. Bila tersedia lingkungan lokal, verifikasi login, dashboard, upload gambar, katalog, dan checkout manual tanpa mengirim pesanan nyata.

**Selesai jika:** seluruh tes lulus, format bersih, dokumentasi akurat, dan diff hanya berisi perubahan refactor terkait.

## Aturan kerja untuk setiap PR/commit

- Satu tahap atau satu concern per perubahan; commit kecil dan deskriptif.
- Tes lulus sebelum dan sesudah perubahan. Jika gagal, cari regresi sebelum lanjut.
- Bandingkan JSON sebelum/sesudah untuk endpoint yang dipindahkan.
- Tinjau `git diff` untuk memastikan tidak ada `.env`, data lokal, gambar unggahan, atau perubahan di luar lingkup.
- Jangan melakukan deploy, push, migrasi data produksi, atau perubahan credential sebagai bagian rencana ini.
- Jika kontrak yang ada bertentangan dengan asumsi, berhenti dan minta keputusan pemilik; jangan menebak.

## Definisi refactor selesai

Refactor selesai saat tanggung jawab tiap file lebih jelas, route hanya mendefinisikan pemetaan/middleware, validasi HTTP terpisah, aturan bisnis dapat diuji tanpa browser, view/asset lebih modular, seluruh tes lulus, dan perilaku yang diamati pengguna tetap sama. Banyak file atau pola arsitektur tertentu bukan ukuran keberhasilan; keterbacaan dan risiko regresi yang rendah adalah ukurannya.
