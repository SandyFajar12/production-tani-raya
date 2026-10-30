@extends('layouts.app')

@section('title', 'Update Stok Kebutuhan — TaniRaya ERP')
@section('page-title', 'Update Stok')
@section('page-subtitle', 'Stok kebutuhan kebun per kebun & penyesuaian')
@section('back-url', route('needs.index'))

@section('content')

@php
  $iEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  $itemData = $formItems->map(function ($s) {
    return ['id' => $s->id, 'name' => $s->name, 'stocks' => $s->gardenStocks->pluck('quantity', 'garden_id')->all()];
  })->values();
@endphp

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Stok Terkini</button>
  @permission('need_stock.adjust')
  <button class="tab-btn" data-tab-target="viewForm">+ Penyesuaian / Transfer</button>
  @endpermission
</div>

@permission('need_stock.ledger')
<div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
  <a href="{{ route('kebun.stocks.ledger') }}" class="section__link">Lihat Kartu Stok (riwayat) →</a>
</div>
@endpermission

<section id="viewList" class="view is-active">
  <div class="form-group">
    <label class="form-label" for="st-garden">Kebun</label>
    <select class="form-control" id="st-garden" onchange="window.location.href = updateQueryParam('garden_id', this.value)">
      <option value="">Semua Kebun</option>
      @foreach ($allGardens as $g)
        <option value="{{ $g->id }}" {{ (string) request('garden_id') === (string) $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
      @endforeach
    </select>
  </div>

  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari kode / item / kebun...'])

  @if (request('low_stock'))
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12.5px; color:var(--color-text-muted);">
      <span>Menampilkan stok yang menipis atau habis saja</span>
      <a href="{{ route('kebun.stocks.index') }}" class="section__link">Lihat semua →</a>
    </div>
  @endif

  <div class="table-wrap">
    <table class="data-table data-table--wide">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Item</th>
          <th>Kebun</th>
          <th class="num">Stok</th>
          <th class="num">Stok Min.</th>
          <th>Keterangan</th>
          <th class="col-aksi">Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($stocks as $row)
        @php
          $it = $row->item;
          $unit = $it->unit->name ?? '';
          $min = (int) $it->min_stock;
          $qty = (int) $row->quantity;
          if ($qty <= 0) { $label = 'Habis'; $badge = 'rejected'; }
          elseif ($qty <= $min) { $label = 'Menipis'; $badge = 'pending'; }
          else { $label = 'Aman'; $badge = 'active'; }

          $detail = [
            'Kode' => $it->code,
            'Nama' => $it->name,
            'Kategori' => $it->category->name ?? '-',
          ] + $it->stockDetail() + [
            'Stok Minimum' => $min.' '.$unit.' per kebun',
            'Status di '.($row->garden->name ?? '-') => $label,
          ];
        @endphp
        <tr>
          <td style="white-space:nowrap;">{{ $it->code }}</td>
          <td>{{ $it->name }}</td>
          <td>{{ $row->garden->name ?? '-' }}</td>
          <td class="num">{{ $qty }} {{ $unit }}</td>
          <td class="num">{{ $min }} {{ $unit }}</td>
          <td><span class="badge badge--{{ $badge }}">{{ $label }}</span></td>
          <td class="col-aksi">
            <div class="action-group">
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="showDetailModal(this)"
                data-detail-title="Detail Stok"
                data-detail="{{ json_encode($detail, JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iEye !!}</button>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="empty-state">Belum ada data stok.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @include('partials.pagination', ['paginator' => $stocks])
</section>

@permission('need_stock.adjust')
<section id="viewForm" class="view">
<form method="POST" action="{{ route('kebun.stocks.adjust') }}" data-safe-form="kebun-stock" id="stockForm">
    @csrf

    <div class="form-group">
      <label class="form-label" for="us-jenis">Jenis Penyesuaian</label>
      <select class="form-control" id="us-jenis" name="type">
        <option value="opname" {{ old('type') === 'opname' ? 'selected' : '' }}>Stok Opname (koreksi / stok awal)</option>
        <option value="damaged_lost" {{ old('type') === 'damaged_lost' ? 'selected' : '' }}>Barang Rusak / Hilang</option>
        <option value="return_to_supplier" {{ old('type') === 'return_to_supplier' ? 'selected' : '' }}>Retur ke Supplier</option>
        <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Lainnya</option>
        <option value="transfer" {{ old('type') === 'transfer' ? 'selected' : '' }}>Transfer Stok (kirim ke kebun lain)</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" id="gardenLabel" for="us-garden">Kebun</label>
      <select class="form-control" id="us-garden" name="garden_id" required>
        <option value="">Pilih kebun…</option>
        @foreach ($gardens as $g)
          <option value="{{ $g->id }}" {{ old('garden_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group" id="toGardenWrap" style="display:none;">
      <label class="form-label" for="us-togarden">Kebun Tujuan</label>
      <select class="form-control" id="us-togarden" name="to_garden_id">
        <option value="">Pilih kebun tujuan…</option>
        @foreach ($gardens as $g)
          <option value="{{ $g->id }}" {{ old('to_garden_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="us-item">Item <span style="font-weight:500;">(angka = stok di kebun terpilih)</span></label>
      <select class="form-control" id="us-item" name="item_id" required>
        <option value="">Pilih item…</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" id="qtyLabel" for="us-qty">Stok Fisik Aktual</label>
      <input class="form-control" id="us-qty" name="physical_qty" type="number" min="0" value="{{ old('physical_qty') }}" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="us-alasan">Alasan / Catatan</label>
      <textarea class="form-control" id="us-alasan" name="reason" rows="3" placeholder="contoh: stok awal, selisih hasil stok opname bulanan, atau kirim ke kebun lain" required>{{ old('reason') }}</textarea>
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
@permission('need_stock.adjust')
<script>
const ITEMS = @json($itemData);
let oldItem = @json((string) old('item_id'));

(function () {
  const form = document.getElementById('stockForm');
  if (!form) return;

  const type = form.querySelector('[name="type"]');
  const garden = form.querySelector('[name="garden_id"]');
  const toWrap = document.getElementById('toGardenWrap');
  const toSel = form.querySelector('[name="to_garden_id"]');
  const sp = form.querySelector('[name="item_id"]');
  const gardenLabel = document.getElementById('gardenLabel');
  const qtyLabel = document.getElementById('qtyLabel');
  const qtyInput = form.querySelector('[name="physical_qty"]');

  function stockOf(item, gid) {
    if (!gid) return null;
    return parseInt((item.stocks || {})[gid] || 0, 10);
  }

  // Isi ulang dropdown Item: tampilkan stok di kebun terpilih, dan sembunyikan stok 0 untuk transfer
  function rebuild() {
    const isTransfer = type.value === 'transfer';
    const gid = garden.value;
    const current = sp.value || oldItem;
    oldItem = '';

    sp.innerHTML = '';
    const ph = document.createElement('option');
    ph.value = '';
    ph.textContent = 'Pilih item…';
    sp.appendChild(ph);

    ITEMS.forEach(function (item) {
      const q = stockOf(item, gid);
      if (isTransfer && gid && !q) return;
      const o = document.createElement('option');
      o.value = item.id;
      o.textContent = q === null ? item.name : item.name + ' (' + q + ')';
      sp.appendChild(o);
    });

    sp.value = String(current);
    if (sp.value !== String(current)) sp.value = '';
  }

  function refresh() {
    const isTransfer = type.value === 'transfer';
    toWrap.style.display = isTransfer ? 'block' : 'none';
    toSel.required = isTransfer;
    gardenLabel.textContent = isTransfer ? 'Kebun Asal' : 'Kebun';
    qtyLabel.textContent = isTransfer ? 'Jumlah Dikirim' : 'Stok Fisik Aktual';
    qtyInput.min = isTransfer ? 1 : 0;

    // Kebun tujuan tidak boleh sama dengan kebun asal
    Array.prototype.forEach.call(toSel.options, function (o) { o.disabled = !!o.value && o.value === garden.value; });
    if (toSel.value && toSel.value === garden.value) toSel.value = '';

    // Batas jumlah kirim = stok di kebun asal
    qtyInput.removeAttribute('max');
    if (isTransfer && sp.value && garden.value) {
      const item = ITEMS.find(function (s) { return String(s.id) === String(sp.value); });
      if (item) qtyInput.max = stockOf(item, garden.value);
    }
  }

  function all() { rebuild(); refresh(); }

  type.addEventListener('change', all);
  garden.addEventListener('change', all);
  sp.addEventListener('change', refresh);

  document.addEventListener('DOMContentLoaded', all); // jalan setelah draf dipulihkan
  all();
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