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
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada data sparepart.</p>
  @endforelse
</section>

<section id="viewForm" class="view">
  <form method="POST" action="{{ route('stocks.adjust') }}">
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

    <button type="submit" class="btn btn-primary btn-block">Simpan Penyesuaian</button>
  </form>
</section>

@endsection
