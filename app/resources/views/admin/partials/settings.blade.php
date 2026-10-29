        <section class="panel settings-panel" aria-label="Pengaturan toko">
            <details class="settings-details">
                <summary>Pengaturan Toko</summary>
                <div class="settings-content">
                    <p>Informasi ini digunakan pada halaman katalog dan saat pelanggan membuat pesanan.</p>
                    <form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-grid">
                            <div class="field full"><label for="store-logo">Logo toko</label><div class="image-upload"><span class="image-preview" id="store-logo-preview" aria-label="Pratinjau logo toko">@if($logoUrl)<img src="{{ $logoUrl }}" alt="Pratinjau logo toko">@else✿@endif</span><span><input id="store-logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp"><span class="field-hint">JPEG, PNG, atau WebP · maksimal 5 MB dan 4000 × 4000 piksel. Gambar dikompresi otomatis.</span><label><input type="checkbox" name="remove_logo" value="1"> Hapus logo saat ini</label></span></div></div>
                            <div class="field"><label for="store-name">Nama toko</label><input id="store-name" name="store_name" required maxlength="120" value="{{ old('store_name', $settings['store_name'] ?? 'Racik Tea') }}"></div>
                            <div class="field"><label for="store-tagline">Slogan</label><input id="store-tagline" name="store_tagline" maxlength="120" value="{{ old('store_tagline', $settings['store_tagline'] ?? '') }}" placeholder="Artisan &amp; Seduh Segar"></div>
                            <div class="field"><label for="hero-heading">Judul halaman depan</label><input id="hero-heading" name="hero_heading" required maxlength="160" value="{{ old('hero_heading', $settings['hero_heading'] ?? 'Pilih minuman favoritmu.') }}"></div>
                            <div class="field"><label for="store-open">Jam buka</label><input id="store-open" name="open_time" type="time" value="{{ old('open_time', $settings['open_time'] ?? config('store.open_time', '08:00')) }}" maxlength="5"></div>
                            <div class="field"><label for="store-close">Jam tutup</label><input id="store-close" name="close_time" type="time" value="{{ old('close_time', $settings['close_time'] ?? config('store.close_time', '21:00')) }}" maxlength="5"></div>
                            <div class="field"><label for="whatsapp">WhatsApp kedai</label><input id="whatsapp" name="whatsapp" inputmode="numeric" pattern="62[1-9][0-9]{7,12}" value="{{ old('whatsapp', $settings['whatsapp'] ?? '') }}" placeholder="6281234567890"><span class="field-hint">Format internasional, angka saja.</span></div>
                            <div class="field"><label for="delivery-fee">Ongkir dasar (Rp)</label><input id="delivery-fee" name="delivery_fee" type="number" min="0" max="10000000" required value="{{ old('delivery_fee', $settings['delivery_fee'] ?? 0) }}"></div>
                            <div class="field"><label for="delivery-fee-per-km">Tarif per km (Rp)</label><input id="delivery-fee-per-km" name="delivery_fee_per_km" type="number" min="0" max="10000000" value="{{ old('delivery_fee_per_km', $settings['delivery_fee_per_km'] ?? 0) }}" placeholder="0 = ongkir tetap"><span class="field-hint">Isi 0 jika ongkir tidak tergantung jarak.</span></div>
                            <div class="field full">
                                <label for="store-map-search">Lokasi toko (untuk hitung ongkir)</label>
                                <div class="map-picker">
                                    <div class="map-search"><input id="store-map-search" type="text" maxlength="200" placeholder="Ketik nama toko atau alamat, mis. Racik Tea Bandung"><button class="btn-sec-action" type="button" id="store-map-search-btn">Cari</button></div>
                                    <div id="store-map" style="height:300px;border-radius:12px;border:1px solid #ddd;"></div>
                                    <p class="field-hint" id="maps-hint">Klik titik lokasi toko Anda di peta. Koordinat terisi otomatis.</p>
                                </div>
                                <div id="maps-fallback" hidden>
                                    <p class="field-hint">Peta tidak dapat dimuat. Masukkan angka koordinat manual dari Google Maps (contoh: -6.2088, 106.8456).</p>
                                    <div class="field"><label for="manual-lat">Latitude</label><input id="manual-lat" type="number" step="any"></div>
                                    <div class="field"><label for="manual-lng">Longitude</label><input id="manual-lng" type="number" step="any"></div>
                                    <button class="btn-sec-action" type="button" id="manual-coords-btn">Pakai koordinat ini</button>
                                </div>
                                <input type="hidden" name="store_maps_url" value="{{ old('store_maps_url', $settings['store_maps_url'] ?? '') }}">
                            </div>
                            <input type="hidden" name="store_latitude" id="store-latitude" value="{{ old('store_latitude', $settings['store_latitude'] ?? '') }}">
                            <input type="hidden" name="store_longitude" id="store-longitude" value="{{ old('store_longitude', $settings['store_longitude'] ?? '') }}">
                            <input type="hidden" name="store_address" id="store-address" value="{{ old('store_address', $settings['store_address'] ?? '') }}">
                            <input type="hidden" name="delivery_included_km" value="{{ old('delivery_included_km', $settings['delivery_included_km'] ?? 0) }}">
                            <input type="hidden" name="delivery_rounding_km" value="{{ old('delivery_rounding_km', $settings['delivery_rounding_km'] ?? 0.5) }}">
                            <input type="hidden" name="delivery_max_km" value="{{ old('delivery_max_km', $settings['delivery_max_km'] ?? 0) }}">
                            <div class="field full"><label for="hero-description">Deskripsi halaman depan</label><textarea id="hero-description" name="hero_description" required maxlength="500">{{ old('hero_description', $settings['hero_description'] ?? 'Telusuri menu yang tersedia dan sesuaikan racikan sebelum memesan.') }}</textarea></div>
                            <div class="field full"><label for="marketing-badges">Informasi singkat</label><textarea id="marketing-badges" name="marketing_badges" maxlength="1000" placeholder="Jam buka, area layanan, atau informasi toko">{{ old('marketing_badges', $settings['marketing_badges'] ?? '') }}</textarea><span class="field-hint">Satu informasi per baris.</span></div>
                        </div>
                        <div class="form-actions"><button class="button button-primary" type="submit">Simpan Pengaturan</button></div>
                    </form>
                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
                    <script>
                    (function () {
                        var latInput  = document.getElementById('store-latitude');
                        var lngInput  = document.getElementById('store-longitude');
                        var addrInput = document.getElementById('store-address');
                        var hint      = document.getElementById('maps-hint');
                        var picker    = document.querySelector('.map-picker');
                        var fallback  = document.getElementById('maps-fallback');
                        var savedLat  = latInput.value ? parseFloat(latInput.value) : null;
                        var savedLng  = lngInput.value ? parseFloat(lngInput.value) : null;

                        function setPoint(lat, lng, label) {
                            latInput.value = lat.toFixed(7);
                            lngInput.value = lng.toFixed(7);
                            if (label && addrInput) addrInput.value = label;
                            hint.textContent = 'Titik dipilih: ' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '. Jangan lupa tekan Simpan.';
                        }

                        function startSearch() {
                            var box = document.getElementById('store-map-search');
                            return (box && box.value.trim()) || (addrInput && addrInput.value.trim()) || '';
                        }

                        function loadFallback() {
                            if (picker) picker.hidden = true;
                            if (fallback) fallback.hidden = false;
                            hint.textContent = 'Peta gagal dimuat. Isi koordinat manual atau periksa koneksi internet.';
                            var btn = document.getElementById('manual-coords-btn');
                            if (btn) btn.addEventListener('click', function () {
                                var la = parseFloat(document.getElementById('manual-lat').value);
                                var lo = parseFloat(document.getElementById('manual-lng').value);
                                if (isFinite(la) && isFinite(lo)) setPoint(la, lo);
                            });
                        }

                        function initMap(L) {
                            var mapEl = document.getElementById('store-map');
                            var details = mapEl && mapEl.closest('details');
                            var map = null;

                            function doInit() {
                                if (map) return; // already inited
                                if (mapEl.offsetWidth === 0 || mapEl.offsetHeight === 0) return; // still hidden, wait
                                map = L.map('store-map').setView(isFinite(savedLat) && isFinite(savedLng) ? [savedLat, savedLng] : [-2.5, 118], isFinite(savedLat) && isFinite(savedLng) ? 15 : 5);
                                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
                                var marker = (isFinite(savedLat) && isFinite(savedLng)) ? L.marker([savedLat, savedLng], { draggable: true }).addTo(map) : null;
                                if (isFinite(savedLat) && isFinite(savedLng)) hint.textContent = 'Lokasi tersimpan saat ini. Klik peta atau geser penanda untuk mengubah.';
                                map.on('click', function (e) {
                                    if (marker) marker.setLatLng(e.latlng); else marker = L.marker(e.latlng, { draggable: true }).addTo(map);
                                    setPoint(e.latlng.lat, e.latlng.lng);
                                });
                                if (marker) marker.on('dragend', function () {
                                    var p = marker.getLatLng();
                                    setPoint(p.lat, p.lng);
                                });
                                if (details) details.addEventListener('toggle', function () { if (details.open) setTimeout(function () { map.invalidateSize(); }, 150); });
                            }

                            // If details is already open, init now; otherwise wait for user to open it
                            if (details && details.open) {
                                doInit();
                            } else {
                                // Try a few times in case CSS animation delays it
                                var tries = 0;
                                var interval = setInterval(function () {
                                    tries++;
                                    doInit();
                                    if (map || tries > 20) clearInterval(interval);
                                }, 300);
                            }

                            document.getElementById('store-map-search-btn').addEventListener('click', function () {
                                if (!map) { hint.textContent = 'Buka panel Pengaturan Toko terlebih dahulu agar peta muncul.'; return; }
                                var q = startSearch();
                                if (!q) { hint.textContent = 'Ketik dulu nama toko atau alamatnya.'; return; }
                                hint.textContent = 'Mencari lokasi...';
                                fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q), { headers: { 'Accept-Language': 'id' } })
                                    .then(function (r) { return r.json(); })
                                    .then(function (res) {
                                        if (res && res[0]) {
                                            var lat = parseFloat(res[0].lat), lng = parseFloat(res[0].lon);
                                            map.setView([lat, lng], 16);
                                            if (marker) marker.setLatLng([lat, lng]); else marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                                            setPoint(lat, lng, res[0].display_name);
                                        } else {
                                            hint.textContent = 'Nama/alamat tidak ditemukan.';
                                        }
                                    })
                                    .catch(function () { hint.textContent = 'Pencarian gagal.'; });
                            });
                        }

                        var script = document.createElement('script');
                        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        script.onload = function () { if (window.L) initMap(window.L); else loadFallback(); };
                        script.onerror = loadFallback;
                        document.head.appendChild(script);
                    })();
                    </script>
                </div>
            </details>
        </section>

