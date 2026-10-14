# PRD v2 — Website Pemesanan Minuman

Versi: 2.0 · Bahasa: Indonesia · Status: Draft siap dibangun (menunggu konten dari pemilik)

## 1. Ringkasan

Website order untuk usaha minuman: pelanggan melihat menu, memilih varian, mengisi keranjang, lalu mengirim order ke WhatsApp/Telegram pemilik. Pemilik mengelola menu, harga, banner, dan pengaturan lewat dashboard admin tanpa deploy ulang. Order juga dicatat di database supaya ada riwayat dan pemilik bisa melihat order baru.

**Tujuan utama**

- Pelanggan menyelesaikan order dalam ≤ 2 menit dari buka link/scan QR.
- Pemilik bisa ubah menu/harga/status habis sendiri dalam < 1 menit.
- Tidak ada order yang "hilang": setiap order tercatat di DB walaupun pengiriman ke chat gagal.

**Bukan tujuan (MVP):** akun pelanggan, POS, stok otomatis, payment gateway/QRIS otomatis, promo code, loyalty, delivery tracking, aplikasi native.

## 2. Keputusan Default

Development tidak boleh menunggu semua jawaban pemilik. Kolom "Default" dipakai sampai ada keputusan lain.

| Topik | Default | Perlu konfirmasi pemilik? |
|---|---|---|
| Stack | Laravel monolit (Blade + Livewire/Alpine) + MySQL + Tailwind | Tidak (keputusan teknis) |
| Penyimpanan order | Order **disimpan di DB** + dikirim ke chat | Ya, bila pemilik tidak mau data pelanggan disimpan |
| Kanal order | WhatsApp (link `wa.me`) + Telegram (via backend), dapat diaktif/nonaktifkan di admin | Ya: nomor WA, bot/chat Telegram |
| Pembayaran | Manual (transfer/tunai), dikonfirmasi pemilik lewat chat | Ya |
| Layanan | Ambil + antar, dapat diaktif/nonaktifkan | Ya: ongkir & wilayah |
| Admin | 1 akun pemilik (role `owner`), siap tambah `staff` | Ya: email login |
| Toko tutup | Menu tetap tampil, checkout dinonaktifkan dengan pesan jelas | Ya: jam buka |
| Domain | Subdomain dulu (mis. di abysoft.my.id), pindah ke domain final belakangan | Ya |

**Kenapa Laravel monolit, bukan Next.js + Laravel:** satu deployment, tanpa CORS dan auth lintas domain, hosting lebih murah, lebih cepat selesai. Pisah frontend hanya bila ada kebutuhan nyata (mis. aplikasi lain memakai API yang sama).

## 3. Pengguna

- **Pelanggan:** akses dari ponsel (mayoritas), tanpa login.
- **Pemilik (`owner`):** akses penuh dashboard, termasuk pengaturan kanal order.
- **Staff (opsional):** kelola menu dan status order; tidak bisa ubah nomor WA/Telegram, kredensial, atau akun.

## 4. Fitur Halaman Publik

### 4.1 Menu

- Header usaha: logo, nama, deskripsi, jam buka, alamat (opsional), indikator **Buka/Tutup**.
- Banner promosi (opsional, bisa lebih dari satu, dijadwalkan mulai/selesai).
- Navigasi kategori yang menempel (sticky) + search bila produk > 15.
- Kartu produk: foto, nama, deskripsi, harga (Rp), label (mis. Baru, Best Seller), status Tersedia/Habis. Produk habis tampil abu-abu, tombol tambah dinonaktifkan.
- Detail produk (modal/sheet): pilih grup opsi, jumlah, catatan.

### 4.2 Grup opsi

Opsi dikelompokkan, bukan daftar datar:

- Contoh grup: Ukuran (wajib, pilih 1), Gula (wajib, pilih 1), Es (opsional, pilih 1), Topping (opsional, pilih 0–3).
- Tiap pilihan punya harga tambahan (boleh 0).
- Grup dapat dipakai ulang untuk banyak produk (relasi many-to-many) agar admin tidak input berulang.
- Tombol "Tambah ke keranjang" aktif hanya bila semua grup wajib terisi dan batas min/maks terpenuhi.

