<?php $__env->startSection('title', 'Pre-Order — TaniRaya ERP'); ?>
<?php $__env->startSection('page-title', 'Pre-Order'); ?>
<?php $__env->startSection('page-subtitle', 'Pengadaan Sparepart'); ?>

<?php $__env->startSection('content'); ?>

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Pengajuan</button>
  <button class="tab-btn" data-tab-target="viewForm">+ Ajukan Baru</button>
</div>

<section id="viewList" class="view list-view is-active">
  <?php $__empty_1 = true; $__currentLoopData = $preorders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $po): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title"><?php echo e($po->sparepart->name ?? '-'); ?> — <?php echo e($po->quantity); ?> <?php echo e($po->unit->name ?? ''); ?></span>
        <span class="list-card__meta"><?php echo e($po->fleet->name ?? '-'); ?> &middot; Diajukan oleh <?php echo e($po->requester->name ?? '-'); ?> &middot; <?php echo e($po->created_at->format('d M Y')); ?></span>
      </div>
      <div class="list-card__side">
        <?php if($po->status === 'pending'): ?>
          <span class="badge badge--pending">Menunggu</span>
          <?php if(auth()->user()->hasPermission('preorder.approve')): ?>
            <div style="display:flex; gap:6px; margin-top:4px;">
              <form method="POST" action="<?php echo e(route('preorders.approve', $po)); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-sm btn-primary" style="width:auto; height:30px; padding:0 10px;">Setujui</button>
              </form>
              <form method="POST" action="<?php echo e(route('preorders.reject', $po)); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:30px; padding:0 10px;">Tolak</button>
              </form>
            </div>
          <?php endif; ?>
        <?php elseif($po->status === 'approved'): ?>
          <span class="badge badge--approved">Disetujui</span>
        <?php else: ?>
          <span class="badge badge--rejected">Ditolak</span>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p class="empty-state">Belum ada pengajuan pre-order.</p>
  <?php endif; ?>
</section>

<section id="viewForm" class="view">
  <form method="POST" action="<?php echo e(route('preorders.store')); ?>">
    <?php echo csrf_field(); ?>

    <div class="form-group">
      <label class="form-label" for="po-sparepart">Nama Sparepart</label>
      <select class="form-control" id="po-sparepart" name="sparepart_id" required>
        <?php $__currentLoopData = $spareparts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($sp->id); ?>" <?php echo e(old('sparepart_id') == $sp->id ? 'selected' : ''); ?>><?php echo e($sp->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="po-qty">Jumlah</label>
      <input class="form-control" id="po-qty" name="quantity" type="number" min="1" value="<?php echo e(old('quantity')); ?>" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="po-armada">Unit Armada Terkait</label>
      <select class="form-control" id="po-armada" name="fleet_id">
        <option value="">— Tidak terkait armada —</option>
        <?php $__currentLoopData = $fleets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($f->id); ?>" <?php echo e(old('fleet_id') == $f->id ? 'selected' : ''); ?>><?php echo e($f->name); ?> — <?php echo e($f->code); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="po-tanggal">Tanggal Dibutuhkan</label>
      <input class="form-control" id="po-tanggal" name="needed_date" type="date" value="<?php echo e(old('needed_date')); ?>">
    </div>

    <div class="form-group">
      <label class="form-label" for="po-catatan">Alasan / Catatan</label>
      <textarea class="form-control" id="po-catatan" name="reason" rows="3" placeholder="contoh: stok habis, komponen aus"><?php echo e(old('reason')); ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Ajukan Pre-Order</button>
  </form>
</section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/preorders/index.blade.php ENDPATH**/ ?>