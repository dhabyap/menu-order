        <section class="panel settings-panel" aria-label="Pengaturan toko">
            <details class="settings-details">
                <summary>Pengaturan Toko</summary>
                <div class="settings-content">
                    <p>Informasi ini digunakan pada halaman katalog dan saat pelanggan membuat pesanan.</p>
                    <form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-grid">
                            <div class="field full"><label for="store-logo">Logo toko</label><div class="image-upload"><span class="image-preview" id="store-logo-preview" aria-label="Pratinjau logo toko">@if($logoUrl)<img src="{{ $logoUrl }}" alt="Pratinjau logo toko">@else✿@endif</span><span><input id="store-logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp"><span class="field-hint">JPEG, PNG, atau WebP · maksimal 5 MB dan 4000 × 4000 piksel. Gambar dikompresi otomatis.</span><label><input type="checkbox" name="remove_logo" value="1"> Hapus logo saat ini</label></span></div></div>
                            <div class="field"><label for="store-name">Nama toko</label><input id="store-name" name="store_name" required maxlength="120" value="{{ old('store_name', $settings['store_name'] ?? 'Toko') }}"></div>
                            <div class="field"><label for="store-tagline">Slogan</label><input id="store-tagline" name="store_tagline" maxlength="120" value="{{ old('store_tagline', $settings['store_tagline'] ?? '') }}" placeholder="Artisan &amp; Seduh Segar"></div>
                            <div class="field"><label for="hero-heading">Judul halaman depan</label><input id="hero-heading" name="hero_heading" required maxlength="160" value="{{ old('hero_heading', $settings['hero_heading'] ?? 'Pilih minuman favoritmu.') }}"></div>
                            <div class="field"><label for="store-open">Jam buka</label><input id="store-open" name="open_time" type="time" value="{{ old('open_time', $settings['open_time'] ?? config('store.open_time', '08:00')) }}" maxlength="5"></div>
                            <div class="field"><label for="store-close">Jam tutup</label><input id="store-close" name="close_time" type="time" value="{{ old('close_time', $settings['close_time'] ?? config('store.close_time', '21:00')) }}" maxlength="5"></div>
                            <div class="field"><label for="whatsapp">WhatsApp kedai</label><input id="whatsapp" name="whatsapp" inputmode="numeric" pattern="62[1-9][0-9]{7,12}" value="{{ old('whatsapp', $settings['whatsapp'] ?? '') }}" placeholder="6281234567890"><span class="field-hint">Format internasional, angka saja.</span></div>
                            <div class="field"><label for="delivery-fee">Ongkir dasar (Rp)</label><input id="delivery-fee" name="delivery_fee" type="number" min="0" max="10000000" required value="{{ old('delivery_fee', $settings['delivery_fee'] ?? 0) }}"></div>
                            <div class="field"><label for="delivery-fee-per-km">Tarif per km (Rp)</label><input id="delivery-fee-per-km" name="delivery_fee_per_km" type="number" min="0" max="10000000" value="{{ old('delivery_fee_per_km', $settings['delivery_fee_per_km'] ?? 0) }}" placeholder="0 = ongkir tetap"><span class="field-hint">Isi 0 jika ongkir tidak tergantung jarak.</span></div>
                            <div class="field full">
                                <label>Lokasi toko (untuk hitung ongkir)</label>
                                <p class="field-hint">
                                    <a href="https://maps.google.com/?q={{ urlencode($settings['store_latitude'] ?? -6.8819220) }},{{ urlencode($settings['store_longitude'] ?? 107.5928889) }}" target="_blank" rel="noopener">Lihat di Google Maps</a>
                                </p>
                                <div class="field"><label for="store-latitude">Latitude</label><input id="store-latitude" name="store_latitude" type="number" step="any" value="{{ $settings['store_latitude'] ?? -6.8819220 }}"></div>
                                <div class="field"><label for="store-longitude">Longitude</label><input id="store-longitude" name="store_longitude" type="number" step="any" value="{{ $settings['store_longitude'] ?? 107.5928889 }}" autofocus></div>
                            </div>
                            <div class="field full"><label for="store-address-input">Alamat lengkap toko</label><input id="store-address-input" name="store_address" maxlength="500" value="{{ old('store_address', $settings['store_address'] ?? '') }}" placeholder="Jl. Contoh No. 1, Kel. Contoh, Kec. Contoh, Kota"></div>
                            <input type="hidden" name="delivery_included_km" value="{{ old('delivery_included_km', $settings['delivery_included_km'] ?? 0) }}">
                            <input type="hidden" name="delivery_rounding_km" value="{{ old('delivery_rounding_km', $settings['delivery_rounding_km'] ?? 0.5) }}">
                            <input type="hidden" name="delivery_max_km" value="{{ old('delivery_max_km', $settings['delivery_max_km'] ?? 0) }}">
                            <div class="field full"><label for="hero-description">Deskripsi halaman depan</label><textarea id="hero-description" name="hero_description" required maxlength="500">{{ old('hero_description', $settings['hero_description'] ?? 'Telusuri menu yang tersedia dan sesuaikan racikan sebelum memesan.') }}</textarea></div>
                            <div class="field full"><label for="marketing-badges">Informasi singkat</label><textarea id="marketing-badges" name="marketing_badges" maxlength="1000" placeholder="Jam buka, area layanan, atau informasi toko">{{ old('marketing_badges', $settings['marketing_badges'] ?? '') }}</textarea><span class="field-hint">Satu informasi per baris.</span></div>
                        </div>

                        <div class="panel-heading" style="margin-top: 24px;">
                            <div>
                                <h2>Backup Database</h2>
                                <p>Simpan salinan database untuk keamanan.</p>
                            </div>
                            <div class="orders-heading-actions">
                                <button class="button button-soft button-small" type="button" id="backup-now">Backup Sekarang</button>
                                <button class="button button-soft button-small" type="button" id="backup-refresh">⟳ Muat ulang</button>
                            </div>
                        </div>
                        <div class="table-scroll">
                            <table class="orders-table">
                                <thead><tr><th>File</th><th>Ukuran</th><th>Diperbarui</th><th>Aksi</th></tr></thead>
                                <tbody id="backups-body">
                                    <tr><td colspan="4" style="text-align:center; color: var(--muted);">Memuat…</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="form-actions"><button class="button button-primary" type="submit">Simpan Pengaturan</button></div>
                    </form>

                </div>
            </details>
        </section>