### 4.3 Keranjang

- Tambah, ubah jumlah, hapus. Item yang sama dengan opsi berbeda menjadi baris terpisah.
- Disimpan di `localStorage` agar bertahan saat refresh; saat dimuat ulang, item divalidasi ke server (harga berubah/habis/dihapus → beri tahu pelanggan).
- Desktop: panel "Pesanan Anda" di kanan. Mobile: bar ringkasan di bawah (jumlah item + total) yang membuka bottom sheet.
- Semua kalkulasi memakai integer Rupiah.

### 4.4 Checkout

- Form: nama, nomor telepon (validasi format Indonesia), tipe layanan, alamat (hanya jika antar), catatan.
- Ringkasan sebelum kirim: item, opsi, subtotal, ongkir, total, kanal tujuan.
- Cart kosong, toko tutup, atau layanan tidak aktif → tombol kirim nonaktif dengan alasan yang jelas.
- Setelah kirim: halaman konfirmasi berisi nomor order dan teks jujur. Contoh: "Pesanan kamu sudah kami catat. Menunggu konfirmasi pemilik." Jangan klaim "diterima/dibayar" sebelum pemilik mengonfirmasi.

### 4.5 Alur pengiriman order

1. Browser mengirim cart ke `POST /api/orders`.
2. Server memvalidasi ulang item, opsi, harga, status tersedia, jam buka, dan layanan; menghitung total sendiri; menyimpan order + snapshot item (nama dan harga saat transaksi) dengan status `baru`.
3. **Telegram:** server mengirim notifikasi ke chat pemilik lewat bot (token hanya di `.env`). Kegagalan kirim dicatat dan tidak membatalkan order.
4. **WhatsApp:** server mengembalikan URL `wa.me` berisi teks order berformat; browser membukanya. Pelanggan menekan kirim di WhatsApp sendiri, jadi dashboard menampilkan order sebagai `baru` sampai pemilik mengonfirmasi.
5. Fallback: jika WA gagal terbuka, tampilkan tombol "Salin pesanan".

### 4.6 Contoh format pesan

```
🧾 Pesanan #A-0123
Nama: Rina
Telp: 0812xxxxxxx
Layanan: Antar
Alamat: Jl. Contoh No. 1

1x Jasmine Osmanthus (Besar, Less Sugar, Es Normal) — Rp27.000
2x Teh Tarik (Panas) — Rp26.000
Catatan: tanpa sedotan

Subtotal: Rp53.000
Ongkir: Rp5.000
Total: Rp58.000
Pembayaran: konfirmasi manual
```

*(Data di atas hanya contoh format, bukan menu sebenarnya.)*

## 5. Dashboard Admin

Akses: `/admin`, login server-side, semua halaman di balik middleware auth.

| Modul | Kemampuan |
|---|---|
| Ringkasan | Item aktif, item habis, order hari ini, order `baru` |
| Kategori | CRUD, urutan (drag/atur manual), aktif/nonaktif |
| Menu | CRUD, foto, harga, label, kategori, tersedia/habis (toggle cepat), urutan, arsip (bukan hard delete) |
| Grup opsi | CRUD grup + pilihan, wajib/opsional, min/maks, harga tambahan, lampirkan ke produk |
| Banner | Gambar, tautan opsional, jadwal tayang, urutan |
| Info usaha | Nama, deskripsi, logo, warna utama, alamat, kontak, jam buka per hari |
| Pengaturan order | Layanan ambil/antar, ongkir, nomor WA, Telegram chat ID, aktif/nonaktif per kanal. **Hanya `owner`.** |
| Order | Daftar, filter status/tanggal, detail; status: `baru → dikonfirmasi → diproses → selesai` atau `dibatalkan` |

Aturan:

