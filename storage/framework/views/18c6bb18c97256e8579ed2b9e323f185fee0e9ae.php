<?php $__env->startSection('title', 'Kartu Stok — TaniRaya ERP'); ?>
<?php $__env->startSection('page-title', 'Kartu Stok'); ?>
<?php $__env->startSection('page-subtitle', 'Riwayat pergerakan stok'); ?>
<?php $__env->startSection('back-url', route('stocks.index')); ?>

<?php $__env->startSection('content'); ?>

<div class="form-group">
  <label class="form-label" for="ks-filter">Pilih Sparepart</label>
  <select class="form-control" id="ks-filter" onchange="window.location.href='<?php echo e(route('stocks.ledger')); ?>?sparepart_id=' + this.value">
    <?php $__currentLoopData = $spareparts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <option value="<?php echo e($sp->id); ?>" <?php echo e($selected && $selected->id === $sp->id ? 'selected' : ''); ?>><?php echo e($sp->name); ?> (<?php echo e($sp->code); ?>)</option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>

<section class="section">
  <div class="section__head">
    <h2 class="section__title">Riwayat — <?php echo e($selected->name ?? '-'); ?></h2>
  </div>

  <div class="list-view is-active">
    <?php $__empty_1 = true; $__currentLoopData = $movements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <div class="list-card">
        <div class="list-card__main">
          <span class="list-card__title">
            <?php if($m->type === 'purchase'): ?> Pembelian
            <?php elseif($m->type === 'usage'): ?> Pemakaian
            <?php else: ?> Penyesuaian Stok
            <?php endif; ?>
          </span>
          <span class="list-card__meta"><?php echo e($m->created_at->format('d M Y H:i')); ?></span>
        </div>
        <div class="list-card__side">
          <?php if($m->type === 'purchase'): ?>
            <span class="badge badge--approved">Masuk</span>
          <?php elseif($m->type === 'usage'): ?>
            <span class="badge badge--rejected">Keluar</span>
          <?php else: ?>
            <span class="badge badge--pending">Opname</span>
          <?php endif; ?>
          <span class="list-card__value"><?php echo e($m->quantity_change > 0 ? '+' : ''); ?><?php echo e($m->quantity_change); ?></span>
          <span class="list-card__meta">Saldo: <?php echo e($m->balance_after); ?></span>

          <?php if($m->type === 'adjustment' && $adjustments->has($m->reference_id)): ?>
            <?php $adj = $adjustments[$m->reference_id]; ?>
            <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px; margin-top:4px;"
              onclick="showDetailModal(this)"
              data-detail-title="Detail Penyesuaian Stok"
              data-detail="<?php echo e(json_encode([
                '__photo__' => $adj->photo_path ? route('stocks.photo', $adj->photo_path) : null,
                'Jenis' => ucfirst($adj->type),
                'Stok Sebelum' => $adj->system_qty_before,
                'Stok Fisik' => $adj->physical_qty,
                'Selisih' => $adj->difference,
                'Catatan' => $adj->reason ?? '-',
                'Lokasi' => $adj->location_label ?? '-',
                'Koordinat' => ($adj->latitude && $adj->longitude) ? $adj->latitude.', '.$adj->longitude : '-',
                'Dilakukan Oleh' => $adj->adjuster->name ?? '-',
              ], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">Detail</button>
          <?php elseif($m->type === 'purchase' && $purchases->has($m->reference_id)): ?>
            <?php $pur = $purchases[$m->reference_id]; ?>
            <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px; margin-top:4px;"
              onclick="showDetailModal(this)"
              data-detail-title="Detail Pembelian"
              data-detail="<?php echo e(json_encode([
                'Supplier' => $pur->supplier->name ?? '-',
                'No. Invoice' => $pur->invoice_number ?? '-',
                'Jumlah' => $pur->quantity,
                'Total' => 'Rp'.number_format($pur->total_price, 0, ',', '.'),
                'Dicatat Oleh' => $pur->recorder->name ?? '-',
              ], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">Detail</button>
          <?php elseif($m->type === 'usage' && $usages->has($m->reference_id)): ?>
            <?php $use = $usages[$m->reference_id]; ?>
            <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px; margin-top:4px;"
              onclick="showDetailModal(this)"
              data-detail-title="Detail Pemakaian"
              data-detail="<?php echo e(json_encode([
                'Armada' => $use->fleet->name ?? '-',
                'Jumlah' => $use->quantity,
                'Catatan' => $use->notes ?? '-',
                'Dicatat Oleh' => $use->recorder->name ?? '-',
              ], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">Detail</button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <p class="empty-state">Belum ada riwayat pergerakan stok untuk sparepart ini.</p>
    <?php endif; ?>
  </div>
</section>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/stocks/ledger.blade.php ENDPATH**/ ?>