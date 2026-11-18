// --- Client-side error reporting (admin dashboard) ---
// Same mechanism as the customer catalog: uncaught errors and unhandled
// promise rejections are sent to /api/client-logs so operators can see what
// broke in the dashboard without asking the admin to check dev tools.
(function initAdminErrorReporting() {
  let lastRequestId = null;
  window.addEventListener("error", (event) => {
    reportAdminError(event.error?.message || String(event.error), {
      stack: event.error?.stack,
      line: event.lineno,
      col: event.colno,
    });
  });
  window.addEventListener("unhandledrejection", (event) => {
    const reason = event.reason && (event.reason.message || event.reason);
    reportAdminError(String(reason), { stack: event.reason?.stack });
  });
  window.fetch = (function (originalFetch) {
    return async function (...args) {
      const response = await originalFetch.apply(this, args);
      const header = response.headers.get("X-Request-Id");
      if (header) lastRequestId = header;
      return response;
    };
  })(window.fetch);

  function reportAdminError(message, details = {}) {
    if (lastRequestId) details.request_id = lastRequestId;
    details.url = location.href;
    fetch("/api/client-logs", {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({
        message: String(message).slice(0, 1000),
        stack: details.stack ? String(details.stack).slice(0, 4000) : null,
        line: details.line,
        col: details.col,
        source: "admin",
        url: details.url,
        request_id: details.request_id,
      }),
    }).catch(() => {});
  }
})();

// Global toast notification. Bottom-anchored so it stays reachable on
// mobile; click a toast to dismiss it early.
function toast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.setAttribute('aria-live', 'polite');
        document.body.appendChild(container);
    }

    const item = document.createElement('div');
    item.className = 'admin-toast admin-toast--' + type;
    item.setAttribute('role', type === 'error' ? 'alert' : 'status');
    item.textContent = message;

    const dismiss = () => {
        if (!item.isConnected) return;
        item.classList.add('admin-toast--out');
        setTimeout(() => item.remove(), 250);
    };
    item.addEventListener('click', dismiss);

    container.appendChild(item);
    setTimeout(dismiss, type === 'error' ? 6000 : 4000);
}
window.toast = toast;

// Surface server-side flash messages as toasts on page load, and make
// sure a validation error banner is in view even after scroll restore.
const flashSuccess = document.getElementById('flash-success');
if (flashSuccess && flashSuccess.dataset.message) {
    toast(flashSuccess.dataset.message, 'success');
}
const flashErrors = document.getElementById('flash-errors');
if (flashErrors) {
    flashErrors.scrollIntoView({ block: 'center' });
}

