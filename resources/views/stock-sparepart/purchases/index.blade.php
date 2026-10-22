@extends('layouts.app')

@section('title', 'Pembelian — TaniRaya ERP')
@section('page-title', 'Pembelian')
@section('page-subtitle', 'Transaksi pembelian sparepart')

@section('content')

@php
  $iEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
@endphp

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Riwayat</button>
  @permission('purchase.create')
  <button class="tab-btn" data-tab-target="viewForm">+ Catat Pembelian</button>
  @endpermission
</div>

<section id="viewList" class="view is-active">
  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari nama sparepart...'])

  @if (request('date') === 'today')
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12.5px; color:var(--color-text-muted);">
      <span>Menampilkan pembelian hari ini saja</span>
      <a href="{{ route('purchases.index') }}" class="section__link">Lihat semua →</a>
    </div>
  @endif

  <div class="table-wrap">
    <table class="data-table data-table--wide">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Sparepart</th>
          <th class="num">Qty</th>
          <th class="num">Total</th>
          <th>Supplier</th>
          <th>Diterima di</th>
          <th>Kebun Tujuan</th>
          <th>Keterangan</th>
          <th class="col-aksi">Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($purchases as $p)
        @php
          $note = collect([
            $p->invoice_number ? 'Invoice '.$p->invoice_number : null,
            $p->preorder ? 'Pre-order '.$p->preorder->code : null,
          ])->filter()->implode(' · ');
        @endphp
        <tr>
          <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($p->purchase_date)->format('d M Y') }}</td>
          <td>{{ $p->sparepart->name ?? '-' }}</td>
          <td class="num">{{ $p->quantity }}</td>
          <td class="num">Rp{{ number_format($p->total_price, 0, ',', '.') }}</td>
          <td>{{ $p->supplier->name ?? '-' }}</td>
          <td>{{ $p->receivedGarden->name ?? '-' }}</td>
          <td>{{ $p->targetGarden->name ?? '-' }}</td>
          <td>{{ $note ?: '-' }}</td>
          <td class="col-aksi">
            <div class="action-group">
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="showDetailModal(this)"
                data-detail-title="Detail Pembelian"
                data-detail="{{ json_encode([
                  'Sparepart' => $p->sparepart->name ?? '-',
                  'Jumlah' => $p->quantity,
                  'Harga Satuan' => 'Rp'.number_format($p->unit_price, 0, ',', '.'),
                  'Total' => 'Rp'.number_format($p->total_price, 0, ',', '.'),
                  'Supplier' => $p->supplier->name ?? '-',
                  'No. Invoice' => $p->invoice_number ?? '-',
                  'Tanggal' => \Carbon\Carbon::parse($p->purchase_date)->format('d M Y'),
                  'Diterima di' => $p->receivedGarden->name ?? '-',
                  'Kebun Tujuan' => $p->targetGarden->name ?? '-',
                  'Terkait Pre-Order' => $p->preorder->code ?? '-',
                  'Dicatat Oleh' => $p->recorder->name ?? '-',
                ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iEye !!}</button>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="9" class="empty-state">Belum ada riwayat pembelian.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @include('partials.pagination', ['paginator' => $purchases])
</section>

@permission('purchase.create')
<section id="viewForm" class="view">
<form method="POST" action="{{ route('purchases.store') }}" data-safe-form="purchase">
    @csrf

    <div class="form-group">
      <label class="form-label" for="pb-preorder">Terkait Pre-Order (opsional)</label>
      <select class="form-control" id="pb-preorder" name="preorder_id">
        <option value="">— Tidak terkait pre-order —</option>
        @foreach ($approvedPreorders as $po)
          <option value="{{ $po->id }}" data-sparepart="{{ $po->sparepart_id }}" data-garden="{{ $po->garden_id }}" data-remaining="{{ $po->remaining_quantity }}" @selected(old('preorder_id') == $po->id)>{{ $po->sparepart->name ?? '-' }} — sisa {{ $po->remaining_quantity }} ({{ $po->code }})</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="pb-sparepart">Sparepart</label>
      <select class="form-control" id="pb-sparepart" name="sparepart_id" required>
        @foreach ($spareparts as $sp)
          <option value="{{ $sp->id }}" {{ old('sparepart_id') == $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="pb-qty">Jumlah</label>
        <input class="form-control" id="pb-qty" name="quantity" type="number" min="1" value="{{ old('quantity') }}" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="pb-harga">Harga Satuan (Rp)</label>
        <input class="form-control" id="pb-harga" name="unit_price" type="text" inputmode="numeric" data-currency autocomplete="off" value="{{ old('unit_price') }}" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="pb-supplier">Supplier</label>
      <select class="form-control" id="pb-supplier" name="supplier_id" required>
        @foreach ($suppliers as $s)
          <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="pb-garden">Diterima di</label>
      <select class="form-control" id="pb-garden" name="garden_id" required>
        <option value="">Pilih lokasi penerimaan…</option>
        @foreach ($gardens as $g)
          <option value="{{ $g->id }}" {{ old('garden_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="pb-target">Kebun Tujuan</label>
      <select class="form-control" id="pb-target" name="target_garden_id" required>
        <option value="">Pilih kebun tujuan…</option>
        @foreach ($gardens as $g)
          <option value="{{ $g->id }}" {{ old('target_garden_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="pb-invoice">No. PO / Invoice</label>
        <input class="form-control" id="pb-invoice" name="invoice_number" value="{{ old('invoice_number') }}" placeholder="INV-2026-XXXX">
      </div>
      <div class="form-group">
        <label class="form-label" for="pb-tanggal">Tanggal</label>
        <input class="form-control" id="pb-tanggal" name="purchase_date" type="date" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan Pembelian</button>
  </form>
</section>
@endpermission

@endsection

@section('scripts')
<script>
(function () {
  var po = document.getElementById('pb-preorder');
  var sp = document.getElementById('pb-sparepart');
  var tg = document.getElementById('pb-target');
  var qty = document.getElementById('pb-qty');
  if (!po || !sp || !tg || !qty) return;

  function lock(el, locked) {
    el.style.pointerEvents = locked ? 'none' : '';
    el.style.background = locked ? 'var(--color-border)' : '';
    el.tabIndex = locked ? -1 : 0;
  }

  function sync() {
    var opt = po.options[po.selectedIndex];
    var has = po.value !== '';

    if (has) {
      if (opt.dataset.sparepart) sp.value = opt.dataset.sparepart;
      if (opt.dataset.garden) tg.value = opt.dataset.garden;
      qty.max = opt.dataset.remaining;
      if (!qty.value) qty.value = opt.dataset.remaining;
    } else {
      qty.removeAttribute('max');
    }

    lock(sp, has);
    lock(tg, has && !!(opt && opt.dataset.garden));
  }

  po.addEventListener('change', sync);
  document.addEventListener('DOMContentLoaded', sync); // jalan setelah draf dipulihkan
  sync();
})();
</script>
@endsection