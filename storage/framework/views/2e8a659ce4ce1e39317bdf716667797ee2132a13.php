<form method="GET" class="list-toolbar">
  <div class="list-toolbar__search">
    <svg class="list-toolbar__search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="search" class="form-control list-toolbar__search-input" placeholder="<?php echo e($searchPlaceholder ?? 'Cari...'); ?>" value="<?php echo e(request('search')); ?>">
  </div>
  <div class="list-toolbar__row2">
    <div class="list-toolbar__perpage">
      <label class="form-label" style="margin:0; white-space:nowrap;">Tampilkan</label>
      <select class="form-control" style="width:auto; height:40px;" name="per_page" onchange="this.form.submit()">
        <?php $__currentLoopData = [10, 20, 50, 100]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($opt); ?>" <?php if (request('per_page', 20) == $opt) echo 'selected'; ?>><?php echo e($opt); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>
    <div style="display:flex; gap:8px;">
      <?php if(request('search')): ?>
        <a href="<?php echo e(request()->url()); ?>" class="btn btn-sm btn-secondary" style="width:auto;">Reset</a>
      <?php endif; ?>
      <button type="submit" class="btn btn-sm btn-primary" style="width:auto;">Cari</button>
    </div>
  </div>
</form><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/partials/list-toolbar.blade.php ENDPATH**/ ?>