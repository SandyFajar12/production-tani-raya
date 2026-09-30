<?php $__env->startSection('title', 'Pembelian — TaniRaya ERP'); ?>
<?php $__env->startSection('page-title', 'Pembelian'); ?>
<?php $__env->startSection('page-subtitle', 'Transaksi pembelian sparepart'); ?>

<?php $__env->startSection('content'); ?>

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Riwayat</button>
  <button class="tab-btn" data-tab-target="viewForm">+ Catat Pembelian</button>
</div>

<section id="viewList" class="view list-view is-active">
  <?php $__empty_1 = true; $__currentLoopData = $purchases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title"><?php echo e($p->sparepart->name ?? '-'); ?> × <?php echo e($p->quantity); ?></span>
        <span class="list-card__meta"><?php echo e($p->supplier->name ?? '-'); ?> &middot; <?php echo e($p->invoice_number ?? '-'); ?> &middot; <?php echo e(\Carbon\Carbon::parse($p->purchase_date)->format('d M Y')); ?></span>
      </div>
      <div class="list-card__side">
        <span class="list-card__value">Rp<?php echo e(number_format($p->total_price, 0, ',', '.')); ?></span>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p class="empty-state">Belum ada riwayat pembelian.</p>
  <?php endif; ?>
</section>

<section id="viewForm" class="view">
  <form method="POST" action="<?php echo e(route('purchases.store')); ?>">
    <?php echo csrf_field(); ?>

    <div class="form-group">
      <label class="form-label" for="pb-preorder">Terkait Pre-Order (opsional)</label>
      <select class="form-control" id="pb-preorder" name="preorder_id">
        <option value="">— Tidak terkait pre-order —</option>
        <?php $__currentLoopData = $approvedPreorders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $po): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($po->id); ?>" <?php if (old('preorder_id') == $po->id) echo 'selected'; ?>><?php echo e($po->sparepart->name ?? '-'); ?> — <?php echo e($po->quantity); ?> (<?php echo e($po->code); ?>)</option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="pb-sparepart">Sparepart</label>
      <select class="form-control" id="pb-sparepart" name="sparepart_id" required>
        <?php $__currentLoopData = $spareparts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($sp->id); ?>" <?php if (old('sparepart_id') == $sp->id) echo 'selected'; ?>><?php echo e($sp->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="pb-qty">Jumlah</label>
        <input class="form-control" id="pb-qty" name="quantity" type="number" min="1" value="<?php echo e(old('quantity')); ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="pb-harga">Harga Satuan (Rp)</label>
        <input class="form-control" id="pb-harga" name="unit_price" type="number" min="0" value="<?php echo e(old('unit_price')); ?>" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="pb-supplier">Supplier</label>
      <select class="form-control" id="pb-supplier" name="supplier_id" required>
        <?php $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($s->id); ?>" <?php if (old('supplier_id') == $s->id) echo 'selected'; ?>><?php echo e($s->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="pb-invoice">No. PO / Invoice</label>
        <input class="form-control" id="pb-invoice" name="invoice_number" value="<?php echo e(old('invoice_number')); ?>" placeholder="INV-2026-XXXX">
      </div>
      <div class="form-group">
        <label class="form-label" for="pb-tanggal">Tanggal</label>
        <input class="form-control" id="pb-tanggal" name="purchase_date" type="date" value="<?php echo e(old('purchase_date', date('Y-m-d'))); ?>" required>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan Pembelian</button>
  </form>
</section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/purchases/index.blade.php ENDPATH**/ ?>