# 📄 SEO Racik Tea — Audit Rank Math 55/100

## Masalah
Site `https://menu.abysoft.my.id/` (Racik Tea — pemesanan minuman) di-audit dengan Rank Math SEO Analyzer. Skor **55/100** (Basic SEO): 16/28 lolos, 2/28 warning, **10/28 gagal**.

> ⚠️ **PENTING:** Site ini **Laravel 10**, BUKAN WordPress. Saran tool "install Rank Math plugin" tidak berlaku. Semua perbaikan dikerjakan **manual** di file statis.

| No | Item Audit | Status | Bukti |
|----|-----------|--------|-------|
| 1 | Meta description | ❌ Gagal | Tidak ada `<meta name="description">` di `public/index.html` |
| 2 | Canonical tag | ❌ Gagal | Tidak ada `<link rel="canonical">` di `<head>` |
| 3 | WWW canonicalization | ❌ Gagal | `https://www.menu.abysoft.my.id/` balas HTTP 200 (konten sama, tanpa redirect) |
| 4 | OpenGraph meta | ❌ Gagal | 0 tag `og:` ditemukan |
| 5 | Schema.org data | ❌ Gagal | Tidak ada JSON-LD |
| 6 | Sitemaps | ❌ Gagal | `/sitemap.xml` → 404 |
| 7 | Internal links | ❌ Gagal | Nav hanya anchor `#productGrid` (0 link internal) |
| 8 | Minify CSS/JS | ❌ Gagal | `catalog.css` 39.244 B, `catalog.js` 42.307 B, tidak minified |
| 9 | Response time | ❌ Gagal | 1,44 s (tool) vs target ≤ 0,8 s |
| 10 | Keywords in title & description | ❌ Gagal | Akibat meta description kosong |

**Sudah lolos (jangan diubah):** title 32 char bagus (`Racik Tea — Menu & Pesanan Cepat`), 1 H1 (`Pilih minuman favoritmu.`), 3 H2 (`Cara pesan`, `Pesanan Anda`, `Cek Pesanan`), media query ada, homepage reachable, robots.txt valid, https aktif, directory listing mati, plugin/theme tidak terlihat publik, 0 broken link.

## Tujuan
Menaikkan skor SEO on-page manual. Murni frontend/statis — tidak menyentuh logika Laravel (controller, route, DB).

## File yang harus diubah
| No | File | Aksi |
|----|------|------|
| 1 | `app/public/index.html` | Tambah meta description, canonical, OpenGraph, JSON-LD (di `<head>`) |
| 2 | `app/public/.htaccess` | Redirect `www` → non-www (301) |
| 3 | `app/public/sitemap.xml` | **Buat baru** |
| 4 | `app/public/robots.txt` | Tambah baris `Sitemap:` |
| 5 | `app/public/assets/catalog/catalog.css` | Opsional: minify (prioritas rendah) |
| 6 | `app/public/assets/catalog/catalog.js` | Opsional: minify (prioritas rendah) |

## Langkah-Langkah Implementasi

### 1. Meta description + canonical + OG + Schema — `app/public/index.html`
Tempatkan semua blok di bawah `<title>` (baris 8), sebelum `<link rel="preconnect">` (baris 9).

**Meta description** (target keyword: *racik tea, pesan minuman, menu*):
```html
<meta name="description"
  content="Pesan Racik Tea online: pilih minuman favoritmu, racik sesuai selera (ukuran, kemanisan, topping), lalu checkout via WhatsApp. Antar atau ambil di kedai." />
```

**Canonical**:
```html
<link rel="canonical" href="https://menu.abysoft.my.id/" />
```

**OpenGraph** (untuk preview saat link dishare):
```html
<meta property="og:title" content="Racik Tea — Menu & Pesanan Cepat" />
<meta property="og:description"
  content="Pesan Racik Tea online: pilih minuman favoritmu, racik sesuai selera, lalu checkout via WhatsApp." />
<meta property="og:type" content="website" />
<meta property="og:url" content="https://menu.abysoft.my.id/" />
<meta property="og:locale" content="id_ID" />
```

