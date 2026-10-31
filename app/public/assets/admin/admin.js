document.querySelectorAll('.edit-trigger-button').forEach((button) => {
    button.addEventListener('click', () => {
        const row = button.closest('tr');
        const editor = row.nextElementSibling?.querySelector('.edit-details');
        if (editor) editor.open = !editor.open;
        if (editor?.open) editor.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
});

const addMenuDialog = document.getElementById('add-menu-dialog');
const addMenuForm = document.getElementById('add-product-form');
const optionGroups = document.getElementById('option-groups');
const optionsJson = document.getElementById('options-json');
const priceInput = document.getElementById('new-price');
const pricePreview = document.getElementById('price-preview');
const formatRupiah = (value) => `Rp${Number(value || 0).toLocaleString('id-ID')}`;
const escapeHtml = (str) => String(str ?? '').replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));

document.getElementById('open-add-menu').addEventListener('click', () => {
    addMenuDialog.showModal();
    document.body.classList.add('dialog-open');
    window.setTimeout(() => document.getElementById('new-name').focus(), 0);
});

const closeAddMenu = () => {
    addMenuDialog.close();
    document.body.classList.remove('dialog-open');
};
document.getElementById('close-add-menu').addEventListener('click', closeAddMenu);
document.getElementById('cancel-add-menu').addEventListener('click', closeAddMenu);
addMenuDialog.addEventListener('close', () => document.body.classList.remove('dialog-open'));
addMenuDialog.addEventListener('click', (event) => {
    if (event.target === addMenuDialog) closeAddMenu();
});

priceInput.addEventListener('input', () => {
    pricePreview.textContent = formatRupiah(priceInput.value);
});

const bindImagePreview = (input, preview, alt = 'Pratinjau foto menu') => {
    input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            input.setCustomValidity('Pilih gambar JPEG, PNG, atau WebP.');
            input.reportValidity();
            input.value = '';
            return;
        }
        input.setCustomValidity('');
        const image = document.createElement('img');
        image.alt = alt;
        image.src = URL.createObjectURL(file);
        image.onload = () => URL.revokeObjectURL(image.src);
        preview.replaceChildren(image);
    });
};
bindImagePreview(document.getElementById('new-image'), document.getElementById('new-image-preview'));
const storeLogoInput = document.getElementById('store-logo');
if (storeLogoInput) bindImagePreview(storeLogoInput, document.getElementById('store-logo-preview'), 'Pratinjau logo toko');

document.querySelectorAll('.edit-form input[type="file"]').forEach((input) => {
    const preview = input.closest('.image-upload').querySelector('.edit-image-preview');
    bindImagePreview(input, preview);
});

const makeField = (labelText, input, className = 'field') => {
    const field = document.createElement('div');
    field.className = className;
    const label = document.createElement('label');
    label.textContent = labelText;
    field.append(label, input);
    return field;
};

const addOptionRow = (rows) => {
    const row = document.createElement('div');
    row.className = 'option-row';

    const labelInput = document.createElement('input');
    labelInput.type = 'text';
    labelInput.required = true;
    labelInput.maxLength = 80;
    labelInput.placeholder = 'Contoh: Large';
    labelInput.dataset.optionLabel = '';
    labelInput.setAttribute('aria-label', 'Nama pilihan');

    const priceInput = document.createElement('input');
    priceInput.type = 'number';
    priceInput.required = true;
    priceInput.min = '0';
    priceInput.max = '10000000';
    priceInput.step = '1';
    priceInput.inputMode = 'numeric';
    priceInput.placeholder = '0';
    priceInput.dataset.optionPrice = '';
    priceInput.setAttribute('aria-label', 'Harga tambahan dalam rupiah');

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'remove-option';
    remove.textContent = '×';
    remove.setAttribute('aria-label', 'Hapus pilihan');
    remove.addEventListener('click', () => {
        const group = rows.closest('.option-group');
        row.remove();
        if (!rows.querySelector('.option-row')) addOptionRow(rows);
        group.querySelector('[data-option-label]').focus();
    });

    row.append(
        makeField('Nama pilihan', labelInput),
        makeField('Tambah harga (Rp)', priceInput),
        remove
    );
    rows.append(row);
};

