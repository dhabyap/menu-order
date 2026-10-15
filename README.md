# Racik Tea — Prototype Pemesanan Digital

Prototype web pemesanan minuman Racik Tea. Pelanggan dapat menjelajahi menu, menyesuaikan minuman, mengatur pesanan, lalu menyiapkan checkout melalui WhatsApp. Dashboard pengelola menyediakan contoh pengelolaan menu dan ringkasan pesanan.

## Demo

- **GitHub Pages:** https://dhabyap.github.io/menu-order/menu-order-prototype.html
- **Jalankan lokal:** buka `menu-order-prototype.html` di browser.

## Berkas

- [`menu-order-prototype.html`](./menu-order-prototype.html) — aplikasi prototype dalam satu file HTML.
- [`PRD_V2.md`](./PRD_V2.md) — dokumen kebutuhan produk.

## Fitur

### Pemesanan pelanggan

- Katalog menu dengan pencarian dan filter kategori.
- Penyesuaian pilihan minuman, seperti ukuran, tingkat kemanisan, dan penyajian.
- Keranjang dengan pengaturan jumlah, catatan, serta pilihan ambil di kedai atau antar.
- Formulir data pemesan yang tersimpan otomatis di browser.
- Rincian pesanan dapat disalin atau disiapkan untuk dikirim melalui WhatsApp.
- Tampilan responsif untuk perangkat desktop dan mobile.
- Modal dan drawer dengan latar belakang blur, dukungan keyboard, dan penguncian scroll saat terbuka.

### Dashboard pengelola

- Ringkasan jumlah pesanan, menu tersedia/habis, dan estimasi omzet.
- Tambah, edit, hapus, serta ubah ketersediaan menu.
- Tabel menu dan riwayat pesanan untuk demonstrasi.

## Teknologi dan penyimpanan

- HTML, CSS, dan JavaScript murni; tidak memerlukan proses build atau instalasi dependensi.
- Data menu, keranjang, profil pelanggan, dan pesanan disimpan di `localStorage` browser.
- Tidak ada backend atau sinkronisasi data antarperangkat. Menghapus data browser akan menghapus data prototype yang tersimpan.

## Batasan prototype

- Dashboard pengelola hanya contoh antarmuka lokal dan belum dilindungi autentikasi.
- Checkout mencatat pesanan di browser dan membuka WhatsApp dengan rincian pesanan; belum ada pemrosesan pembayaran atau konfirmasi pesanan dari server.
- Nomor WhatsApp tujuan pada kode masih berupa nomor contoh dan perlu disesuaikan sebelum digunakan.
- Harga ongkos antar dan data pesanan hanya untuk demonstrasi.

## Pengembangan

Edit `menu-order-prototype.html`, lalu buka kembali file tersebut di browser untuk melihat perubahan.

## Lisensi

Prototype ini ditujukan untuk penggunaan internal dan evaluasi produk.