**Schema.org JSON-LD** (sebelum `</head>`, baris 16):
```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Restaurant",
  "name": "Racik Tea",
  "url": "https://menu.abysoft.my.id/",
  "servesCuisine": "Minuman",
  "priceRange": "$",
  "acceptsReservations": "False"
}
</script>
```

### 2. Redirect www → non-www — `app/public/.htaccess`
Tambahkan tepat setelah `RewriteEngine On` (baris 6):
```apache
# Redirect www ke non-www (SEO canonical)
RewriteCond %{HTTP_HOST} ^www\.menu\.abysoft\.my\.id$ [NC]
RewriteRule ^(.*)$ https://menu.abysoft.my.id/$1 [L,R=301]
```

### 3. Sitemap — `app/public/sitemap.xml` (file baru)
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://menu.abysoft.my.id/</loc>
    <lastmod>2026-10-23</lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
```

### 4. Robots.txt — `app/public/robots.txt`
Tambahkan baris `Sitemap:` di akhir (file sekarang hanya `User-agent: *` + `Disallow:` kosong = izinkan semua):
```
User-agent: *
Disallow:

Sitemap: https://menu.abysoft.my.id/sitemap.xml
```

### 5–6. Minify CSS/JS — OPSIONAL, prioritas rendah
`catalog.css` (39 KB) dan `catalog.js` (42 KB) belum minified. Minify HANYA bila tidak merusak fungsi — `catalog.js` menangani keranjang + checkout WhatsApp, jangan minify manual sampai ada file sumber yang jelas. Tandai sebagai `// @preserve` bila memakai tool minifier.

## BUKAN bug (verifikasi saja — jangan diubah)
- **Internal links: 0** — site satu halaman (single-page), nav memang memakai anchor `#productGrid`, `#cekPesanan`. Ini normal untuk landing menu, bukan kekurangan.
- **Image ALT: 0** — memang tidak ada gambar lokal di halaman ini (produk dimuat via JS dari API). Bukan bug.
- **og:image** — belum ada aset gambar; tandai "butuh aset nanti", jangan buat gambar placeholder.
- **Response time 1,44 s** — butuh caching server (LiteSpeed/Cloudflare), di luar scope frontend. Catat saja.

## Acceptance Criteria
1. `curl -s https://menu.abysoft.my.id/ | grep -c "name=\"description\""` → `1`
2. `grep -c "rel=\"canonical\""` pada HTML → `1`
3. `curl -sI https://www.menu.abysoft.my.id/` → status **301** (redirect ke non-www)
4. `curl -s https://menu.abysoft.my.id/sitemap.xml` → 200, XML valid, berisi `<loc>https://menu.abysoft.my.id/</loc>`
5. `curl -s https://menu.abysoft.my.id/robots.txt` → mengandung `Sitemap:`
6. Halaman tetap tampil normal: menu, keranjang, checkout WhatsApp masih jalan (uji manual di browser)

## Cara Test
```bash
# Cek meta & canonical
curl -s https://menu.abysoft.my.id/ | grep -E "description|canonical|og:"

# Cek redirect www
curl -sI https://www.menu.abysoft.my.id/ | head -5

# Cek sitemap + robots
curl -s https://menu.abysoft.my.id/sitemap.xml | head -5
curl -s https://menu.abysoft.my.id/robots.txt
```
Ulangi audit di https://rankmath.com/tools/seo-analyzer/ — target skor naik ke ≥ 75/100 (semua 10 failure teratasi kecuali item opsional).

## Reference
- Audit asli: Rank Math SEO Analyzer 2026-10-23, `https://menu.abysoft.my.id/`
- Sumber lokal: `D:/Latihan/website-pacar/app/public/index.html` (head baris 4–16), `app/public/.htaccess`, `app/public/robots.txt`
- Google: https://developers.google.com/search/docs/fundamentals/seo-starter-guide
