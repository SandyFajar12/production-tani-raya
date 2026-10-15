@extends('layouts.app')

@section('title', 'Master Kebun — TaniRaya ERP')
@section('page-title', 'Master Kebun')
@section('page-subtitle', 'Data kebun & kantor pusat')

@section('content')

@php
  $iEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  $iEdit = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>';
  $iTrash = '<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';
@endphp

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Kebun</button>
  @permission('garden.manage')
  <button class="tab-btn" data-tab-target="viewForm" onclick="resetFormToCreate('#gardenForm', '{{ route('gardens.store') }}', 'Simpan Kebun')">+ Tambah Kebun</button>
  @endpermission
</div>

<section id="viewList" class="view is-active">
  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari nama / alamat / PIC kebun...'])

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Alamat</th>
          <th>PIC</th>
          <th>Keterangan</th>
          <th class="col-aksi">Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($gardens as $g)
        <tr>
          <td>{{ $g->name }}</td>
          <td>{{ $g->address ?: '-' }}</td>
          <td>{{ $g->pic->name ?? '-' }}</td>
          <td><span class="badge badge--{{ $g->is_active ? 'active' : 'inactive' }}">{{ $g->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
          <td class="col-aksi">
            <div class="action-group">
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="showDetailModal(this)"
                data-detail-title="Detail Kebun"
                data-detail="{{ json_encode([
                  'Nama' => $g->name,
                  'Alamat' => $g->address ?? '-',
                  'PIC Kebun' => $g->pic->name ?? '-',
                  'Status' => $g->is_active ? 'Aktif' : 'Nonaktif',
                ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iEye !!}</button>
              @permission('garden.manage')
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="openEditForm(this, '#gardenForm')"
                data-update-url="{{ route('gardens.update', $g) }}"
                data-edit="{{ json_encode([
                  'name' => $g->name, 'address' => $g->address ?? '',
                  'pic_user_id' => $g->pic_user_id ?? '', 'is_active' => $g->is_active ? 1 : 0,
                ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Edit" aria-label="Edit">{!! $iEdit !!}</button>
              <form method="POST" action="{{ route('gardens.destroy', $g) }}" onsubmit="return confirmDelete('Hapus kebun {{ addslashes($g->name) }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-secondary" title="Hapus" aria-label="Hapus">{!! $iTrash !!}</button>
              </form>
              @endpermission
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty-state">Belum ada data kebun.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @include('partials.pagination', ['paginator' => $gardens])
</section>

@permission('garden.manage')
<section id="viewForm" class="view">
  <form method="POST" action="{{ route('gardens.store') }}" id="gardenForm" data-safe-form="master-garden" data-no-draft>
    @csrf

    <div class="form-group">
      <label class="form-label" for="mk-nama">Nama Kebun / Lokasi</label>
      <input class="form-control" id="mk-nama" name="name" value="{{ old('name') }}" placeholder="contoh: Kebun Kapundung (Pusat)" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="mk-alamat">Alamat</label>
      <textarea class="form-control" id="mk-alamat" name="address" rows="3" placeholder="opsional">{{ old('address') }}</textarea>
    </div>

    <div class="form-group">
      <label class="form-label" for="mk-pic">PIC Kebun</label>
      <select class="form-control" id="mk-pic" name="pic_user_id">
        <option value="">— pilih PIC (opsional) —</option>
        @foreach ($pics as $u)
          <option value="{{ $u->id }}" {{ old('pic_user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}{{ $u->is_active ? '' : ' (Nonaktif)' }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="mk-status">Status</label>
      <select class="form-control" id="mk-status" name="is_active">
        <option value="1">Aktif</option>
        <option value="0">Nonaktif</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-block" data-form-submit>Simpan Kebun</button>
  </form>
</section>
@endpermission

@endsection