- Hapus kategori/produk yang masih dipakai → arsip/nonaktif, dengan dialog konfirmasi.
- Setiap perubahan pengaturan order dicatat (siapa, kapan) di tabel audit sederhana.
- Perubahan tampil di halaman publik tanpa deploy (cache dibersihkan saat simpan).

## 6. Model Data

```
users(id, name, email, password_hash, role[owner|staff])
settings(key, value)                         -- info usaha, kanal, ongkir, warna
business_hours(id, weekday, open_at, close_at, is_closed)
categories(id, name, sort_order, is_active)
products(id, category_id, name, description, price_idr, image_path,
         labels json, is_available, is_archived, sort_order)
option_groups(id, name, is_required, min_select, max_select)
option_choices(id, group_id, name, extra_price_idr, sort_order, is_active)
product_option_group(product_id, option_group_id, sort_order)
banners(id, image_path, link_url, starts_at, ends_at, sort_order, is_active)
orders(id, public_code, customer_name, phone, service_type, address, note,
       subtotal_idr, delivery_fee_idr, total_idr, status, channel,
       telegram_sent_at, created_at)
order_items(id, order_id, product_id, product_name, unit_price_idr, qty,
            options_snapshot json, line_total_idr, note)
audit_logs(id, user_id, action, subject, meta json, created_at)
```

Prinsip: semua uang `integer` Rupiah; `order_items` menyimpan snapshot sehingga riwayat tidak berubah saat harga menu diperbarui.

## 7. Keamanan dan Privasi

- Password di-hash (bcrypt/argon2), cookie session `HttpOnly` + `Secure` + `SameSite`, CSRF aktif, rate limit pada login.
- **Rate limit endpoint order** (mis. 5 order/menit/IP + honeypot atau Turnstile bila spam muncul) agar bot Telegram dan DB tidak dibanjiri.
- Validasi server untuk semua input; harga dari browser diabaikan.
- Upload foto: whitelist tipe (jpg/png/webp), batas ukuran, nama file di-generate, resize/kompres ke webp, simpan di storage Laravel, bukan sebagai input bebas.
- Token Telegram dan secret lain hanya di `.env`; tidak pernah masuk response/JS.
- Data pelanggan: hanya nama, telepon, alamat (jika antar), catatan. Tampilkan teks singkat tujuan pemakaian data di checkout. Retensi: hapus/anonimkan data order setelah periode yang disepakati (default usul: 12 bulan).
- Jangan mengumpulkan data kartu/PIN/OTP.

## 8. Non-Fungsional

- **Performa:** LCP < 2,5 dtk di 4G; gambar lazy-load, webp, ukuran responsif; menu di-cache dan dibersihkan saat admin mengubah.
- **Responsif:** tanpa horizontal overflow mulai 360px; diuji di 360, 390, 768, desktop.
- **Aksesibilitas:** navigasi keyboard, focus terlihat, alt text, kontras memadai, target sentuh ≥ 44px.
- **SEO/Share:** title, meta description, Open Graph (nama usaha, banner) agar link di WhatsApp/Instagram tampil rapi; favicon.
- **Operasional:** backup harian DB + folder foto, error logging, `/health` sederhana.
- **Bahasa:** Indonesia; format Rupiah `Rp27.000`.

## 9. QR Code

QR statis menuju URL menu final. Buat setelah domain disetujui, uji dengan kamera ponsel dan cetak uji dulu sebelum cetak massal. Opsional: parameter `?src=qr` untuk melihat jumlah scan.

## 10. Konten dari Pemilik

- Nama brand, logo, warna, font, banner (aset orisinal/berizin; jangan menyalin brand referensi).
- Daftar produk: nama, deskripsi, harga, kategori, foto asli, status.
- Grup opsi dan harga tambahan.
- Jam buka, alamat, nomor WA, Telegram chat tujuan, layanan ambil/antar, ongkir/wilayah.
- Pembayaran yang dipakai dan kebijakan (refund/batal) bila ada.

