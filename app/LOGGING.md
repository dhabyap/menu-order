# Panduan Operasional Logging (LOGGING.md)

Log jalan bertingkat dan saling terhubung lewat satu `request_id`:

```
Browser ──[X-Request-Id]──► Middleware RequestId ──► Log context
   │                              │
   │                              ├─► File: storage/logs/app-YYYY-MM-DD.log (JSON, 30 hari)
   └──[POST /api/client-logs]─────┤
                                  ├─► DB: tabel event_logs (audit, halaman Log Sistem admin)
                                  └─► Notifikasi Slack (critical/error) jika LOG_SLACK_WEBHOOK_URL diisi
```

File log tertulis ke `storage/logs/app-YYYY-MM-DD.log` dengan channel `app` (daily, 30 hari).
Tabel audit: `event_logs`, retensi 90 hari via artisan `log:purge` (jadwal harian).
Waktu aplikasi: `Asia/Jakarta` (`config/app.php`).

---

## Konvensi penamaan event

`<domain>.<subjek>.<aksi>` — huruf kecil, titik pemisah:

`order.created` · `order.rejected` · `order.duplicate` · `order.db_failed` · `order.status_changed` · `order.lookup` · `track.form` · `track.viewed` · `quote.calculated` · `quote.failed` · `quote.fallback_used` · `quote.provider_error` · `product.created` · `product.updated` · `product.deleted` · `product.unavailable` · `settings.updated` · `admin.login.success` · `admin.login.failed` · `admin.login.throttled` · `admin.logout` · `admin.action.success` · `admin.action.failed` · `data.exported` · `admin.backup_downloaded` · `admin.order_viewed` · `backup.started` · `backup.completed` · `backup.failed` · `backup.table_failed` · `backup.purged` · `log.purged` · `client.error` · `http.client_error` · `http.server_error` · `http.request` · `store_config.whatsapp_missing`

---

## Aturan level log

| Level | Kapan |
|---|---|
| `debug` | detail teknis, hanya lokal |
| `info` | kejadian normal yang perlu jejak |
| `warning` | ditolak aturan bisnis / anomali wajar |
| `error` | kesalahan sistem (exception, API eksternal down, backup gagal) |
| `critical` | butuh tindakan segera |

---

## Privasi (penting)

Jangan simpan mentah: nomor telepon, alamat lengkap, nama customer, isi catatan pesanan.
Gunakan: phone `****7890`, nama `A***`, alamat `*****<hash4>`, catatan hash SHA-256.
`password` dan header `Authorization` → `[redacted]`.

---

## Cara investigasi per `request_id`

Setiap HTTP request ditandai header response `X-Request-Id` (UUID). Semua log file dan baris DB milik request itu pakai `request_id` yang sama.

1. Mulai dari `request_id` yang muncul di response header (atau di console browser).
2. File log: `grep -n "<request_id>" storage/logs/app-YYYY-MM-DD.log`
3. DB: halaman admin **Log Sistem** → filter `Request ID` → lihat semua event request tersebut.
4. Client-side error: cari `client.error` di Log Sistem dengan `request_id` yang sama untuk memastikan apakah error JS berhubungan dengan request server tertentu.

Contoh investigasi "pesanan #12 gagal":
- Cari `order.rejected` / `order.db_failed` di event_logs.
- Baca `context` → `reason` (`address_required`, `store_closed`, `quote_pending`, dst).
- Lihat baris `http.client_error` / `http.server_error` dengan `request_id` yang sama.

---

## Menyalakan notifikasi Slack (opsional)

Isi `LOG_SLACK_WEBHOOK_URL` di `.env` (sudah tersedia di `.env.example`). EventLogger akan mengirim notifikasi ke channel Slack untuk event level `critical` dan `error`.

---

## Checklist verifikasi

| # | Aksi | Yang harus muncul |
|---|---|---|
| 1 | Buka katalog | Log HTTP `200`, tidak ada error |
| 2 | Checkout tanpa alamat (layanan Antar) | `order.rejected` warning, `reason_code=address_required` |
| 3 | Set jam tutup toko ke masa lalu, checkout | `order.rejected` warning, `reason_code=store_closed` |
| 4 | Checkout normal | `order.created` info + `quote.calculated` info, `request_id` sama |
| 5 | Klik checkout 2× cepat | `order.duplicate` info, **tidak** ada 2 baris `order.created` |
| 6 | Kosongkan `ADMIN_PASSWORD`, login | `admin.login.failed` warning |
| 7 | Login 6× salah | `admin.login.throttled` warning + response 429 |
| 8 | Tambah menu dengan harga kosong | `product.created` **failed** (bukan success) |
| 9 | Tambah menu valid | `product.created` success + `activity_logs` terisi |
| 10 | Ekspor CSV pesanan | `data.exported` info dengan jumlah baris |
| 11 | Backup normal | `backup.completed` info berisi ukuran file |
| 12 | Rename folder `storage/app/backups` jadi read-only, backup | HTTP 500 + `backup.failed` error + **tidak** ada pesan "Backup selesai." |
| 13 | Matikan koneksi internet, hitung ongkir | `quote.provider_error` error, customer dapat fallback |
| 14 | `throw new Error('x')` di console browser | `client.error` warning di server |
| 15 | Akses `/halaman-tidak-ada` | Log HTTP `404` (warning) |
