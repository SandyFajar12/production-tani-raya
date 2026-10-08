<?php $__env->startSection('title', 'Master Supplier — TaniRaya ERP'); ?>
<?php $__env->startSection('page-title', 'Master Supplier'); ?>
<?php $__env->startSection('page-subtitle', 'Data pemasok sparepart'); ?>

<?php $__env->startSection('content'); ?>

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Supplier</button>
  <?php if (\Illuminate\Support\Facades\Blade::check('permission', 'master.manage')): ?>
  <button class="tab-btn" data-tab-target="viewForm" onclick="resetFormToCreate('#supplierForm', '<?php echo e(route('suppliers.store')); ?>', 'Simpan Supplier')">+ Tambah Supplier</button>
  <?php endif; ?>
</div>

<section id="viewList" class="view is-active">
  <?php echo $__env->make('partials.list-toolbar', ['searchPlaceholder' => 'Cari nama / kontak supplier...'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

  <div class="list-view is-active">
  <?php $__empty_1 = true; $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title"><?php echo e($s->name); ?></span>
        <span class="list-card__meta">Kontak: <?php echo e($s->contact_person ?? '-'); ?> &middot; <?php echo e($s->phone ?? '-'); ?></span>
      </div>
      <div class="list-card__side">
        <span class="badge badge--<?php echo e($s->is_active ? 'active' : 'inactive'); ?>"><?php echo e($s->is_active ? 'Aktif' : 'Nonaktif'); ?></span>
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="showDetailModal(this)"
          data-detail-title="Detail Supplier"
          data-detail="<?php echo e(json_encode([
            'Nama' => $s->name,
            'Kontak' => $s->contact_person ?? '-',
            'Telepon' => $s->phone ?? '-',
            'Email' => $s->email ?? '-',
            'Alamat' => $s->address ?? '-',
            'Status' => $s->is_active ? 'Aktif' : 'Nonaktif',
          ], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">Detail</button>
        <?php if (\Illuminate\Support\Facades\Blade::check('permission', 'master.manage')): ?>
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="openEditForm(this, '#supplierForm')"
          data-update-url="<?php echo e(route('suppliers.update', $s)); ?>"
          data-edit="<?php echo e(json_encode([
            'name' => $s->name, 'contact_person' => $s->contact_person, 'phone' => $s->phone,
            'email' => $s->email, 'address' => $s->address, 'is_active' => $s->is_active ? 1 : 0,
          ], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">Edit</button>
        <?php endif; ?>
        <?php if (\Illuminate\Support\Facades\Blade::check('permission', 'master.delete')): ?>
        <form method="POST" action="<?php echo e(route('suppliers.destroy', $s)); ?>" onsubmit="return confirmDelete('Hapus supplier <?php echo e($s->name); ?>?')">
          <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
          <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;">Hapus</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p class="empty-state">Belum ada data supplier.</p>
  <?php endif; ?>
  </div>

  <?php echo $__env->make('partials.pagination', ['paginator' => $suppliers], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</section>

<?php if (\Illuminate\Support\Facades\Blade::check('permission', 'master.manage')): ?>
<section id="viewForm" class="view">
  <form method="POST" action="<?php echo e(route('suppliers.store')); ?>" id="supplierForm">
    <?php echo csrf_field(); ?>

    <div class="form-group">
      <label class="form-label" for="sp-nama">Nama Supplier</label>
      <input class="form-control" id="sp-nama" name="name" value="<?php echo e(old('name')); ?>" placeholder="contoh: CV Sumber Sparepart" required>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="sp-kontak">Nama Kontak (PIC)</label>
        <input class="form-control" id="sp-kontak" name="contact_person" value="<?php echo e(old('contact_person')); ?>" placeholder="contoh: Pak Herman">
      </div>
      <div class="form-group">
        <label class="form-label" for="sp-telepon">No. Telepon</label>
        <input class="form-control" id="sp-telepon" name="phone" value="<?php echo e(old('phone')); ?>" placeholder="08xx-xxxx-xxxx">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="sp-email">Email</label>
      <input class="form-control" id="sp-email" name="email" type="email" value="<?php echo e(old('email')); ?>" placeholder="opsional">
    </div>

    <div class="form-group">
      <label class="form-label" for="sp-alamat">Alamat</label>
      <textarea class="form-control" id="sp-alamat" name="address" rows="3" placeholder="opsional"><?php echo e(old('address')); ?></textarea>
    </div>

    <div class="form-group">
      <label class="form-label" for="sp-status">Status</label>
      <select class="form-control" id="sp-status" name="is_active">
        <option value="1">Aktif</option>
        <option value="0">Nonaktif</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-block" data-form-submit>Simpan Supplier</button>
  </form>
</section>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/suppliers/index.blade.php ENDPATH**/ ?>