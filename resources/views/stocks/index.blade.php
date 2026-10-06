@extends('layouts.app')

@section('title', 'Update Stok — TaniRaya ERP')
@section('page-title', 'Update Stok')
@section('page-subtitle', 'Stok terkini & penyesuaian')

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Stok Terkini</button>
  <button class="tab-btn" data-tab-target="viewForm">+ Penyesuaian Stok</button>
</div>

<div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
  <a href="{{ route('stocks.ledger') }}" class="section__link">Lihat Kartu Stok (riwayat) →</a>
</div>

@include('partials.per-page-selector')

<section id="viewList" class="view list-view is-active">
  @forelse ($spareparts as $sp)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $sp->name }}</span>
        <span class="list-card__meta">{{ $sp->code }} &middot; Min. stok {{ $sp->min_stock }} {{ $sp->unit->name ?? '' }}</span>
      </div>
      <div class="list-card__side">
        <span class="list-card__value">{{ $sp->current_stock }} {{ $sp->unit->name ?? '' }}</span>
        <span class="badge badge--{{ $sp->isLowStock() ? 'pending' : 'active' }}">{{ $sp->isLowStock() ? 'Menipis' : 'Aman' }}</span>
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px; margin-top:4px;"
          onclick="showDetailModal(this)"
          data-detail-title="Detail Stok"
          data-detail="{{ json_encode([
            'Kode' => $sp->code,
            'Nama' => $sp->name,
            'Kategori' => $sp->category->name ?? '-',
            'Dimensi' => $sp->dimension ?? '-',
            'Stok Saat Ini' => $sp->current_stock.' '.($sp->unit->name ?? ''),
            'Stok Minimum' => $sp->min_stock.' '.($sp->unit->name ?? ''),
            'Status' => $sp->isLowStock() ? 'Menipis' : 'Aman',
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}">Detail</button>
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada data sparepart.</p>
  @endforelse
</section>

@include('partials.pagination', ['paginator' => $spareparts])

<section id="viewForm" class="view">
<form method="POST" action="{{ route('stocks.adjust') }}" data-safe-form="stock">
    @csrf

    <div class="form-group">
      <label class="form-label" for="us-sparepart">Sparepart</label>
      <select class="form-control" id="us-sparepart" name="sparepart_id" required>
        <option value="">Pilih sparepart…</option>
        @foreach ($spareparts as $sp)
          <option value="{{ $sp->id }}" {{ old('sparepart_id') == $sp->id ? 'selected' : '' }}>{{ $sp->name }} — stok sistem: {{ $sp->current_stock }}</option>
        @endforeach
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
        <input class="form-control" id="us-qty" name="physical_qty" type="number" min="0" value="{{ old('physical_qty') }}" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="us-alasan">Alasan Penyesuaian</label>
      <textarea class="form-control" id="us-alasan" name="reason" rows="3" placeholder="contoh: selisih hasil stok opname bulanan" required>{{ old('reason') }}</textarea>
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

@endsection

@section('scripts')
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
@endsection
