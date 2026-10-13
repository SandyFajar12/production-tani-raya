@extends('layouts.app')

@section('title', 'Master Armada — TaniRaya ERP')
@section('page-title', 'Master Armada')
@section('page-subtitle', 'Data unit kendaraan & alat')

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Armada</button>
  @permission('master.manage')
  <button class="tab-btn" data-tab-target="viewForm" onclick="resetFormToCreate('#fleetForm', '{{ route('fleets.store') }}', 'Simpan Armada')">+ Tambah Armada</button>
  @endpermission
</div>

<section id="viewList" class="view is-active">
  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari kode / nama armada...'])

  <div class="list-view is-active">
  @forelse ($fleets as $f)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $f->name }} — {{ $f->code }}</span>
        <span class="list-card__meta">Tipe: {{ $f->type }}</span>
      </div>
      <div class="list-card__side">
        @if ($f->status === 'aktif')
          <span class="badge badge--active">Aktif</span>
        @elseif ($f->status === 'maintenance')
          <span class="badge badge--pending">Maintenance</span>
        @else
          <span class="badge badge--inactive">Nonaktif</span>
        @endif
        <div class="action-group">
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="showDetailModal(this)"
          data-detail-title="Detail Armada"
          data-detail="{{ json_encode([
            'Kode' => $f->code,
            'Nama' => $f->name,
            'Tipe' => $f->type,
            'Status' => ucfirst($f->status),
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        @permission('master.manage')
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="openEditForm(this, '#fleetForm')"
          data-update-url="{{ route('fleets.update', $f) }}"
          data-edit="{{ json_encode([
            'code' => $f->code, 'name' => $f->name, 'type' => $f->type, 'status' => $f->status,
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Edit" aria-label="Edit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg></button>
        @endpermission
        @permission('master.delete')
        <form method="POST" action="{{ route('fleets.destroy', $f) }}" onsubmit="return confirmDelete('Hapus armada {{ $f->name }}?')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;" title="Hapus" aria-label="Hapus"><svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg></button>
        </form>
        @endpermission
        </div>
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada data armada.</p>
  @endforelse
  </div>

  @include('partials.pagination', ['paginator' => $fleets])
</section>

@permission('master.manage')
<section id="viewForm" class="view">
  <form method="POST" action="{{ route('fleets.store') }}" id="fleetForm" data-safe-form="master-fleet" data-no-draft>
    @csrf

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="ma-kode">Kode Armada</label>
        <input class="form-control" id="ma-kode" name="code" value="{{ old('code') }}" placeholder="contoh: FR-D9" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="ma-tipe">Tipe</label>
        <select class="form-control" id="ma-tipe" name="type">
          <option>Truk</option>
          <option>Traktor</option>
          <option>Dump Truck</option>
          <option>Excavator</option>
          <option>Lainnya</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="ma-nama">Nama / Keterangan Armada</label>
      <input class="form-control" id="ma-nama" name="name" value="{{ old('name') }}" placeholder="contoh: Truk Angkut Blok A" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="ma-status">Status</label>
      <select class="form-control" id="ma-status" name="status">
        <option value="aktif">Aktif</option>
        <option value="maintenance">Maintenance</option>
        <option value="nonaktif">Nonaktif</option>
      </select>
    </div>

        <button type="submit" class="btn btn-primary btn-block" data-form-submit>Simpan Armada</button>
      </form>
    </section>
    @endpermission

@endsection
