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
  data-flash-error="<?php echo e($errors->any() ? $errors->first() : ''); ?>"
>
<div class="app-shell">

  <?php echo $__env->make('partials.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

  <main class="app-main">
    <?php echo $__env->yieldContent('content'); ?>
  </main>

  <?php echo $__env->make('partials.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

</div>

<script src="<?php echo e(asset('js/main.js')); ?>"></script>
<?php echo $__env->yieldContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\IT ZETKA\Downloads\taniraya-erp-laravel\resources\views/layouts/app.blade.php ENDPATH**/ ?>