document.querySelectorAll('.edit-trigger-button').forEach((button) => {
    button.addEventListener('click', () => {
        const row = button.closest('tr');
        const editRow = row.nextElementSibling;
        if (editRow && editRow.classList.contains('edit-row')) {
            const isHidden = editRow.hidden;
            editRow.hidden = !isHidden;
            button.setAttribute('aria-expanded', String(!isHidden));
            button.textContent = isHidden ? 'Tutup edit' : 'Edit';
            if (!isHidden) {
                editRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
});

document.querySelectorAll('[data-copy-example]').forEach((button) => {
    button.addEventListener('click', async () => {
        const code = button.closest('.option-example-body')?.querySelector('pre code')?.textContent || '';
        try {
            await navigator.clipboard.writeText(code);
            button.textContent = 'Tersalin';
        } catch {
            button.textContent = 'Gagal menyalin, salin manual';
        }
        setTimeout(() => { button.textContent = 'Salin contoh'; }, 2200);
    });
});

document.querySelectorAll('.edit-close, .edit-cancel').forEach((button) => {
    button.addEventListener('click', () => {
        const editRow = button.closest('.edit-row');
        if (editRow) {
            editRow.hidden = true;
            const productRow = editRow.previousElementSibling;
            const trigger = productRow?.querySelector('.edit-trigger-button');
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
                trigger.textContent = 'Edit';
            }
        }
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

const addOptionRow = (rows, initial = {}) => {
    const row = document.createElement('div');
    row.className = 'option-row';
    const labelInput = document.createElement('input');
    labelInput.type = 'text'; labelInput.required = true; labelInput.maxLength = 80;
    labelInput.placeholder = 'Contoh: Large'; labelInput.dataset.optionLabel = '';
    labelInput.setAttribute('aria-label', 'Nama pilihan'); labelInput.value = initial.label ?? '';
    const priceInput = document.createElement('input');
    priceInput.type = 'number'; priceInput.required = true; priceInput.min = '0';
    priceInput.max = '10000000'; priceInput.step = '1'; priceInput.inputMode = 'numeric';
    priceInput.placeholder = '0'; priceInput.dataset.optionPrice = '';
    priceInput.setAttribute('aria-label', 'Harga tambahan dalam rupiah'); priceInput.value = initial.price ?? 0;
    const remove = document.createElement('button');
    remove.type = 'button'; remove.className = 'remove-option'; remove.textContent = '×';
    remove.setAttribute('aria-label', 'Hapus pilihan');
    remove.addEventListener('click', () => {
        const group = rows.closest('.option-group');
        row.remove();
        if (!rows.querySelector('.option-row')) addOptionRow(rows);
        group.querySelector('[data-option-label]').focus();
    });
    row.append(makeField('Nama pilihan', labelInput), makeField('Tambah harga (Rp)', priceInput), remove);
    rows.append(row);
};

const addOptionGroup = (container, initial = {}, focus = true) => {
    container.querySelector('.option-empty')?.remove();
    const group = document.createElement('fieldset');
    group.className = 'option-group';
    const groupHead = document.createElement('div');
    groupHead.className = 'option-group-head';
    const groupName = document.createElement('input');
    groupName.type = 'text'; groupName.required = true; groupName.maxLength = 80;
    groupName.placeholder = 'Contoh: Ukuran'; groupName.dataset.groupName = '';
    groupName.setAttribute('aria-label', 'Nama grup pilihan'); groupName.value = initial.name ?? '';
    groupHead.append(makeField('Nama grup', groupName));
    const removeGroup = document.createElement('button');
    removeGroup.type = 'button'; removeGroup.className = 'button button-danger button-small';
    removeGroup.textContent = 'Hapus grup';
    removeGroup.addEventListener('click', () => {
        group.remove();
        if (!container.querySelector('.option-group')) {
            const empty = document.createElement('div');
            empty.className = 'option-empty';
            empty.textContent = 'Belum ada pilihan tambahan. Menu tetap bisa dipesan tanpa opsi.';
            container.append(empty);
        }
    });
    groupHead.append(removeGroup);
    const rows = document.createElement('div'); rows.className = 'option-rows';
    const actions = document.createElement('div'); actions.className = 'option-group-actions';
    const hint = document.createElement('span'); hint.className = 'field-hint';
    hint.textContent = 'Tambahkan pilihan dan harga tambahannya.';
    const addChoice = document.createElement('button');
    addChoice.type = 'button'; addChoice.className = 'button button-soft button-small';
    addChoice.textContent = '＋ Tambah Pilihan';
    addChoice.addEventListener('click', () => addOptionRow(rows));
    actions.append(hint, addChoice);
    group.append(groupHead, rows, actions);
    container.append(group);
    const choices = Array.isArray(initial.options) && initial.options.length ? initial.options : [{}];
    choices.forEach((option) => addOptionRow(rows, option));
    if (focus) groupName.focus();
};

const serializeBuilder = (container, hiddenInput) => {
    const groups = [...container.querySelectorAll('.option-group')].map((group) => ({
        name: group.querySelector('[data-group-name]').value.trim(),
        options: [...group.querySelectorAll('.option-row')].map((row) => ({
            label: row.querySelector('[data-option-label]').value.trim(),
            price: Number(row.querySelector('[data-option-price]').value),
        })),
    }));
    if (groups.some((group) => !group.name || group.options.some((option) => !option.label || !Number.isInteger(option.price) || option.price < 0))) return false;
    hiddenInput.value = JSON.stringify(groups);
    return true;
};

document.getElementById('add-option-group').addEventListener('click', () => addOptionGroup(optionGroups));
addMenuForm.addEventListener('submit', (event) => {
    if (!serializeBuilder(optionGroups, optionsJson)) event.preventDefault();
});

document.querySelectorAll('[data-edit-option-builder]').forEach((container) => {
    let initial;
    try { initial = JSON.parse(container.dataset.options || '[]'); } catch { initial = []; }
    if (Array.isArray(initial)) initial.forEach((group) => addOptionGroup(container, group, false));
    if (!container.querySelector('.option-group')) {
        const empty = document.createElement('div'); empty.className = 'option-empty';
        empty.textContent = 'Belum ada pilihan tambahan. Menu tetap bisa dipesan tanpa opsi.';
        container.append(empty);
    }
    container.closest('form').addEventListener('submit', (event) => {
        if (!serializeBuilder(container, container.closest('form').querySelector('[name="options_json"]'))) event.preventDefault();
    });
});

document.querySelectorAll('[data-add-option-group]').forEach((button) => {
    button.addEventListener('click', () => {
        const container = button.closest('.field').querySelector('[data-edit-option-builder]');
        if (container) addOptionGroup(container);
    });
});

// Remove legacy JSON example panel from edit forms; all options use visual fields now.

// Order processing: change status, live counter, and auto-refresh.
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const ordersBody = document.getElementById('orders-body');
const ordersLive = document.getElementById('orders-live'); // may be null after redesign
const ordersRefresh = document.getElementById('orders-refresh'); // may be null after redesign
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

    if (ordersRefresh) {
        ordersRefresh.addEventListener('click', () => refreshOrderSummary());
    }

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
            let message = 'Gagal mengubah status pesanan.';
            try {
                const payload = await response.json();
                if (payload && payload.message) message = payload.message;
            } catch (_) { /* non-JSON error body */ }
            toast(message, 'error');
            return;
        }
        select.className = statusClass(select.value);
        const statusLabel = select.options[select.selectedIndex]?.text || select.value;
        toast(`Status pesanan #${id} → ${statusLabel}`, 'success');
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
        toast('Gagal memuat daftar backup.', 'error');
    }
};

if (backupNow) {
    backupNow.addEventListener('click', async () => {
        backupNow.disabled = true;
        backupNow.textContent = 'Membuat…';
        try {
            const response = await fetch('/admin/backups/now', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' } });
            if (!response.ok) throw new Error('backup failed');
            await reloadBackups();
            toast('Backup berhasil dibuat.', 'success');
        } catch {
            toast('Gagal membuat backup.', 'error');
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
const menuFilterButtons = [...document.querySelectorAll('[data-menu-filter]')];
let activeMenuFilter = 'all';
function filterMenuRows() {
    const q = (productsSearch?.value || '').trim().toLocaleLowerCase('id-ID');
    let visibleCount = 0;
    document.querySelectorAll('#products-table-body .product-row').forEach(row => {
        const matchesQuery = !q || `${row.dataset.name || ''} ${row.dataset.category || ''}`.toLocaleLowerCase('id-ID').includes(q);
        const matchesFilter = activeMenuFilter === 'all'
            || (activeMenuFilter === 'available' && row.dataset.available === '1')
            || (activeMenuFilter === 'recommended' && row.dataset.recommended === '1');
        const visible = matchesQuery && matchesFilter;
        row.hidden = !visible;
        const editRow = row.nextElementSibling?.classList.contains('edit-row') ? row.nextElementSibling : null;
        if (editRow) editRow.hidden = !visible;
        if (visible) visibleCount++;
    });
    const count = document.getElementById('menu-result-count');
    if (count) count.textContent = `${visibleCount} menu${visibleCount === 1 ? '' : ''}`;
}
productsSearch?.addEventListener('input', filterMenuRows);
menuFilterButtons.forEach(button => button.addEventListener('click', () => {
    activeMenuFilter = button.dataset.menuFilter;
    menuFilterButtons.forEach(item => {
        const active = item === button;
        item.classList.toggle('active', active);
        item.setAttribute('aria-pressed', String(active));
    });
    filterMenuRows();
}));
document.addEventListener('keydown', event => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k' && productsSearch) {
        event.preventDefault();
        productsSearch.focus();
    }
});

const ordersStatusFilter = document.getElementById('orders-status-filter');
const ordersSearch = document.getElementById('orders-search');
const ordersFrom = document.getElementById('orders-from');
const ordersTo = document.getElementById('orders-to');

let ordersDebounceTimer = null;

function buildOrdersQueryParams() {
    const params = new URLSearchParams();
    if (ordersStatusFilter?.value) params.set('status', ordersStatusFilter.value);
    if (ordersSearch?.value) params.set('search', ordersSearch.value);
    if (ordersFrom?.value) params.set('from', ordersFrom.value);
    if (ordersTo?.value) params.set('to', ordersTo.value);
    return params.toString();
}

async function reloadOrdersTable(appendPage = false) {
    if (!ordersBody) return;
    const query = buildOrdersQueryParams();
    const url = `/admin/orders/table?${query}`;

    try {
        const response = await fetch(url, { headers: { Accept: 'text/html' } });
        if (!response.ok) throw new Error('failed');
        const html = await response.text();

        if (appendPage) {
            // For pagination: append to existing body (though we'll just replace for simplicity)
            ordersBody.innerHTML = html;
        } else {
            ordersBody.innerHTML = html;
        }
        updateOrdersIndicator(response.headers.get('X-Total-Count') ?? document.querySelectorAll('#orders-body tr[data-status]').length);
        rebindOrderDetailTriggers();
    } catch {
        ordersBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color: var(--muted);">Gagal memuat pesanan.</td></tr>';
    }
}

function debouncedReloadOrders() {
    clearTimeout(ordersDebounceTimer);
    ordersDebounceTimer = setTimeout(() => reloadOrdersTable(), 300);
}

function rebindOrderDetailTriggers() {
    if (!ordersBody || ordersBody._detailBound) return;
    ordersBody._detailBound = true;
    ordersBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-order-detail]');
        if (!button) return;
        event.preventDefault();
        openOrderDetail(button.dataset.id);
    });
}

// Filter event listeners
if (ordersStatusFilter) {
    ordersStatusFilter.addEventListener('change', debouncedReloadOrders);
}
if (ordersSearch) {
    ordersSearch.addEventListener('input', debouncedReloadOrders);
}
if (ordersFrom) {
    ordersFrom.addEventListener('change', debouncedReloadOrders);
}
if (ordersTo) {
    ordersTo.addEventListener('change', debouncedReloadOrders);
}
if (ordersRefresh) {
    ordersRefresh.addEventListener('click', () => reloadOrdersTable());
}

// Pagination: delegate clicks on pagination links in orders-body
if (ordersBody) {
    ordersBody.addEventListener('click', (event) => {
        const link = event.target.closest('.pagination a[href]');
        if (!link) return;
        event.preventDefault();
        const url = new URL(link.href, window.location.origin);
        // Load the page via AJAX
        fetch(url.pathname + url.search, { headers: { Accept: 'text/html' } })
            .then(r => r.ok ? r.text() : Promise.reject())
            .then(html => {
                ordersBody.innerHTML = html;
                rebindOrderDetailTriggers();
            })
            .catch(() => {
                ordersBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color: var(--muted);">Gagal memuat halaman.</td></tr>';
            });
    });
    // Initial bind
    rebindOrderDetailTriggers();
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

const systemLogBody = document.getElementById('system-log-body');
if (systemLogBody) {
    let currentFilter = 'all';

    const loadSystemLog = async (level = currentFilter) => {
        try {
            const url = level && level !== 'all' ? `/admin/system-log?level=${encodeURIComponent(level)}` : '/admin/system-log';
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            const events = Array.isArray(data.events) ? data.events.slice(0, 20) : [];

            if (!events.length) {
                systemLogBody.innerHTML = '<tr><td colspan="5" style="text-align:center; color: var(--muted);">Belum ada catatan sistem.</td></tr>';
                return;
            }

            systemLogBody.innerHTML = events.map(evt => {
                const date = new Date(evt.created_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'medium' });
                const reqId = evt.request_id ? `<code>${escapeHtml(evt.request_id.slice(0, 8))}…</code>` : '—';
                return `
                    <tr>
                        <td>${date}</td>
                        <td><span class="order-status status-${escapeHtml(evt.level)}">${escapeHtml(evt.level)}</span></td>
                        <td><code>${escapeHtml(evt.event)}</code></td>
                        <td>${escapeHtml(evt.message || '—')}</td>
                        <td>${reqId}</td>
                    </tr>
                `;
            }).join('');
        } catch {
            systemLogBody.innerHTML = '<tr><td colspan="5" style="text-align:center; color: var(--muted);">Gagal memuat log sistem.</td></tr>';
        }
    };

    document.querySelectorAll('#system-log-filters [data-level]').forEach(btn => {
        btn.addEventListener('click', () => {
            currentFilter = btn.dataset.level;
            document.querySelectorAll('#system-log-filters [data-level]').forEach(b => b.classList.remove('button-primary'));
            btn.classList.add('button-primary');
            loadSystemLog(currentFilter);
        });
    });

    loadSystemLog();
    setInterval(() => loadSystemLog(currentFilter), 30000);
}

// =============================================================================
// CATEGORIES TAB FUNCTIONALITY
// =============================================================================

(function initCategoriesTab() {
    const categoriesBody = document.getElementById('categories-body');
    const categoriesSearch = document.getElementById('categories-search');
    const addCategoryBtn = document.getElementById('open-add-category');
    const addCategoryDialog = document.getElementById('add-category-dialog');
    const addCategoryForm = document.getElementById('add-category-form');
    const editCategoryDialog = document.getElementById('edit-category-dialog');
    const editCategoryForm = document.getElementById('edit-category-form');
    const mergeSourceSelect = document.getElementById('merge-source');
    const mergeTargetSelect = document.getElementById('merge-target');
    const mergeBtn = document.getElementById('btn-merge-categories');
    const mergeResult = document.getElementById('merge-result');

    if (!categoriesBody) return;

    // CSRF token helper
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    // Load categories table via AJAX
    async function loadCategoriesTable() {
        try {
            const search = categoriesSearch?.value || '';
            const params = new URLSearchParams();
            if (search) params.set('search', search);

            const response = await fetch(`/admin/categories?${params.toString()}`, {
                headers: { Accept: 'text/html' }
            });
            if (!response.ok) throw new Error('Failed to load categories');

            const html = await response.text();
            categoriesBody.innerHTML = html;

            // Re-initialize SortableJS after content is loaded
            initSortable();
        } catch (error) {
            categoriesBody.innerHTML = '<tr><td colspan="6" style="text-align:center; color: var(--muted);">Gagal memuat kategori.</td></tr>';
            console.error('Load categories error:', error);
        }
    }

    // Initialize SortableJS for drag-drop sorting
    function initSortable() {
        if (typeof Sortable === 'undefined') {
            // Load SortableJS if not already loaded - use local file first
            const script = document.createElement('script');
            script.src = '/assets/admin/vendor/sortable.min.js';
            script.onload = initSortable;
            script.onerror = function() {
                // Fallback to CDN if local file fails
                script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
            };
            document.head.appendChild(script);
            return;
        }

        const tbody = categoriesBody;
        if (tbody._sortable) {
            tbody._sortable.destroy();
        }

        tbody._sortable = new Sortable(tbody, {
            handle: '.sort-handle',
            draggable: '.category-row',
            animation: 140,
            onEnd: function (evt) {
                const rows = Array.from(tbody.querySelectorAll('.category-row'));
                const ids = rows.map(row => row.dataset.id);

                // Update sort_order via AJAX
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/categories/reorder';
                ids.forEach((id, index) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'order[]';
                    input.value = id;
                    form.appendChild(input);
                });
                const method = document.createElement('input');
                method.type = 'hidden'; method.name = '_method'; method.value = 'PATCH'; form.appendChild(method);
                const token = document.createElement('input');
                token.type = 'hidden'; token.name = '_token'; token.value = getCsrfToken(); form.appendChild(token);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Dialog helpers
    function openDialog(dialog) {
        if (!dialog) return;
        dialog.showModal();
        const focusTarget = dialog.querySelector('[autofocus]') || dialog.querySelector('input, select, textarea, button');
        focusTarget?.focus();
    }

    function closeDialog(dialog) {
        if (!dialog) return;
        dialog.close();
        const form = dialog.querySelector('form');
        if (form) form.reset();
    }

    // Add category dialog
    if (addCategoryBtn && addCategoryDialog && addCategoryForm) {
        addCategoryBtn.addEventListener('click', () => openDialog(addCategoryDialog));

        addCategoryDialog.addEventListener('click', (e) => {
            if (e.target === addCategoryDialog || e.target.classList.contains('dialog-close') || e.target.classList.contains('dialog-cancel')) {
                closeDialog(addCategoryDialog);
            }
        });

        addCategoryForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(addCategoryForm);
            const data = Object.fromEntries(formData);

            try {
                const response = await fetch('/admin/categories', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify(data),
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Gagal menambah kategori');
                }

                toast('Kategori berhasil ditambahkan', 'success');
                closeDialog(addCategoryDialog);
                loadCategoriesTable();
            } catch (error) {
                toast(error.message || 'Gagal menambah kategori', 'error');
            }
        });
    }

    // Edit category dialog
    let editingCategoryId = null;

    if (categoriesBody && editCategoryDialog && editCategoryForm) {
        // Delegate click on edit buttons
        categoriesBody.addEventListener('click', (e) => {
            const btn = e.target.closest('.edit-category');
            if (!btn) return;

            editingCategoryId = btn.dataset.id;
            document.getElementById('edit-cat-id').value = btn.dataset.id;
            document.getElementById('edit-cat-name').value = btn.dataset.name;
            document.getElementById('edit-cat-sort').value = btn.dataset.sort || 0;
            document.getElementById('edit-cat-active').checked = btn.dataset.active === '1';

            openDialog(editCategoryDialog);
        });

        editCategoryDialog.addEventListener('click', (e) => {
            if (e.target === editCategoryDialog || e.target.classList.contains('dialog-close') || e.target.classList.contains('dialog-cancel')) {
                closeDialog(editCategoryDialog);
                editingCategoryId = null;
            }
        });

        editCategoryForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!editingCategoryId) return;

            const formData = new FormData(editCategoryForm);
            const data = Object.fromEntries(formData);
            // Convert active checkbox to boolean
            data.active = data.active === '1';

            try {
                const response = await fetch(`/admin/categories/${editingCategoryId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify(data),
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Gagal mengupdate kategori');
                }

                toast('Kategori berhasil diupdate', 'success');
                closeDialog(editCategoryDialog);
                editingCategoryId = null;
                loadCategoriesTable();
            } catch (error) {
                toast(error.message || 'Gagal mengupdate kategori', 'error');
            }
        });
    }

    // Delete category
    if (categoriesBody) {
        categoriesBody.addEventListener('click', async (e) => {
            const btn = e.target.closest('.delete-category');
            if (!btn) return;

            const name = btn.dataset.name;
            if (!confirm(`Hapus kategori "${name}"? Tindakan ini tidak bisa dibatalkan.`)) return;

            try {
                const response = await fetch(`/admin/categories/${btn.dataset.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Gagal menghapus kategori');
                }

                toast('Kategori dihapus', 'success');
                loadCategoriesTable();
            } catch (error) {
                toast(error.message || 'Gagal menghapus kategori', 'error');
            }
        });
    }

    // Merge categories
    if (mergeBtn && mergeSourceSelect && mergeTargetSelect && mergeResult) {
        mergeBtn.addEventListener('click', async () => {
            const sourceIds = Array.from(mergeSourceSelect.selectedOptions).map(o => o.value);
            const targetId = mergeTargetSelect.value;

            if (sourceIds.length < 2) {
                mergeResult.textContent = 'Pilih minimal 2 kategori sumber.';
                mergeResult.style.color = 'var(--danger)';
                return;
            }
            if (!targetId) {
                mergeResult.textContent = 'Pilih kategori target.';
                mergeResult.style.color = 'var(--danger)';
                return;
            }
            if (sourceIds.includes(targetId)) {
                mergeResult.textContent = 'Kategori target tidak boleh dipilih sebagai sumber.';
                mergeResult.style.color = 'var(--danger)';
                return;
            }

            mergeBtn.disabled = true;
            mergeResult.textContent = 'Menggabungkan...';
            mergeResult.style.color = 'var(--muted)';

            try {
                const response = await fetch('/admin/categories/merge', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify({ ids: sourceIds, target_id: targetId }),
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Gagal menggabungkan kategori');
                }

                toast('Kategori berhasil digabung', 'success');
                mergeResult.textContent = 'Berhasil!';
                mergeResult.style.color = 'var(--success)';
                loadCategoriesTable();
            } catch (error) {
                toast(error.message || 'Gagal menggabungkan kategori', 'error');
                mergeResult.textContent = 'Gagal: ' + error.message;
                mergeResult.style.color = 'var(--danger)';
            } finally {
                mergeBtn.disabled = false;
            }
        });
    }

    // Search filter
    if (categoriesSearch) {
        let searchDebounce = null;
        categoriesSearch.addEventListener('input', () => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(loadCategoriesTable, 300);
        });
    }

    // Initial load
    initSortable();
})();
