@php($orderStatuses = \App\Support\OrderStatus::labels())
@forelse($orders as $order)
    @php($orderItems = is_string($order->items) ? json_decode($order->items, true) : $order->items)
    <tr>
        <td><span class="order-code">{{ $order->code }}</span><br><span class="order-time">{{ $order->created_at }}</span></td>
        <td class="order-customer"><strong>{{ $order->customer_name }}</strong><small>{{ $order->phone }}@if($order->address) · {{ $order->address }}@endif</small></td>
        <td>{{ ucfirst($order->service) }}@if($order->note)<br><span class="order-time">Catatan: {{ $order->note }}</span>@endif</td>
        <td class="order-items">{{ collect($orderItems ?: [])->map(fn ($item) => ($item['quantity'] ?? 1).'× '.($item['name'] ?? 'Menu'))->implode(', ') ?: '—' }}</td>
        <td class="order-total">Rp{{ number_format($order->total, 0, ',', '.') }}</td>
        <td>
            <select class="order-status-select status-{{ $order->status }}" data-order-status data-id="{{ $order->id }}" aria-label="Status pesanan {{ $order->code }}">
                @foreach($orderStatuses as $value => $label)
                    <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td class="order-actions">
            <button class="button button-soft button-small" type="button" data-order-detail data-id="{{ $order->id }}">Detail</button>
        </td>
    </tr>
@empty
    <tr><td colspan="7"><div class="empty-state"><strong>Belum ada pesanan</strong>Pesanan pelanggan akan muncul di sini.</div></td></tr>
@endforelse
