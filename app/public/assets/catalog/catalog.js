
    // --- Data Initializer ---
    const fmt = (n) => "Rp" + Number(n || 0).toLocaleString("id-ID");
    let products = [];
    let deliveryFeeConfigured = 0;
    fetch("/api/store-config").then(r => r.ok ? r.json() : null).then(config => {
      if (!config) return;
      deliveryFeeConfigured = Number(config.delivery_fee) || 0;
      document.getElementById("brandTitle").textContent = config.store_name;
      const brandMark = document.getElementById("brandMark");
      if (config.logo_url) {
        const logo = document.createElement("img");
        logo.className = "brand-logo";
        logo.src = config.logo_url;
        logo.alt = `Logo ${config.store_name}`;
        brandMark.replaceWith(logo);
        const favicon = document.createElement("link");
        favicon.rel = "icon";
        favicon.href = config.logo_url;
        document.head.append(favicon);
      } else {
        brandMark.textContent = [...config.store_name][0] || "R";
      }
      document.getElementById("brandTagline").textContent = config.store_tagline || "";
      document.getElementById("heroEyebrow").textContent = config.store_name;
      document.getElementById("heroHeading").textContent = config.hero_heading;
      document.getElementById("heroDescription").textContent = config.hero_description;
      document.title = `${config.store_name} — Menu & Pesanan`;
      const badges = document.getElementById("heroBadges");
      badges.replaceChildren(...config.marketing_badges.map(text => {
        const item = document.createElement("span");
        item.className = "badge-item";
        item.textContent = `✓ ${text}`;
        return item;
      }));
      badges.hidden = config.marketing_badges.length === 0;
    }).catch(() => {});
    fetch("/api/products").then(r => r.ok ? r.json() : []).then(rows => { products = rows.map(p => ({ id:p.id, name:p.name, category:p.category_name || p.category, desc:p.desc || "", price:Number(p.price), available:Boolean(p.available), tone:p.tone || "green", icon:p.icon || "✿", imageUrl:p.image_url || "", labels:p.labels || [], groups:p.groups || [] })); renderCategories(); renderProducts(); }).catch(() => { products = []; renderCategories(); renderProducts(); });
    localStorage.removeItem("racik_cart_v2");
    let cart = JSON.parse(localStorage.getItem("racik_cart_v3") || "[]");
    localStorage.removeItem("racik_orders_v2");
    let orders = [];
    let profile = JSON.parse(localStorage.getItem("racik_prof_v2") || '{"name":"","phone":"","address":"","srv":"Ambil Sendiri"}');
    let activeCategory = "Semua";
    let searchQuery = "";
    let service = profile.srv || "Ambil Sendiri";
    let currentCustomItem = null;
    let currentCustomOpts = {};
    let currentCustomQty = 1;
    let editingCartIndex = null;
    let removingCartIndex = null;
    const activeLayers = [];
    const layerReturnFocus = new WeakMap();

    function openLayer(layer, focusTarget) {
      if (activeLayers.includes(layer)) return;

      const previousLayer = activeLayers[activeLayers.length - 1];
      if (previousLayer) previousLayer.inert = true;
      else document.querySelector(".shell").inert = true;

      layerReturnFocus.set(layer, document.activeElement);
      layer.inert = false;
      layer.classList.add("active");
      layer.setAttribute("aria-hidden", "false");
      activeLayers.push(layer);
      document.body.classList.add("has-overlay");

      if (layer.id === "cartDrawer") {
        document.getElementById("cartBackdrop").classList.add("active");
      }

      requestAnimationFrame(() => {
        const target = focusTarget || layer.querySelector(
          'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        );
        if (target && !target.disabled) target.focus({ preventScroll: true });
      });
    }

    function resetCatalog() {
      activeCategory = "Semua";
      searchQuery = "";
      document.getElementById("searchInput").value = "";
      renderCategories();
      renderProducts();
    }

    function closeLayer(layer) {
      const layerIndex = activeLayers.indexOf(layer);
      layer.classList.remove("active");
      layer.setAttribute("aria-hidden", "true");
      layer.inert = true;
      if (layer.id === "cartDrawer") {
        document.getElementById("cartBackdrop").classList.remove("active");
      }
      if (layerIndex < 0) return;

      activeLayers.splice(layerIndex, 1);
      const previousLayer = activeLayers[activeLayers.length - 1];
      const returnFocus = layerReturnFocus.get(layer);
      if (previousLayer) {
        previousLayer.inert = false;
      } else {
        document.querySelector(".shell").inert = false;
        document.body.classList.remove("has-overlay");
      }

      requestAnimationFrame(() => {
        if (returnFocus && returnFocus.isConnected && !returnFocus.disabled) {
          returnFocus.focus({ preventScroll: true });
        } else if (previousLayer) {
          previousLayer.querySelector(
            'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
          )?.focus({ preventScroll: true });
        } else {
          const adminFallback = document.getElementById("btnAddMenu");
          const focusFallback = adminFallback.getClientRects().length ? adminFallback : document.getElementById("topCartBtn");
          focusFallback?.focus({ preventScroll: true });
        }
      });
    }

    function closeActiveLayer() {
      const layer = activeLayers[activeLayers.length - 1];
      if (!layer) return;
      const closeById = {
        cartDrawer: closeCart,
        customModal: closeCustomize,
        menuEditorModal: closeMenuEditor,
        deleteMenuModal: closeDeleteMenu,
        clearCartModal: closeClearCart,
        removeCartItemModal: closeRemoveCartItem,
      };
      closeById[layer.id]?.();
    }

    document.addEventListener("keydown", (event) => {
      const layer = activeLayers[activeLayers.length - 1];
      if (!layer) return;

      if (event.key === "Escape") {
        event.preventDefault();
        closeActiveLayer();
        return;
      }

      if (event.key !== "Tab") return;
      const focusable = [...layer.querySelectorAll(
        'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
      )].filter((element) => element.getClientRects().length > 0);
      if (!focusable.length) {
        event.preventDefault();
        layer.focus();
        return;
      }

      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && (document.activeElement === first || !layer.contains(document.activeElement))) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && (document.activeElement === last || !layer.contains(document.activeElement))) {
        event.preventDefault();
        first.focus();
      }
    });

    const saveAll = () => {
      localStorage.setItem("racik_cart_v3", JSON.stringify(cart));
      localStorage.setItem("racik_prof_v2", JSON.stringify(profile));
    };

    // --- Render Navigation & Menu ---
    function init() {
      renderCategories();
      renderProducts();
      updateCartUI();
      populateProfile();
    }

    function renderCategories() {
      const cats = ["Semua", ...new Set(products.map((p) => p.category))];
      const nav = document.getElementById("categoryNav");
      nav.innerHTML = cats.map((c) => `<button type="button" class="cat-btn ${c === activeCategory ? "active" : ""}" data-cat="${esc(c)}" aria-pressed="${c === activeCategory}">${esc(c)}</button>`).join("");
      nav.querySelectorAll("[data-cat]").forEach((b) => {
        b.onclick = () => {
          activeCategory = b.dataset.cat;
          renderCategories();
          renderProducts();
        };
      });
    }

    function renderProducts() {
      const grid = document.getElementById("productGrid");
      const filtered = products.filter((p) => {
        if (activeCategory !== "Semua" && p.category !== activeCategory) return false;
        if (searchQuery.trim()) {
          const q = searchQuery.toLowerCase();
          return p.name.toLowerCase().includes(q) || p.desc.toLowerCase().includes(q) || p.category.toLowerCase().includes(q);
        }
        return true;
      });

      if (!filtered.length) {
        grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:40px;background:#fff;border-radius:16px;border:1px solid var(--line)" role="status">
      <b style="font-size:15px">${products.length ? "Menu Tidak Ditemukan" : "Menu Belum Tersedia"}</b>
      <p style="color:var(--muted);font-size:12px;margin-top:4px">${products.length ? "Coba kata kunci lain atau reset pencarian dan kategori." : "Menu akan tampil di sini setelah pengelola menambahkannya."}</p>
      ${products.length ? '<button class="btn-sec-action" type="button" onclick="resetCatalog()" style="margin:12px auto 0">Tampilkan Semua Menu</button>' : ""}
    </div>`;
        return;
      }

      grid.innerHTML = filtered
        .map((p) => {
          const inCartQty = cart.filter((c) => c.id === p.id).reduce((s, x) => s + x.qty, 0);
          return `<article class="product">
      <div class="prod-art ${p.tone || "green"} ${p.imageUrl ? "has-photo" : ""}" role="img" aria-label="${esc(p.name)}">
        ${p.imageUrl ? `<img class="prod-photo" src="${esc(p.imageUrl)}" alt="${esc(p.name)}" loading="lazy">` : ""}
        <span class="tag">${esc((p.labels || [])[0] || p.category)}</span>
        <div class="steam-group">
          <div class="steam"></div><div class="steam"></div><div class="steam"></div>
        </div>
        <div class="cup"><span>${esc(p.icon || "✿")}</span></div>
        ${p.available ? "" : '<div class="sold">HABIS HARI INI</div>'}
      </div>
      <div class="prod-body">
        <div>
          <h3 class="prod-title">
            <button class="prod-title-button" type="button" onclick="openCustomize('${esc(p.id)}')" aria-label="Lihat pilihan ${esc(p.name)}" ${p.available ? "" : "disabled"}>${esc(p.name)}</button>
          </h3>
          <p class="desc">${esc(p.desc)}</p>
          <div class="meta-labels">${esc(p.category)} · ${(p.labels || []).join(" · ")}</div>
        </div>
        <div class="prod-foot">
          <div class="price-box">
            <small>Mulai</small>
            <span class="price">${fmt(p.price)}</span>
          </div>
          <div class="card-actions">
            ${p.available
              ? `
              <button class="btn-opt" type="button" onclick="openCustomize('${esc(p.id)}')" title="Kustomisasi" aria-label="Kustomisasi ${esc(p.name)}">⚙</button>
              ${inCartQty > 0
                ? `
                <div class="card-stepper">
                  <button type="button" aria-label="Kurangi ${esc(p.name)}" onclick="decrementProduct('${esc(p.id)}')">−</button>
                  <span aria-label="Jumlah dalam keranjang ${inCartQty}">${inCartQty}</span>
                  <button type="button" aria-label="Tambah ${esc(p.name)}" onclick="quickAddProduct('${esc(p.id)}')">+</button>
                </div>
              `
                : `
                <button class="btn-add" type="button" onclick="quickAddProduct('${esc(p.id)}')" aria-label="Tambah ${esc(p.name)} ke pesanan">
                  <span>+ Pesan</span>
                </button>
              `
              }
            `
              : ""
            }
          </div>
        </div>
      </div>
    </article>`;
        })
        .join("");
    }

    // --- Cart Logic & Steppers ---
    function quickAddProduct(id) {
      const p = products.find((x) => x.id === id);
      if (!p || !p.available) return;

      const defaultOpts = (p.groups || []).map((g) => g.options[0].label);
      const key = p.id + "|" + defaultOpts.join("|");
      const found = cart.find((x) => x.id === p.id && (x.options || []).join("|") === defaultOpts.join("|") && !x.note);
      if (found) {
        found.qty++;
      } else {
        cart.unshift({ key: key + "|", id: p.id, name: p.name, price: p.price, qty: 1, options: defaultOpts, note: "" });
      }
      saveAll();
      updateCartUI();
      renderProducts();
      toast(`${p.name} ditambahkan ke pesanan.`);
    }

    function decrementProduct(id) {
      const idx = cart.findIndex((x) => x.id === id);
      if (idx >= 0) {
        cart[idx].qty--;
        if (cart[idx].qty < 1) cart.splice(idx, 1);
        saveAll();
        updateCartUI();
        renderProducts();
      }
    }

    function updateCartUI() {
      const count = cart.reduce((s, x) => s + x.qty, 0);
      const subtotal = cart.reduce((s, x) => s + x.price * x.qty, 0);
      const deliveryFee = service === "Antar" ? deliveryFeeConfigured : 0;
      const grandTotal = subtotal + deliveryFee;

      // Top header button
      const topCount = document.getElementById("topCartCount");
      if (count > 0) {
        topCount.textContent = count;
        topCount.style.display = "inline";
      } else {
        topCount.style.display = "none";
      }

      // Floating bottom bar
      const floatBar = document.getElementById("floatingCart");
      if (count > 0 && document.getElementById("viewPublic").style.display !== "none") {
        floatBar.style.display = "flex";
        document.getElementById("floatBadge").textContent = count;
        document.getElementById("floatSub").textContent = `${count} menu · Ketuk untuk checkout`;
        document.getElementById("floatTotal").textContent = fmt(grandTotal);
      } else {
        floatBar.style.display = "none";
      }

      // Drawer totals
      document.getElementById("cartDrawerSubtitle").textContent = `${count} menu di pesanan`;
      document.getElementById("cartSubtotal").textContent = fmt(subtotal);
      document.getElementById("cartGrandTotal").textContent = fmt(grandTotal);
      document.getElementById("btnCheckoutTotal").textContent = fmt(grandTotal);
      document.getElementById("deliveryRow").style.display = service === "Antar" ? "flex" : "none";
      document.getElementById("btnCheckoutWA").disabled = count === 0;
      document.getElementById("btnCopyOrder").disabled = count === 0;
      document.getElementById("btnClearCart").disabled = count === 0;

      // Items list in drawer
      const listEl = document.getElementById("cartItemsList");
      if (!cart.length) {
        listEl.innerHTML = `<div style="text-align:center;padding:30px 10px;color:var(--muted)">
      <b>Keranjang Masih Kosong</b>
      <p style="font-size:12px;margin-top:4px">Pilih teh segar favoritmu untuk mulai memesan.</p>
      <button class="btn-sec-action" type="button" onclick="closeCart(); document.getElementById('productGrid').scrollIntoView({behavior:'smooth',block:'start'})" style="margin:12px auto 0">Jelajahi Menu</button>
    </div>`;
      } else {
        listEl.innerHTML = cart
          .map(
            (item, idx) => `
      ${(() => { const product = products.find((p) => p.id === item.id); return `<div class="cart-line${product?.imageUrl ? " has-image" : ""}">${product?.imageUrl ? `<img class="cart-line-image" src="${esc(product.imageUrl)}" alt="${esc(item.name)}">` : ""}`; })()}
        <div>
          <div class="cart-line-title">${esc(item.name)}</div>
          <div class="cart-line-opts">${esc(item.options.join(" · "))}</div>
          ${item.note ? `<div class="cart-line-note">Catatan: "${esc(item.note)}"</div>` : ""}
          <div class="cart-line-actions">
            <div class="cart-stepper-row">
              <button class="btn-sm-step" aria-label="Kurangi ${esc(item.name)}" onclick="changeQty(${idx}, -1)">−</button>
              <span style="font-weight:700;font-size:12px;width:20px;text-align:center" aria-label="Jumlah ${item.qty}">${item.qty}</span>
              <button class="btn-sm-step" aria-label="Tambah ${esc(item.name)}" onclick="changeQty(${idx}, 1)">+</button>
            </div>
            <button class="btn-edit-line" type="button" onclick="editCartItem(${idx})">Ubah pilihan</button>
            <button class="btn-remove" type="button" onclick="removeCartItem(${idx})">Hapus</button>
          </div>
        </div>
        <div style="font-weight:700;font-size:13px;font-variant-numeric:tabular-nums">${fmt(item.price * item.qty)}</div>
      </div>
    `,
          )
          .join("");
      }
    }

    function editCartItem(index) {
      const item = cart[index];
      if (!item) return;
      openCustomize(item.id, index);
    }

    function changeQty(index, delta) {
      cart[index].qty += delta;
      if (cart[index].qty < 1) cart.splice(index, 1);
      saveAll();
      updateCartUI();
      renderProducts();
    }

    function removeCartItem(index) {
      const item = cart[index];
      if (!item) return;
      removingCartIndex = index;
      document.getElementById("removeCartItemText").textContent = `Hapus “${item.name}” dari keranjang?`;
      openLayer(document.getElementById("removeCartItemModal"), document.getElementById("btnCancelRemoveCartItem"));
    }

    function closeRemoveCartItem() {
      closeLayer(document.getElementById("removeCartItemModal"));
      removingCartIndex = null;
    }

    function confirmRemoveCartItem() {
      if (removingCartIndex == null || !cart[removingCartIndex]) return;
      const [item] = cart.splice(removingCartIndex, 1);
      saveAll();
      updateCartUI();
      renderProducts();
      closeRemoveCartItem();
      toast(`${item.name} dihapus dari pesanan.`);
    }

    document.getElementById("btnCloseRemoveCartItem").onclick = closeRemoveCartItem;
    document.getElementById("btnCancelRemoveCartItem").onclick = closeRemoveCartItem;
    document.getElementById("btnConfirmRemoveCartItem").onclick = confirmRemoveCartItem;
    document.getElementById("removeCartItemModal").onclick = (event) => {
      if (event.target.id === "removeCartItemModal") closeRemoveCartItem();
    };

    // --- Customization Modal ---
    function openCustomize(id, cartIndex = null) {
      const p = products.find((x) => x.id === id);
      if (!p || !p.available) return;

      currentCustomItem = p;
      editingCartIndex = cartIndex;
      const editingItem = cartIndex == null ? null : cart[cartIndex];
      currentCustomQty = editingItem ? editingItem.qty : 1;
      currentCustomOpts = {};
      (p.groups || []).forEach((g) => {
        const savedOption = (editingItem?.options || []).find((label) => g.options.some((option) => option.label === label));
        currentCustomOpts[g.name] = savedOption || g.options[0].label;
      });

      document.getElementById("modalDrinkTitle").textContent = p.name;
      document.getElementById("modalDrinkDesc").textContent = p.desc;
      const modalImage = document.getElementById("modalDrinkImage");
      if (modalImage) {
        modalImage.replaceChildren();
        if (p.imageUrl) {
          const image = document.createElement("img");
          image.src = p.imageUrl;
          image.alt = p.name;
          modalImage.append(image);
          modalImage.hidden = false;
        } else {
          modalImage.hidden = true;
        }
      }
      document.getElementById("modalQtyVal").textContent = currentCustomQty;
      document.getElementById("customItemNote").value = editingItem?.note || "";
      document.querySelector("#btnConfirmCustom span:first-child").textContent = editingItem ? "Simpan Perubahan" : "Tambah ke Pesanan";

      renderModalOptions();
      recalcModalPrice();

      openLayer(document.getElementById("customModal"), document.getElementById("btnCloseModal"));
    }

    function renderModalOptions() {
      const body = document.getElementById("modalOptionsBody");
      body.innerHTML = (currentCustomItem.groups || [])
        .map(
          (g) => `
    <div class="modal-group">
      <div class="group-title">
        <span>${esc(g.name)}</span>
        <span class="group-req">Wajib</span>
      </div>
      <div class="group-options">
        <div class="group-option-list" role="group" aria-label="${esc(g.name)}">
        ${g.options
              .map(
                (opt) => `
          <button type="button" class="btn-opt-card ${currentCustomOpts[g.name] === opt.label ? "selected" : ""}" aria-pressed="${currentCustomOpts[g.name] === opt.label}" onclick="pickCustomOpt('${esc(g.name)}','${esc(opt.label)}')">
            <span>${esc(opt.label)}</span>
            ${opt.price > 0 ? `<small>+${fmt(opt.price)}</small>` : ""}
          </button>
        `,
              )
              .join("")}
        </div>
      </div>
    </div>
  `,
        )
        .join("");
    }

    function pickCustomOpt(group, label) {
      currentCustomOpts[group] = label;
      renderModalOptions();
      recalcModalPrice();
    }

    function recalcModalPrice() {
      if (!currentCustomItem) return;
      let unit = currentCustomItem.price;
      (currentCustomItem.groups || []).forEach((g) => {
        const sel = currentCustomOpts[g.name];
        const found = g.options.find((o) => o.label === sel);
        if (found) unit += found.price;
      });
      const total = unit * currentCustomQty;
      document.getElementById("modalTotalPrice").textContent = fmt(total);
    }

    document.getElementById("modalQtyMinus").onclick = () => {
      if (currentCustomQty > 1) {
        currentCustomQty--;
        document.getElementById("modalQtyVal").textContent = currentCustomQty;
        recalcModalPrice();
      }
    };
    document.getElementById("modalQtyPlus").onclick = () => {
      currentCustomQty++;
      document.getElementById("modalQtyVal").textContent = currentCustomQty;
      recalcModalPrice();
    };

    document.getElementById("btnConfirmCustom").onclick = () => {
      if (!currentCustomItem) return;
      let unit = currentCustomItem.price;
      const optLabels = [];
      const itemNote = document.getElementById("customItemNote").value.trim();
      (currentCustomItem.groups || []).forEach((g) => {
        const sel = currentCustomOpts[g.name];
        optLabels.push(sel);
        const found = g.options.find((o) => o.label === sel);
        if (found) unit += found.price;
      });

      const key = currentCustomItem.id + "|" + optLabels.join("|") + "|" + itemNote;
      if (editingCartIndex != null && cart[editingCartIndex]) {
        cart[editingCartIndex] = {
          ...cart[editingCartIndex],
          key,
          id: currentCustomItem.id,
          name: currentCustomItem.name,
          price: unit,
          qty: currentCustomQty,
          options: optLabels,
          note: itemNote,
        };
      } else {
        const found = cart.find((x) =>
          x.id === currentCustomItem.id &&
          (x.options || []).join("|") === optLabels.join("|") &&
          (x.note || "") === itemNote,
        );
        if (found) {
          found.qty += currentCustomQty;
        } else {
          cart.unshift({
            key,
            id: currentCustomItem.id,
            name: currentCustomItem.name,
            price: unit,
            qty: currentCustomQty,
            options: optLabels,
            note: itemNote,
          });
        }
      }

      const itemName = currentCustomItem.name;
      const wasEditing = editingCartIndex != null;
      saveAll();
      updateCartUI();
      renderProducts();
      closeCustomize();
      toast(wasEditing ? `${itemName} berhasil diperbarui.` : `${itemName} ditambahkan ke pesanan.`);
    };

    function closeCustomize() {
      const m = document.getElementById("customModal");
      closeLayer(m);
      editingCartIndex = null;
    }

    document.getElementById("btnCloseModal").onclick = closeCustomize;
    document.getElementById("customModal").onclick = (e) => {
      if (e.target.id === "customModal") closeCustomize();
    };

    // --- Drawer Toggles ---
    function openCart() {
      openLayer(document.getElementById("cartDrawer"), document.getElementById("btnCloseCart"));
    }
    function closeCart() {
      closeLayer(document.getElementById("cartDrawer"));
    }
    document.getElementById("topCartBtn").onclick = openCart;
    document.getElementById("floatingCart").onclick = openCart;
    document.getElementById("btnCloseCart").onclick = closeCart;
    document.getElementById("cartBackdrop").onclick = closeCart;

    // --- Service Switcher (Ambil Sendiri / Antar) ---
    document.getElementById("srvPickup").onclick = () => setService("Ambil Sendiri");
    document.getElementById("srvDelivery").onclick = () => setService("Antar");
    function setService(srv) {
      service = srv;
      profile.srv = srv;
      document.getElementById("srvPickup").classList.toggle("active", srv === "Ambil Sendiri");
      document.getElementById("srvDelivery").classList.toggle("active", srv === "Antar");
      document.getElementById("srvPickup").setAttribute("aria-pressed", String(srv === "Ambil Sendiri"));
      document.getElementById("srvDelivery").setAttribute("aria-pressed", String(srv === "Antar"));
      document.getElementById("fieldAddress").style.display = srv === "Antar" ? "block" : "none";
      saveAll();
      updateCartUI();
    }

    function normalizeIndonesianPhone(value) {
      let digits = value.replace(/\D/g, "");
      if (digits.startsWith("62")) digits = "0" + digits.slice(2);
      else if (digits.startsWith("8")) digits = "0" + digits;
      return /^08\d{8,11}$/.test(digits) ? digits : null;
    }

    function showCheckoutFeedback(message) {
      document.getElementById("checkoutFeedback").textContent = message;
    }

    function clearPhoneError() {
      document.getElementById("fieldPhone").classList.remove("has-error");
      document.getElementById("phoneError").textContent = "";
      document.getElementById("inputPhone").removeAttribute("aria-invalid");
    }

    document.getElementById("inputPhone").addEventListener("input", () => {
      clearPhoneError();
      showCheckoutFeedback("");
    });

    ["inputName", "inputAddress"].forEach((id) => {
      document.getElementById(id).addEventListener("input", () => showCheckoutFeedback(""));
    });

    function populateProfile() {
      if (profile.name) document.getElementById("inputName").value = profile.name;
      if (profile.phone) document.getElementById("inputPhone").value = profile.phone;
      if (profile.address) document.getElementById("inputAddress").value = profile.address;
      setService(profile.srv || "Ambil Sendiri");
    }

    // --- Checkout to WhatsApp ---
    document.getElementById("btnCheckoutWA").onclick = async () => {
      if (!cart.length) {
        toast("Keranjang masih kosong!");
        return;
      }
      const name = document.getElementById("inputName").value.trim();
      const phone = document.getElementById("inputPhone").value.trim();
      const addr = document.getElementById("inputAddress").value.trim();
      const note = document.getElementById("inputNote").value.trim();

      if (!name) {
        showCheckoutFeedback("Isi nama pemesan terlebih dahulu.");
        document.getElementById("inputName").focus();
        return;
      }

      const normalizedPhone = normalizeIndonesianPhone(phone);
      if (!normalizedPhone) {
        document.getElementById("fieldPhone").classList.add("has-error");
        document.getElementById("phoneError").textContent = "Masukkan nomor WhatsApp Indonesia yang valid, contoh 0812 3456 7890.";
        document.getElementById("inputPhone").setAttribute("aria-invalid", "true");
        showCheckoutFeedback("");
        document.getElementById("inputPhone").focus();
        return;
      }

      if (service === "Antar" && !addr) {
        showCheckoutFeedback("Isi alamat lengkap agar pesanan antar memiliki tujuan yang jelas.");
        document.getElementById("inputAddress").focus();
        return;
      }
      clearPhoneError();
      showCheckoutFeedback("");

      // Save profile for future 1-click orders
      profile.name = name;
      profile.phone = normalizedPhone;
      profile.address = addr;
      profile.srv = service;
      saveAll();

      let savedOrder;
      try {
        const response = await fetch("/api/orders", { method:"POST", headers:{"Content-Type":"application/json","Accept":"application/json"}, body:JSON.stringify({name, phone:normalizedPhone, address:addr, service, note, items:cart.map(item => ({product_id:item.id, quantity:item.qty, options:Object.fromEntries((products.find(product => product.id === item.id)?.groups || []).map((group,index) => [group.name,item.options[index]]))}))}) });
        savedOrder = await response.json();
        if (!response.ok) throw new Error(savedOrder.message || "Pesanan gagal disimpan.");
      } catch (error) { showCheckoutFeedback(error.message || "Pesanan gagal disimpan. Coba lagi."); return; }
      const code = savedOrder.code;
      const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
      const delivery = savedOrder.total - subtotal;
      const total = savedOrder.total;

      // Format WhatsApp Message
      const itemsText = cart.map((it, i) => `${i + 1}. *${it.name}* (x${it.qty}) - ${fmt(it.price * it.qty)}\n   Varian: ${it.options.join(", ")}${it.note ? `\n   Catatan: ${it.note}` : ""}`).join("\n\n");
      const message = `*PESANAN BARU - RACIK TEA*
Kode: *#${code}*
Waktu: ${new Date().toLocaleString("id-ID")}
-------------------------------------
*Data Pemesan:*
Nama: *${name}*
No. HP: ${normalizedPhone}
Layanan: *${service}*
${service === "Antar" ? `Alamat Antar: ${addr}\n` : ""}${note ? `Catatan: "${note}"\n` : ""}-------------------------------------
*Rincian Menu:*
${itemsText}

-------------------------------------
*Subtotal: ${fmt(subtotal)}*
${service === "Antar" ? `Ongkos antar: ${fmt(delivery)}\n` : ""}*Total: ${fmt(total)}*

Pesanan tersimpan dengan kode ${code}. Terima kasih.`;

      closeCart();
      const targetPhone = savedOrder.whatsapp;
      if (!targetPhone) { showCheckoutFeedback("Nomor WhatsApp belum diatur oleh pengelola. Hubungi kedai untuk rincian pesanan."); return; }
      const url = `https://wa.me/${targetPhone}?text=${encodeURIComponent(message)}`;
      window.open(url, "_blank", "noopener,noreferrer");
      toast(`Pesanan ${code} tersimpan.`);
    };

    // Copy format
    document.getElementById("btnCopyOrder").onclick = async () => {
      if (!cart.length) return;
      const name = document.getElementById("inputName").value.trim() || "Pelanggan";
      const phone = document.getElementById("inputPhone").value.trim();
      const addr = document.getElementById("inputAddress").value.trim();
      const note = document.getElementById("inputNote").value.trim();
      const subtotal = cart.reduce((s, x) => s + x.price * x.qty, 0);
      const delivery = service === "Antar" ? deliveryFeeConfigured : 0;
      const items = cart.map((item, i) =>
        `${i + 1}. ${item.qty}x ${item.name} (${item.options.join(", ")}) — ${fmt(item.price * item.qty)}${item.note ? `\n   Catatan: ${item.note}` : ""}`,
      ).join("\n");
      const text = `PESANAN RACIK TEA\nPemesan: ${name}\n${phone ? `WhatsApp: ${phone}\n` : ""}Layanan: ${service}${service === "Antar" && addr ? `\nAlamat: ${addr}` : ""}\n\n${items}\n\nSubtotal: ${fmt(subtotal)}\n${service === "Antar" ? `Ongkos antar: ${fmt(delivery)}\n` : ""}Total: ${fmt(subtotal + delivery)}${note ? `\nCatatan umum: ${note}` : ""}`;
      try {
        if (navigator.clipboard?.writeText) {
          try {
            await navigator.clipboard.writeText(text);
          } catch {
            if (!copyTextFallback(text)) throw new Error("Clipboard API and fallback both failed.");
          }
        } else if (!copyTextFallback(text)) {
          throw new Error("Clipboard copy was rejected.");
        }
        toast("Rincian pesanan disalin.");
      } catch (error) {
        showCheckoutFeedback("Tidak dapat menyalin otomatis. Rincian pesanan tetap terlihat di keranjang untuk disalin manual.");
      }
    };

    function copyTextFallback(text) {
      const copyField = document.createElement("textarea");
      copyField.value = text;
      copyField.setAttribute("readonly", "");
      copyField.style.position = "fixed";
      copyField.style.opacity = "0";
      document.body.append(copyField);
      try {
        copyField.select();
        return document.execCommand("copy");
      } finally {
        copyField.remove();
      }
    }

    document.getElementById("btnClearCart").onclick = () => {
      openLayer(document.getElementById("clearCartModal"), document.getElementById("btnCancelClearCart"));
    };
    function closeClearCart() {
      closeLayer(document.getElementById("clearCartModal"));
    }
    document.getElementById("btnCloseClearCart").onclick = closeClearCart;
    document.getElementById("btnCancelClearCart").onclick = closeClearCart;
    document.getElementById("clearCartModal").onclick = (event) => {
      if (event.target.id === "clearCartModal") closeClearCart();
    };
    document.getElementById("btnConfirmClearCart").onclick = () => {
      cart = [];
      saveAll();
      updateCartUI();
      renderProducts();
      closeClearCart();
      toast("Keranjang dikosongkan.");
    };

    // --- Admin Dashboard View ---
    document.getElementById("btnToggleAdmin").onclick = () => { window.location.href = "/admin/login"; };

    document.getElementById("btnAdminBack").onclick = () => {
      document.getElementById("viewAdmin").style.display = "none";
      document.getElementById("viewPublic").style.display = "block";
      closeMenuEditor();
      closeDeleteMenu();
      updateCartUI();
    };

    const DEFAULT_MENU_GROUPS = [
      {
        name: "Ukuran",
        options: [
          { label: "Regular (350ml)", price: 0 },
          { label: "Large (500ml)", price: 5000 },
        ],
      },
      {
        name: "Tingkat Manis",
        options: [
          { label: "Normal Manis", price: 0 },
          { label: "Less Sugar (50%)", price: 0 },
          { label: "Tanpa Gula", price: 0 },
        ],
      },
      {
        name: "Penyajian",
        options: [
          { label: "Dingin dengan Es", price: 0 },
          { label: "Hangat Poci", price: 0 },
        ],
      },
    ];

    let editingMenuIndex = null;
    let deletingMenuIndex = null;
    let editorStockAvailable = true;

    function uniqueMenuId(name) {
      const base = String(name)
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");
      let id = base || "menu-" + Date.now();
      let n = 2;
      while (products.some((p) => p.id === id)) {
        id = (base || "menu") + "-" + n++;
      }
      return id;
    }

    function fillCategoryList() {
      const cats = [...new Set(products.map((p) => p.category))];
      document.getElementById("menuCategoryList").innerHTML = cats
        .map((c) => `<option value="${esc(c)}"></option>`)
        .join("");
    }

    function setEditorStock(available) {
      editorStockAvailable = available;
      document.getElementById("menuStockAvail").classList.toggle("active", available);
      document.getElementById("menuStockOut").classList.toggle("active", !available);
    }

    function readMenuForm() {
      return {
        name: document.getElementById("menuName").value.trim(),
        category: document.getElementById("menuCategory").value.trim(),
        price: parseInt(document.getElementById("menuPrice").value, 10),
        desc: document.getElementById("menuDesc").value.trim(),
        icon: document.getElementById("menuIcon").value,
        tone: document.getElementById("menuTone").value,
        label: document.getElementById("menuLabel").value.trim(),
      };
    }

    function openMenuEditor(mode, index) {
      fillCategoryList();
      const modal = document.getElementById("menuEditorModal");
      const form = document.getElementById("formMenuEditor");
      form.reset();
      editingMenuIndex = mode === "edit" ? index : null;
      document.getElementById("menuStockField").style.display = mode === "edit" ? "block" : "none";

      if (mode === "edit") {
        const p = products[index];
        document.getElementById("menuEditorTitle").textContent = "Edit Menu";
        document.getElementById("menuEditorSubtitle").textContent = "Perubahan langsung terlihat di katalog pelanggan.";
        document.getElementById("btnSaveMenuEditor").textContent = "Simpan Perubahan";
        document.getElementById("menuName").value = p.name;
        document.getElementById("menuCategory").value = p.category;
        document.getElementById("menuPrice").value = p.price;
        document.getElementById("menuDesc").value = p.desc || "";
        const iconSelect = document.getElementById("menuIcon");
        if (p.icon && ![...iconSelect.options].some((o) => o.value === p.icon)) {
          iconSelect.insertAdjacentHTML("beforeend", `<option value="${esc(p.icon)}">${esc(p.icon)} Lainnya</option>`);
        }
        iconSelect.value = p.icon || "✿";
        document.getElementById("menuTone").value = p.tone || "green";
        document.getElementById("menuLabel").value = (p.labels && p.labels[0]) || "";
        setEditorStock(p.available !== false);
      } else {
        document.getElementById("menuEditorTitle").textContent = "Tambah Menu Baru";
        document.getElementById("menuEditorSubtitle").textContent = "Menu baru langsung tampil di katalog pelanggan.";
        document.getElementById("btnSaveMenuEditor").textContent = "Simpan Menu";
        setEditorStock(true);
      }

      openLayer(modal, document.getElementById("menuName"));
    }

    function closeMenuEditor() {
      const modal = document.getElementById("menuEditorModal");
      closeLayer(modal);
      editingMenuIndex = null;
    }

    function openDeleteMenu(index) {
      deletingMenuIndex = index;
      const p = products[index];
      document.getElementById("deleteMenuText").textContent =
        `Hapus “${p.name}” dari katalog? Menu yang sudah ada di keranjang juga akan dibersihkan.`;
      const modal = document.getElementById("deleteMenuModal");
      openLayer(modal, document.getElementById("btnCancelDeleteMenu"));
    }

    function closeDeleteMenu() {
      const modal = document.getElementById("deleteMenuModal");
      closeLayer(modal);
      deletingMenuIndex = null;
    }

    function confirmDeleteMenu() {
      if (deletingMenuIndex == null) return;
      const p = products[deletingMenuIndex];
      const removedId = p.id;
      const name = p.name;
      products.splice(deletingMenuIndex, 1);
      cart = cart.filter((item) => item.id !== removedId);
      saveAll();
      closeDeleteMenu();
      renderAdmin();
      renderProducts();
      updateCartUI();
      toast(`${name} dihapus dari menu.`);
    }

    document.getElementById("btnAddMenu").onclick = () => openMenuEditor("add");
    document.getElementById("btnCloseMenuEditor").onclick = closeMenuEditor;
    document.getElementById("btnCancelMenuEditor").onclick = closeMenuEditor;
    document.getElementById("menuEditorModal").onclick = (e) => {
      if (e.target.id === "menuEditorModal") closeMenuEditor();
    };
    document.getElementById("menuStockAvail").onclick = () => setEditorStock(true);
    document.getElementById("menuStockOut").onclick = () => setEditorStock(false);

    document.getElementById("btnCloseDeleteMenu").onclick = closeDeleteMenu;
    document.getElementById("btnCancelDeleteMenu").onclick = closeDeleteMenu;
    document.getElementById("btnConfirmDeleteMenu").onclick = confirmDeleteMenu;
    document.getElementById("deleteMenuModal").onclick = (e) => {
      if (e.target.id === "deleteMenuModal") closeDeleteMenu();
    };

    document.getElementById("formMenuEditor").onsubmit = (e) => {
      e.preventDefault();
      const data = readMenuForm();
      if (!data.name || !data.category || isNaN(data.price) || data.price < 0) {
        toast("Lengkapi nama, kategori, dan harga menu.");
        return;
      }

      if (editingMenuIndex != null) {
        const p = products[editingMenuIndex];
        const oldId = p.id;
        p.name = data.name;
        p.category = data.category;
        p.price = data.price;
        p.desc = data.desc || "Racikan teh khas Racik Tea.";
        p.icon = data.icon;
        p.tone = data.tone;
        p.labels = data.label ? [data.label] : [];
        p.available = editorStockAvailable;
        cart.forEach((item) => {
          if (item.id === oldId) item.name = p.name;
        });
        saveAll();
        renderAdmin();
        renderProducts();
        updateCartUI();
        closeMenuEditor();
        toast(`${p.name} berhasil diperbarui.`);
        return;
      }

      products.push({
        id: uniqueMenuId(data.name),
        name: data.name,
        category: data.category,
        desc: data.desc || "Racikan teh khas Racik Tea.",
        price: data.price,
        available: true,
        tone: data.tone,
        icon: data.icon,
        labels: data.label ? [data.label] : ["Baru"],
        groups: JSON.parse(JSON.stringify(DEFAULT_MENU_GROUPS)),
      });
      saveAll();
      renderAdmin();
      renderProducts();
      closeMenuEditor();
      toast(`${data.name} ditambahkan ke daftar menu.`);
    };

    function renderAdmin() {
      const avail = products.filter((p) => p.available).length;
      document.getElementById("statTotalOrders").textContent = orders.length;
      document.getElementById("statAvailMenu").textContent = avail;
      document.getElementById("statSoldOutMenu").textContent = products.length - avail;
      const rev = orders.reduce((s, x) => s + x.total, 0);
      document.getElementById("statRevenue").textContent = fmt(rev);

      // Menu table
      const tbody = document.getElementById("adminTableBody");
      tbody.innerHTML = products
        .map(
          (p, idx) => `
    <tr>
      <td>
        <div class="admin-menu-cell">
          <span class="admin-menu-art">${esc(p.icon || "✿")}</span>
          <div>
            <b>${esc(p.name)}</b>
            <small>${esc(p.desc || "")}</small>
          </div>
        </div>
      </td>
      <td>${esc(p.category)}</td>
      <td style="font-variant-numeric:tabular-nums">${fmt(p.price)}</td>
      <td>
        <span class="badge-status ${p.available ? "badge-avail" : "badge-out"}">
          ${p.available ? "● Tersedia" : "✕ Habis"}
        </span>
      </td>
      <td>
        <div class="admin-actions">
          <button class="btn-table btn-table-edit" type="button" onclick="openMenuEditor('edit', ${idx})">Edit</button>
          <button class="btn-table btn-table-stock" type="button" onclick="toggleStock(${idx})">${p.available ? "Tandai Habis" : "Aktifkan"}</button>
          <button class="btn-table btn-table-delete" type="button" onclick="openDeleteMenu(${idx})">Hapus</button>
        </div>
      </td>
    </tr>
  `,
        )
        .join("");

      // Orders table
      const orderBody = document.getElementById("adminOrdersBody");
      if (!orders.length) {
        orderBody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">Belum ada pesanan.</td></tr>`;
      } else {
        orderBody.innerHTML = orders
          .map(
            (o) => `
      <tr>
        <td><b>#${esc(o.code)}</b></td>
        <td>${esc(o.name)}<br><small style="color:var(--muted)">${esc(o.phone)}</small></td>
        <td>${esc(o.service)}</td>
        <td>${(o.items || []).map((i) => `<div>${i.qty}x ${esc(i.name)}${i.options?.length ? `<small style="display:block;color:var(--muted)">${esc(i.options.join(" · "))}</small>` : ""}${i.note ? `<small style="display:block;color:var(--muted)">Catatan: ${esc(i.note)}</small>` : ""}</div>`).join("")}${o.address ? `<small style="display:block;color:var(--muted);margin-top:4px">Alamat: ${esc(o.address)}</small>` : ""}${o.note ? `<small style="display:block;color:var(--muted);margin-top:4px">Catatan umum: ${esc(o.note)}</small>` : ""}</td>
        <td style="font-weight:700">${fmt(o.total)}</td>
        <td><span class="badge-status badge-avail">${esc(o.status)}</span></td>
      </tr>
    `,
          )
          .join("");
      }
    }

    function toggleStock(index) {
      products[index].available = !products[index].available;
      saveAll();
      renderAdmin();
      renderProducts();
      toast(`${products[index].name} diperbarui.`);
    }

    window.openMenuEditor = openMenuEditor;
    window.openDeleteMenu = openDeleteMenu;

    // Search listener
    document.getElementById("searchInput").addEventListener("input", (e) => {
      searchQuery = e.target.value;
      renderProducts();
    });

    // Toast notification helper
    function toast(msg) {
      const root = document.getElementById("toastRoot");
      root.innerHTML = `<div class="toast"><span>✿ ${esc(msg)}</span></div>`;
      setTimeout(() => {
        if (root.firstChild) root.firstChild.remove();
      }, 3000);
    }

    function esc(s) {
      return String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
    }

    // Kickstart
    init();
  