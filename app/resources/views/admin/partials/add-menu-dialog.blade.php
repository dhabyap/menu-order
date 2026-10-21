<dialog class="add-menu-dialog" id="add-menu-dialog" aria-labelledby="add-menu-title">
    <div class="dialog-layout">
        <div class="dialog-header">
            <div><div class="dialog-kicker">Katalog kedai</div><h2 id="add-menu-title">Tambah Menu Baru</h2><p>Isi detail menu dan atur opsi racikan jika tersedia.</p></div>
            <button class="dialog-close" id="close-add-menu" type="button" aria-label="Tutup formulir">×</button>
        </div>
        <form class="dialog-form" id="add-product-form" method="post" action="{{ route('admin.products.create') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="options_json" id="options-json" value="[]">
            <div class="dialog-body">
                <section class="form-section" aria-labelledby="basic-details-title">
                    <div class="form-section-heading"><div><h3 id="basic-details-title">Informasi menu</h3><p>Nama, kategori, dan harga yang akan dilihat pelanggan.</p></div></div>
                    <div class="form-grid">
                        <div class="field"><label for="new-name">Nama menu <span aria-hidden="true">*</span></label><input id="new-name" name="name" required maxlength="120" placeholder="Contoh: Teh Melati"></div>
                        <div class="field"><label for="new-category">Kategori <span aria-hidden="true">*</span></label><input id="new-category" name="category" required maxlength="80" placeholder="Contoh: Teh klasik"></div>
                        <div class="field"><label for="new-price">Harga dasar <span aria-hidden="true">*</span></label><div class="price-field"><span>Rp</span><input id="new-price" name="price" required type="number" min="0" max="10000000" step="500" inputmode="numeric" placeholder="12.000"></div></div>
                        <div class="field"><label>Pratinjau harga</label><div class="price-preview"><span>Harga mulai dari</span><strong id="price-preview">Rp0</strong></div></div>
                        <div class="field full"><label for="new-description">Deskripsi <span style="font-weight:500;color:var(--muted)">(opsional)</span></label><textarea id="new-description" name="description" maxlength="1000" placeholder="Ceritakan rasa atau bahan utama menu ini"></textarea></div>
                        <div class="field full"><label for="new-labels">Label <span style="font-weight:500;color:var(--muted)">(opsional)</span></label><input id="new-labels" name="labels" maxlength="200" placeholder="Contoh: Favorit, Segar"><span class="field-hint">Pisahkan beberapa label dengan koma.</span></div>
                        <div class="field full"><label for="new-image">Foto menu <span style="font-weight:500;color:var(--muted)">(opsional)</span></label><div class="image-upload"><span class="image-preview" id="new-image-preview" aria-label="Pratinjau foto menu">✿</span><span><input id="new-image" name="image" type="file" accept="image/jpeg,image/png,image/webp"><span class="field-hint">Pilih foto produk yang jelas. Format JPEG, PNG, atau WebP, maksimal 5 MB.</span></span></div></div>
                    </div>
                </section>
                <section class="form-section" aria-labelledby="options-title">
                    <div class="form-section-heading">
                        <div><h3 id="options-title">Pilihan racikan <span style="font:500 10px 'Plus Jakarta Sans',sans-serif;color:var(--muted)">(opsional)</span></h3><p>Contoh: ukuran, tingkat gula, atau topping. Harga di bawah adalah tambahan.</p></div>
                        <button class="button button-soft button-small" id="add-option-group" type="button">＋ Tambah Grup</button>
                    </div>
                    <div class="option-groups" id="option-groups"><div class="option-empty">Belum ada pilihan tambahan. Menu tetap bisa dipesan tanpa opsi.</div></div>
                </section>
            </div>
            <div class="dialog-footer">
                <span class="dialog-footer-note">Menu akan langsung tersimpan di katalog.</span>
                <div class="form-actions"><button class="button button-soft" id="cancel-add-menu" type="button">Batal</button><button class="button button-primary" type="submit">Simpan Menu</button></div>
            </div>
        </form>
    </div>
</dialog>
