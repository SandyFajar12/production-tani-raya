@extends('layouts.app')

@section('title', 'Master Sparepart — TaniRaya ERP')
@section('page-title', 'Master Sparepart')
@section('page-subtitle', 'Data induk sparepart')

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Sparepart</button>
  <button class="tab-btn" data-tab-target="viewForm">+ Tambah Sparepart</button>
</div>

<section id="viewList" class="view list-view is-active">
  @forelse ($spareparts as $sp)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $sp->name }}</span>
        <span class="list-card__meta">{{ $sp->code }} &middot; Kategori: {{ $sp->category->name ?? '-' }} &middot; {{ $sp->dimension ?? '-' }}</span>
      </div>
      <div class="list-card__side">
        <span class="list-card__value">{{ $sp->current_stock }} {{ $sp->unit->name ?? '' }}</span>
        @if ($sp->isLowStock())
          <span class="badge badge--pending">Menipis</span>
        @endif
        <form method="POST" action="{{ route('spareparts.destroy', $sp) }}" onsubmit="return confirmDelete('Hapus sparepart {{ $sp->name }}?')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px; margin-top:4px;">Hapus</button>
        </form>
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada data sparepart.</p>
  @endforelse
</section>

<section id="viewForm" class="view">
  <form method="POST" action="{{ route('spareparts.store') }}">
    @csrf

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="ms-kode">Kode Sparepart</label>
        <input class="form-control" id="ms-kode" name="code" value="{{ old('code') }}" placeholder="contoh: SP-0060" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="ms-kategori">Jenis / Kategori</label>
        <select class="form-control" id="ms-kategori" name="category_id" required>
          @foreach ($categories as $cat)
            <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="ms-nama">Nama Sparepart</label>
      <input class="form-control" id="ms-nama" name="name" value="{{ old('name') }}" placeholder="contoh: Filter Oli Mesin" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="ms-dimensi">Dimensi / Ukuran</label>
      <input class="form-control" id="ms-dimensi" name="dimension" value="{{ old('dimension') }}" placeholder="contoh: Ø8cm x 12cm">
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="ms-satuan">Satuan</label>
        <select class="form-control" id="ms-satuan" name="unit_id" required>
          @foreach ($units as $unit)
            <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="ms-minstok">Stok Minimum</label>
        <input class="form-control" id="ms-minstok" name="min_stock" type="number" min="0" value="{{ old('min_stock', 0) }}" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="ms-harga">Harga Standar (Rp)</label>
        <input class="form-control" id="ms-harga" name="standard_price" type="number" min="0" value="{{ old('standard_price') }}">
      </div>
      <div class="form-group">
        <label class="form-label" for="ms-supplier">Supplier Utama</label>
        <select class="form-control" id="ms-supplier" name="main_supplier_id">
          <option value="">— pilih supplier —</option>
          @foreach ($suppliers as $sup)
            <option value="{{ $sup->id }}" @selected(old('main_supplier_id') == $sup->id)>{{ $sup->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan Sparepart</button>
  </form>
</section>

@endsection