document.getElementById('add-option-group').addEventListener('click', () => {
    optionGroups.querySelector('.option-empty')?.remove();
    const group = document.createElement('fieldset');
    group.className = 'option-group';

    const groupHead = document.createElement('div');
    groupHead.className = 'option-group-head';
    const groupName = document.createElement('input');
    groupName.type = 'text';
    groupName.required = true;
    groupName.maxLength = 80;
    groupName.placeholder = 'Contoh: Ukuran';
    groupName.dataset.groupName = '';
    groupName.setAttribute('aria-label', 'Nama grup pilihan');
    groupHead.append(makeField('Nama grup', groupName));

    const removeGroup = document.createElement('button');
    removeGroup.type = 'button';
    removeGroup.className = 'button button-danger button-small';
    removeGroup.textContent = 'Hapus grup';
    removeGroup.addEventListener('click', () => {
        group.remove();
        if (!optionGroups.querySelector('.option-group')) {
            const empty = document.createElement('div');
            empty.className = 'option-empty';
            empty.textContent = 'Belum ada pilihan tambahan. Menu tetap bisa dipesan tanpa opsi.';
            optionGroups.append(empty);
        }
    });
    groupHead.append(removeGroup);

    const rows = document.createElement('div');
    rows.className = 'option-rows';
    const actions = document.createElement('div');
    actions.className = 'option-group-actions';
    const hint = document.createElement('span');
    hint.className = 'field-hint';
    hint.textContent = 'Tambahkan pilihan dan harga tambahannya.';
    const addChoice = document.createElement('button');
    addChoice.type = 'button';
    addChoice.className = 'button button-soft button-small';
    addChoice.textContent = '＋ Tambah Pilihan';
    addChoice.addEventListener('click', () => addOptionRow(rows));
    actions.append(hint, addChoice);

    group.append(groupHead, rows, actions);
    optionGroups.append(group);
    addOptionRow(rows);
    groupName.focus();
});

addMenuForm.addEventListener('submit', (event) => {
    const groups = [...optionGroups.querySelectorAll('.option-group')].map((group) => ({
        name: group.querySelector('[data-group-name]').value.trim(),
        options: [...group.querySelectorAll('.option-row')].map((row) => ({
            label: row.querySelector('[data-option-label]').value.trim(),
            price: Number(row.querySelector('[data-option-price]').value),
        })),
    }));

    if (groups.some((group) => !group.name || group.options.some((option) => !option.label || !Number.isInteger(option.price) || option.price < 0))) {
        event.preventDefault();
        return;
    }

    optionsJson.value = JSON.stringify(groups);
});

// Order processing: change status, live counter, and auto-refresh.
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const ordersBody = document.getElementById('orders-body');
const ordersLive = document.getElementById('orders-live');
const ordersRefresh = document.getElementById('orders-refresh');
const totalOrdersStat = document.querySelector('.stats .stat-card strong');
const revenueStat = document.querySelector('.stats .stat-card:nth-child(4) strong');
let latestOrderId = 0;

const statusClass = (status) => `order-status-select status-${status}`;

const updateOrdersIndicator = (count) => {
    if (!ordersLive) return;
    ordersLive.textContent = `${count} pesanan · diperbarui ${new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}`;
};

const reloadOrders = async () => {
    if (!ordersBody) return;
    const response = await fetch('/admin/orders/table', { headers: { Accept: 'text/html' } });
    if (!response.ok) return;
    ordersBody.innerHTML = await response.text();
};

const refreshOrderSummary = async (forceTable = false) => {
    if (!ordersBody) return;
    const response = await fetch('/admin/orders', { headers: { Accept: 'application/json' } });
    if (!response.ok) return;
    const data = await response.json();

    if (totalOrdersStat) totalOrdersStat.textContent = data.count;
    if (revenueStat) revenueStat.textContent = formatRupiah(data.revenue);
    updateOrdersIndicator(data.count);

    if (forceTable || (latestOrderId && data.latest_id > latestOrderId)) {
        await reloadOrders();
        if (!forceTable && ordersLive) ordersLive.textContent = `Pesanan baru masuk (${data.count})`;
    }
    latestOrderId = Math.max(latestOrderId, data.latest_id ?? 0);
};

