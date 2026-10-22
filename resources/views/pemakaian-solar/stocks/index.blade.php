@extends('layouts.app')

@section('title', 'Stok Solar — TaniRaya ERP')
@section('page-title', 'Stok Solar')
@section('page-subtitle', 'Stok solar per kebun & penyesuaian')
@section('back-url', route('fuel.index'))

@section('content')

@php
  $iEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  $fmt = fn ($v) => \App\Models\PemakaianSolar\Stock::fmt($v);
@endphp

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Stok Terkini</button>
  @permission('fuel_stock.adjust')
  <button class="tab-btn" data-tab-target="viewForm">+ Penyesuaian / Transfer</button>
  <button class="tab-btn" data-tab-target="viewMin">Atur Stok Minimum</button>
  @endpermission
</div>

@permission('fuel_stock.ledger')
<div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
  <a href="{{ route('solar.stocks.ledger') }}" class="section__link">Lihat Kartu Stok (riwayat) →</a>
</div>
@endpermission

<section id="viewList" class="view is-active">
  @if (request('low_stock'))
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12.5px; color:var(--color-text-muted);">
      <span>Menampilkan kebun dengan stok menipis saja</span>
      <a href="{{ route('solar.stocks.index') }}" class="section__link">Lihat semua →</a>
    </div>
  @endif
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Kebun</th>
          <th class="num">Stok Solar</th>
          <th class="num">Stok Min.</th>
          <th>Keterangan</th>
          <th class="col-aksi">Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($gardens as $g)
        @php
          $st = $stocks->get($g->id);
          $qty = (float) ($st->quantity ?? 0);
          $min = (float) ($st->min_stock ?? 0);
          if ($qty <= 0) { $label = 'Habis'; $badge = 'rejected'; }
          elseif ($min > 0 && $qty <= $min) { $label = 'Menipis'; $badge = 'pending'; }
          else { $label = 'Aman'; $badge = 'active'; }

          $detail = [
            'Kebun' => $g->name,
            'Stok Solar' => $fmt($qty).' L',
            'Stok Minimum' => $min > 0 ? $fmt($min).' L' : 'Belum diatur',
            'Status' => $label,
          ];
        @endphp
        <tr>
          <td>{{ $g->name }}@if (! $g->is_active) <span class="badge badge--inactive">Nonaktif</span>@endif</td>
          <td class="num">{{ $fmt($qty) }} L</td>
          <td class="num">{{ $min > 0 ? $fmt($min).' L' : '-' }}</td>
          <td><span class="badge badge--{{ $badge }}">{{ $label }}</span></td>
          <td class="col-aksi">
            <div class="action-group">
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="showDetailModal(this)"
                data-detail-title="Detail Stok Solar"
                data-detail="{{ json_encode($detail, JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iEye !!}</button>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Belum ada data kebun.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</section>

