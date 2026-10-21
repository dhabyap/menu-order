# PRD — Racik Tea

**Produk:** Website pemesanan minuman Racik Tea
**Versi:** 1.0
**Status:** Draf kebutuhan implementasi
**Dokumen acuan desain dan interaksi:** [`menu-order-prototype.html`](./menu-order-prototype.html)

## 1. Ringkasan

Racik Tea adalah website untuk pelanggan menjelajahi menu minuman, memilih opsi racikan, membuat pesanan, lalu mengirim rincian checkout melalui WhatsApp. Pengelola menggunakan dashboard untuk melihat ringkasan pesanan dan mengelola menu.

Website akan dibangun menggunakan Laravel dan database relasional, dengan deployment pada shared hosting. Prototype HTML menjadi sumber kebenaran untuk tampilan dan perilaku antarmuka.

## 2. Tujuan produk

- Memudahkan pelanggan menemukan minuman dan mengatur pesanan dari ponsel maupun desktop.
- Mengurangi langkah pemesanan dengan mengirim ringkasan order melalui WhatsApp.
- Memungkinkan pengelola memperbarui ketersediaan dan data menu serta memantau pesanan.
- Memindahkan penyimpanan dari browser lokal ke backend agar data dapat diakses lintas sesi/perangkat.

## 3. Batasan desain — wajib

`menu-order-prototype.html` adalah spesifikasi visual dan interaksi yang wajib diikuti. Hasil implementasi harus sama persis, tanpa perubahan atau interpretasi ulang.

- Jangan mengubah layout, ukuran, jarak, warna, tipografi, ikon, teks UI, urutan konten, animasi, breakpoint, atau keadaan komponen.
- Jangan menambah, menghapus, mengganti, atau menyederhanakan komponen maupun alur yang terlihat.
- Pertahankan perilaku responsive, drawer, modal, blur, scroll lock, fokus keyboard, dan feedback sesuai prototype.
- Integrasi Laravel hanya boleh mengganti sumber/persistensi data dan menghubungkan aksi yang sudah ada ke backend.
- Gunakan prototype sebagai baseline perbandingan visual pada ukuran viewport yang sama. Perbedaan visual dianggap bug.
- Jangan mengubah file prototype sebagai jalan pintas untuk menyesuaikan implementasi.

## 4. Pengguna dan peran

### Pelanggan

Pengunjung yang mencari menu, mengatur minuman dan jumlah, mengisi informasi pemesan, lalu menyiapkan pesanan untuk dikirim melalui WhatsApp.

### Pengelola

Pengguna terautentikasi yang mengelola menu dan melihat pesanan serta ringkasan operasional.

## 5. Cakupan fitur

### 5.1 Pemesanan pelanggan

1. Menampilkan katalog menu dan kategori.
2. Mencari menu berdasarkan kata kunci dan menyaring berdasarkan kategori.
3. Menampilkan status ketersediaan menu.
4. Menampilkan opsi racikan yang tersedia per menu, termasuk ukuran, tingkat kemanisan, dan penyajian sebagaimana ditetapkan prototype/data menu.
5. Menambahkan konfigurasi menu ke keranjang. Item dengan konfigurasi berbeda diperlakukan sesuai perilaku prototype.
6. Mengubah jumlah dan menghapus item dari keranjang.
7. Memilih ambil sendiri atau antar. Ongkos kirim dan aturan layanan harus dapat dikonfigurasi, bukan dianggap nilai produksi tetap.
8. Mengisi nama, nomor telepon, alamat bila diperlukan, dan catatan.
9. Memvalidasi kolom wajib dan nomor telepon dengan feedback yang konsisten dengan prototype.
10. Menampilkan subtotal, ongkos layanan bila berlaku, dan total.
11. Membuat rekaman pesanan di server sebelum mengarahkan pelanggan ke WhatsApp dengan ringkasan pesanan.
12. Menyediakan status/feedback checkout. Nomor WhatsApp tujuan harus berasal dari konfigurasi server.
13. Menyimpan informasi pelanggan yang diperlukan secara aman; jangan bergantung pada localStorage sebagai sumber data utama.

### 5.2 Dashboard pengelola

