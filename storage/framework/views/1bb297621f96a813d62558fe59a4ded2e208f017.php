<?php $__env->startSection('title', 'Master Sparepart — TaniRaya ERP'); ?>
<?php $__env->startSection('page-title', 'Master Sparepart'); ?>
<?php $__env->startSection('page-subtitle', 'Data induk sparepart'); ?>

<?php $__env->startSection('content'); ?>

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Sparepart</button>
  <?php if (\Illuminate\Support\Facades\Blade::check('permission', 'master.manage')): ?>
  <button class="tab-btn" data-tab-target="viewForm" onclick="resetFormToCreate('#sparepartForm', '<?php echo e(route('spareparts.store')); ?>', 'Simpan Sparepart')">+ Tambah Sparepart</button>
  <?php endif; ?>
</div>

<section id="viewList" class="view is-active">
  <?php echo $__env->make('partials.list-toolbar', ['searchPlaceholder' => 'Cari kode / nama sparepart...'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

  <?php if(request('low_stock')): ?>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12.5px; color:var(--color-text-muted);">
      <span>Menampilkan sparepart yang stoknya menipis saja</span>
      <a href="<?php echo e(route('spareparts.index')); ?>" class="section__link">Lihat semua →</a>
    </div>
  <?php endif; ?>

  <div class="list-view is-active">
  <?php $__empty_1 = true; $__currentLoopData = $spareparts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title"><?php echo e($sp->name); ?></span>
        <span class="list-card__meta"><?php echo e($sp->code); ?> &middot; Kategori: <?php echo e($sp->category->name ?? '-'); ?> &middot; <?php echo e($sp->dimension); ?></span>
      </div>
      <div class="list-card__side">
        <span class="list-card__value"><?php echo e($sp->current_stock); ?> <?php echo e($sp->unit->name ?? ''); ?></span>
        <?php if (\Illuminate\Support\Facades\Blade::check('permission', 'master.manage')): ?>
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="openEditForm(this, '#sparepartForm')"
          data-update-url="<?php echo e(route('spareparts.update', $sp)); ?>"
          data-edit="<?php echo e(json_encode([
            'code' => $sp->code, 'name' => $sp->name, 'category_id' => $sp->category_id,
            'dimension' => $sp->dimension, 'unit_id' => $sp->unit_id, 'min_stock' => $sp->min_stock,
            'standard_price' => $sp->standard_price, 'main_supplier_id' => $sp->main_supplier_id,
          ], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">Edit</button>
        <?php endif; ?>
        <?php if (\Illuminate\Support\Facades\Blade::check('permission', 'master.delete')): ?>
        <form method="POST" action="<?php echo e(route('spareparts.destroy', $sp)); ?>" onsubmit="return confirmDelete('Hapus sparepart <?php echo e($sp->name); ?>?')">
          <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
          <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;">Hapus</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p class="empty-state">Belum ada data sparepart.</p>
  <?php endif; ?>
  </div>

  <?php echo $__env->make('partials.pagination', ['paginator' => $spareparts], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</section>

<?php if (\Illuminate\Support\Facades\Blade::check('permission', 'master.manage')): ?>
<section id="viewForm" class="view">
  <form method="POST" action="<?php echo e(route('spareparts.store')); ?>" id="sparepartForm">
    <?php echo csrf_field(); ?>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="ms-kode">Kode Sparepart</label>
        <input class="form-control" id="ms-kode" name="code" value="<?php echo e(old('code')); ?>" placeholder="contoh: SP-0060" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="ms-kategori">Jenis / Kategori</label>
        <select class="form-control" id="ms-kategori" name="category_id" required>
          <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($cat->id); ?>" <?php echo e(old('category_id') == $cat->id ? 'selected' : ''); ?>><?php echo e($cat->name); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="ms-nama">Nama Sparepart</label>
      <input class="form-control" id="ms-nama" name="name" value="<?php echo e(old('name')); ?>" placeholder="contoh: Filter Oli Mesin" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="ms-dimensi">Dimensi / Ukuran</label>
      <input class="form-control" id="ms-dimensi" name="dimension" value="<?php echo e(old('dimension')); ?>" placeholder="contoh: Ø8cm x 12cm">
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="ms-satuan">Satuan</label>
        <select class="form-control" id="ms-satuan" name="unit_id" required>
          <?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($unit->id); ?>" <?php echo e(old('unit_id') == $unit->id ? 'selected' : ''); ?>><?php echo e($unit->name); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="ms-minstok">Stok Minimum</label>
        <input class="form-control" id="ms-minstok" name="min_stock" type="number" min="0" value="<?php echo e(old('min_stock', 0)); ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="ms-harga">Harga Standar (Rp)</label>
        <input class="form-control" id="ms-harga" name="standard_price" type="number" min="0" value="<?php echo e(old('standard_price')); ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="ms-supplier">Supplier Utama</label>
        <select class="form-control" id="ms-supplier" name="main_supplier_id">
          <option value="">— pilih supplier —</option>
          <?php $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($sup->id); ?>" <?php echo e(old('main_supplier_id') == $sup->id ? 'selected' : ''); ?>><?php echo e($sup->name); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block" data-form-submit>Simpan Sparepart</button>
  </form>
</section>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/spareparts/index.blade.php ENDPATH**/ ?>