if (ordersBody) {
    refreshOrderSummary();
    window.setInterval(() => refreshOrderSummary(), 20000);

    ordersRefresh?.addEventListener('click', () => refreshOrderSummary(true));

    ordersBody.addEventListener('change', async (event) => {
        const select = event.target.closest('select[data-order-status]');
        if (!select) return;
        const id = select.dataset.id;
        const previous = select.dataset.previous ?? select.value;
        select.disabled = true;
        select.dataset.previous = select.value;

        const response = await fetch(`/admin/orders/${id}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ status: select.value }),
        });

        select.disabled = false;
        if (!response.ok) {
            select.value = previous;
            select.dataset.previous = previous;
            return;
        }
        select.className = statusClass(select.value);
    });
}

// Order detail dialog.
const orderDetailDialog = document.getElementById('order-detail-dialog');
const orderDetailError = document.getElementById('order-detail-error');

const orderDetailField = (id) => document.getElementById(`order-detail-${id}`);

const closeOrderDetail = () => {
    if (orderDetailDialog?.open) orderDetailDialog.close();
};

const resetOrderDetail = () => {
    orderDetailError.hidden = true;
    orderDetailError.textContent = '';
    ['code', 'customer', 'phone', 'address', 'service', 'note', 'created', 'updated', 'subtotal', 'fee', 'total']
        .forEach((id) => { const el = orderDetailField(id); if (el) el.textContent = '—'; });
    orderDetailField('items-body').replaceChildren();
    orderDetailField('status').textContent = '—';
    orderDetailField('status').className = '';
    orderDetailField('fee-row').hidden = false;
};

const renderOrderDetail = (data) => {
    orderDetailField('code').textContent = `#${data.code}`;
    orderDetailField('customer').textContent = data.customer_name || '—';
    orderDetailField('phone').textContent = data.phone || '—';
    orderDetailField('address').textContent = data.address || '—';
    orderDetailField('service').textContent = data.service || '—';
    orderDetailField('note').textContent = data.note || '—';
    orderDetailField('created').textContent = formatDateTime(data.created_at);
    orderDetailField('updated').textContent = formatDateTime(data.updated_at);

    const status = orderDetailField('status');
    status.textContent = data.status_label || data.status;
    status.className = `order-status status-${data.status}`;

    const body = orderDetailField('items-body');
    body.replaceChildren();
    (Array.isArray(data.items) ? data.items : []).forEach((item) => {
        const options = Object.entries(item.options || {})
            .map(([key, value]) => `${key}: ${value}`)
            .join(' · ');
        const note = item.note ? ` · Catatan: ${item.note}` : '';
        const name = `${item.name || '—'}${options ? ` (${options})` : ''}${note}`;

        const row = document.createElement('tr');
        const nameCell = document.createElement('td');
        nameCell.textContent = name;
        const priceCell = document.createElement('td');
        priceCell.textContent = formatRupiah(item.unit_price);
        const qtyCell = document.createElement('td');
        qtyCell.textContent = `× ${item.quantity ?? 1}`;
        const subtotalCell = document.createElement('td');
        subtotalCell.textContent = formatRupiah(item.subtotal);

        row.append(nameCell, priceCell, qtyCell, subtotalCell);
        body.append(row);
    });

    orderDetailField('subtotal').textContent = formatRupiah(data.subtotal);
    orderDetailField('total').textContent = formatRupiah(data.total);

    const hasFee = Number(data.service_fee) > 0;
    orderDetailField('fee-row').hidden = !hasFee;
    orderDetailField('fee-label').textContent = data.service === 'Antar' ? 'Ongkir antar' : 'Ongkir';
    orderDetailField('fee').textContent = formatRupiah(data.service_fee);
};

const formatDateTime = (value) => {
    if (!value) return '—';
    const date = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
};

const openOrderDetail = async (id) => {
    if (!id || !orderDetailDialog) return;

    resetOrderDetail();
    orderDetailDialog.showModal();
    document.body.classList.add('dialog-open');

    try {
        const response = await fetch(`/admin/orders/${id}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('not found');
        renderOrderDetail(await response.json());
    } catch (error) {
        orderDetailError.hidden = false;
        orderDetailError.textContent = 'Gagal memuat detail pesanan.';
    }
};

const bindOrderDetailTriggers = (container) => {
    if (!container) return;
    container.addEventListener('click', (event) => {
        const button = event.target.closest('[data-order-detail]');
        if (!button) return;
        event.preventDefault();
        openOrderDetail(button.dataset.id);
    });
};

bindOrderDetailTriggers(ordersBody);
bindOrderDetailTriggers(document.getElementById('recent-orders-body'));

if (orderDetailDialog) {
    document.getElementById('close-order-detail').addEventListener('click', closeOrderDetail);
    orderDetailDialog.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeOrderDetail();
        }
    });
    orderDetailDialog.addEventListener('close', () => document.body.classList.remove('dialog-open'));
    orderDetailDialog.addEventListener('click', (event) => {
        if (event.target === orderDetailDialog) closeOrderDetail();
    });
}

const backupsBody = document.getElementById('backups-body');
const backupNow = document.getElementById('backup-now');
const backupRefresh = document.getElementById('backup-refresh');

const formatBytes = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const reloadBackups = async () => {
    if (!backupsBody) return;
    try {
        const response = await fetch('/admin/backups', { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('failed');
        const data = await response.json();
        const backups = data.backups || [];
        if (!backups.length) {
            backupsBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color: var(--muted);">Belum ada backup.</td></tr>';
            return;
        }
        backupsBody.innerHTML = backups.map((backup) => `
            <tr>
                <td><code>${escapeHtml(backup.name)}</code></td>
                <td>${formatBytes(backup.size)}</td>
                <td>${backup.modified}</td>
                <td><a class="button button-soft button-small" href="/admin/backups/download/${backup.name}" download>Unduh</a></td>
            </tr>
        `).join('');
    } catch {
        backupsBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color: var(--muted);">Gagal memuat backup.</td></tr>';
    }
};

if (backupNow) {
    backupNow.addEventListener('click', async () => {
        backupNow.disabled = true;
        backupNow.textContent = 'Membuat…';
        try {
            await fetch('/admin/backups/now', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' } });
            await reloadBackups();
        } catch {
            alert('Gagal membuat backup.');
        } finally {
            backupNow.disabled = false;
            backupNow.textContent = 'Backup Sekarang';
        }
    });
}

if (backupRefresh) {
    backupRefresh.addEventListener('click', reloadBackups);
}

if (backupsBody) {
    reloadBackups();
}

const activityLogBody = document.getElementById('activity-log-body');
if (activityLogBody) {
    const reloadActivityLog = async () => {
        try {
            const response = await fetch('/admin/activity-log', { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('failed');
            const data = await response.json();
            const logs = data.logs || [];
            if (!logs.length) {
                activityLogBody.innerHTML = '<tr><td colspan="5" style="text-align:center; color: var(--muted);">Belum ada aktivitas.</td></tr>';
                return;
            }
            activityLogBody.innerHTML = logs.map((log) => `
                <tr>
                    <td>${new Date(log.created_at).toLocaleString('id-ID')}</td>
                    <td>${log.user_email || '—'}</td>
                    <td><code>${escapeHtml(log.action)}</code></td>
                    <td>${escapeHtml(log.description || '—')}</td>
                    <td>${log.ip_address || '—'}</td>
                </tr>
            `).join('');
        } catch {
            activityLogBody.innerHTML = '<tr><td colspan="5" style="text-align:center; color: var(--muted);">Gagal memuat activity log.</td></tr>';
        }
    };
    reloadActivityLog();
}

// Tab switching and quick-action filters
function switchTab(name) {
    document.querySelectorAll('.tab-link').forEach(btn => btn.classList.toggle('active', btn.dataset.tab === name));
    document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.toggle('active', panel.dataset.tab === name));
    window.history.replaceState(null, '', `?tab=${encodeURIComponent(name)}`);
}
document.querySelectorAll('.tab-link').forEach(btn => btn.addEventListener('click', () => switchTab(btn.dataset.tab)));
const urlTab = new URLSearchParams(window.location.search).get('tab');
if (urlTab) switchTab(urlTab);

document.querySelectorAll('.stat-card').forEach(card => {
    card.addEventListener('click', () => switchTab(card.dataset.tab));
});

document.getElementById('quick-add-menu')?.addEventListener('click', () => {
    const dialog = document.getElementById('add-menu-dialog');
    if (dialog) { dialog.showModal(); document.body.classList.add('dialog-open'); }
});
document.getElementById('quick-export')?.addEventListener('click', (event) => {
    const button = event.currentTarget;
    window.location.href = button.dataset.exportUrl || '/admin/orders/export';
});

const productsSearch = document.getElementById('products-search');
if (productsSearch) {
    productsSearch.addEventListener('input', () => {
        const q = productsSearch.value.trim().toLowerCase();
        document.querySelectorAll('#products-table-body tr[data-name]').forEach(row => {
            const name = (row.dataset.name || '').toLowerCase();
            const cat = (row.dataset.category || '').toLowerCase();
            row.style.display = (!q || name.includes(q) || cat.includes(q)) ? '' : 'none';
        });
    });
}

const ordersStatusFilter = document.getElementById('orders-status-filter');
if (ordersStatusFilter) {
    ordersStatusFilter.addEventListener('change', () => {
        const q = ordersStatusFilter.value;
        document.querySelectorAll('#orders-body tr[data-status]').forEach(row => {
            row.style.display = (!q || row.dataset.status === q) ? '' : 'none';
        });
    });
}

const recentOrdersBody = document.getElementById('recent-orders-body');
if (recentOrdersBody) {
    const loadRecentOrders = async () => {
        try {
            const response = await fetch('/admin/orders', { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('failed');
            const data = await response.json();
            const orders = Array.isArray(data.orders) ? data.orders : [];
            const live = document.getElementById('quick-orders-live');
            if (live) live.textContent = `${data.count ?? orders.length} pesanan · diperbarui ${new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}`;

            if (!orders.length) {
                recentOrdersBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color: var(--muted);">Belum ada pesanan.</td></tr>';
                return;
            }

            recentOrdersBody.innerHTML = orders.map(order => `
                <tr>
                    <td><a href="#" class="order-code" data-order-detail data-id="${order.id}">${escapeHtml(order.code)}</a><br><span class="order-time">${escapeHtml(formatDateTime(order.created_at))}</span></td>
                    <td class="order-customer"><strong>${escapeHtml(order.customer_name || '—')}</strong><small>${escapeHtml(order.phone || '')}</small></td>
                    <td class="order-total">${formatRupiah(order.total)}</td>
                    <td><span class="order-status status-${escapeHtml(order.status)}">${escapeHtml(order.status_label || order.status)}</span></td>
                </tr>
            `).join('');
        } catch {
            recentOrdersBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color: var(--muted);">Gagal memuat pesanan terbaru.</td></tr>';
        }
    };
    loadRecentOrders();
    setInterval(loadRecentOrders, 30000);
}

const recentActivityBody = document.getElementById('recent-activity-body');
if (recentActivityBody) {
    const loadRecentActivity = async () => {
        try {
            const response = await fetch('/admin/activity-log', { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            const logs = (data.logs || []).slice(0, 10);
            recentActivityBody.innerHTML = logs.map(log => `
                <tr>
                    <td>${new Date(log.created_at).toLocaleString('id-ID')}</td>
                    <td><code>${escapeHtml(log.action)}</code></td>
                    <td>${escapeHtml(log.description || '—')}</td>
                </tr>
            `).join('');
        } catch {
            recentActivityBody.innerHTML = '<tr><td colspan="3" style="text-align:center; color: var(--muted);">Gagal memuat aktivitas.</td></tr>';
        }
    };
    loadRecentActivity();
    setInterval(loadRecentActivity, 60000);
}