@permission('fuel_stock.adjust')
<section id="viewMin" class="view">
  <form method="POST" action="{{ route('solar.stocks.minimum') }}" data-safe-form="solar-min-stock" data-no-draft>
    @csrf

    <div class="form-group">
      <label class="form-label" for="mn-garden">Kebun</label>
      <select class="form-control" id="mn-garden" name="garden_id" required>
        <option value="">Pilih kebun…</option>
        @foreach ($formGardens as $g)
          <option value="{{ $g->id }}">{{ $g->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="mn-min">Stok Minimum (liter)</label>
      <input class="form-control" id="mn-min" name="min_stock" type="number" min="0" step="0.01" inputmode="decimal" required>
    </div>

    <p class="demo-note" style="text-align:left; margin:0 0 14px;">Isi 0 untuk mematikan notifikasi stok menipis di kebun ini. Notifikasi dikirim ke Admin Pusat dan Owner saat stok solar turun sampai batas ini. Nilai yang sedang berlaku terlihat di kolom Stok Min. pada daftar.</p>

    <button type="submit" class="btn btn-primary btn-block">Simpan Stok Minimum</button>
  </form>
</section>

<section id="viewForm" class="view">
<form method="POST" action="{{ route('solar.stocks.adjust') }}" data-safe-form="solar-stock" id="stockForm">
    @csrf

    <div class="form-group">
      <label class="form-label" for="us-jenis">Jenis Penyesuaian</label>
      <select class="form-control" id="us-jenis" name="type">
        <option value="opname" {{ old('type') === 'opname' ? 'selected' : '' }}>Stok Opname (koreksi / stok awal)</option>
        <option value="damaged_lost" {{ old('type') === 'damaged_lost' ? 'selected' : '' }}>Rusak / Hilang / Tumpah</option>
        <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Lainnya</option>
        <option value="transfer" {{ old('type') === 'transfer' ? 'selected' : '' }}>Transfer Stok (kirim ke kebun lain)</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" id="gardenLabel" for="us-garden">Kebun</label>
      <select class="form-control" id="us-garden" name="garden_id" required>
        <option value="">Pilih kebun…</option>
        @foreach ($formGardens as $g)
          <option value="{{ $g->id }}" {{ old('garden_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
      </select>
      <span id="stockHint" style="display:block; margin-top:6px; font-size:12px; color:var(--color-text-muted);"></span>
    </div>

    <div class="form-group" id="toGardenWrap" style="display:none;">
      <label class="form-label" for="us-togarden">Kebun Tujuan</label>
      <select class="form-control" id="us-togarden" name="to_garden_id">
        <option value="">Pilih kebun tujuan…</option>
        @foreach ($formGardens as $g)
          <option value="{{ $g->id }}" {{ old('to_garden_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" id="qtyLabel" for="us-qty">Stok Fisik Aktual (liter)</label>
      <input class="form-control" id="us-qty" name="physical_qty" type="number" min="0" step="0.01" inputmode="decimal" value="{{ old('physical_qty') }}" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="us-alasan">Alasan / Catatan</label>
      <textarea class="form-control" id="us-alasan" name="reason" rows="3" placeholder="contoh: stok awal, selisih hasil cek tangki, atau kirim ke kebun lain" required>{{ old('reason') }}</textarea>
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

    <button type="submit" class="btn btn-primary btn-block">Simpan</button>
  </form>
</section>
@endpermission

@endsection

@section('scripts')
@permission('fuel_stock.adjust')
<script>
const STOCKS = @json($stockData);

(function () {
  const form = document.getElementById('stockForm');
  if (!form) return;

  const type = form.querySelector('[name="type"]');
  const garden = form.querySelector('[name="garden_id"]');
  const toWrap = document.getElementById('toGardenWrap');
  const toSel = form.querySelector('[name="to_garden_id"]');
  const gardenLabel = document.getElementById('gardenLabel');
  const qtyLabel = document.getElementById('qtyLabel');
  const qtyInput = form.querySelector('[name="physical_qty"]');
  const hint = document.getElementById('stockHint');

  function stockOf(gid) {
    return gid ? parseFloat(STOCKS[gid] || 0) : null;
  }

  function fmt(n) {
    return (Math.round(n * 100) / 100).toString().replace('.', ',');
  }

  function refresh() {
    const isTransfer = type.value === 'transfer';
    toWrap.style.display = isTransfer ? 'block' : 'none';
    toSel.required = isTransfer;
    gardenLabel.textContent = isTransfer ? 'Kebun Asal' : 'Kebun';
    qtyLabel.textContent = isTransfer ? 'Jumlah Dikirim (liter)' : 'Stok Fisik Aktual (liter)';
    qtyInput.min = isTransfer ? 0.01 : 0;

    // Kebun tujuan tidak boleh sama dengan kebun asal
    Array.prototype.forEach.call(toSel.options, function (o) { o.disabled = !!o.value && o.value === garden.value; });
    if (toSel.value && toSel.value === garden.value) toSel.value = '';

    // Info stok di kebun terpilih, dan batas jumlah kirim = stok di kebun asal
    const stock = stockOf(garden.value);
    hint.textContent = stock === null ? '' : 'Stok solar saat ini di kebun ini: ' + fmt(stock) + ' L';

    qtyInput.removeAttribute('max');
    if (isTransfer && stock !== null) qtyInput.max = stock;
  }

  type.addEventListener('change', refresh);
  garden.addEventListener('change', refresh);

  document.addEventListener('DOMContentLoaded', refresh); // jalan setelah draf dipulihkan
  refresh();
})();

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
@endpermission
@endsection