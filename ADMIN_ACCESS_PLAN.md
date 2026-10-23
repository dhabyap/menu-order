# Rencana Pemisahan Akses Admin dan Pelanggan

Dokumen kerja untuk programmer junior. Tujuan: menu dan halaman pengelolaan toko hanya terlihat oleh admin, serta seluruh endpoint pengelolaan tetap terlindungi dari akses pelanggan.

## Kondisi proyek saat ini

- Halaman pelanggan dilayani dari `/` menggunakan `public/index.html`.
- Login admin tersedia di `/admin/login`.
- Route kelola toko sudah dikelompokkan di `/admin` dan memakai middleware `auth` pada `app/routes/web.php`.
- Login memakai satu akun admin dari konfigurasi `ADMIN_EMAIL` dan `ADMIN_PASSWORD` melalui `AdminAuthService`.
- Menyembunyikan tombol/menu saja bukan kontrol keamanan. Route dan API pengelolaan wajib tetap menolak pengunjung yang belum terautentikasi.

## Hasil yang diharapkan

1. Pelanggan tidak melihat tautan, tombol, navigasi, atau panel admin pada halaman pelanggan.
2. Pengunjung tanpa sesi admin yang membuka URL admin diarahkan ke login atau menerima status `401/403` sesuai jenis route.
3. Admin yang berhasil login dapat memakai seluruh fungsi kelola toko.
4. API pelanggan tetap berjalan seperti sebelumnya.
5. Tidak ada password admin atau rahasia autentikasi di HTML/JavaScript yang dikirim ke browser.

## Batasan keamanan

- Jangan mengandalkan URL admin yang sulit ditebak, CSS tersembunyi, atau JavaScript untuk mengamankan fitur.
- Jangan hapus middleware `auth` dari group route admin.
- Semua endpoint ubah data (produk, pesanan, pengaturan toko) harus berada di balik autentikasi admin, termasuk endpoint API bila ada.
- Jangan menaruh kredensial admin di frontend. Simpan di `.env` atau konfigurasi server; jangan commit `.env`.
- Jangan mengubah akses API pelanggan jika tidak terkait dengan pekerjaan ini.

## Langkah pengerjaan

### 1. Periksa antarmuka pelanggan

- Periksa `public/index.html`, `app/resources/views/`, dan `app/resources/js/` untuk menemukan menu, tombol, atau panel admin.
- Pastikan halaman pelanggan tidak menampilkan navigasi atau antarmuka admin.
- Jika halaman pelanggan menampilkan menu admin, hapus menu dari tampilan pelanggan; jangan hanya menyembunyikannya dengan `display: none`.
- Jika menu admin berada pada tata letak bersama, tampilkan hanya setelah server memastikan sesi admin valid. Jangan mengandalkan data yang dikirim browser.

### 2. Pastikan route admin dilindungi

- Periksa `app/routes/web.php`. Pertahankan semua route admin di dalam `Route::middleware('auth')->prefix('admin')->group(...)`.
- Pastikan setiap route baru yang mengubah data berada di dalam middleware.
- Pastikan halaman login bisa diakses tanpa login, sedangkan route logout dan halaman admin menangani sesi dengan benar.
- Jika tersedia API admin, lindungi semua endpoint-nya dengan middleware autentikasi yang sesuai. Periksa `app/routes/api.php`.
- Pastikan guard dan middleware menggunakan sesi admin yang benar.

### 3. Pastikan hanya admin yang mendapat akses

- Periksa `AdminAuthService` dan controller login. Hanya kredensial admin dari konfigurasi yang boleh membuat sesi admin.
- Jika aplikasi memakai beberapa jenis akun, tambahkan pemeriksaan peran atau izin di server untuk seluruh route admin. Akun yang sekadar sudah login tidak otomatis menjadi admin.
- Aplikasi saat ini memakai satu akun admin dari konfigurasi. Pastikan tidak ada jalur login pelanggan yang dapat mengakses grup route admin.

### 4. Lindungi sesi dan konfigurasi

- Setelah login berhasil, buat ulang ID sesi untuk mencegah session fixation.
- Saat logout, batalkan sesi dan buat ulang token CSRF.
- Gunakan HTTPS di lingkungan produksi. Atur cookie sesi dengan `Secure`, `HttpOnly`, dan `SameSite` yang sesuai.
- Atur `ADMIN_EMAIL` dan `ADMIN_PASSWORD` melalui konfigurasi lingkungan saat deployment. Utamakan kata sandi berbentuk hash jika konfigurasi saat ini mendukungnya dengan aman.

### 5. Jalankan pengujian regresi

Tambahkan atau perbarui feature test di `app/tests/Feature/` untuk memastikan:

- Pengunjung yang belum login tidak mendapat isi dashboard dari `GET /admin` atau halaman admin lainnya.
- Permintaan POST/PATCH/PUT/DELETE tanpa login tidak mengubah data.
- Kredensial salah tidak berhasil login dan tidak membuat sesi admin.
- Kredensial admin yang benar berhasil login dan membuka route admin.
- Setelah logout, sesi admin tidak berlaku dan route admin kembali ditolak.
- Halaman pelanggan `/` tetap berfungsi dan tidak menampilkan menu atau panel admin.
- API pelanggan dan proses pemesanan tetap berfungsi.

## Kriteria selesai

- Pelanggan tidak melihat fungsi admin pada antarmuka.
- Semua route admin menolak akses langsung tanpa sesi admin.
- Kredensial login yang salah tidak memberikan akses admin.
- Admin yang sah tetap dapat mengelola produk, pesanan, dan pengaturan toko seperti sebelumnya.
- Pengujian regresi terkait berhasil dijalankan.
- Tidak ada kredensial atau rahasia yang ditambahkan ke source code atau frontend.

## Laporan untuk reviewer

Dalam pull request, jelaskan:

- Lokasi menu pelanggan yang dihapus atau dibatasi berdasarkan sesi admin.
- Route admin dan middleware yang telah diperiksa.
- Feature test yang ditambahkan atau dijalankan beserta hasilnya.
- Konfigurasi lingkungan yang diperlukan; jangan cantumkan nilai rahasia dalam laporan.

### Catatan implementasi UI

- Tombol "Kelola" dan "Kelola toko" dihapus dari `public/index.html`.
- Bagian `#viewAdmin` dihapus dari HTML pelanggan.
- Semua route `/admin*` tetap dilindungi middleware `auth` di `app/routes/web.php` tanpa perubahan.
- Fitur admin hanya tersedia melalui URL langsung setelah login valid.
