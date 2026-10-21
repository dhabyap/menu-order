<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>Masuk Pengelola · Racik Tea</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --paper: #f7f5f0;
            --surface: #fffefa;
            --ink: #1c221a;
            --muted: #73796d;
            --line: #e8e4db;
            --green: #2e4334;
            --green-hover: #233528;
            --green-light: #e9efe8;
            --plum: #6f4b63;
            --rose: #a8564a;
            --rose-light: #f7ebe8;
            --shadow: 0 24px 70px rgba(28, 34, 26, .12);
        }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; padding: 22px; display: grid; grid-template-rows: auto 1fr auto; background: var(--paper); color: var(--ink); font: 14px/1.5 "Plus Jakarta Sans", system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        a { color: inherit; }
        button, input { font: inherit; }
        :focus-visible { outline: 3px solid rgba(46, 67, 52, .45); outline-offset: 3px; }
        .page-top, .page-bottom { width: min(100%, 1120px); margin: 0 auto; }
        .page-top { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; font-size: 20px; font-weight: 800; letter-spacing: -.5px; }
        .mark { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 50%; background: var(--green); color: #fff; font: 700 19px "Fraunces", Georgia, serif; box-shadow: 0 3px 10px rgba(46, 67, 52, .2); }
        .brand small { display: block; margin-top: -3px; color: var(--muted); font-size: 10px; font-weight: 500; letter-spacing: 0; }
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--green); text-decoration: none; font-size: 12px; font-weight: 800; }
        .back-link:hover { text-decoration: underline; text-underline-offset: 3px; }
        .login-area { width: 100%; display: grid; place-items: center; padding: 46px 0; }
        .login-layout { width: min(100%, 880px); display: grid; grid-template-columns: 1fr minmax(320px, 420px); align-items: stretch; border: 1px solid var(--line); border-radius: 22px; background: var(--surface); box-shadow: var(--shadow); overflow: hidden; }
        .welcome-panel { position: relative; display: flex; flex-direction: column; justify-content: space-between; min-height: 440px; padding: 42px; overflow: hidden; background: var(--green); color: #fff; }
        .welcome-panel::after { content: "✿"; position: absolute; right: -42px; bottom: -92px; color: rgba(255, 255, 255, .08); font: 300 310px/1 "Fraunces", Georgia, serif; transform: rotate(-18deg); pointer-events: none; }
        .welcome-kicker { display: flex; align-items: center; gap: 8px; color: #d6e1d4; font-size: 10px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; }
        .welcome-copy { position: relative; z-index: 1; max-width: 370px; }
        .welcome-copy h1 { margin: 0 0 12px; font: 700 clamp(34px, 5vw, 48px)/1.04 "Fraunces", Georgia, serif; letter-spacing: -1px; }
        .welcome-copy p { max-width: 340px; margin: 0; color: #dce4da; font-size: 13px; line-height: 1.8; }
        .welcome-note { position: relative; z-index: 1; display: flex; align-items: center; gap: 9px; color: #dce4da; font-size: 11px; }
        .welcome-note span { width: 28px; height: 28px; display: grid; place-items: center; border: 1px solid rgba(255, 255, 255, .2); border-radius: 50%; color: #fff; }
        .form-panel { padding: 40px 34px; align-self: center; }
        .eyebrow { color: var(--plum); font-size: 10px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; }
        .form-panel h2 { margin: 8px 0 5px; font: 700 27px/1.15 "Fraunces", Georgia, serif; letter-spacing: -.4px; }
        .form-intro { margin: 0 0 24px; color: var(--muted); font-size: 12px; }
        .field { margin-bottom: 15px; }
        .field label { display: block; margin-bottom: 6px; font-size: 11px; font-weight: 800; }
        .input-wrap { position: relative; }
        .input-icon { position: absolute; top: 50%; left: 12px; color: var(--muted); font-size: 14px; transform: translateY(-50%); pointer-events: none; }
        .field input { width: 100%; height: 45px; padding: 10px 12px 10px 38px; border: 1px solid var(--line); border-radius: 10px; outline: none; background: #fff; color: var(--ink); font-size: 12px; transition: border-color .15s, box-shadow .15s; }
        .field input:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(46, 67, 52, .09); }
        .field input::placeholder { color: #a1a497; }
        .error-message { display: flex; align-items: flex-start; gap: 8px; margin: 0 0 16px; padding: 10px 11px; border: 1px solid #e8c9c3; border-radius: 9px; background: var(--rose-light); color: #853f35; font-size: 11px; }
        .error-message span { font-weight: 800; }
        .remember { display: flex; align-items: center; gap: 8px; margin: 2px 0 18px; color: var(--muted); font-size: 11px; }
        .remember input { width: 15px; height: 15px; margin: 0; accent-color: var(--green); }
        .submit-button { width: 100%; min-height: 45px; display: flex; align-items: center; justify-content: center; gap: 8px; border: 0; border-radius: 10px; background: var(--green); color: #fff; font-size: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 10px rgba(46, 67, 52, .16); transition: background .15s, transform .15s; }
        .submit-button:hover { background: var(--green-hover); transform: translateY(-1px); }
        .secure-note { display: flex; justify-content: center; align-items: center; gap: 6px; margin: 16px 0 0; color: var(--muted); font-size: 10px; text-align: center; }
        .page-bottom { color: var(--muted); font-size: 10px; text-align: center; }
        @media (max-width: 680px) {
            body { padding: 18px 14px; }
            .login-area { padding: 30px 0; }
            .login-layout { max-width: 440px; grid-template-columns: 1fr; }
            .welcome-panel { min-height: 205px; padding: 24px; }
            .welcome-copy { margin: 26px 0 22px; }
            .welcome-copy h1 { font-size: 34px; }
            .welcome-copy p { font-size: 12px; }
            .welcome-panel::after { right: -25px; bottom: -60px; font-size: 205px; }
            .form-panel { padding: 28px 24px 30px; }
            .page-top .back-link { font-size: 0; }
            .page-top .back-link span { font-size: 18px; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition-duration: .01ms !important; } }
    </style>
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
