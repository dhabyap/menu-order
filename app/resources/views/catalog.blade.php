<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width,initial-scale=1,viewport-fit=cover" />
  <title>Racik Tea — Menu & Pesanan Cepat</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
    rel="stylesheet" />
    <link rel="stylesheet" href="assets/catalog/catalog.css" />

</head>

<body>
  <div class="shell">
    <!-- Top Navigation Bar -->
    <header class="top" id="top">
      <div class="brand">
        <span class="mark" id="brandMark">R</span>
        <div class="brand-text">
          <span id="brandTitle">Racik Tea</span>
          <small id="brandTagline"></small>
        </div>
      </div>
      <nav class="site-nav" aria-label="Navigasi utama">
        @auth
          <a href="/admin">Dashboard Admin</a>
        @endauth
        <a href="#productGrid">Menu</a>
        <a href="#orderGuide">Cara pesan</a>
        <a href="/track" id="navOrderHistory">Cek Pesanan</a>
        <a href="#storeFooter">Kontak</a>
      </nav>
      <div class="top-actions">
        <span class="badge-open" id="statusBadge">Pesan online</span>
        <button class="btn-cart-top" id="topCartBtn">
          <span>Keranjang</span>
          <span id="topCartCount"
            style="background: rgba(255, 255, 255, 0.25); padding: 1px 6px; border-radius: 6px; font-size: 10px; display: none">0</span>
        </button>
      </div>
    </header>

    <!-- Public Customer View -->
    <main id="viewPublic">
      <!-- Hero Section -->
      <section class="hero">
        <div>
          <div class="eyebrow" id="heroEyebrow">Menu Racik Tea</div>
          <h1 id="heroHeading">Pilih minuman favoritmu.</h1>
          <p id="heroDescription">Telusuri menu yang tersedia dan sesuaikan racikan sebelum memesan.</p>
          <div class="hero-actions">
            <a class="hero-action-primary" href="#productGrid">Lihat menu <span aria-hidden="true">↓</span></a>
            <a class="hero-action-secondary" href="#orderGuide">Cara pesan</a>
          </div>
          <div class="hero-badges" id="heroBadges" hidden>
          </div>
        </div>
        <div class="promo-box">
          <b>Pesan dengan mudah</b>
          <span>Pilih menu yang tersedia dan sesuaikan racikannya. Pesanan disimpan sebelum diteruskan ke WhatsApp.</span>
        </div>
      </section>


      <!-- Catalog Sticky Tools -->
      <section class="catalog-tools">
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Cari teh, melati, lemon, manis..." aria-label="Cari menu" />
        </div>
        <nav class="category-nav" id="categoryNav" aria-label="Kategori Menu"></nav>
      </section>

      <!-- Products Grid -->
      <div class="products" id="productGrid"></div>

      <section class="order-guide" id="orderGuide" aria-labelledby="orderGuideTitle">
        <div class="section-heading">
          <span class="eyebrow">Mudah dan praktis</span>
          <h2 id="orderGuideTitle">Cara pesan</h2>
          <p>Siapkan pesananmu dalam beberapa langkah.</p>
        </div>
        <ol class="order-steps">
          <li><span class="step-number">01</span><div><strong>Pilih menu</strong><p>Jelajahi menu dan pilih minuman yang kamu suka.</p></div></li>
          <li><span class="step-number">02</span><div><strong>Atur racikan</strong><p>Pilih opsi yang tersedia, jumlah, dan tambahkan catatan bila perlu.</p></div></li>
          <li><span class="step-number">03</span><div><strong>Tinjau pesanan</strong><p>Periksa keranjang, lalu teruskan pesanan melalui WhatsApp.</p></div></li>
        </ol>
      </section>

      <footer class="site-footer" id="storeFooter">
      <a class="footer-brand" href="#top" aria-label="Kembali ke atas">
        <span class="footer-mark" id="footerMark">R</span>
        <span><strong id="footerStoreName">Racik Tea</strong><small id="footerStoreTagline">Menu dan pemesanan</small></span>
      </a>
      <div class="footer-links"><a href="#productGrid">Menu</a><a href="#orderGuide">Cara pesan</a><button type="button" id="footerOrderHistory">Cek pesanan</button></div>
      <p class="footer-copy">&copy; <span id="footerYear"></span> <span id="footerCopyrightName">Racik Tea</span></p>
      </footer>
    </main>

  </div>

  <!-- Floating Bottom Cart Bar -->
  <button class="float-cart" id="floatingCart" type="button" aria-label="Buka keranjang pesanan" style="display: none">
    <div class="float-info">
      <div class="cart-badge" id="floatBadge">0</div>
      <div>
        <span class="float-title">Pesanan Kamu</span>
        <span class="float-sub" id="floatSub">0 menu siap diracik</span>
      </div>
    </div>
    <div class="float-right">
      <span class="float-total" id="floatTotal">Rp0</span>
      <span>→</span>
    </div>
  </button>

  <!-- Cart Drawer / Bottom Sheet -->
  <div class="drawer-backdrop" id="cartBackdrop"></div>
  <aside class="drawer" id="cartDrawer" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cartDrawerTitle" inert>
    <div class="drawer-head">
      <div>
        <h2 id="cartDrawerTitle">Pesanan Anda</h2>
        <span style="font-size: 11px; color: var(--muted)" id="cartDrawerSubtitle">0 menu</span>
      </div>
      <button class="btn-close" id="btnCloseCart" aria-label="Tutup keranjang">×</button>
    </div>

    <div class="drawer-body">
      <div id="cartItemsList"></div>

      <div
        style="background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 14px; margin-top: 14px">
        <span style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px">Data Pemesan</span>
        <span class="field-hint" style="margin-bottom: 10px">Tersimpan hanya di browser/perangkat ini.</span>

        <div class="field">
          <label>Metode Pengambilan</label>
          <div class="service-toggle">
            <button class="btn-srv active" type="button" data-srv="Ambil Sendiri" id="srvPickup" aria-pressed="true">Ambil di Kedai</button>
            <button class="btn-srv" type="button" data-srv="Antar" id="srvDelivery" aria-pressed="false">Antar</button>
          </div>
        </div>

        <div class="field">
          <label for="inputName">Nama Kamu *</label>
          <input type="text" id="inputName" autocomplete="name" maxlength="80" placeholder="Contoh: Rian" />
        </div>

        <div class="field" id="fieldPhone">
          <label for="inputPhone">Nomor WhatsApp *</label>
          <input type="tel" id="inputPhone" autocomplete="tel" inputmode="tel" aria-describedby="phoneError" placeholder="Contoh: 0812 3456 7890" />
          <span class="field-error" id="phoneError" aria-live="polite"></span>
        </div>

        <div class="field" id="fieldAddress" style="display: none">
          <label for="inputAddress">Alamat Pengantaran *</label>
          <textarea id="inputAddress" autocomplete="street-address" maxlength="1000" rows="2" placeholder="Nama jalan, nomor rumah, patokan..."></textarea>
          <button class="btn-sec-action" type="button" id="btnCalculateDelivery" hidden>Hitung ongkir</button>
          <p class="field-hint">Alamat digunakan untuk mencari rute dan menghitung estimasi ongkir.</p>

          <div id="deliveryQuoteCard" class="delivery-quote-card" hidden aria-live="polite">
            <div class="dqc-head">
              <span class="dqc-icon">📍</span>
              <div class="dqc-main">
                <div class="dqc-label">Tujuan pengantaran</div>
                <div class="dqc-dest" id="deliveryQuoteDest">—</div>
              </div>
            </div>
            <div class="dqc-rows">
              <div class="dqc-row">
                <span class="dqc-key">Estimasi rute</span>
                <span class="dqc-val" id="deliveryQuoteDist">—</span>
              </div>
              <div class="dqc-row dqc-row-fee">
                <span class="dqc-key">Ongkos kirim</span>
                <span class="dqc-val dqc-fee" id="deliveryQuoteFee">Rp0</span>
              </div>
            </div>
            <label class="dqc-confirm" id="deliveryQuoteConfirmWrap">
              <input type="checkbox" id="deliveryQuoteConfirm">
              <span>Konfirmasi alamat sudah benar</span>
            </label>
          </div>

          <p id="deliveryQuoteFeedback" class="checkout-hint" role="status" aria-live="polite"></p>
        </div>

        <div class="field">
          <label for="inputNote">Catatan Tambahan (Opsional)</label>
          <input type="text" id="inputNote" placeholder="Contoh: es dipisah, jangan terlalu manis" />
        </div>
      </div>
    </div>

    <div class="drawer-foot">
      <div
        style="display: flex; justify-content: space-between; font-size: 12px; color: var(--muted); margin-bottom: 6px">
        <span>Subtotal</span>
        <span id="cartSubtotal" style="font-weight: 600; font-variant-numeric: tabular-nums">Rp0</span>
      </div>
      <div id="deliveryRow"
        style="display: none; justify-content: space-between; font-size: 12px; color: var(--muted); margin-bottom: 6px">
        <span>Ongkos Kirim <small id="deliveryDistanceLabel"></small></span>
        <span style="font-weight: 600; font-variant-numeric: tabular-nums">Rp0</span>
      </div>
      <div
        style="display: flex; justify-content: space-between; font-size: 15px; font-weight: 700; color: var(--ink); margin-bottom: 14px">
        <span>Total Bayar</span>
        <span id="cartGrandTotal" style="color: var(--green); font-variant-numeric: tabular-nums">Rp0</span>
      </div>

      <p id="checkoutFeedback" class="checkout-hint" role="status" aria-live="polite" style="margin: -6px 0 8px"></p>
      <button class="btn-primary-action" id="btnCheckoutWA">
        <span>Kirim Pesanan via WhatsApp</span>
        <span id="btnCheckoutTotal">Rp0</span>
      </button>
      <p class="checkout-hint" id="checkoutHint">Pesanan akan disimpan terlebih dahulu, lalu WhatsApp terbuka untuk mengirim rincian ke kedai.</p>
      <div class="checkout-sub-actions">
        <button class="btn-sec-action" id="btnCopyOrder">Salin Format Teks</button>
        <button class="btn-sec-action" id="btnClearCart" style="color: var(--rose)">Kosongkan</button>
      </div>
    </div>
  </aside>

  <!-- Order History Drawer / Bottom Sheet -->
  <div class="drawer-backdrop" id="historyBackdrop"></div>
  <aside class="drawer" id="historyDrawer" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="historyDrawerTitle" inert>
    <div class="drawer-head">
      <div>
        <h2 id="historyDrawerTitle">Cek Pesanan</h2>
        <span style="font-size: 11px; color: var(--muted)" id="historyDrawerSubtitle">Riwayat pesanan kamu</span>
      </div>
      <button class="btn-close" id="btnCloseHistory" type="button" aria-label="Tutup cek pesanan">&times;</button>
    </div>

    <div class="drawer-body">
      <div class="history-search">
        <div class="field" style="margin-bottom: 10px">
          <label for="historyPhone">Nomor WhatsApp</label>
          <input type="tel" id="historyPhone" autocomplete="tel" inputmode="tel" placeholder="Contoh: 0812 3456 7890" />
        </div>
        <div class="history-search-actions">
          <button class="btn-primary-action" id="btnFindHistory" type="button">
            <span>Cari Pesanan</span>
          </button>
          <button class="btn-sec-action" id="historyRefresh" type="button">Muat Ulang</button>
        </div>
        <p class="field-hint" style="margin: 10px 0 0">Pesanan ditampilkan berdasarkan nomor WhatsApp yang dipakai saat memesan.</p>
      </div>

      <p class="history-hint" id="historyHint" role="status" aria-live="polite"></p>
      <div id="historyList"></div>
    </div>
  </aside>

  <!-- Customize Drink Modal -->
  <div class="modal-wrap" id="customModal" aria-hidden="true" inert>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalDrinkTitle" aria-describedby="modalDrinkDesc">
      <div class="modal-drink-image" id="modalDrinkImage" hidden></div>
      <div class="modal-head">
        <div>
          <h3 id="modalDrinkTitle">Nama Minuman</h3>
          <p id="modalDrinkDesc">Deskripsi seduhan teh</p>
        </div>
        <button class="btn-close" id="btnCloseModal" type="button" aria-label="Tutup penyesuaian menu">×</button>
      </div>
      <div class="modal-scroll" id="modalOptionsBody"></div>
      <div class="modal-note">
        <div class="field" style="margin-bottom: 0">
          <label for="customItemNote">Catatan untuk minuman ini (opsional)</label>
          <textarea id="customItemNote" rows="2" maxlength="120" placeholder="Contoh: es dipisah, jangan pakai sedotan"></textarea>
        </div>
      </div>
      <div class="modal-foot">
        <div class="modal-stepper">
          <button type="button" id="modalQtyMinus" aria-label="Kurangi jumlah">−</button>
          <span id="modalQtyVal" aria-live="polite">1</span>
          <button type="button" id="modalQtyPlus" aria-label="Tambah jumlah">+</button>
        </div>
        <button class="btn-primary-action" id="btnConfirmCustom" style="flex: 1">
          <span>Tambah ke Pesanan</span>
          <span id="modalTotalPrice">Rp0</span>
        </button>
      </div>
    </div>
  </div>

  <div class="modal-wrap" id="clearCartModal" aria-hidden="true" inert>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="clearCartTitle" aria-describedby="clearCartText" style="width: min(400px, 100%)">
      <div class="modal-head">
        <div>
          <h3 id="clearCartTitle">Kosongkan Keranjang?</h3>
          <p id="clearCartText">Semua menu dan jumlah pesanan di keranjang akan dihapus.</p>
        </div>
        <button class="btn-close" id="btnCloseClearCart" type="button" aria-label="Tutup konfirmasi kosongkan keranjang">×</button>
      </div>
      <div class="modal-foot">
        <button class="btn-sec-action" id="btnCancelClearCart" type="button">Pertahankan Pesanan</button>
        <button class="btn-danger-action" id="btnConfirmClearCart" type="button">Kosongkan</button>
      </div>
    </div>
  </div>

  <div class="modal-wrap" id="removeCartItemModal" aria-hidden="true" inert>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="removeCartItemTitle" aria-describedby="removeCartItemText" style="width: min(400px, 100%)">
      <div class="modal-head">
        <div>
          <h3 id="removeCartItemTitle">Hapus Menu dari Pesanan?</h3>
          <p id="removeCartItemText">Menu ini akan dihapus dari keranjang.</p>
        </div>
        <button class="btn-close" id="btnCloseRemoveCartItem" type="button" aria-label="Tutup konfirmasi hapus menu">×</button>
      </div>
      <div class="modal-foot">
        <button class="btn-sec-action" id="btnCancelRemoveCartItem" type="button">Batal</button>
        <button class="btn-danger-action" id="btnConfirmRemoveCartItem" type="button">Ya, Hapus</button>
      </div>
    </div>
  </div>

  <div id="toastRoot" aria-live="polite" aria-atomic="true"></div>

    <script src="assets/catalog/catalog.js"></script>

</body>

</html>
