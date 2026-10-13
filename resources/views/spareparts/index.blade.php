@extends('layouts.app')

@section('title', 'Master Sparepart — TaniRaya ERP')
@section('page-title', 'Master Sparepart')
@section('page-subtitle', 'Data induk sparepart')

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Sparepart</button>
  @permission('master.manage')
  <button class="tab-btn" data-tab-target="viewForm" onclick="resetFormToCreate('#sparepartForm', '{{ route('spareparts.store') }}', 'Simpan Sparepart')">+ Tambah Sparepart</button>
  @endpermission
</div>

<section id="viewList" class="view is-active">
  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari kode / nama sparepart...'])

  @if (request('low_stock'))
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12.5px; color:var(--color-text-muted);">
      <span>Menampilkan sparepart yang stoknya menipis saja</span>
      <a href="{{ route('spareparts.index') }}" class="section__link">Lihat semua →</a>
    </div>
  @endif

  <div class="list-view is-active">
  @forelse ($spareparts as $sp)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $sp->name }}</span>
        <span class="list-card__meta">{{ $sp->code }} &middot; Kategori: {{ $sp->category->name ?? '-' }} &middot; {{ $sp->dimension }}</span>
      </div>
      <div class="list-card__side">
        <span class="list-card__value">{{ $sp->current_stock }} {{ $sp->unit->name ?? '' }}</span>
        <div class="action-group">
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="showDetailModal(this)"
          data-detail-title="Detail Sparepart"
          data-detail="{{ json_encode([
            'Kode' => $sp->code,
            'Nama' => $sp->name,
            'Kategori' => $sp->category->name ?? '-',
            'Dimensi' => $sp->dimension ?? '-',
            'Satuan' => $sp->unit->name ?? '-',
            'Stok Saat Ini' => $sp->current_stock.' '.($sp->unit->name ?? ''),
            'Stok Minimum' => $sp->min_stock.' '.($sp->unit->name ?? ''),
            'Harga Standar' => $sp->standard_price !== null ? 'Rp'.number_format($sp->standard_price, 0, ',', '.') : '-',
            'Supplier Utama' => $sp->mainSupplier->name ?? '-',
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        @permission('master.manage')
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="openEditForm(this, '#sparepartForm')"
          data-update-url="{{ route('spareparts.update', $sp) }}"
          data-edit="{{ json_encode([
            'code' => $sp->code, 'name' => $sp->name, 'category_id' => $sp->category_id,
            'dimension' => $sp->dimension, 'unit_id' => $sp->unit_id, 'min_stock' => $sp->min_stock,
            'standard_price' => $sp->standard_price !== null ? (int) round($sp->standard_price) : null, 'main_supplier_id' => $sp->main_supplier_id,
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Edit" aria-label="Edit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg></button>
        @endpermission
        @permission('master.delete')
        <form method="POST" action="{{ route('spareparts.destroy', $sp) }}" onsubmit="return confirmDelete('Hapus sparepart {{ $sp->name }}?')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;" title="Hapus" aria-label="Hapus"><svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg></button>
        </form>
        @endpermission
        </div>
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada data sparepart.</p>
  @endforelse
  </div>

  @include('partials.pagination', ['paginator' => $spareparts])
</section>

@permission('master.manage')
<section id="viewForm" class="view">
  <form method="POST" action="{{ route('spareparts.store') }}" id="sparepartForm" data-safe-form="master-sparepart" data-no-draft>
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
            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
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
            <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
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
        <input class="form-control" id="ms-harga" name="standard_price" type="text" inputmode="numeric" data-currency autocomplete="off" value="{{ old('standard_price') }}">
      </div>
      <div class="form-group">
        <label class="form-label" for="ms-supplier">Supplier Utama</label>
        <select class="form-control" id="ms-supplier" name="main_supplier_id">
          <option value="">— pilih supplier —</option>
          @foreach ($suppliers as $sup)
            <option value="{{ $sup->id }}" {{ old('main_supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block" data-form-submit>Simpan Sparepart</button>
  </form>
</section>
@endpermission

@endsection