Jangan mengarang produk, harga, bahan/alergen, alamat, ongkir, atau jam operasional. Data contoh hanya untuk development dan **wajib dihapus sebelum publikasi** (dibuat via seeder khusus `local`).

## 11. Kriteria Penerimaan

**Publik**
- [ ] Kategori, detail, harga, dan status produk tampil benar.
- [ ] Grup opsi wajib/opsional dan batas min/maks ditegakkan.
- [ ] Subtotal/total benar untuk kombinasi opsi dan jumlah.
- [ ] Cart bertahan saat refresh dan divalidasi ulang ke server.
- [ ] Toko tutup/layanan nonaktif memblokir checkout dengan pesan jelas.
- [ ] Order tersimpan di DB dan muncul di dashboard dengan nomor order.
- [ ] WhatsApp terbuka dengan pesan terformat; Telegram terkirim lewat backend; kegagalan Telegram tidak menghilangkan order.
- [ ] Pesan status jujur (tidak mengklaim diterima/dibayar sebelum konfirmasi).

**Admin**
- [ ] Dashboard tidak bisa diakses tanpa login; `staff` tidak bisa membuka pengaturan kanal.
- [ ] CRUD kategori, produk, grup opsi, banner, info usaha berfungsi dan langsung tampil di publik.
- [ ] Arsip menggantikan hard delete untuk data yang dipakai.
- [ ] Perubahan pengaturan order tercatat di audit log.

**Kualitas**
- [ ] Tidak ada secret di respons browser.
- [ ] Rate limit order dan login aktif.
- [ ] Tidak ada overflow horizontal ≥ 360px; keyboard dan focus OK.
- [ ] Lighthouse mobile: Performance ≥ 85, Accessibility ≥ 90.
- [ ] Production build/deploy lulus; backup terjadwal diuji restore.
- [ ] Tidak ada data contoh/fiktif di versi publik.

## 12. Tahapan

1. **Fondasi:** setup Laravel, auth admin, migrasi, seeder dev.
2. **Admin inti:** kategori, produk, grup opsi, upload foto, info usaha.
3. **Publik:** menu, detail + opsi, keranjang, tampilan responsif.
4. **Checkout:** endpoint order, snapshot, WA + Telegram, rate limit, halaman konfirmasi.
5. **Order admin + banner + audit log.**
6. **QA:** harga/opsi, responsif, aksesibilitas, keamanan, performa.
7. **Konten final + deploy + QR code** setelah pemilik menyetujui.

## 13. Risiko

| Risiko | Mitigasi |
|---|---|
| Konten/aset pemilik terlambat | Seeder dev + tema bisa diubah dari admin; jangan blok pengembangan |
| Spam ke endpoint order/Telegram | Rate limit, honeypot/Turnstile, validasi ketat |
| Pelanggan tidak menekan kirim di WhatsApp | Order sudah tercatat di DB + notifikasi Telegram |
| Harga di browser dimanipulasi | Server hitung ulang dari DB |
| Foto besar memperlambat halaman | Kompres/resize otomatis saat upload |

## 14. Catatan dan Asumsi

- Screenshot "Artisan Tea" dipakai hanya sebagai acuan struktur (menu + panel pesanan + kirim ke WA/Telegram). Harga/produk di screenshot adalah referensi, bukan data final.
- Referensi `liat.menu` belum diperiksa langsung.
- Brand, logo, foto, dan copy final harus milik pasangan/pemilik usaha; jangan menyalin aset TEA ARTISAN.

## 15. Pertanyaan Terbuka (tidak memblokir development)

1. Nama usaha/brand, logo, warna.
2. Nomor WhatsApp dan Telegram chat/bot tujuan.
3. Ambil/antar, ongkir, wilayah layanan.
4. Pembayaran manual atau nanti gateway resmi.
5. Jam buka dan perilaku saat tutup (default: checkout nonaktif).
6. Email admin dan apakah perlu akun staff.
7. Domain/hosting final.
8. Setuju data order disimpan di DB? Berapa lama retensinya?
