# Reorder Menu (Dashboard) Implementation Plan

**Goal:** Tambah urutan susunan menu di dashboard admin tanpa mengubah UI prototype pelanggan.

**Architecture:** Tambah satu route PATCH baru, satu method controller, dan interaksi drag-and-drop di Blade admin. Gunakan SortableJS dari CDN (tanpa npm install).

**Tech Stack:** Laravel 10, MySQL, Blade, vanilla JS + SortableJS CDN.

---

## Pertanyaan jawab dulu

> Harus bikin makanan dulu biar bisa dipindah?
> Tidak. Urutan (`sort_order`) disimpan per produk yang ada. Jika belum ada produk, daftar kosong dan tombol reorder tidak muncul.

---

## Asumsi baseline

- Tabel `products` sudah punya kolom `sort_order` (unsigned integer, default 0). Terbukti ada di migration dan query `orderBy('products.sort_order')`.
- Produk baru otomatis dapat `sort_order = max(sort_order) + 1` (lihat `ProductController::store`).
- Belum ada route atau UI untuk mengubah urutan.
- Dashboard admin menggunakan layout tanpa bundler; menambahkan CDN aman.

---

## Files yang akan diubah

- `routes/web.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `resources/views/admin/partials/menu-table.blade.php`
- `tests/Feature/AdminProductReorderTest.php` (baru)

---

## Task 1: Tambah route reorder

**Objective:** Daftarkan endpoint PATCH untuk menerima urutan produk baru.

**Files:** Modify `routes/web.php`

**Step 1:** Buka `routes/web.php`, cari route group `prefix('admin')` dengan middleware `auth`.

**Step 2:** Tambahkan satu baris route setelah `admin.products.delete`:

```php
Route::patch('/products/reorder', [ProductController::class, 'reorder'])
    ->name('admin.products.reorder');
```

**Step 3:** Verifikasi

Jalankan: `cd app && php artisan route:list --name=admin.products.reorder`

Expected output menunjukkan route PATCH dengan middleware `auth`.

---

## Task 2: Tambah method reorder di controller

**Objective:** Terima daftar ID berurutan, lalu tulis ulang `sort_order` setiap produk.

**Files:** Modify `app/Http/Controllers/Admin/ProductController.php`

**Step 1:** Tambah method baru di class `ProductController`:

```php
/**
 * Update the display order of all products.
 */
public function reorder(\Illuminate\Http\Request $request)
{
    $validated = $request->validate([
        'order' => 'required|array|min:1',
        'order.*' => 'integer|exists:products,id',
    ]);

    DB::transaction(function () use ($validated) {
        foreach ($validated['order'] as $position => $id) {
            DB::table('products')
                ->where('id', $id)
                ->update(['sort_order' => $position + 1]);
        }
    });

    return back()->with('success', 'Urutan menu berhasil disimpan.');
}
```

**Step 2:** Verifikasi sintaks

Jalankan: `cd app && php artisan about`

Expected: tidak ada error fatal. Jika ada, baca pesan error dan perbaiki sebelum lanjut.

---

## Task 3: Tambah test fitur reorder

**Objective:** Pastikan route bisa diakses admin, ditolak guest, dan benar-benar mengubah urutan.

**Files:** Create `tests/Feature/AdminProductReorderTest.php`

**Step 1:** Tulis file test:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AdminProductReorderTest extends TestCase
{
    private function adminHeaders(): array
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        return ['HTTP_REFERER' => url('/admin')];
    }

    public function test_guest_cannot_reorder()
    {
        $response = $this->patchJson('/admin/products/reorder', ['order' => [1]]);

        $response->assertRedirect('/admin/login');
    }

    public function test_reorder_updates_sort_order()
    {
        $this->adminHeaders();

        DB::table('products')->insert([
            ['name' => 'A', 'price' => 1000, 'available' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'B', 'price' => 2000, 'available' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'C', 'price' => 3000, 'available' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $ids = DB::table('products')->orderBy('sort_order')->pluck('id')->all();

        $response = $this->patch('/admin/products/reorder', ['order' => array_reverse($ids)], $this->adminHeaders());

        $response->assertSessionHas('success');

        $this->assertEquals(3, DB::table('products')->where('id', $ids[0])->value('sort_order'));
        $this->assertEquals(2, DB::table('products')->where('id', $ids[1])->value('sort_order'));
        $this->assertEquals(1, DB::table('products')->where('id', $ids[2])->value('sort_order'));
    }

    public function test_reorder_rejects_invalid_ids()
    {
        $this->adminHeaders();

        $response = $this->patchJson('/admin/products/reorder', ['order' => [99999]]);

        $response->assertSessionHasErrors('order.0');
    }
}
```

