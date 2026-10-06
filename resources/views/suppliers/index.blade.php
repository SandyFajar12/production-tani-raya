@extends('layouts.app')

@section('title', 'Master Supplier — TaniRaya ERP')
@section('page-title', 'Master Supplier')
@section('page-subtitle', 'Data pemasok sparepart')

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Supplier</button>
  <button class="tab-btn" data-tab-target="viewForm">+ Tambah Supplier</button>
</div>

@include('partials.per-page-selector')

<section id="viewList" class="view list-view is-active">
  @forelse ($suppliers as $s)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $s->name }}</span>
        <span class="list-card__meta">Kontak: {{ $s->contact_person ?? '-' }} &middot; {{ $s->phone ?? '-' }}</span>
      </div>
      <div class="list-card__side">
        <span class="badge badge--{{ $s->is_active ? 'active' : 'inactive' }}">{{ $s->is_active ? 'Aktif' : 'Nonaktif' }}</span>
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="showDetailModal(this)"
          data-detail-title="Detail Supplier"
          data-detail="{{ json_encode([
            'Nama' => $s->name,
            'Kontak' => $s->contact_person ?? '-',
            'Telepon' => $s->phone ?? '-',
            'Email' => $s->email ?? '-',
            'Alamat' => $s->address ?? '-',
            'Status' => $s->is_active ? 'Aktif' : 'Nonaktif',
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}">Detail</button>
        <form method="POST" action="{{ route('suppliers.destroy', $s) }}" onsubmit="return confirmDelete('Hapus supplier {{ $s->name }}?')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;">Hapus</button>
        </form>
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada data supplier.</p>
  @endforelse
</section>

@include('partials.pagination', ['paginator' => $suppliers])

<section id="viewForm" class="view">
  <form method="POST" action="{{ route('suppliers.store') }}">
    @csrf

    <div class="form-group">
      <label class="form-label" for="sp-nama">Nama Supplier</label>
      <input class="form-control" id="sp-nama" name="name" value="{{ old('name') }}" placeholder="contoh: CV Sumber Sparepart" required>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="sp-kontak">Nama Kontak (PIC)</label>
        <input class="form-control" id="sp-kontak" name="contact_person" value="{{ old('contact_person') }}" placeholder="contoh: Pak Herman">
      </div>
      <div class="form-group">
        <label class="form-label" for="sp-telepon">No. Telepon</label>
        <input class="form-control" id="sp-telepon" name="phone" value="{{ old('phone') }}" placeholder="08xx-xxxx-xxxx">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="sp-email">Email</label>
      <input class="form-control" id="sp-email" name="email" type="email" value="{{ old('email') }}" placeholder="opsional">
    </div>

    <div class="form-group">
      <label class="form-label" for="sp-alamat">Alamat</label>
      <textarea class="form-control" id="sp-alamat" name="address" rows="3" placeholder="opsional">{{ old('address') }}</textarea>
    </div>

    <div class="form-group">
      <label class="form-label" for="sp-status">Status</label>
      <select class="form-control" id="sp-status" name="is_active">
        <option value="1">Aktif</option>
        <option value="0">Nonaktif</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan Supplier</button>
  </form>
</section>

@endsection
