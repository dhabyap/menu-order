        <div class="intro">
            <div>
                <div class="eyebrow"><span>✿</span> Panel pengelola</div>
                <h1>Dashboard Pengelola</h1>
                <p>Halo, {{ auth()->user()->name }}. Kelola menu, pesanan, dan informasi kedai.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="notice" role="status">{{ session('success') }}</div>
        @endif
        @if($errors->any() && !session('success'))
            <div class="notice error" role="alert">
                <strong>Periksa kembali informasi berikut:</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

