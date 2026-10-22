    <header class="top">
        <a class="brand" href="/" aria-label="{{ $storeName }}, kembali ke katalog">
            @if($logoUrl)<img class="mark brand-logo" src="{{ $logoUrl }}" alt="Logo {{ $storeName }}">@else<span class="mark">{{ mb_substr($storeName, 0, 1) }}</span>@endif
            <span class="brand-text">{{ $storeName }}<small>{{ $storeTagline }}</small></span>
        </a>
        <div class="top-actions">
            <a class="top-link" href="/">← <span>Lihat katalog</span></a>
            <form method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button class="logout-button" type="submit">Keluar</button>
            </form>
        </div>
    </header>
