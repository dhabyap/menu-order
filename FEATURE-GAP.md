# Feature Gap Analysis — Racik Tea
Dicetak: 2026-10-28
Repo: dhabyap/menu-order

## Ringkasan
Website sudah jalan: katalog pelanggan, keranjang, checkout WhatsApp, dashboard admin, kelola menu, kelola pesanan. Masih banyak fitur yang belum ada untuk production siap pakai. Berikut daftar yang **BELUM** (miss), dikelompokkan prioritas.

---

## PRIORITAS TINGGI (butuh sebelum production sungguhan)

### 1. Pelanggan
- **Akun pelanggan (register/login)** — saat ini checkout anonymous, tidak ada histori pesanan terhubung akun. Pelanggan tidak bisa lihat status pesanan kecuali tautan WhatsApp.
- **Halaman tracking pesanan** — pelanggan butuh halaman `/{kode_pesanan}` atau `track/{code}` untuk cek status tanpa login.
- **Gambar produk** — field `image_path` sudah ada dan katalog menampilkan gambar per produk.
- **Jam operasional** — tidak ada konfigurasi jam buka/tutup. Website bisa dipesan kapan saja meskipun tutup.

### 2. Admin/Operasional
- **Role & staff** — cuma ada 1 akun admin (`ADMIN_EMAIL`). Tidak bisa tambah kasir, kitchen display, atau staff lain.
- **Kitchen Order System (KDS)** — tidak ada antrian dapur, tidak ada print struk/kitchen ticket.
- **Export data** — admin tidak bisa export pesanan ke CSV/Excel untuk rekonsiliasi.
- **Backup database** — shared hosting mati kapan saja. Tidak ada backup otomatis.
- **Activity log** — tidak ada audit trail siapa yang mengubah menu/hapus pesanan.
- **Pembatalan pesanan dengan alasan** — admin bisa batalkan tapi tidak ada catatan mengapa.

### 3. Keamanan & Keandalan
- **Rate limiting checkout** — endpoint `/api/checkout` tidak ada throttle khusus. Bisa dibom spam.
- **Backup otomatis** — MySQL bisa rusak di shared hosting.
- **Validasi zona antar** — ongkos kirim flat `Rp 0` default. Tidak ada aturan zona/minimal order.

---

## PRIORITAS MENENGAH (setelah production stabil)

### 4. Pemesanan & Pembayaran
- **Pembayaran online** — saat ini cuma WhatsApp. Belum ada transfer bank/e-wallet/dana/ovo.
- **Invoice/struk digital** — admin bisa cetak struk dari dashboard.
- **Voucher/promo** — tidak ada sistem diskon atau kode promo.
- **Pengembalian dana/refund** — tidak ada alur resi.
- **Split bill / pembayaran gabungan** — satu pesanan bayar oleh beberapa orang.

### 5. Pelanggan
- **Riwayat pesanan terhubung akun** — jika ada login pelanggan, mereka bisa lihat semua order mereka.
- **Ulasan/rating** — tidak ada review produk.
- **Favorit** — tidak ada wishlist.
- **Re-order** — tombol "Pesan lagi" di halaman detail pesanan.
- **Notifikasi WhatsApp** — saat ini manual. Tidak ada notifikasi otomatis ke pelanggan saat status pesanan berubah.

### 6. Admin
- **Import produk via Excel/CSV** — input menu satu per satu lambat.
- **Bulk edit** — ubah harga/kategori/hapus beberapa menu sekaligus.
- **Dashboard lanjutan** — grafik penjualan mingguan/bulanan, produk terlaris, jam sibuk.
- **Manajemen kategori** — tambah/edit/hapus kategori (saat ini auto-create saat buat produk).
- **Manajemen opsi racikan** — opsi size/tingkat manis/topping hardcoded di JSON per produk. Tidak ada master opsi reusable.

---

## PRIORITAS RENDAH (nice-to-have)

### 7. Pengalaman
- **PWA/offline** — tidak bisa dipakai offline. Tidak ada Add to Homescreen.
- **Dark mode** — cuma tema terang.
- **Multi bahasa** — cuma Bahasa Indonesia.
- **SEO meta tags** — title/description statis. Tidak ada Open Graph untuk share WhatsApp/FB.
- **Banner/announcement** — tidak ada banner promosi di atas katalog.
- **Sitemap & robots.txt** — SEO dasar.

### 8. Teknis
- **Queue/background job** — semua proses sync. Tidak ada antrian untuk email/notifikasi berat.
- **Image CDN/optimization** — upload gambar mentah ke storage. Tidak ada resize/compress otomatis.
- **Sentry/error monitoring** — error 500 langsung ke user tanpa monitoring.
- **Health check endpoint** — untuk monitoring uptime shared hosting.
- **Caching** — katalog hit database tiap request. Tidak ada cache Redis/file.

---

## Fitur yang SUDAH ADA (cek ulang dulu sebelum minta ditambah)

- Katalog + search + filter kategori
- Keranjang + opsi racikan (size, manis, topping, harga tambahan)
- Checkout simpan ke DB + redirect WhatsApp
- Admin login + auth
- Dashboard statistik (pesanan, menu tersedia/habis, omset)
- CRUD menu + upload gambar
- Toggle ketersediaan menu
- Badge "Habis" + disable tombol untuk produk unavailable di katalog
- Reorder menu via drag-and-drop
- Setting toko (nama, tagline, hero, WhatsApp, ongkir, logo, badge)
- Manajemen pesanan (list, detail, update status: menunggu/diproses/selesai/dibatalkan)
- Lookup pesanan via nomor WhatsApp
- Tombol Dashboard Admin di katalog jika login
- Test: API contract, auth, reorder, order processing

---

## Rekomendasi urutan kerja

1. **Sekarang:** Fix bug produksi (PDO MySQL), test lokal, pull ke server.
2. **Minggu 1:** Jam operasional + badge habis di katalog + halaman tracking pesanan publik.
3. **Minggu 2:** Export CSV + backup DB + rate limiting checkout + activity log.
4. **Minggu 3:** Pembayaran online + invoice print.
5. **Minggu 4:** Import CSV + bulk edit + kitchen display.
