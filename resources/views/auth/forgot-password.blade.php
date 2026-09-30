<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lupa Kata Sandi — TaniRaya ERP</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-page">
<div class="app-shell">

  <div class="login-hero">
    <div class="login-hero__mark">TR</div>
    <h1>Lupa Kata Sandi</h1>
    <p>Masukkan email akun Anda untuk menerima link reset</p>
  </div>

  <div class="login-form-wrap">
    <div class="login-card">
      <h2>Reset kata sandi</h2>
      <p class="hint">Kami akan mengirim link untuk membuat kata sandi baru ke email terdaftar.</p>

      @if (session('status'))
        <div class="form-group" style="color:#0E8A5C; font-size:13px; font-weight:600;">
          {{ session('status') }}
        </div>
      @endif

      <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" placeholder="contoh: admin@taniraya.co.id" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Kirim Link Reset</button>
      </form>

      <p class="login-footer-link">Sudah ingat kata sandi? <a href="{{ route('login') }}">Kembali ke Login</a></p>
    </div>

    <div class="role-hint">
      <strong>Catatan:</strong> alur reset password lewat email belum terhubung ke server SMTP sungguhan.
      Untuk sekarang, minta admin reset manual lewat menu Master User.
    </div>
  </div>

</div>
</body>
</html>
