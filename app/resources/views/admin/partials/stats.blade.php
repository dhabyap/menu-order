        <section aria-label="Ringkasan toko">
            <div class="stats">
                <div class="stat-card"><span>Total Pesanan</span><strong>{{ $orders->count() }}</strong><em>Pesanan tersimpan</em></div>
                <div class="stat-card"><span>Menu Tersedia</span><strong>{{ $available }}</strong><em>Siap dipesan pelanggan</em></div>
                <div class="stat-card"><span>Menu Habis</span><strong>{{ $unavailable }}</strong><em>Sedang tidak tersedia</em></div>
                <div class="stat-card"><span>Nilai Pesanan</span><strong>Rp{{ number_format($revenue, 0, ',', '.') }}</strong><em>Di luar pesanan dibatalkan</em></div>
            </div>
        </section>

