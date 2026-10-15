# Usulan Enhancement — Racik Tea

Dokumen ini berisi rekomendasi pengembangan berdasarkan prototype saat ini dan arah pada [`PRD_V2.md`](./PRD_V2.md). Tahap yang sedang dikerjakan dibatasi pada UI/UX di prototype satu-file HTML. Pilihan tech stack dan implementasi backend akan dibahas kemudian, setelah alur produk dan kebutuhan operasional disepakati.

## Ringkasan rekomendasi

Prioritas utama bukan menambah banyak fitur sekaligus, tetapi memastikan pesanan akurat dan pelanggan paham apakah pesanannya benar-benar tercatat. Prototype saat ini cocok untuk validasi tampilan dan alur, tetapi data hanya tersimpan di browser dan belum menjadi sistem pemesanan operasional.

**Urutan yang disarankan:**

1. Rapikan hal-hal penting pada prototype dan hilangkan informasi checkout yang masih berupa contoh.
2. Uji alur pelanggan dan admin dengan data serta kebijakan usaha yang sudah disetujui pemilik.
3. Jika sudah siap dipakai pelanggan sungguhan, sepakati kebutuhan operasional dan pilihan teknologi terlebih dahulu, lalu rencanakan penyimpanan pesanan dan menu secara terpusat.

## P0 — Sebelum prototype dibagikan sebagai demo

### 1. Lengkapi konfigurasi toko

- Ganti nomor WhatsApp contoh dengan nomor tujuan milik usaha.
- Jadikan ongkos kirim, nama toko, jam buka, dan status layanan sebagai pengaturan yang jelas.
- Tampilkan keterangan bahwa harga, ongkir, menu, dan pesanan masih data demo bila informasi final belum tersedia.
- Hindari menyatakan pesanan “berhasil dicatat” jika pencatatan hanya terjadi di browser pelanggan.

**Manfaat:** mencegah pelanggan mengirim pesanan ke nomor yang salah atau mengira demo sudah terhubung dengan operasional toko.

### 2. Perkuat validasi checkout dan hasil pengiriman

- Validasi format nomor telepon, bukan hanya memastikan kolom tidak kosong.
- Tampilkan ringkasan item, subtotal, ongkir, dan total sebelum membuka WhatsApp.
- Beri instruksi yang jelas bahwa pelanggan masih perlu menekan tombol kirim di WhatsApp.
- Sediakan fallback salin pesan jika WhatsApp tidak terbuka; tangani kegagalan Clipboard API dengan pesan yang jelas.
- Jangan mengosongkan keranjang atau mengklaim order terkirim sebelum pelanggan memahami bahwa WhatsApp hanya membuka draft pesan.

**Manfaat:** mengurangi pesanan yang hilang, data kurang lengkap, dan kebingungan setelah checkout.

### 3. Lindungi data prototype di browser

- Tangani data `localStorage` yang rusak atau tidak sesuai format agar aplikasi bisa pulih dengan pesan yang dapat dimengerti.
- Tambahkan tindakan untuk menghapus data demo secara sadar.
- Beri peringatan bahwa profil, keranjang, dan riwayat order tersimpan hanya di browser/perangkat yang digunakan.

**Manfaat:** mengurangi kehilangan data dan ekspektasi keliru tentang sinkronisasi pesanan.

## P1 — Penyempurnaan UX pelanggan dan pengelola

### Pengalaman pelanggan

- **Foto produk asli:** tambahkan foto yang konsisten, terkompresi, dan memiliki teks alternatif. Pakai placeholder yang rapi sebelum foto final tersedia.
- **Pilihan menu yang lebih fleksibel:** dukung opsi wajib/opsional dan batas pilihan minimum/maksimum, bukan hanya memilih opsi pertama sebagai default.
- **Catatan per item:** tempatkan catatan pada minuman tertentu agar instruksi tidak rancu ketika ada beberapa item. Pertahankan catatan checkout untuk instruksi umum.
- **Penyuntingan item di keranjang:** pelanggan dapat membuka kembali kustomisasi dan mengubah opsi tanpa menghapus item lalu membuatnya ulang.
- **Kejelasan harga:** tampilkan tambahan harga pada pilihan dan harga akhir per item dengan format Rupiah.
- **Kondisi toko dan menu:** tampilkan toko buka/tutup, perkiraan waktu, status habis, serta alasan tombol checkout tidak tersedia.
- **Aksesibilitas:** pastikan target sentuh cukup besar, label tombol jelas, kontras terbaca, dan semua tindakan dapat dilakukan dengan keyboard/screen reader.

### Dashboard pengelola

