        <div class="intro">
            <div>
                <div class="eyebrow"><span>✿</span> Panel pengelola</div>
                <h1>Dashboard Pengelola</h1>
                <p>Halo! Kelola menu, pesanan, dan informasi kedai {{ $storeName }}.</p>
            </div>
        </div>

        @if(session('success'))
            <div id="flash-success" data-message="{{ session('success') }}" hidden></div>
        @endif
        @if($errors->any() && !session('success'))
            <div class="notice error" id="flash-errors" role="alert">
                <strong>Periksa kembali informasi berikut:</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

