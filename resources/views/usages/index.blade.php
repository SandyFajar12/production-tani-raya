@extends('layouts.app')

@section('title', 'Pemakaian Sparepart — TaniRaya ERP')
@section('page-title', 'Pemakaian Sparepart')
@section('page-subtitle', 'Riwayat & catat pemakaian')

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Riwayat</button>
  <button class="tab-btn" data-tab-target="viewForm">+ Catat Pemakaian</button>
</div>

<section id="viewList" class="view list-view is-active">
  @forelse ($usages as $u)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $u->sparepart->name ?? '-' }}</span>
        <span class="list-card__meta">{{ $u->fleet->name ?? '-' }} &middot; dicatat oleh {{ $u->recorder->name ?? '-' }} &middot; {{ \Carbon\Carbon::parse($u->usage_date)->format('d M Y') }}</span>
      </div>
      <div class="list-card__side">
        <span class="list-card__value">−{{ $u->quantity }} {{ $u->sparepart->unit->name ?? '' }}</span>
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada riwayat pemakaian.</p>
  @endforelse
</section>

<section id="viewForm" class="view">
  <form method="POST" action="{{ route('usages.store') }}">
    @csrf

    <div class="form-group">
      <label class="form-label" for="pk-sparepart">Sparepart</label>
      <select class="form-control" id="pk-sparepart" name="sparepart_id" required>
        <option value="">Pilih sparepart…</option>
        @foreach ($spareparts as $sp)
          <option value="{{ $sp->id }}" {{ old('sparepart_id') == $sp->id ? 'selected' : '' }}>{{ $sp->name }} (stok: {{ $sp->current_stock }})</option>
        @endforeach
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
      <label class="form-label" for="pk-armada">Unit Armada / Alat</label>
      <select class="form-control" id="pk-armada" name="fleet_id" required>
        @foreach ($fleets as $f)
          <option value="{{ $f->id }}" {{ old('fleet_id') == $f->id ? 'selected' : '' }}>{{ $f->name }} — {{ $f->code }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="pk-catatan">Catatan</label>
      <textarea class="form-control" id="pk-catatan" name="notes" rows="3" placeholder="opsional">{{ old('notes') }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan Pemakaian</button>
  </form>
</section>

@endsection
