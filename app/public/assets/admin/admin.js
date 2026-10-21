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

const bindImagePreview = (input, preview) => {
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
        image.alt = 'Pratinjau foto menu';
        image.src = URL.createObjectURL(file);
        image.onload = () => URL.revokeObjectURL(image.src);
        preview.replaceChildren(image);
    });
};
bindImagePreview(document.getElementById('new-image'), document.getElementById('new-image-preview'));

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
