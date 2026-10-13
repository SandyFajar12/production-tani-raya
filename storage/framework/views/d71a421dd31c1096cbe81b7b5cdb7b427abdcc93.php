

<?php $__env->startSection('title', 'Riwayat Transaksi — TaniRaya ERP'); ?>
<?php $__env->startSection('page-title', 'Riwayat Transaksi'); ?>
<?php $__env->startSection('page-subtitle', 'Semua aktivitas hari ini'); ?>

<?php $__env->startSection('content'); ?>

<div class="list-view is-active">
  <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title"><?php echo e($t['title']); ?></span>
        <span class="list-card__meta"><?php echo e($t['meta']); ?> &middot; <?php echo e($t['time']->format('H:i')); ?></span>
      </div>
      <div class="list-card__side">
        <?php if($t['type'] === 'purchase'): ?>
          <span class="badge badge--approved">Pembelian</span>
        <?php elseif($t['type'] === 'usage'): ?>
          <span class="badge badge--rejected">Pemakaian</span>
        <?php else: ?>
          <span class="badge badge--pending">Pre-Order</span>
        <?php endif; ?>
        <span class="list-card__value"><?php echo e($t['value']); ?></span>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p class="empty-state">Belum ada transaksi hari ini.</p>
  <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/transactions/index.blade.php ENDPATH**/ ?>