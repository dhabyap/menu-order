@php
    $orderStatuses = \App\Support\OrderStatus::labels();
@endphp

<div class="categories-toolbar">
    <label class="categories-search" for="categories-search">
        <span aria-hidden="true">⌕</span>
        <input id="categories-search" type="search" placeholder="Cari nama kategori" autocomplete="off">
        <kbd>Ctrl K</kbd>
    </label>
</div>

<table class="categories-table" id="categories-table">
    <thead>
        <tr>
            <th scope="col">Urutan</th>
            <th scope="col">Nama</th>
            <th scope="col">Slug</th>
            <th scope="col">Status</th>
            <th scope="col">Produk</th>
            <th scope="col">Aksi</th>
        </tr>
    </thead>
    <tbody id="categories-body" class="sortable-list">
        @forelse($categories as $category)
            <tr data-id="{{ $category->id }}" data-sort="{{ $category->sort_order }}" class="category-row">
                <td class="sort-handle" aria-label="Seret untuk mengubah urutan">
                    <span aria-hidden="true">⋮⋮</span>
                </td>
                <td class="category-name">{{ $category->name }}</td>
                <td class="category-slug"><code>{{ $category->slug }}</code></td>
                <td>
                    <span class="badge {{ $category->active ? 'badge-active' : 'badge-inactive' }}">
                        {{ $category->active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td class="category-count">
                    {{ $category->products()->count() }} produk
                </td>
                <td class="category-actions">
                    <button class="button button-soft button-small edit-category" data-id="{{ $category->id }}" data-name="{{ $category->name }}" data-sort="{{ $category->sort_order }}" data-active="{{ $category->active ? '1' : '0' }}" aria-label="Edit kategori {{ $category->name }}">Edit</button>
                    <button class="button button-danger button-small delete-category" data-id="{{ $category->id }}" data-name="{{ $category->name }}" aria-label="Hapus kategori {{ $category->name }}">Hapus</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <div class="empty-state">
                        <strong>Belum ada kategori</strong>
                        Klik "Tambah Kategori" untuk membuat kategori baru.
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if($categories->count() > 1)
<div class="merge-form" style="margin-top: 16px; padding: 16px; background: var(--bg-soft); border-radius: 12px; border: 1px solid var(--line);">
    <h4 style="margin: 0 0 12px;">Gabung Kategori</h4>
    <p style="font-size: 13px; color: var(--muted); margin-bottom: 12px;">
        Pilih beberapa kategori sumber dan satu kategori target. Semua produk dari kategori sumber akan dipindahkan ke kategori target, lalu kategori sumber dihapus.
    </p>
    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: end;">
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; font-size: 12px; margin-bottom: 4px;">Kategori Sumber (minimal 2)</label>
            <select id="merge-source" multiple size="4" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px; background: var(--bg);">
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->products()->count() }} produk)</option>
                @endforeach
            </select>
        </div>
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; font-size: 12px; margin-bottom: 4px;">Kategori Target</label>
            <select id="merge-target" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 8px; background: var(--bg);">
                <option value="">Pilih kategori target</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="button button-soft" type="button" id="btn-merge-categories">Gabungkan</button>
    </div>
    <p id="merge-result" style="margin-top: 8px; font-size: 13px; min-height: 18px;"></p>
</div>
@endif

{{-- Add Category Dialog --}}
<dialog id="add-category-dialog" class="dialog">
    <form method="dialog" id="add-category-form">
        <header class="dialog-header">
            <h3>Tambah Kategori</h3>
            <button class="dialog-close" type="button" aria-label="Tutup">×</button>
        </header>
        <div class="dialog-body">
            <div class="field">
                <label for="cat-name">Nama Kategori</label>
                <input type="text" id="cat-name" name="name" required maxlength="100" autocomplete="off" placeholder="Contoh: Minuman Dingin">
            </div>
            <div class="field">
                <label for="cat-sort">Urutan Tampil</label>
                <input type="number" id="cat-sort" name="sort_order" min="0" value="0" inputmode="numeric">
            </div>
            <div class="field">
                <label class="checkbox-label">
                    <input type="checkbox" id="cat-active" name="active" value="1" checked>
                    <span>Aktif (tampil di katalog)</span>
                </label>
            </div>
        </div>
        <footer class="dialog-footer">
            <button type="button" class="button button-soft dialog-cancel">Batal</button>
            <button type="submit" class="button button-primary">Simpan</button>
        </footer>
    </form>
</dialog>

{{-- Edit Category Dialog --}}
<dialog id="edit-category-dialog" class="dialog">
    <form method="dialog" id="edit-category-form">
        <header class="dialog-header">
            <h3>Edit Kategori</h3>
            <button class="dialog-close" type="button" aria-label="Tutup">×</button>
        </header>
        <div class="dialog-body">
            <input type="hidden" id="edit-cat-id" name="id">
            <div class="field">
                <label for="edit-cat-name">Nama Kategori</label>
                <input type="text" id="edit-cat-name" name="name" required maxlength="100" autocomplete="off">
            </div>
            <div class="field">
                <label for="edit-cat-sort">Urutan Tampil</label>
                <input type="number" id="edit-cat-sort" name="sort_order" min="0" value="0" inputmode="numeric">
            </div>
            <div class="field">
                <label class="checkbox-label">
                    <input type="checkbox" id="edit-cat-active" name="active" value="1">
                    <span>Aktif (tampil di katalog)</span>
                </label>
            </div>
        </div>
        <footer class="dialog-footer">
            <button type="button" class="button button-soft dialog-cancel">Batal</button>
            <button type="submit" class="button button-primary">Simpan Perubahan</button>
        </footer>
    </form>
</dialog>