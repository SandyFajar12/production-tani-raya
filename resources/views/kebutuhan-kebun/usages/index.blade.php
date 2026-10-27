@extends('layouts.app')

@section('title', 'Pemakaian Kebutuhan — TaniRaya ERP')
@section('page-title', 'Pemakaian')
@section('page-subtitle', 'Riwayat & catat pemakaian kebutuhan kebun')
@section('back-url', route('needs.index'))

@section('content')

@php
  $iEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  $itemData = $items->map(fn ($s) => [
    'id' => $s->id,
    'name' => $s->name,
    'stocks' => $s->gardenStocks->pluck('quantity', 'garden_id')->all(),
  ])->values();
@endphp

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Riwayat</button>
  @permission('need_usage.create')
  <button class="tab-btn" data-tab-target="viewForm">+ Catat Pemakaian</button>
  @endpermission
</div>

<section id="viewList" class="view is-active">
  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari nama item...'])

  @if (request('date') === 'today')
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12.5px; color:var(--color-text-muted);">
      <span>Menampilkan pemakaian hari ini saja</span>
      <a href="{{ route('kebun.usages.index') }}" class="section__link">Lihat semua →</a>
    </div>
  @endif

  <div class="table-wrap">
    <table class="data-table data-table--wide">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Item</th>
          <th>Kebun</th>
          <th class="num">Qty</th>
          <th>Dicatat Oleh</th>
          <th>Keterangan</th>
          <th class="col-aksi">Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($usages as $u)
        <tr>
          <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($u->usage_date)->format('d M Y') }}</td>
          <td>{{ $u->item->name ?? '-' }}</td>
          <td>{{ $u->garden->name ?? '-' }}</td>
          <td class="num">−{{ $u->quantity }} {{ $u->item->unit->name ?? '' }}</td>
          <td>{{ $u->recorder->name ?? '-' }}</td>
          <td>{{ $u->notes ?: '-' }}</td>
          <td class="col-aksi">
            <div class="action-group">
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="showDetailModal(this)"
                data-detail-title="Detail Pemakaian"
                data-detail="{{ json_encode([
                  'Item' => $u->item->name ?? '-',
                  'Jumlah' => $u->quantity.' '.($u->item->unit->name ?? ''),
                  'Lokasi Kebun' => $u->garden->name ?? '-',
                  'Tanggal' => \Carbon\Carbon::parse($u->usage_date)->format('d M Y'),
                  'Catatan' => $u->notes ?? '-',
                  'Dicatat Oleh' => $u->recorder->name ?? '-',
                ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iEye !!}</button>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="empty-state">Belum ada riwayat pemakaian.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @include('partials.pagination', ['paginator' => $usages])
</section>

@permission('need_usage.create')
<section id="viewForm" class="view">
<form method="POST" action="{{ route('kebun.usages.store') }}" data-safe-form="kebun-usage" id="usageForm">
    @csrf

    <div class="form-group">
      <label class="form-label" for="pk-garden">Lokasi Kebun (stok diambil dari sini)</label>
      <select class="form-control" id="pk-garden" name="garden_id" required>
        <option value="">Pilih kebun…</option>
        @foreach ($gardens as $g)
          <option value="{{ $g->id }}" {{ old('garden_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="pk-item">Item <span style="font-weight:500;">(angka = stok di kebun terpilih)</span></label>
      <select class="form-control" id="pk-item" name="item_id" required>
        <option value="">Pilih item…</option>
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="pk-qty">Jumlah Dipakai</label>
        <input class="form-control" id="pk-qty" name="quantity" type="number" min="1" value="{{ old('quantity') }}" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="pk-tanggal">Tanggal</label>
        <input class="form-control" id="pk-tanggal" name="usage_date" type="date" value="{{ old('usage_date', date('Y-m-d')) }}" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="pk-catatan">Catatan</label>
      <textarea class="form-control" id="pk-catatan" name="notes" rows="3" placeholder="opsional, contoh: pemupukan blok A">{{ old('notes') }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan Pemakaian</button>
  </form>
</section>
@endpermission

@endsection

@section('scripts')
<script>
const ITEMS = @json($itemData);
let oldItem = @json((string) old('item_id'));

(function () {
  const form = document.getElementById('usageForm');
  if (!form) return;

  const garden = form.querySelector('[name="garden_id"]');
  const sp = form.querySelector('[name="item_id"]');
  const qty = form.querySelector('[name="quantity"]');

  function stockOf(item, gid) {
    if (!gid) return null;
    return parseInt((item.stocks || {})[gid] || 0, 10);
  }

  // Tampilkan stok di kebun terpilih, dan sembunyikan item yang stoknya 0 (tidak bisa dipakai)
  function rebuild() {
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
      if (gid && !q) return;
      const o = document.createElement('option');
      o.value = item.id;
      o.textContent = q === null ? item.name : item.name + ' (' + q + ')';
      sp.appendChild(o);
    });

    sp.value = String(current);
    if (sp.value !== String(current)) sp.value = '';
  }

  function refresh() {
    qty.removeAttribute('max');
    if (sp.value && garden.value) {
      const item = ITEMS.find(function (s) { return String(s.id) === String(sp.value); });
      if (item) qty.max = stockOf(item, garden.value);
    }
  }

  function all() { rebuild(); refresh(); }

  garden.addEventListener('change', all);
  sp.addEventListener('change', refresh);
  document.addEventListener('DOMContentLoaded', all); // jalan setelah draf dipulihkan
  all();
})();
</script>
@endsection