- Cari dan filter menu/pesanan serta urutkan pesanan berdasarkan waktu atau status.
- Tampilkan detail order yang mudah dibaca, termasuk item, opsi, catatan, layanan, alamat, dan total.
- Tambahkan status pesanan yang nyata (baru, dikonfirmasi, diproses, selesai, dibatalkan) jika alur operasional sudah disepakati.
- Gunakan arsip/nonaktifkan untuk menu yang pernah dipesan, bukan menghapus riwayatnya.
- Beri peringatan bahwa dashboard saat ini dapat diakses tanpa login dan hanya bekerja pada data lokal.

**Manfaat:** pelanggan lebih yakin memilih dan mengelola pesanan; pengelola lebih cepat menangani order.

## P2 — Kebutuhan untuk operasional sungguhan (stack menyusul)

Tahap ini belum dikerjakan pada prototype. Setelah alur dan proses usaha disepakati, evaluasi solusi produksi yang sesuai. Daftar berikut adalah kebutuhan produk, bukan keputusan tech stack:

- Penyimpanan terpusat untuk menu, pengaturan toko, pengguna admin, serta riwayat order.
- Login dan otorisasi dashboard; bedakan pemilik dan staff bila diperlukan.
- Server memvalidasi ulang produk, opsi, harga, ketersediaan, ongkir, dan total saat order dibuat.
- Simpan snapshot nama produk, opsi, dan harga pada order agar riwayat transaksi tidak berubah ketika menu diedit.
- Integrasi WhatsApp dengan pesan terformat serta notifikasi Telegram melalui backend bila kanal tersebut disetujui.
- Catat order sebelum mengirim notifikasi, sehingga kegagalan kanal komunikasi tidak menghilangkan pesanan.
- Perlindungan privasi dan keamanan: validasi input, rate limit, perlindungan secret, retensi data, backup, serta log operasional.
- Halaman konfirmasi order yang menampilkan status secara jujur dan nomor referensi.

**Penting:** admin tanpa autentikasi dan localStorage tidak aman/andal untuk pengelolaan order pelanggan. Jangan gunakan prototype sebagai sistem operasional sebelum backend dan kontrol akses tersedia.

## P3 — Setelah alur dasar stabil

- QR code menuju halaman menu dan penanda sumber kunjungan (misalnya QR meja atau brosur).
- Promo/banner dengan periode tayang, setelah pemilik memiliki materi dan aturan promo.
- Jam buka per hari dan pengaktifan/nonaktifkan layanan ambil/antar.
- Analitik sederhana: menu populer, jumlah order, dan sumber kunjungan—hindari mengumpulkan data pelanggan yang tidak dibutuhkan.
- PWA/offline hanya jika ada kebutuhan nyata; bukan prioritas sebelum order tersimpan terpusat.

## Kriteria sukses yang bisa dipakai

- Pelanggan dapat menyelesaikan pemilihan menu dan memahami langkah kirim WhatsApp tanpa bantuan.
- Total pada ringkasan sama dengan total pesan WhatsApp untuk kombinasi opsi dan layanan yang diuji.
- Tidak ada pesan “order berhasil” yang menimbulkan kesan palsu bahwa WhatsApp sudah menerima pesan.
- Tidak ada overflow horizontal pada lebar layar 360px dan seluruh alur utama bisa digunakan dengan keyboard.
- Pada versi produksi, order tetap tercatat walau WhatsApp/Telegram gagal dibuka atau mengirim notifikasi.
- Pemilik dapat mengubah ketersediaan menu dan melihat perubahan tanpa mengedit atau deploy ulang kode.

## Keputusan yang perlu disepakati sebelum implementasi

1. Apakah tujuan berikutnya demo yang lebih matang, atau sistem yang akan menerima order sungguhan?
2. Apa nama toko, nomor WhatsApp, jam buka, ongkir, wilayah antar, dan kebijakan saat toko tutup?
3. Apakah Telegram diperlukan, atau WhatsApp saja cukup untuk tahap pertama?
4. Apakah catatan diperlukan per item, untuk seluruh pesanan, atau keduanya?
5. Siapa yang boleh mengakses dashboard dan berapa lama data pelanggan/order disimpan?
6. Apakah konten menu, harga, foto, serta biaya tambahan sudah final dan disetujui pemilik?

## Rekomendasi tahap berikutnya

Lanjutkan dulu penyempurnaan UI/UX dan alur dalam file HTML prototype. Validasi dengan beberapa pelanggan/pengelola. Keputusan teknologi dan implementasi operasional dibahas pada tahap terpisah setelah kebutuhan dan proses usaha lebih jelas.
