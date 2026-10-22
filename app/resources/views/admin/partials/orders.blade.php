        <section class="panel" aria-labelledby="orders-title">
            <div class="panel-heading">
                <div>
                    <h2 id="orders-title">Riwayat Pesanan</h2>
                    <p>Pesanan yang masuk tersimpan di database toko. Ubah status untuk memproses pesanan.</p>
                </div>
                <div class="orders-heading-actions">
                    <span class="orders-live" id="orders-live" aria-live="polite">Memuat…</span>
                    <button class="button button-soft button-small" type="button" id="orders-refresh">⟳ Muat ulang</button>
                </div>
            </div>
            <div class="table-scroll">
                <table class="orders-table">
                    <thead><tr><th>Kode</th><th>Pemesan</th><th>Layanan</th><th>Rincian Menu</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody id="orders-body">
                    @include('admin.partials.orders-rows', ['orders' => $orders])
                    </tbody>
                </table>
            </div>
        </section>
        <p class="footer-note">Perubahan menu dan pengaturan tersimpan langsung di database toko.</p>
