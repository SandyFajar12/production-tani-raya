<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — TaniRaya ERP</title>
<link rel="stylesheet" href="<?php echo e(asset('css/style.css')); ?>">
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

      <?php if($errors->any()): ?>
        <div class="form-group" style="color:#D22E52; font-size:13px; font-weight:600;">
          <?php echo e($errors->first()); ?>

        </div>
      <?php endif; ?>

      <form method="POST" action="<?php echo e(route('login.attempt')); ?>">
        <?php echo csrf_field(); ?>
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" placeholder="contoh: admin@taniraya.co.id" autocomplete="username" required autofocus>
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

      <p class="login-footer-link">Lupa kata sandi? <a href="<?php echo e(route('password.request')); ?>">Reset di sini</a></p>
    </div>
  </div>

</div>
</body>
</html>
<?php /**PATH C:\laragon\www\taniraya-erp-laravel\resources\views/auth/login.blade.php ENDPATH**/ ?>