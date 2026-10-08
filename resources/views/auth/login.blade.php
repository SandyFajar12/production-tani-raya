<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — TaniRaya ERP</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
<link rel="manifest" href="{{ asset('manifest.json') }}" crossorigin="use-credentials">
<meta name="theme-color" content="#7C4FE0">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="TaniRaya">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
</head>
<body class="login-page">
<div class="app-shell">

  <div class="login-hero">
    <div class="login-hero__mark">TR</div>
    <h1>TaniRaya</h1>
    <p>ERP Stok Sparepart Armada Operasional</p>
  </div>

  <div class="login-form-wrap">
    <div class="login-card">
      <h2>Masuk ke akun Anda</h2>
      <p class="hint">Gunakan email &amp; kata sandi yang terdaftar di sistem.</p>

      @if ($errors->any())
        <div class="form-group" style="color:#D22E52; font-size:13px; font-weight:600;">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="contoh: admin@taniraya.co.id" autocomplete="username" required autofocus>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Kata Sandi</label>
          <input class="form-control" type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
        </div>

        <label class="form-check">
          <input type="checkbox" id="remember" name="remember">
          Ingat saya di perangkat ini
        </label>

        <button type="submit" class="btn btn-primary btn-block">Masuk</button>
      </form>

      <p class="login-footer-link">Lupa kata sandi? <a href="{{ route('password.request') }}">Reset di sini</a></p>
    </div>
  </div>

</div>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('/sw.js').catch(function () {});
  });
}
</script>
</body>
</html>
