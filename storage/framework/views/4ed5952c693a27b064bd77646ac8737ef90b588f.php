<?php $__env->startSection('title', 'Pemakaian Sparepart — TaniRaya ERP'); ?>
<?php $__env->startSection('page-title', 'Pemakaian Sparepart'); ?>
<?php $__env->startSection('page-subtitle', 'Riwayat & catat pemakaian'); ?>

<?php $__env->startSection('content'); ?>

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Riwayat</button>
  <?php if (\Illuminate\Support\Facades\Blade::check('permission', 'usage.create')): ?>
  <button class="tab-btn" data-tab-target="viewForm">+ Catat Pemakaian</button>
  <?php endif; ?>
</div>

<section id="viewList" class="view is-active">
  <?php echo $__env->make('partials.list-toolbar', ['searchPlaceholder' => 'Cari nama sparepart...'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

  <?php if(request('date') === 'today'): ?>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12.5px; color:var(--color-text-muted);">
      <span>Menampilkan pemakaian hari ini saja</span>
      <a href="<?php echo e(route('usages.index')); ?>" class="section__link">Lihat semua →</a>
    </div>
  <?php endif; ?>

  <div class="list-view is-active">
  <?php $__empty_1 = true; $__currentLoopData = $usages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title"><?php echo e($u->sparepart->name ?? '-'); ?></span>
        <span class="list-card__meta"><?php echo e($u->fleet->name ?? '-'); ?> &middot; dicatat oleh <?php echo e($u->recorder->name ?? '-'); ?> &middot; <?php echo e(\Carbon\Carbon::parse($u->usage_date)->format('d M Y')); ?></span>
      </div>
      <div class="list-card__side">
        <span class="list-card__value">−<?php echo e($u->quantity); ?> <?php echo e($u->sparepart->unit->name ?? ''); ?></span>
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="showDetailModal(this)"
          data-detail-title="Detail Pemakaian"
          data-detail="<?php echo e(json_encode([
            'Sparepart' => $u->sparepart->name ?? '-',
            'Jumlah' => $u->quantity.' '.($u->sparepart->unit->name ?? ''),
            'Armada' => $u->fleet->name ?? '-',
            'Tanggal' => \Carbon\Carbon::parse($u->usage_date)->format('d M Y'),
            'Catatan' => $u->notes ?? '-',
            'Dicatat Oleh' => $u->recorder->name ?? '-',
          ], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">Detail</button>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p class="empty-state">Belum ada riwayat pemakaian.</p>
  <?php endif; ?>
  </div>

  <?php echo $__env->make('partials.pagination', ['paginator' => $usages], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</section>

<?php if (\Illuminate\Support\Facades\Blade::check('permission', 'usage.create')): ?>
<section id="viewForm" class="view">
<form method="POST" action="<?php echo e(route('usages.store')); ?>" data-safe-form="usage">
    <?php echo csrf_field(); ?>

    <div class="form-group">
      <label class="form-label" for="pk-sparepart">Sparepart</label>
      <select class="form-control" id="pk-sparepart" name="sparepart_id" required>
        <option value="">Pilih sparepart…</option>
        <?php $__currentLoopData = $spareparts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($sp->id); ?>" <?php echo e(old('sparepart_id') == $sp->id ? 'selected' : ''); ?>><?php echo e($sp->name); ?> (stok: <?php echo e($sp->current_stock); ?>)</option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="pk-qty">Jumlah Dipakai</label>
        <input class="form-control" id="pk-qty" name="quantity" type="number" min="1" value="<?php echo e(old('quantity')); ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="pk-tanggal">Tanggal</label>
        <input class="form-control" id="pk-tanggal" name="usage_date" type="date" value="<?php echo e(old('usage_date', date('Y-m-d'))); ?>" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="pk-armada">Unit Armada / Alat</label>
      <select class="form-control" id="pk-armada" name="fleet_id" required>
        <?php $__currentLoopData = $fleets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($f->id); ?>" <?php echo e(old('fleet_id') == $f->id ? 'selected' : ''); ?>><?php echo e($f->name); ?> — <?php echo e($f->code); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="pk-catatan">Catatan</label>
      <textarea class="form-control" id="pk-catatan" name="notes" rows="3" placeholder="opsional"><?php echo e(old('notes')); ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan Pemakaian</button>
  </form>
</section>
<?php endif; ?>          

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/usages/index.blade.php ENDPATH**/ ?>