- Login dan logout pengelola.
- Ringkasan jumlah pesanan, menu tersedia/habis, dan estimasi omzet sesuai definisi status pesanan.
- Daftar menu dan fungsi tambah, edit, hapus, serta ubah ketersediaan.
- Validasi data menu dan harga di server.
- Daftar riwayat pesanan dengan informasi yang diperlukan untuk memproses order.
- Pembatasan akses: semua route dan aksi pengelolaan hanya tersedia bagi pengelola terautentikasi.

Dashboard harus mempertahankan tampilan dan struktur pada prototype. Autentikasi boleh menggunakan halaman/login flow yang sesuai implementasi hanya jika diperlukan backend, tanpa mengubah dashboard acuan.

## 6. Alur utama

### Pelanggan

1. Membuka katalog.
2. Mencari atau memilih kategori.
3. Memilih menu dan opsi racikan.
4. Menambahkan item ke keranjang.
5. Mengubah jumlah, layanan, dan rincian pemesan.
6. Meninjau total.
7. Mengirim checkout; server memvalidasi dan menyimpan order.
8. Browser membuka WhatsApp tujuan dengan ringkasan order yang sudah tersimpan.

### Pengelola

1. Login.
2. Melihat ringkasan dashboard.
3. Mengelola menu atau memeriksa riwayat pesanan.
4. Perubahan tersimpan di database dan tercermin pada katalog pelanggan.

## 7. Aturan bisnis

- Harga dan subtotal dihitung ulang di server dari harga menu aktif; jangan mempercayai total dari browser.
- Order menyimpan snapshot nama menu, opsi racikan, harga satuan, jumlah, layanan, ongkos layanan, subtotal, total, data pemesan, catatan, serta waktu pembuatan. Snapshot menjaga rincian order tetap benar meskipun menu berubah kemudian.
- Menu tidak tersedia tidak dapat dipesan.
- Backend memvalidasi bahwa opsi racikan dan kategori yang dikirim memang sah untuk menu terkait.
- Pengiriman WhatsApp bukan konfirmasi pembayaran. Status pembayaran tidak boleh dinyatakan berhasil tanpa integrasi pembayaran.
- Status order awal dapat menggunakan `pending`; daftar status final dan perubahan status oleh pengelola perlu ditetapkan sebelum implementasi apabila dibutuhkan.
- Ongkos antar, nomor WhatsApp, zona layanan, mata uang, dan jam operasional harus dapat dikonfigurasi.

## 8. Kebutuhan nonfungsional

- **Kesetiaan visual:** pixel-level comparison terhadap prototype pada viewport dan data yang sama; tidak ada perubahan desain.
- **Responsif:** fungsi dan layout konsisten di viewport mobile dan desktop seperti prototype.
- **Keamanan:** CSRF protection, validasi server-side, autentikasi dan otorisasi dashboard, hashing password, proteksi mass assignment, escaping output, dan rate limiting pada endpoint order/login yang sesuai.
- **Privasi:** kumpulkan data pelanggan seperlunya; jangan menyimpan data pembayaran sensitif.
- **Keandalan:** order tersimpan sebelum redirect WhatsApp; kegagalan simpan menampilkan feedback dan tidak membuat klaim order berhasil.
- **Aksesibilitas:** pertahankan label, focus state, navigasi keyboard, dialog semantics, dan pengumuman status yang ada di prototype.
- **Kinerja:** gunakan query terindeks dan pagination untuk data admin; hindari dependensi build berat yang tidak diperlukan.
- **Kompatibilitas hosting:** aplikasi dapat berjalan pada versi PHP/Laravel dan ekstensi yang tersedia di shared hosting target; versi final ditentukan setelah memeriksa dukungan hosting.

## 9. Stack dan arsitektur yang disarankan

- **Backend:** Laravel versi stabil yang kompatibel dengan versi PHP shared hosting.
- **Database:** MySQL/MariaDB yang disediakan hosting.
- **Frontend:** Blade untuk render server-side; CSS dan JavaScript prototype dipertahankan semirip mungkin. Gunakan Vite hanya jika hosting mendukung proses build saat development; deploy hasil build statis.
- **Penyimpanan:** database untuk menu, konfigurasi, akun pengelola, dan order. Session Laravel untuk sesi autentikasi/flash data.
- **Integrasi WhatsApp:** deep link `wa.me` dengan nomor tujuan dari konfigurasi; tidak mengasumsikan WhatsApp API atau pengiriman otomatis.
- **Deployment:** document root diarahkan ke direktori `public` Laravel bila hosting memungkinkan. Jika tidak, atur struktur publik dengan aman sesuai fasilitas hosting; jangan mengekspos `.env`, source aplikasi, atau direktori storage.
- **Operasional shared hosting:** gunakan cron hosting untuk scheduler bila diperlukan; jangan mensyaratkan worker queue permanen. Fitur inti harus bekerja tanpa Redis, Docker, atau proses daemon.

