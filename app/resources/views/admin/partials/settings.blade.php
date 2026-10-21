        <section class="panel settings-panel" aria-label="Pengaturan toko">
            <details class="settings-details">
                <summary>Pengaturan Toko</summary>
                <div class="settings-content">
                    <p>Informasi ini digunakan pada halaman katalog dan saat pelanggan membuat pesanan.</p>
                    <form method="post" action="{{ route('admin.settings.update') }}">
                        @csrf
                        <div class="form-grid">
                            <div class="field"><label for="store-name">Nama toko</label><input id="store-name" name="store_name" required maxlength="120" value="{{ old('store_name', $settings['store_name'] ?? 'Racik Tea') }}"></div>
                            <div class="field"><label for="store-tagline">Slogan</label><input id="store-tagline" name="store_tagline" maxlength="120" value="{{ old('store_tagline', $settings['store_tagline'] ?? '') }}" placeholder="Artisan &amp; Seduh Segar"></div>
                            <div class="field"><label for="hero-heading">Judul halaman depan</label><input id="hero-heading" name="hero_heading" required maxlength="160" value="{{ old('hero_heading', $settings['hero_heading'] ?? 'Pilih minuman favoritmu.') }}"></div>
                            <div class="field"><label for="whatsapp">WhatsApp kedai</label><input id="whatsapp" name="whatsapp" inputmode="numeric" pattern="62[1-9][0-9]{7,12}" value="{{ old('whatsapp', $settings['whatsapp'] ?? '') }}" placeholder="6281234567890"><span class="field-hint">Format internasional, angka saja.</span></div>
                            <div class="field"><label for="delivery-fee">Ongkos antar (Rp)</label><input id="delivery-fee" name="delivery_fee" type="number" min="0" max="10000000" required value="{{ old('delivery_fee', $settings['delivery_fee'] ?? 0) }}"></div>
                            <div class="field full"><label for="hero-description">Deskripsi halaman depan</label><textarea id="hero-description" name="hero_description" required maxlength="500">{{ old('hero_description', $settings['hero_description'] ?? 'Telusuri menu yang tersedia dan sesuaikan racikan sebelum memesan.') }}</textarea></div>
                            <div class="field full"><label for="marketing-badges">Informasi singkat</label><textarea id="marketing-badges" name="marketing_badges" maxlength="1000" placeholder="Jam buka, area layanan, atau informasi toko">{{ old('marketing_badges', $settings['marketing_badges'] ?? '') }}</textarea><span class="field-hint">Satu informasi per baris.</span></div>
                        </div>
                        <div class="form-actions"><button class="button button-primary" type="submit">Simpan Pengaturan</button></div>
                    </form>
                </div>
            </details>
        </section>

