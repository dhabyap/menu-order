    <header class="top">
        <a class="brand" href="/" aria-label="Racik Tea, kembali ke katalog">
            <span class="mark">R</span>
            <span class="brand-text">Racik Tea<small>Artisan &amp; Seduh Segar</small></span>
        </a>
        <div class="top-actions">
            <a class="top-link" href="/">← <span>Lihat katalog</span></a>
            <form method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button class="logout-button" type="submit">Keluar</button>
            </form>
        </div>
    </header>
