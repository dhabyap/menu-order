<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>Masuk Pengelola · Racik Tea</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/login.css') }}">
</head>
<body>
    <header class="page-top">
        <a class="brand" href="/" aria-label="Racik Tea, kembali ke katalog">
            <span class="mark">R</span>
            <span>Racik Tea<small>Artisan &amp; Seduh Segar</small></span>
        </a>
        <a class="back-link" href="/"><span aria-hidden="true">← </span>Kembali ke katalog</a>
    </header>

    <main class="login-area">
        <div class="login-layout">
            <section class="welcome-panel" aria-label="Selamat datang di Racik Tea">
                <div class="welcome-kicker"><span>✿</span> Ruang pengelola</div>
                <div class="welcome-copy">
                    <h1>Racik kedai, sepenuh hati.</h1>
                    <p>Masuk untuk mengelola menu, meninjau pesanan, dan memperbarui informasi kedai Racik Tea.</p>
                </div>
                <div class="welcome-note"><span>✓</span> Dashboard khusus pengelola kedai</div>
            </section>

            <section class="form-panel" aria-labelledby="login-title">
                <div class="eyebrow">Selamat datang kembali</div>
                <h2 id="login-title">Masuk Pengelola</h2>
                <p class="form-intro">Gunakan akun pengelola untuk melanjutkan.</p>
                @if($errors->any())
                    <div class="error-message" role="alert"><span>!</span><div>{{ $errors->first('email') }}</div></div>
                @endif
                <form method="post" action="{{ route('admin.login.post') }}">
                    @csrf
                    <div class="field">
                        <label for="email">Email</label>
                        <div class="input-wrap"><span class="input-icon" aria-hidden="true">✉</span><input id="email" type="email" name="email" required autofocus autocomplete="username" value="{{ old('email') }}" placeholder="nama@kedai.com"></div>
                    </div>
                    <div class="field">
                        <label for="password">Kata sandi</label>
                        <div class="input-wrap"><span class="input-icon" aria-hidden="true">⌑</span><input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi"></div>
                    </div>
                    <label class="remember"><input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini</label>
                    <button class="submit-button" type="submit">Masuk ke Dashboard <span aria-hidden="true">→</span></button>
                </form>
                <p class="secure-note"><span aria-hidden="true">◈</span> Halaman ini hanya dapat diakses oleh pengelola.</p>
            </section>
        </div>
    </main>
    <footer class="page-bottom">Racik Tea · Diracik Perlahan, Diseduh Segar</footer>
</body>
</html>