## 10. Model data minimum

### `users`
Akun pengelola: nama, email unik, password hash, timestamps.

### `categories`
Nama, slug unik, urutan tampil, status aktif, timestamps.

### `products`
Kategori, nama, deskripsi, harga dalam integer rupiah, gambar/path opsional, status tersedia, urutan tampil, timestamps.

### `product_options` (atau struktur opsi ternormalisasi)
Opsi racikan yang valid per menu, misalnya ukuran, tingkat kemanisan, dan penyajian; nilai, kemungkinan selisih harga, status aktif.

### `orders`
Nomor order unik, data kontak pemesan, alamat opsional, layanan, catatan, subtotal, biaya layanan, total, status order, timestamps.

### `order_items`
Order, referensi produk opsional, snapshot nama produk, konfigurasi racikan snapshot, harga satuan, jumlah, subtotal item.

Struktur akhir harus mendukung konfigurasi aktual dari prototype dan validasi kombinasi opsi. Data sensitif tidak boleh dicatat ke log.

## 11. Kebutuhan konfigurasi

Sediakan konfigurasi server untuk nomor WhatsApp, ongkos antar, zona/aturan antar, jam operasional bila berlaku, nama toko, dan kredensial database. Rahasia hanya di environment/config hosting; jangan commit nilai rahasia.

## 12. Kriteria penerimaan

1. Halaman pelanggan dan dashboard memiliki tampilan yang sama dengan `menu-order-prototype.html`; tidak ada perubahan UI yang disengaja.
2. Perbandingan screenshot pada viewport/data/kondisi yang sama tidak menunjukkan perbedaan layout atau styling.
3. Pencarian dan filter menghasilkan menu yang sesuai.
4. Pelanggan dapat membuat konfigurasi item, mengatur jumlah, dan menghapus item sesuai perilaku prototype.
5. Harga, ongkos, dan total yang disimpan dihitung ulang dan divalidasi server-side.
6. Order valid tersimpan di MySQL sebelum tautan WhatsApp dibuka; pesan memuat ringkasan yang benar.
7. Input tidak valid atau kegagalan server tidak membuat order sukses palsu dan menampilkan feedback yang tepat.
8. Pengunjung tanpa autentikasi tidak dapat mengakses atau mengubah data admin.
9. Perubahan menu pada admin tersimpan dan tercermin pada katalog.
10. Aplikasi dapat dipasang dan dijalankan pada shared hosting target tanpa proses server permanen.
11. Fungsi utama berjalan di mobile dan desktop; keyboard/focus/drawer behavior tetap sesuai prototype.

## 13. Di luar cakupan awal

- Pembayaran online dan rekonsiliasi otomatis.
- WhatsApp Business API atau notifikasi otomatis tanpa tindakan pelanggan.
- Kurir, pelacakan lokasi, dan integrasi peta.
- Sinkronisasi inventori bahan baku.
- Multi-cabang, multi-tenant, dan aplikasi native.
- Analitik lanjutan di luar ringkasan dashboard prototype.

## 14. Asumsi dan keputusan sebelum produksi

- Nomor WhatsApp prototype adalah placeholder dan wajib diganti sebelum live.
- Ongkos antar, zona jangkauan, jam operasional, dan kebijakan pembatalan perlu disahkan pemilik usaha.
- Akun pengelola pertama dibuat melalui proses setup aman, bukan registrasi publik.
- Versi PHP, ekstensi, database, batas storage, cron, dan kemampuan mengatur document root harus diverifikasi pada paket shared hosting yang dipilih.
- Isi produk, harga, gambar, serta opsi racikan produksi perlu disediakan pengelola.
- Prototype saat ini menyimpan data lokal dan bersifat demonstrasi; implementasi harus memigrasikan sumber data ke backend tanpa mengubah pengalaman visual.
