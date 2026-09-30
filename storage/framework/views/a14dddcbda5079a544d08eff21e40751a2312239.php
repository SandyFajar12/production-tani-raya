<?php if (! empty(trim($__env->yieldContent('page-title')))): ?>
<header class="app-header app-header--sub">
  <div class="app-header__row">
    <a href="<?php echo $__env->yieldContent('back-url', route('dashboard')); ?>" class="icon-btn" aria-label="Kembali">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    </a>
    <div class="app-header__titles">
      <div class="app-header__title"><?php echo $__env->yieldContent('page-title'); ?></div>
      <div class="app-header__sub"><?php echo $__env->yieldContent('page-subtitle'); ?></div>
    </div>
    <span style="width:36px"></span>
  </div>
</header>
<?php else: ?>
<header class="app-header">
  <div class="app-header__row">
    <div class="brand">
      <span class="brand__mark">TR</span>
      <div class="brand__text">
        <strong>TaniRaya</strong>
        <span>ERP Sparepart</span>
      </div>
    </div>
    <div class="header-actions">
      <span id="liveClock" class="live-clock"></span>
      <form method="POST" action="<?php echo e(route('logout')); ?>">
        <?php echo csrf_field(); ?>
        <button type="submit" class="icon-btn icon-btn--ghost" aria-label="Keluar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </button>
      </form>
    </div>
  </div>
</header>
<?php endif; ?>
<?php /**PATH C:\Users\IT ZETKA\Downloads\taniraya-erp-laravel\resources\views/partials/header.blade.php ENDPATH**/ ?>