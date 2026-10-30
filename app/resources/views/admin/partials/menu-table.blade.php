        <section class="panel" aria-labelledby="menu-title">
            <div class="panel-heading">
                <div><h2 id="menu-title">Daftar Menu &amp; Ketersediaan Stok</h2><p>Atur harga, pilihan racikan, dan ketersediaan menu.</p></div>
                <button class="button button-primary" id="open-add-menu" type="button">＋ Tambah Menu</button>
            </div>

            <div class="table-scroll">
                <table class="menu-table">
                    <thead><tr><th style="width:40px" aria-label="Urutkan">☰</th><th>Menu Minuman</th><th>Kategori</th><th>Harga Dasar</th><th>Status Stok</th><th style="text-align:right">Tindakan</th></tr></thead>
                    <tbody id="products-table-body">
                    @forelse($products as $product)
                        <tr data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-category="{{ $product->category ?: '' }}">
                            <td><span class="drag-handle" aria-hidden="true" style="cursor:grab;padding-right:8px">☰</span></td>
                            <td><div class="menu-cell"><span class="menu-art">@if($product->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="">@else{{ $product->icon ?: '✿' }}@endif</span><span><strong>{{ $product->name }}</strong><small>{{ $product->description ?: 'Belum ada deskripsi' }}</small></span></div></td>
                            <td><span class="category-pill">{{ $product->category ?: 'Tanpa kategori' }}</span></td>
                            <td><strong>Rp{{ number_format($product->price, 0, ',', '.') }}</strong></td>
                            <td><span class="status-pill {{ $product->available ? 'available' : 'unavailable' }}">{{ $product->available ? 'Tersedia' : 'Habis' }}</span></td>
                            <td>
                                <div class="row-actions">
                                    <button class="action-button action-edit edit-trigger-button" type="button">Edit</button>
                                    <form class="inline-form" method="post" action="{{ route('admin.products.toggle', $product->id) }}">
                                        @csrf @method('PATCH')
                                        <button class="action-button action-stock" type="submit">{{ $product->available ? 'Tandai Habis' : 'Aktifkan' }}</button>
                                    </form>
                                    <form class="inline-form" method="post" action="{{ route('admin.products.delete', $product->id) }}" onsubmit="return confirm('Hapus menu ini? Tindakan ini tidak dapat dibatalkan.')">
                                        @csrf @method('DELETE')
                                        <button class="action-button action-delete" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr class="edit-row">
                            <td colspan="6">
                                <details class="edit-details">
                                    <summary><span class="edit-open-{{ $product->id }}"></span></summary>
                                    <div class="edit-form">
                                        <h3>Edit {{ $product->name }}</h3>
                                        <form method="post" action="{{ route('admin.products.update', $product->id) }}" enctype="multipart/form-data">
                                            @csrf @method('PUT')
                                            <div class="form-grid">
                                                <div class="field"><label>Nama menu</label><input name="name" required maxlength="120" value="{{ $product->name }}"></div>
                                                <div class="field"><label>Kategori</label><input name="category" required maxlength="80" value="{{ $product->category }}"></div>
                                                <div class="field"><label>Harga dasar (Rp)</label><input name="price" type="number" min="0" max="10000000" required value="{{ $product->price }}"></div>
                                                <div class="field"><label>Label</label><input name="labels" maxlength="200" value="{{ implode(', ', json_decode($product->labels ?: '[]', true) ?: []) }}"></div>
                                                <div class="field full"><label>Deskripsi</label><input name="description" maxlength="1000" value="{{ $product->description }}"></div>
                                                <div class="field full"><label>Foto menu (opsional)</label><div class="image-upload"><span class="edit-image-preview">@if($product->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="Foto {{ $product->name }}">@else<span>✿</span>@endif</span><span><input type="file" name="image" accept="image/jpeg,image/png,image/webp" aria-label="Ganti foto {{ $product->name }}"><span class="field-hint">JPEG, PNG, atau WebP · maks. 5 MB dan 4000 × 4000 piksel. Pilih gambar baru untuk mengganti (otomatis dikompresi).</span>@if($product->image_path)<label class="remove-image"><input type="checkbox" name="remove_image" value="1"> Hapus foto saat menyimpan</label>@endif</span></div></div>
                                                <div class="field full"><label>Opsi racikan (JSON)</label><textarea name="options_json">{{ json_encode(json_decode($product->options ?: '[]', true) ?: [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</textarea><span class="field-hint">Array JSON berisi nama kelompok beserta opsi dan harga tambahannya.</span></div>
                                            </div>
                                            <div class="form-actions"><button class="button button-soft" type="button" onclick="this.closest('details').open=false">Batal</button><button class="button button-primary" type="submit">Simpan Perubahan</button></div>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><strong>Belum ada menu</strong>Tambahkan menu pertama kedai menggunakan tombol Tambah Menu.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
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

                        ids.forEach(id => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'order[]';
                            input.value = id;
                            form.appendChild(input);
                        });

                        const method = document.createElement('input');
                        method.type = 'hidden';
                        method.name = '_method';
                        method.value = 'PATCH';

                        const token = document.createElement('input');
                        token.type = 'hidden';
                        token.name = '_token';
                        token.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

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

