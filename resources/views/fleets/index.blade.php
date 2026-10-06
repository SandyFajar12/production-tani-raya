@extends('layouts.app')

@section('title', 'Master Armada — TaniRaya ERP')
@section('page-title', 'Master Armada')
@section('page-subtitle', 'Data unit kendaraan & alat')

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Armada</button>
  <button class="tab-btn" data-tab-target="viewForm">+ Tambah Armada</button>
</div>

@include('partials.per-page-selector')

<section id="viewList" class="view list-view is-active">
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
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="showDetailModal(this)"
          data-detail-title="Detail Armada"
          data-detail="{{ json_encode([
            'Kode' => $f->code,
            'Nama' => $f->name,
            'Tipe' => $f->type,
            'Status' => ucfirst($f->status),
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}">Detail</button>
        <form method="POST" action="{{ route('fleets.destroy', $f) }}" onsubmit="return confirmDelete('Hapus armada {{ $f->name }}?')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;">Hapus</button>
        </form>
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada data armada.</p>
  @endforelse
</section>

@include('partials.pagination', ['paginator' => $fleets])

<section id="viewForm" class="view">
  <form method="POST" action="{{ route('fleets.store') }}">
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

    <button type="submit" class="btn btn-primary btn-block">Simpan Armada</button>
  </form>
</section>

@endsection
