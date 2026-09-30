<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $__env->yieldContent('title', 'TaniRaya ERP'); ?></title>
<link rel="stylesheet" href="<?php echo e(asset('css/style.css')); ?>">
</head>
<body
  data-flash-status="<?php echo e(session('status')); ?>"
>
<div class="app-shell">

  <?php echo $__env->make('partials.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

  <main class="app-main">
    <?php if($errors->any()): ?>
      <div class="alert-warning" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
        <span class="alert-text"><?php echo e($errors->first()); ?></span>
        <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Tutup">&times;</button>
      </div>
    <?php endif; ?>

    <?php echo $__env->yieldContent('content'); ?>
  </main>

  <?php echo $__env->make('partials.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

</div>

<script src="<?php echo e(asset('js/main.js')); ?>"></script>
<?php echo $__env->yieldContent('scripts'); ?>
</body>
</html>
<?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/layouts/app.blade.php ENDPATH**/ ?>