<?php $__env->startSection('title', 'Laporan Transaksi — TaniRaya ERP'); ?>
<?php $__env->startSection('page-title', 'Laporan Transaksi'); ?>
<?php $__env->startSection('page-subtitle', 'Preview & export berdasarkan rentang tanggal'); ?>

<?php $__env->startSection('content'); ?>

<form method="GET" action="<?php echo e(route('reports.index')); ?>">
  <div class="date-range-row">
    <div class="form-group">
      <label class="form-label" for="start_date">Dari Tanggal</label>
      <input class="form-control" type="date" id="start_date" name="start_date" value="<?php echo e($start); ?>">
    </div>
    <div class="form-group">
      <label class="form-label" for="end_date">Sampai Tanggal</label>
      <input class="form-control" type="date" id="end_date" name="end_date" value="<?php echo e($end); ?>">
    </div>
  </div>
  <button type="submit" class="btn btn-secondary btn-block">Tampilkan Preview</button>
</form>

<section class="section">
  <div class="section__head">
    <h2 class="section__title">Preview (<?php echo e($transactions->count()); ?> transaksi)</h2>
    <?php if($transactions->count() > 0): ?>
      <a href="<?php echo e(route('reports.export', ['start_date' => $start, 'end_date' => $end])); ?>" class="section__link">⬇ Export CSV</a>
    <?php endif; ?>
  </div>

  <div class="list-view is-active">
    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <div class="list-card">
        <div class="list-card__main">
          <span class="list-card__title"><?php echo e($t['sparepart']); ?></span>
          <span class="list-card__meta"><?php echo e($t['type']); ?> &middot; <?php echo e($t['related']); ?> &middot; <?php echo e(\Carbon\Carbon::parse($t['date'])->format('d M Y')); ?></span>
        </div>
        <div class="list-card__side">
          <span class="list-card__value">
            <?php echo e($t['quantity']); ?><?php if($t['value']): ?> &middot; Rp<?php echo e(number_format($t['value'], 0, ',', '.')); ?><?php endif; ?>
          </span>
          <span class="list-card__meta"><?php echo e($t['recorded_by']); ?></span>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <p class="empty-state">Tidak ada transaksi di rentang tanggal ini.</p>
    <?php endif; ?>
  </div>
</section>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/report/index.blade.php ENDPATH**/ ?>