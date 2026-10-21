        <section class="panel" aria-labelledby="orders-title">
            <div class="panel-heading"><div><h2 id="orders-title">Riwayat Pesanan</h2><p>Pesanan yang masuk tersimpan di database toko.</p></div></div>
            <div class="table-scroll">
                <table class="orders-table">
                    <thead><tr><th>Kode</th><th>Pemesan</th><th>Layanan</th><th>Rincian Menu</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($orders as $order)
                        @php($orderItems = is_string($order->items) ? json_decode($order->items, true) : $order->items)
                        <tr>
                            <td><span class="order-code">{{ $order->code }}</span><br><span class="order-time">{{ $order->created_at }}</span></td>
                            <td class="order-customer"><strong>{{ $order->customer_name }}</strong><small>{{ $order->phone }}</small></td>
                            <td>{{ ucfirst($order->service) }}</td>
                            <td class="order-items">{{ collect($orderItems ?: [])->map(fn ($item) => ($item['quantity'] ?? 1).'× '.($item['name'] ?? 'Menu'))->implode(', ') ?: '—' }}</td>
                            <td class="order-total">Rp{{ number_format($order->total, 0, ',', '.') }}</td>
                            <td><span class="order-status {{ $order->status === 'cancelled' ? 'cancelled' : '' }}">{{ ucfirst($order->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><strong>Belum ada pesanan</strong>Pesanan pelanggan akan muncul di sini.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <p class="footer-note">Perubahan menu dan pengaturan tersimpan langsung di database toko.</p>
