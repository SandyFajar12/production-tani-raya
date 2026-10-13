<div style="display:flex; justify-content:flex-end; align-items:center; gap:8px; margin-bottom:12px;">
  <label class="form-label" style="margin:0; white-space:nowrap;">Tampilkan</label>
  <select class="form-control" style="width:auto; height:36px;" onchange="window.location.href = updateQueryParam('per_page', this.value)">
    <?php $__currentLoopData = [10, 20, 50, 100]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <option value="<?php echo e($opt); ?>" <?php if (request('per_page', 20) == $opt) echo 'selected'; ?>><?php echo e($opt); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/partials/per-page-selector.blade.php ENDPATH**/ ?>