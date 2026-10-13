@extends('layouts.app')

@section('title', 'Master Supplier — TaniRaya ERP')
@section('page-title', 'Master Supplier')
@section('page-subtitle', 'Data pemasok sparepart')

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Supplier</button>
  @permission('master.manage')
  <button class="tab-btn" data-tab-target="viewForm" onclick="resetFormToCreate('#supplierForm', '{{ route('suppliers.store') }}', 'Simpan Supplier')">+ Tambah Supplier</button>
  @endpermission
</div>

<section id="viewList" class="view is-active">
  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari nama / kontak supplier...'])

  <div class="list-view is-active">
  @forelse ($suppliers as $s)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $s->name }}</span>
        <span class="list-card__meta">Kontak: {{ $s->contact_person ?? '-' }} &middot; {{ $s->phone ?? '-' }}</span>
      </div>
      <div class="list-card__side">
        <span class="badge badge--{{ $s->is_active ? 'active' : 'inactive' }}">{{ $s->is_active ? 'Aktif' : 'Nonaktif' }}</span>
        <div class="action-group">
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
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        @permission('master.manage')
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="openEditForm(this, '#supplierForm')"
          data-update-url="{{ route('suppliers.update', $s) }}"
          data-edit="{{ json_encode([
            'name' => $s->name, 'contact_person' => $s->contact_person, 'phone' => $s->phone,
            'email' => $s->email, 'address' => $s->address, 'is_active' => $s->is_active ? 1 : 0,
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Edit" aria-label="Edit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg></button>
        @endpermission
        @permission('master.delete')
        <form method="POST" action="{{ route('suppliers.destroy', $s) }}" onsubmit="return confirmDelete('Hapus supplier {{ $s->name }}?')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;" title="Hapus" aria-label="Hapus"><svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg></button>
        </form>
        @endpermission
        </div>
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada data supplier.</p>
  @endforelse
  </div>

  @include('partials.pagination', ['paginator' => $suppliers])
</section>

@permission('master.manage')
<section id="viewForm" class="view">
  <form method="POST" action="{{ route('suppliers.store') }}" id="supplierForm" data-safe-form="master-supplier" data-no-draft>
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

    <button type="submit" class="btn btn-primary btn-block" data-form-submit>Simpan Supplier</button>
  </form>
</section>
@endpermission

@endsection
