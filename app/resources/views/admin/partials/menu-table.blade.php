<table class="menu-table">
        <thead>
            <tr>
                <th class="menu-sort-heading" aria-label="Urutan"></th>
                <th>Nama menu</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Ketersediaan</th>
                <th>Rekomendasi</th>
                <th class="menu-actions-heading">Tindakan</th>
            </tr>
        </thead>
        <tbody id="products-table-body">
        @forelse($products as $product)
            <tr class="product-row" data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-category="{{ $product->category ?: '' }}" data-available="{{ $product->available ? '1' : '0' }}" data-recommended="{{ $product->is_recommended ? '1' : '0' }}">
                <td class="menu-sort-cell"><span class="drag-handle" aria-label="Seret untuk mengubah urutan" title="Seret untuk mengubah urutan">⠿</span></td>
                <td>
                    <div class="menu-cell">
                        <span class="menu-art">@if($product->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="">@else{{ $product->icon ?: '✿' }}@endif</span>
                        <span class="menu-name"><strong>{{ $product->name }}</strong><small>{{ $product->description ?: 'Deskripsi belum diisi' }}</small></span>
                    </div>
                </td>
                <td><span class="category-pill">{{ $product->category ?: 'Tanpa kategori' }}</span></td>
                <td class="menu-price">Rp{{ number_format($product->price, 0, ',', '.') }}</td>
                <td><span class="menu-status {{ $product->available ? 'is-available' : 'is-unavailable' }}"><span aria-hidden="true"></span>{{ $product->available ? 'Tersedia' : 'Habis' }}</span></td>
                <td>
                    @if($product->is_recommended)
                        <span class="recommendation-pill"><span aria-hidden="true">✦</span> Rekomendasi</span>
                    @else
                        <span class="recommendation-empty">—</span>
                    @endif
                </td>
                <td>
                    <div class="row-actions">
                        <button class="action-button action-edit edit-trigger-button" type="button" aria-expanded="false">Edit</button>
                        <form class="inline-form" method="post" action="{{ route('admin.products.toggle', $product->id) }}">
                            @csrf @method('PATCH')
                            <button class="action-button action-stock" type="submit">{{ $product->available ? 'Tandai habis' : 'Aktifkan' }}</button>
                        </form>
                        <form class="inline-form" method="post" action="{{ route('admin.products.delete', $product->id) }}" onsubmit="return confirm('Hapus menu ini? Tindakan ini tidak dapat dibatalkan.')">
                            @csrf @method('DELETE')
                            <button class="action-button action-delete" type="submit" aria-label="Hapus {{ $product->name }}">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            <tr class="edit-row" data-edit-for="{{ $product->id }}" hidden>
                <td colspan="7">
                    <div class="edit-panel">
                        <div class="edit-panel-heading"><div><p class="menu-kicker">Detail menu</p><h3>Edit {{ $product->name }}</h3></div><button class="edit-close" type="button" aria-label="Tutup editor">Tutup</button></div>
                        <form method="post" action="{{ route('admin.products.update', $product->id) }}" enctype="multipart/form-data">
                            @csrf @method('PUT')
                            <div class="form-grid">
                                <div class="field"><label>Nama menu</label><input name="name" required maxlength="120" value="{{ $product->name }}"></div>
                                <div class="field"><label>Kategori</label><input name="category" required maxlength="80" value="{{ $product->category }}"></div>
                                <div class="field"><label>Harga dasar (Rp)</label><input name="price" type="number" min="0" max="10000000" required value="{{ $product->price }}"></div>
                                <div class="field"><label>Label menu</label><input name="labels" maxlength="200" value="{{ implode(', ', json_decode($product->labels ?: '[]', true) ?: []) }}"><span class="field-hint">Pisahkan label dengan koma.</span></div>
                                <div class="field full"><label>Deskripsi</label><input name="description" maxlength="1000" value="{{ $product->description }}"></div>
                                <div class="field full"><label>Foto menu (opsional)</label><div class="image-upload"><span class="edit-image-preview">@if($product->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="Foto {{ $product->name }}">@else<span>✿</span>@endif</span><span><input type="file" name="image" accept="image/jpeg,image/png,image/webp" aria-label="Ganti foto {{ $product->name }}"><span class="field-hint">JPEG, PNG, atau WebP, maksimal 5 MB.</span>@if($product->image_path)<label class="remove-image"><input type="checkbox" name="remove_image" value="1"> Hapus foto saat menyimpan</label>@endif</span></div></div>
                                <div class="field"><label class="checkbox-card"><input name="is_recommended" type="checkbox" value="1" {{ $product->is_recommended ? 'checked' : '' }}><span><strong>Tampilkan sebagai rekomendasi</strong><small>Menu ini muncul pada filter Rekomendasi di katalog.</small></span></label></div>
                                <div class="field full edit-options-field">
                                    <label>Pilihan racikan <span class="field-hint">(opsional — ukuran, gula, topping, atau tambahan lain)</span></label>
                                    <input type="hidden" name="options_json" value="[]">
                                    <div class="option-groups" data-edit-option-builder data-options="{{ e(json_encode(json_decode($product->options ?: '[]', true) ?: [], JSON_UNESCAPED_UNICODE)) }}"></div>
                                    <button class="button button-soft button-small" type="button" data-add-option-group>＋ Tambah Grup</button>
                                </div>
                            </div>
                            <div class="form-actions"><button class="button button-soft edit-cancel" type="button">Batal</button><button class="button button-primary" type="submit">Simpan perubahan</button></div>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="empty-state"><strong>Belum ada menu</strong>Tambahkan menu pertama agar pelanggan bisa mulai memesan.</div></td></tr>
        @endforelse
        </tbody>
    </table>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
    const tbody = document.getElementById('products-table-body');
    if (!tbody) return;

    new Sortable(tbody, {
        handle: '.drag-handle',
        draggable: '.product-row',
        animation: 140,
        onEnd: function () {
            const productRows = Array.from(tbody.querySelectorAll('.product-row'));
            const editRows = Array.from(tbody.querySelectorAll('.edit-row'));
            productRows.forEach(row => {
                const editRow = editRows.find(item => item.dataset.editFor === row.dataset.id);
                tbody.appendChild(row);
                if (editRow) tbody.appendChild(editRow);
            });
            const ids = productRows.map(row => row.dataset.id);
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/products/reorder';
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'order[]'; input.value = id;
                form.appendChild(input);
            });
            const method = document.createElement('input');
            method.type = 'hidden'; method.name = '_method'; method.value = 'PATCH'; form.appendChild(method);
            const token = document.createElement('input');
            token.type = 'hidden'; token.name = '_token'; token.value = document.querySelector('meta[name="csrf-token"]').content; form.appendChild(token);
            document.body.appendChild(form); form.submit();
        }
    });
})();
</script>
<style>.visually-hidden{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}</style>
