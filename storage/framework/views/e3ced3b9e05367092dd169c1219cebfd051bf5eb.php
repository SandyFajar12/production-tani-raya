<?php $__env->startSection('title', 'Kelola Role & Permission — TaniRaya ERP'); ?>
<?php $__env->startSection('page-title', 'Kelola Role & Permission'); ?>
<?php $__env->startSection('page-subtitle', 'Atur hak akses per role'); ?>
<?php $__env->startSection('back-url', route('users.index')); ?>

<?php $__env->startSection('content'); ?>

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Role</button>
  <button class="tab-btn" data-tab-target="viewForm">Atur Permission</button>
</div>

<section id="viewList" class="view list-view is-active">
  <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title"><?php echo e($role->name); ?></span>
        <span class="list-card__meta"><?php echo e($role->description); ?> &middot; <?php echo e($role->users_count); ?> user</span>
      </div>
      <div class="list-card__side">
        <a href="<?php echo e(route('roles.index', ['role' => $role->id])); ?>#atur" class="btn btn-sm btn-secondary" style="width:auto; height:30px; padding:0 12px;">Kelola Akses</a>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</section>

<section id="viewForm" class="view">
  <?php if($activeRole): ?>
  <form method="POST" action="<?php echo e(route('roles.update', $activeRole)); ?>">
    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

    <div class="form-group">
      <label class="form-label" for="rp-role">Role yang Diatur</label>
      <select class="form-control" id="rp-role" onchange="window.location.href='<?php echo e(route('roles.index')); ?>?role=' + this.value">
        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($role->id); ?>" @selected($role->id === $activeRole->id)><?php echo e($role->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>

    <?php $activePermissionIds = $activeRole->permissions->pluck('id')->all(); ?>

    <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module => $modulePermissions): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="list-card" style="display:block;">
        <span class="list-card__title"><?php echo e(ucfirst($module)); ?></span>
        <div style="margin-top:10px; display:flex; flex-direction:column; gap:10px;">
          <?php $__currentLoopData = $modulePermissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <label class="form-check">
              <input type="checkbox" name="permissions[]" value="<?php echo e($perm->id); ?>" @checked(in_array($perm->id, $activePermissionIds))>
              <?php echo e($perm->name); ?>

            </label>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan Akses</button>
  </form>
  <?php else: ?>
    <p class="empty-state">Belum ada role. Tambahkan role langsung lewat database.</p>
  <?php endif; ?>
</section>

<p class="demo-note">Centang menentukan menu &amp; aksi apa saja yang boleh diakses role tersebut.</p>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
  if (window.location.search.includes('role=') || window.location.hash === '#atur') {
    document.addEventListener('DOMContentLoaded', () => {
      switchView('viewForm', document.querySelector('[data-tab-target="viewForm"]'));
    });
  }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\taniraya-erp-laravel\resources\views/roles/index.blade.php ENDPATH**/ ?>