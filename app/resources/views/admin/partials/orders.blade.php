        <section class="panel" aria-labelledby="orders-title">
            <div class="panel-heading">
                <div>
                    <h2 id="orders-title">Riwayat Pesanan</h2>
                    <p>Pesanan yang masuk tersimpan di database toko. Ubah status untuk memproses pesanan.</p>
                </div>
                <div class="orders-heading-actions">
                    <span class="orders-live" id="orders-live" aria-live="polite">Memuat…</span>
                    <button class="button button-soft button-small" type="button" id="orders-refresh">⟳ Muat ulang</button>
                    <a class="button button-soft button-small" href="{{ route('admin.orders.export') }}" download>Export CSV</a>
                </div>
            </div>
            <div class="table-scroll">
                <table class="orders-table">
                    <thead><tr><th>Kode</th><th>Pemesan</th><th>Layanan</th><th>Rincian Menu</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody id="orders-body">
                    @include('admin.partials.orders-rows', ['orders' => $orders])
                    </tbody>
                </table>
            </div>
        </section>
        <p class="footer-note">Perubahan menu dan pengaturan tersimpan langsung di database toko.</p>

        <dialog class="add-menu-dialog order-detail-dialog" id="order-detail-dialog" aria-labelledby="order-detail-title">
            <div class="dialog-layout">
                <div class="dialog-header">
                    <div>
                        <div class="dialog-kicker">Rincian pesanan</div>
                        <h2 id="order-detail-title">Detail Pesanan</h2>
                        <p id="order-detail-code">—</p>
                    </div>
                    <button class="dialog-close" id="close-order-detail" type="button" aria-label="Tutup detail pesanan">×</button>
                </div>
                <div class="dialog-body">
                    <p class="notice error" id="order-detail-error" hidden></p>
                    <dl class="order-detail-grid">
                        <dt>Pelanggan</dt><dd id="order-detail-customer">—</dd>
                        <dt>WhatsApp</dt><dd id="order-detail-phone">—</dd>
                        <dt>Alamat</dt><dd id="order-detail-address">—</dd>
                        <dt>Layanan</dt><dd id="order-detail-service">—</dd>
                        <dt>Catatan</dt><dd id="order-detail-note">—</dd>
                        <dt>Dibuat</dt><dd id="order-detail-created">—</dd>
                        <dt>Diperbarui</dt><dd id="order-detail-updated">—</dd>
                        <dt>Status</dt><dd id="order-detail-status">—</dd>
                    </dl>
                    <div class="table-scroll">
                        <table class="order-detail-items">
                            <thead><tr><th>Menu</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th></tr></thead>
                            <tbody id="order-detail-items-body"></tbody>
                        </table>
                    </div>
                    <div class="order-detail-totals">
                        <div><span>Subtotal</span><strong id="order-detail-subtotal">—</strong></div>
                        <div id="order-detail-fee-row"><span id="order-detail-fee-label">Ongkir</span><strong id="order-detail-fee">—</strong></div>
                        <div class="grand"><span>Total</span><strong id="order-detail-total">—</strong></div>
                    </div>
                </div>
            </div>
        </dialog>
