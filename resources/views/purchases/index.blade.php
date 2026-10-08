@extends('layouts.app')

@section('title', 'Pembelian — TaniRaya ERP')
@section('page-title', 'Pembelian')
@section('page-subtitle', 'Transaksi pembelian sparepart')

@section('content')

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

  <div class="list-view is-active">
  @forelse ($purchases as $p)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $p->sparepart->name ?? '-' }} × {{ $p->quantity }}</span>
        <span class="list-card__meta">{{ $p->supplier->name ?? '-' }} &middot; {{ $p->invoice_number ?? '-' }} &middot; {{ \Carbon\Carbon::parse($p->purchase_date)->format('d M Y') }}</span>
      </div>
      <div class="list-card__side">
        <span class="list-card__value">Rp{{ number_format($p->total_price, 0, ',', '.') }}</span>
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
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
            'Terkait Pre-Order' => $p->preorder->code ?? '-',
            'Dicatat Oleh' => $p->recorder->name ?? '-',
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}">Detail</button>
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada riwayat pembelian.</p>
  @endforelse
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
          <option value="{{ $po->id }}" @selected(old('preorder_id') == $po->id)>{{ $po->sparepart->name ?? '-' }} — sisa {{ $po->remaining_quantity }} ({{ $po->code }})</option>
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
        <input class="form-control" id="pb-harga" name="unit_price" type="number" min="0" value="{{ old('unit_price') }}" required>
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
