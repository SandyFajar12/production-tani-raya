<?php if($paginator->hasPages()): ?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; padding-top:12px; border-top:1px solid var(--color-border);">
  <?php if($paginator->onFirstPage()): ?>
    <span class="btn btn-sm btn-secondary" style="width:auto; opacity:0.5;">‹ Sebelumnya</span>
  <?php else: ?>
    <a href="<?php echo e($paginator->previousPageUrl()); ?>" class="btn btn-sm btn-secondary" style="width:auto;">‹ Sebelumnya</a>
  <?php endif; ?>

  <span style="font-size:12.5px; color:var(--color-text-muted); font-weight:600;">
    Hal <?php echo e($paginator->currentPage()); ?> / <?php echo e($paginator->lastPage()); ?> &middot; <?php echo e($paginator->total()); ?> data
  </span>

  <?php if($paginator->hasMorePages()): ?>
    <a href="<?php echo e($paginator->nextPageUrl()); ?>" class="btn btn-sm btn-secondary" style="width:auto;">Berikutnya ›</a>
  <?php else: ?>
    <span class="btn btn-sm btn-secondary" style="width:auto; opacity:0.5;">Berikutnya ›</span>
  <?php endif; ?>
</div>
<?php endif; ?><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/partials/pagination.blade.php ENDPATH**/ ?>