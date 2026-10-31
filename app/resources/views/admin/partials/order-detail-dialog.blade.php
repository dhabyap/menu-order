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