**Step 2:** Jalankan test

```bash
cd app
php artisan test tests/Feature/AdminProductReorderTest.php
```

Expected: 3 passed.

**Catatan:** Jika factory `User` belum ada, pakai insert manual di test atau buat factory terlebih dahulu sesuai pola proyek.

---

## Task 4: Tambah drag handle di tabel menu

**Objective:** Tampilkan handle drag per baris produk agar pengelola bisa menyeret urutan.

**Files:** Modify `resources/views/admin/partials/menu-table.blade.php`

**Step 1:** Di bagian `<thead>`, tambah kolom baru di sebelah kiri sebelum "Menu Minuman":

```html
<th style="width:40px" aria-label="Urutkan">☰</th>
```

**Step 2:** Di bagian `<tbody>`, tiap `<tr>` produk tambah cell handle di kolom pertama:

```html
<td><span class="drag-handle" aria-hidden="true" style="cursor:grab;padding-right:8px">☰</span></td>
```

Hasilnya tiap baris produk punya 6 kolom: handle, menu, kategori, harga, status, tindakan.

**Step 3:** Tambah ID pada `<tbody>` agar SortableJS bisa mendeteksi:

```html
<tbody id="products-table-body">
```

**Step 4:** Verifikasi visual

Jalankan aplikasi, login ke `/admin`, cek tabel menu. Setiap baris harus punya handle `☰` di kiri. Tidak boleh merusak layout desktop/mobile.

---

## Task 5: Tambah JS drag-and-drop + submit

**Objective:** Saat pengelola seret baris, kirim urutan baru ke route reorder.

**Files:** Modify `resources/views/admin/partials/menu-table.blade.php`

**Lokasi JS:** Cari tag `<script>` di file Blade admin utama atau partial ini. Jika belum ada tag script di partial, tambah sebelum `</section>` penutup:

```html
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
    const tbody = document.getElementById('products-table-body');
    if (!tbody) return;

    let sortable = null;

    function initSortable() {
        sortable = new Sortable(tbody, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function () {
                const ids = Array.from(tbody.querySelectorAll('tr'))
                    .map(tr => tr.dataset.id)
                    .filter(Boolean);

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/products/reorder';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'order';
                input.value = JSON.stringify(ids);

                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'PATCH';

                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                form.appendChild(input);
                form.appendChild(method);
                form.appendChild(token);
                document.body.appendChild(form);

                form.submit();
            }
        });
    }

    initSortable();
})();
</script>
```

**Step 1:** Tambah `data-id` pada setiap `<tr>` produk di Blade:

```html
<tr data-id="{{ $product->id }}">
```

Letakkan di tag pembuka `<tr>` yang ada saat ini (tambahkan atribut, jangan ubah yang lain).

**Step 2:** Pastikan meta CSRF ada di layout admin. Cari `<meta name="csrf-token" content="{{ csrf_token() }}">` di `admin.blade.php` atau layout utama. Jika tidak ada, tambahkan di `<head>`.

**Step 3:** Verifikasi manual

1. Login admin.
2. Seret baris menu naik/turun.
3. Setelah drop, halaman reload dan urutan berubah.
4. Jika gagal, cek console browser dan network tab untuk response.

---

## Task 6: Verifikasi end-to-end

**Objective:** Pastikan tidak ada regresi di fitur lain.

**Step 1:** Jalankan seluruh test

```bash
cd app
php artisan test
```

Expected: semua test lulus, termasuk test baru reorder.

**Step 2:** Verifikasi kontrak API pelanggan tidak berubah

```bash
cd app
php artisan test tests/Feature/ApiContractTest.php
```

Expected: pass.

**Step 3:** Cek visual desktop + mobile

Buka `/admin` di browser, uji drag handle pada viewport mobile. Tabel harus tetap bisa di-scroll dan tombol aksi tidak tertutup handle.

---

## Batasan yang harus dijaga

- Jangan ubah URL atau perilaku katalog pelanggan.
- Jangan ubah layout, warna, spacing, atau teks di tabel selain kolom handle.
- Jangan tambah dependensi Composer/NPM. SortableJS pakai CDN.
- Jangan commit `.env` atau nilai rahasia.
- Jangan ubah nama route yang sudah dipakai JS/Blade.

---

## Definisi selesai

Fitur selesai saat admin bisa menggeser urutan menu dengan drag handle, urutan tersimpan ke `sort_order`, dashboard memuat urutan baru, dan seluruh test lulus tanpa regresi.