<?php $__env->startSection('title', 'Master User — TaniRaya ERP'); ?>
<?php $__env->startSection('page-title', 'Master User'); ?>
<?php $__env->startSection('page-subtitle', 'Login & hak akses'); ?>

<?php $__env->startSection('content'); ?>

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar User</button>
  <button class="tab-btn" data-tab-target="viewForm">+ Tambah User</button>
</div>

<div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
  <a href="<?php echo e(route('roles.index')); ?>" class="section__link">Kelola Role &amp; Permission →</a>
</div>

<section id="viewList" class="view list-view is-active">
  <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title"><?php echo e($u->name); ?></span>
        <span class="list-card__meta"><?php echo e($u->email); ?></span>
      </div>
      <div class="list-card__side">
        <span class="badge badge--role-<?php echo e($u->role && $u->role->slug === 'admin' ? 'admin' : ($u->role && $u->role->slug === 'approver' ? 'approver' : 'staff')); ?>"><?php echo e($u->role->name ?? '-'); ?></span>
        <span class="badge badge--<?php echo e($u->is_active ? 'active' : 'inactive'); ?>"><?php echo e($u->is_active ? 'Aktif' : 'Nonaktif'); ?></span>
        <?php if($u->id !== auth()->id()): ?>
          <form method="POST" action="<?php echo e(route('users.destroy', $u)); ?>" onsubmit="return confirmDelete('Hapus user <?php echo e($u->name); ?>?')">
            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
            <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;">Hapus</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p class="empty-state">Belum ada data user.</p>
  <?php endif; ?>
</section>

<section id="viewForm" class="view">
  <form method="POST" action="<?php echo e(route('users.store')); ?>">
    <?php echo csrf_field(); ?>

    <div class="form-group">
      <label class="form-label" for="mu-nama">Nama Lengkap</label>
      <input class="form-control" id="mu-nama" name="name" value="<?php echo e(old('name')); ?>" placeholder="contoh: Dedi Kurniawan" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="mu-email">Email / Username</label>
      <input class="form-control" id="mu-email" name="email" type="email" value="<?php echo e(old('email')); ?>" placeholder="nama@taniraya.co.id" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="mu-password">Kata Sandi Awal</label>
      <input class="form-control" id="mu-password" name="password" type="password" placeholder="minimal 6 karakter" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="mu-role">Role / Hak Akses</label>
      <select class="form-control" id="mu-role" name="role_id" required>
        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($role->id); ?>" @selected(old('role_id') == $role->id)><?php echo e($role->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="mu-status">Status</label>
      <select class="form-control" id="mu-status" name="is_active">
        <option value="1">Aktif</option>
        <option value="0">Nonaktif</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan User</button>
  </form>
</section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\taniraya-erp-laravel\resources\views/users/index.blade.php ENDPATH**/ ?>