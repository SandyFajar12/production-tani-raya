<?php $__env->startSection('title', 'Update Stok — TaniRaya ERP'); ?>
<?php $__env->startSection('page-title', 'Update Stok'); ?>
<?php $__env->startSection('page-subtitle', 'Stok terkini & penyesuaian'); ?>

<?php $__env->startSection('content'); ?>

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Stok Terkini</button>
  <?php if (\Illuminate\Support\Facades\Blade::check('permission', 'stock.adjust')): ?>
  <button class="tab-btn" data-tab-target="viewForm">+ Penyesuaian Stok</button>
  <?php endif; ?>
</div>

<div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
  <a href="<?php echo e(route('stocks.ledger')); ?>" class="section__link">Lihat Kartu Stok (riwayat) →</a>
</div>

<section id="viewList" class="view is-active">
  <?php echo $__env->make('partials.list-toolbar', ['searchPlaceholder' => 'Cari kode / nama sparepart...'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

  <div class="list-view is-active">
  <?php $__empty_1 = true; $__currentLoopData = $spareparts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title"><?php echo e($sp->name); ?></span>
        <span class="list-card__meta"><?php echo e($sp->code); ?> &middot; Min. stok <?php echo e($sp->min_stock); ?> <?php echo e($sp->unit->name ?? ''); ?></span>
      </div>
      <div class="list-card__side">
        <span class="list-card__value"><?php echo e($sp->current_stock); ?> <?php echo e($sp->unit->name ?? ''); ?></span>
        <span class="badge badge--<?php echo e($sp->isLowStock() ? 'pending' : 'active'); ?>"><?php echo e($sp->isLowStock() ? 'Menipis' : 'Aman'); ?></span>
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px; margin-top:4px;"
          onclick="showDetailModal(this)"
          data-detail-title="Detail Stok"
          data-detail="<?php echo e(json_encode([
            'Kode' => $sp->code,
            'Nama' => $sp->name,
            'Kategori' => $sp->category->name ?? '-',
            'Dimensi' => $sp->dimension ?? '-',
            'Stok Saat Ini' => $sp->current_stock.' '.($sp->unit->name ?? ''),
            'Stok Minimum' => $sp->min_stock.' '.($sp->unit->name ?? ''),
            'Status' => $sp->isLowStock() ? 'Menipis' : 'Aman',
          ], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">Detail</button>
      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p class="empty-state">Belum ada data sparepart.</p>
  <?php endif; ?>
  </div>

  <?php echo $__env->make('partials.pagination', ['paginator' => $spareparts], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</section>

<?php if (\Illuminate\Support\Facades\Blade::check('permission', 'stock.adjust')): ?>
<section id="viewForm" class="view">
<form method="POST" action="<?php echo e(route('stocks.adjust')); ?>" data-safe-form="stock">
    <?php echo csrf_field(); ?>

    <div class="form-group">
      <label class="form-label" for="us-sparepart">Sparepart</label>
      <select class="form-control" id="us-sparepart" name="sparepart_id" required>
        <option value="">Pilih sparepart…</option>
        <?php $__currentLoopData = $spareparts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($sp->id); ?>" <?php echo e(old('sparepart_id') == $sp->id ? 'selected' : ''); ?>><?php echo e($sp->name); ?> — stok sistem: <?php echo e($sp->current_stock); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="us-jenis">Jenis Penyesuaian</label>
        <select class="form-control" id="us-jenis" name="type">
          <option value="opname">Stok Opname (koreksi)</option>
          <option value="damaged_lost">Barang Rusak / Hilang</option>
          <option value="return_to_supplier">Retur ke Supplier</option>
          <option value="other">Lainnya</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="us-qty">Stok Fisik Aktual</label>
        <input class="form-control" id="us-qty" name="physical_qty" type="number" min="0" value="<?php echo e(old('physical_qty')); ?>" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="us-alasan">Alasan Penyesuaian</label>
      <textarea class="form-control" id="us-alasan" name="reason" rows="3" placeholder="contoh: selisih hasil stok opname bulanan" required><?php echo e(old('reason')); ?></textarea>
    </div>

    <div class="form-group">
      <label class="form-label">Foto Bukti (Opsional)</label>
      <button type="button" class="btn btn-secondary btn-block" id="btnStartCamera">📷 Aktifkan Kamera</button>
      <video id="cameraVideo" style="width:100%; border-radius:12px; margin-top:8px; display:none;" autoplay playsinline></video>
      <button type="button" class="btn btn-primary btn-block" id="btnCapture" style="display:none; margin-top:8px;">Jepret</button>
      <canvas id="cameraCanvas" style="display:none;"></canvas>
      <img id="photoPreview" style="width:100%; border-radius:12px; margin-top:8px; display:none;">
      <button type="button" class="btn btn-secondary btn-block" id="btnRetake" style="display:none; margin-top:8px;">Ambil Ulang</button>
      <input type="hidden" name="photo_data" id="photoDataInput">
      <input type="hidden" name="latitude" id="latitudeInput">
      <input type="hidden" name="longitude" id="longitudeInput">
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan Penyesuaian</button>
  </form>
</section>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
(function () {
  const startBtn = document.getElementById('btnStartCamera');
  const captureBtn = document.getElementById('btnCapture');
  const retakeBtn = document.getElementById('btnRetake');
  const video = document.getElementById('cameraVideo');
  const canvas = document.getElementById('cameraCanvas');
  const preview = document.getElementById('photoPreview');
  const photoInput = document.getElementById('photoDataInput');
  const latInput = document.getElementById('latitudeInput');
  const lngInput = document.getElementById('longitudeInput');

  let stream = null, currentLat = null, currentLng = null;

  startBtn?.addEventListener('click', async () => {
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
      video.srcObject = stream;
      video.style.display = 'block';
      captureBtn.style.display = 'block';
      startBtn.style.display = 'none';

      navigator.geolocation.getCurrentPosition((pos) => {
        currentLat = pos.coords.latitude;
        currentLng = pos.coords.longitude;
      }, () => showToast('Lokasi tidak diizinkan, foto tetap bisa diambil tanpa GPS.'));
    } catch (err) {
      showToast('Tidak bisa akses kamera: ' + err.message);
    }
  });

  captureBtn?.addEventListener('click', () => {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    const stamp = new Date().toLocaleString('id-ID');
    const coordText = (currentLat && currentLng)
      ? `Lat: ${currentLat.toFixed(6)}, Long: ${currentLng.toFixed(6)}`
      : 'Lokasi tidak tersedia';

    ctx.fillStyle = 'rgba(0,0,0,0.55)';
    ctx.fillRect(0, canvas.height - 60, canvas.width, 60);
    ctx.fillStyle = '#fff';
    ctx.font = '16px sans-serif';
    ctx.fillText(stamp, 12, canvas.height - 36);
    ctx.fillText(coordText, 12, canvas.height - 14);

    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
    photoInput.value = dataUrl;
    latInput.value = currentLat ?? '';
    lngInput.value = currentLng ?? '';

    preview.src = dataUrl;
    preview.style.display = 'block';
    video.style.display = 'none';
    captureBtn.style.display = 'none';
    retakeBtn.style.display = 'block';

    stream?.getTracks().forEach((t) => t.stop());
  });

  retakeBtn?.addEventListener('click', () => {
    preview.style.display = 'none';
    retakeBtn.style.display = 'none';
    photoInput.value = '';
    startBtn.style.display = 'block';
  });
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/vol15_6/infinityfree.com/if0_42998193/htdocs/resources/views/stocks/index.blade.php ENDPATH**/ ?>