@extends('layouts.app')

@section('title', 'Pembelian — TaniRaya ERP')
@section('page-title', 'Pembelian')
@section('page-subtitle', 'Transaksi pembelian sparepart')

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Riwayat</button>
  <button class="tab-btn" data-tab-target="viewForm">+ Catat Pembelian</button>
</div>

<section id="viewList" class="view list-view is-active">
  @forelse ($purchases as $p)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $p->sparepart->name ?? '-' }} × {{ $p->quantity }}</span>
        <span class="list-card__meta">{{ $p->supplier->name ?? '-' }} &middot; {{ $p->invoice_number ?? '-' }} &middot; {{ \Carbon\Carbon::parse($p->purchase_date)->format('d M Y') }}</span>
      </div>
      <div class="list-card__side">
        <span class="list-card__value">Rp{{ number_format($p->total_price, 0, ',', '.') }}</span>
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada riwayat pembelian.</p>
  @endforelse
</section>

<section id="viewForm" class="view">
  <form method="POST" action="{{ route('purchases.store') }}">
    @csrf

    <div class="form-group">
      <label class="form-label" for="pb-preorder">Terkait Pre-Order (opsional)</label>
      <select class="form-control" id="pb-preorder" name="preorder_id">
        <option value="">— Tidak terkait pre-order —</option>
        @foreach ($approvedPreorders as $po)
          <option value="{{ $po->id }}" @selected(old('preorder_id') == $po->id)>{{ $po->sparepart->name ?? '-' }} — {{ $po->quantity }} ({{ $po->code }})</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="pb-sparepart">Sparepart</label>
      <select class="form-control" id="pb-sparepart" name="sparepart_id" required>
        @foreach ($spareparts as $sp)
          <option value="{{ $sp->id }}" @selected(old('sparepart_id') == $sp->id)>{{ $sp->name }}</option>
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
          <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>
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

@endsection
