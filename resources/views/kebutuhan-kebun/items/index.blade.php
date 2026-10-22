@extends('layouts.app')

@section('title', 'Master Kebutuhan Kebun — TaniRaya ERP')
@section('page-title', 'Master Kebutuhan Kebun')
@section('page-subtitle', 'Pupuk, pestisida, bibit, dan lainnya')
@section('back-url', route('needs.index'))

@section('content')

@php
  $iEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  $iEdit = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>';
  $iTrash = '<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';
@endphp

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Item</button>
  @permission('need_item.manage')
  <button class="tab-btn" data-tab-target="viewForm" onclick="resetFormToCreate('#itemForm', '{{ route('kebun.items.store') }}', 'Simpan Item')">+ Tambah Item</button>
  @endpermission
</div>

<section id="viewList" class="view is-active">
  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari kode / nama item...'])

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Nama</th>
          <th>Kategori</th>
          <th class="num">Stok</th>
          <th class="col-aksi">Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($items as $it)
        <tr>
          <td style="white-space:nowrap;">{{ $it->code }}</td>
          <td>{{ $it->name }}</td>
          <td>{{ $it->category->name ?? '-' }}</td>
          <td class="num">{{ $it->current_stock }} {{ $it->unit->name ?? '' }}</td>
          <td class="col-aksi">
            <div class="action-group">
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="showDetailModal(this)"
                data-detail-title="Detail Item"
                data-detail="{{ json_encode([
                  'Kode' => $it->code,
                  'Nama' => $it->name,
                  'Kategori' => $it->category->name ?? '-',
                  'Satuan' => $it->unit->name ?? '-',
                  ] + $it->stockDetail() + [
                  'Stok Minimum' => $it->min_stock.' '.($it->unit->name ?? ''),
                  'Harga Standar' => $it->standard_price !== null ? 'Rp'.number_format($it->standard_price, 0, ',', '.') : '-',
                  'Supplier Utama' => $it->mainSupplier->name ?? '-',
                ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iEye !!}</button>
              @permission('need_item.manage')
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="openEditForm(this, '#itemForm')"
                data-update-url="{{ route('kebun.items.update', $it) }}"
                data-edit="{{ json_encode([
                  'code' => $it->code, 'name' => $it->name, 'category_id' => $it->category_id,
                  'unit_id' => $it->unit_id, 'min_stock' => $it->min_stock,
                  'standard_price' => $it->standard_price !== null ? (int) round($it->standard_price) : null,
                  'main_supplier_id' => $it->main_supplier_id,
                ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Edit" aria-label="Edit">{!! $iEdit !!}</button>
              <form method="POST" action="{{ route('kebun.items.destroy', $it) }}" onsubmit="return confirmDelete('Hapus item {{ addslashes($it->name) }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-secondary" title="Hapus" aria-label="Hapus">{!! $iTrash !!}</button>
              </form>
              @endpermission
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Belum ada data item kebutuhan kebun.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @include('partials.pagination', ['paginator' => $items])
</section>

@permission('need_item.manage')
<section id="viewForm" class="view">
  <form method="POST" action="{{ route('kebun.items.store') }}" id="itemForm" data-safe-form="kebun-item" data-no-draft>
    @csrf

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="ki-kode">Kode Item</label>
        <input class="form-control" id="ki-kode" name="code" value="{{ old('code') }}" placeholder="contoh: KB-0001" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="ki-kategori">Kategori</label>
        <select class="form-control" id="ki-kategori" name="category_id" required>
          @foreach ($categories as $cat)
            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="ki-nama">Nama Item</label>
      <input class="form-control" id="ki-nama" name="name" value="{{ old('name') }}" placeholder="contoh: Pupuk NPK 16-16-16" required>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="ki-satuan">Satuan</label>
        <select class="form-control" id="ki-satuan" name="unit_id" required>
          @foreach ($units as $unit)
            <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="ki-minstok">Stok Minimum</label>
        <input class="form-control" id="ki-minstok" name="min_stock" type="number" min="0" value="{{ old('min_stock', 0) }}" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="ki-harga">Harga Standar (Rp)</label>
        <input class="form-control" id="ki-harga" name="standard_price" type="text" inputmode="numeric" data-currency autocomplete="off" value="{{ old('standard_price') }}">
      </div>
      <div class="form-group">
        <label class="form-label" for="ki-supplier">Supplier Utama</label>
        <select class="form-control" id="ki-supplier" name="main_supplier_id">
          <option value="">— pilih supplier —</option>
          @foreach ($suppliers as $sup)
            <option value="{{ $sup->id }}" {{ old('main_supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block" data-form-submit>Simpan Item</button>
  </form>
</section>
@endpermission

@endsection