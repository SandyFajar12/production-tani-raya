@extends('layouts.app')

@section('title', 'Master Sparepart — TaniRaya ERP')
@section('page-title', 'Master Sparepart')
@section('page-subtitle', 'Data induk sparepart')

@section('content')

@php
  $iEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  $iEdit = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>';
  $iTrash = '<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';
@endphp

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Sparepart</button>
  @permission('sparepart.manage')
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

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Nama</th>
          <th>Kategori</th>
          <th>Dimensi</th>
          <th class="num">Stok</th>
          <th class="col-aksi">Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($spareparts as $sp)
        <tr>
          <td style="white-space:nowrap;">{{ $sp->code }}</td>
          <td>{{ $sp->name }}</td>
          <td>{{ $sp->category->name ?? '-' }}</td>
          <td>{{ $sp->dimension ?: '-' }}</td>
          <td class="num">{{ $sp->current_stock }} {{ $sp->unit->name ?? '' }}</td>
          <td class="col-aksi">
            <div class="action-group">
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="showDetailModal(this)"
                data-detail-title="Detail Sparepart"
                data-detail="{{ json_encode([
                  'Kode' => $sp->code,
                  'Nama' => $sp->name,
                  'Kategori' => $sp->category->name ?? '-',
                  'Dimensi' => $sp->dimension ?? '-',
                  'Satuan' => $sp->unit->name ?? '-',
                  ] + $sp->stockDetail() + [
                  'Stok Minimum' => $sp->min_stock.' '.($sp->unit->name ?? ''),
                  'Harga Standar' => $sp->standard_price !== null ? 'Rp'.number_format($sp->standard_price, 0, ',', '.') : '-',
                  'Supplier Utama' => $sp->mainSupplier->name ?? '-',
                ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iEye !!}</button>
              @permission('sparepart.manage')
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="openEditForm(this, '#sparepartForm')"
                data-update-url="{{ route('spareparts.update', $sp) }}"
                data-edit="{{ json_encode([
                  'code' => $sp->code, 'name' => $sp->name, 'category_id' => $sp->category_id,
                  'dimension' => $sp->dimension, 'unit_id' => $sp->unit_id, 'min_stock' => $sp->min_stock,
                  'standard_price' => $sp->standard_price !== null ? (int) round($sp->standard_price) : null, 'main_supplier_id' => $sp->main_supplier_id,
                ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Edit" aria-label="Edit">{!! $iEdit !!}</button>
              <form method="POST" action="{{ route('spareparts.destroy', $sp) }}" onsubmit="return confirmDelete('Hapus sparepart {{ addslashes($sp->name) }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-secondary" title="Hapus" aria-label="Hapus">{!! $iTrash !!}</button>
              </form>
              @endpermission
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="empty-state">Belum ada data sparepart.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @include('partials.pagination', ['paginator' => $spareparts])
</section>

@permission('sparepart.manage')
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
        <select class="form-control" id="ms-kategori" name="category_id" data-no-search required>
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
        <select class="form-control" id="ms-satuan" name="unit_id" data-no-search required>
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
            <option value="{{ $sup->id }}" {{ old('main_supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}{{ $sup->is_active ? '' : ' (Nonaktif)' }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block" data-form-submit>Simpan Sparepart</button>
  </form>
</section>
@endpermission

@endsection