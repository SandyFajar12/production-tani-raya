<?php $__env->startSection('title', 'Update Stok — TaniRaya ERP'); ?>
<?php $__env->startSection('page-title', 'Update Stok'); ?>
<?php $__env->startSection('page-subtitle', 'Stok terkini & penyesuaian'); ?>

<?php $__env->startSection('content'); ?>

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Stok Terkini</button>
  <button class="tab-btn" data-tab-target="viewForm">+ Penyesuaian Stok</button>
</div>

<div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
  <a href="<?php echo e(route('stocks.ledger')); ?>" class="section__link">Lihat Kartu Stok (riwayat) →</a>
</div>

<section id="viewList" class="view list-view is-active">
  <?php $__empty_1 = true; $__currentLoopData = $spareparts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title"><?php echo e($sp->name); ?></span>
        <span class="list-card__meta"><?php echo e($sp->code); ?> &middot; Min. stok <?php echo e($sp->min_stock); ?> <?php echo e($sp->unit->name ?? ''); ?></span>
      </div>
      <div class="list-card__side">
        <span class="list-card__value"><?php echo e($sp->current_stock); ?> <?php echo e($sp->unit->name ?? ''); ?></span>
        <span class="badge badge--<?php echo e($sp->isLowStock() ? 'pending' : 'active'); ?>"><?php echo e($sp->isLowStock() ? 'Menipis' : 'Aman'); ?></span>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p class="empty-state">Belum ada data sparepart.</p>
  <?php endif; ?>
</section>

<section id="viewForm" class="view">
  <form method="POST" action="<?php echo e(route('stocks.adjust')); ?>">
    <?php echo csrf_field(); ?>

    <div class="form-group">
      <label class="form-label" for="us-sparepart">Sparepart</label>
      <select class="form-control" id="us-sparepart" name="sparepart_id" required>
        <option value="">Pilih sparepart…</option>
        <?php $__currentLoopData = $spareparts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($sp->id); ?>" <?php echo e(old('sparepart_id') == $sp->id ? 'selected' : ''); ?>><?php echo e($sp->name); ?> — stok sistem: <?php echo e($sp->current_stock); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="us-jenis">Jenis Penyesuaian</label>
        <select class="form-control" id="us-jenis" name="type">
          <option value="opname">Stok Opname (koreksi)</option>
          <option value="damaged_lost">Barang Rusak / Hilang</option>
          <option value="return_to_supplier">Retur ke Supplier</option>
          <option value="other">Lainnya</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="us-qty">Stok Fisik Aktual</label>
        <input class="form-control" id="us-qty" name="physical_qty" type="number" min="0" value="<?php echo e(old('physical_qty')); ?>" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="us-alasan">Alasan Penyesuaian</label>
      <textarea class="form-control" id="us-alasan" name="reason" rows="3" placeholder="contoh: selisih hasil stok opname bulanan" required><?php echo e(old('reason')); ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan Penyesuaian</button>
  </form>
</section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/stocks/index.blade.php ENDPATH**/ ?>