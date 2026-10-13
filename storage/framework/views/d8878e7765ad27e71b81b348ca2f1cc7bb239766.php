<?php $__env->startSection('title', 'Master Armada — TaniRaya ERP'); ?>
<?php $__env->startSection('page-title', 'Master Armada'); ?>
<?php $__env->startSection('page-subtitle', 'Data unit kendaraan & alat'); ?>

<?php $__env->startSection('content'); ?>

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Armada</button>
  <?php if (\Illuminate\Support\Facades\Blade::check('permission', 'master.manage')): ?>
  <button class="tab-btn" data-tab-target="viewForm" onclick="resetFormToCreate('#fleetForm', '<?php echo e(route('fleets.store')); ?>', 'Simpan Armada')">+ Tambah Armada</button>
  <?php endif; ?>
</div>

<section id="viewList" class="view is-active">
  <?php echo $__env->make('partials.list-toolbar', ['searchPlaceholder' => 'Cari kode / nama armada...'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

  <div class="list-view is-active">
  <?php $__empty_1 = true; $__currentLoopData = $fleets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title"><?php echo e($f->name); ?> — <?php echo e($f->code); ?></span>
        <span class="list-card__meta">Tipe: <?php echo e($f->type); ?></span>
      </div>
      <div class="list-card__side">
        <?php if($f->status === 'aktif'): ?>
          <span class="badge badge--active">Aktif</span>
        <?php elseif($f->status === 'maintenance'): ?>
          <span class="badge badge--pending">Maintenance</span>
        <?php else: ?>
          <span class="badge badge--inactive">Nonaktif</span>
        <?php endif; ?>
        <div class="action-group">
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="showDetailModal(this)"
          data-detail-title="Detail Armada"
          data-detail="<?php echo e(json_encode([
            'Kode' => $f->code,
            'Nama' => $f->name,
            'Tipe' => $f->type,
            'Status' => ucfirst($f->status),
          ], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>" title="Detail" aria-label="Detail"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        <?php if (\Illuminate\Support\Facades\Blade::check('permission', 'master.manage')): ?>
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="openEditForm(this, '#fleetForm')"
          data-update-url="<?php echo e(route('fleets.update', $f)); ?>"
          data-edit="<?php echo e(json_encode([
            'code' => $f->code, 'name' => $f->name, 'type' => $f->type, 'status' => $f->status,
          ], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>" title="Edit" aria-label="Edit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg></button>
        <?php endif; ?>
        <?php if (\Illuminate\Support\Facades\Blade::check('permission', 'master.delete')): ?>
        <form method="POST" action="<?php echo e(route('fleets.destroy', $f)); ?>" onsubmit="return confirmDelete('Hapus armada <?php echo e($f->name); ?>?')">
          <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
          <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;" title="Hapus" aria-label="Hapus"><svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg></button>
        </form>
        <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p class="empty-state">Belum ada data armada.</p>
  <?php endif; ?>
  </div>

  <?php echo $__env->make('partials.pagination', ['paginator' => $fleets], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</section>

<?php if (\Illuminate\Support\Facades\Blade::check('permission', 'master.manage')): ?>
<section id="viewForm" class="view">
  <form method="POST" action="<?php echo e(route('fleets.store')); ?>" id="fleetForm" data-safe-form="master-fleet" data-no-draft>
    <?php echo csrf_field(); ?>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="ma-kode">Kode Armada</label>
        <input class="form-control" id="ma-kode" name="code" value="<?php echo e(old('code')); ?>" placeholder="contoh: FR-D9" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="ma-tipe">Tipe</label>
        <select class="form-control" id="ma-tipe" name="type">
          <option>Truk</option>
          <option>Traktor</option>
          <option>Dump Truck</option>
          <option>Excavator</option>
          <option>Lainnya</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="ma-nama">Nama / Keterangan Armada</label>
      <input class="form-control" id="ma-nama" name="name" value="<?php echo e(old('name')); ?>" placeholder="contoh: Truk Angkut Blok A" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="ma-status">Status</label>
      <select class="form-control" id="ma-status" name="status">
        <option value="aktif">Aktif</option>
        <option value="maintenance">Maintenance</option>
        <option value="nonaktif">Nonaktif</option>
      </select>
    </div>

        <button type="submit" class="btn btn-primary btn-block" data-form-submit>Simpan Armada</button>
      </form>
    </section>
    <?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/fleets/index.blade.php ENDPATH**/ ?>