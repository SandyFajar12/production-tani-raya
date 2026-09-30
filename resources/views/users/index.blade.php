@extends('layouts.app')

@section('title', 'Master User — TaniRaya ERP')
@section('page-title', 'Master User')
@section('page-subtitle', 'Login & hak akses')

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar User</button>
  <button class="tab-btn" data-tab-target="viewForm">+ Tambah User</button>
</div>

<div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
  <a href="{{ route('roles.index') }}" class="section__link">Kelola Role &amp; Permission →</a>
</div>

<section id="viewList" class="view list-view is-active">
  @forelse ($users as $u)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $u->name }}</span>
        <span class="list-card__meta">{{ $u->email }}</span>
      </div>
      <div class="list-card__side">
        <span class="badge badge--role-{{ $u->role && $u->role->slug === 'admin' ? 'admin' : ($u->role && $u->role->slug === 'approver' ? 'approver' : 'staff') }}">{{ $u->role->name ?? '-' }}</span>
        <span class="badge badge--{{ $u->is_active ? 'active' : 'inactive' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span>
        @if ($u->id !== auth()->id())
          <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirmDelete('Hapus user {{ $u->name }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;">Hapus</button>
          </form>
        @endif
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada data user.</p>
  @endforelse
</section>

<section id="viewForm" class="view">
  <form method="POST" action="{{ route('users.store') }}">
    @csrf

    <div class="form-group">
      <label class="form-label" for="mu-nama">Nama Lengkap</label>
      <input class="form-control" id="mu-nama" name="name" value="{{ old('name') }}" placeholder="contoh: Dedi Kurniawan" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="mu-email">Email / Username</label>
      <input class="form-control" id="mu-email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@taniraya.co.id" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="mu-password">Kata Sandi Awal</label>
      <input class="form-control" id="mu-password" name="password" type="password" placeholder="minimal 6 karakter" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="mu-role">Role / Hak Akses</label>
      <select class="form-control" id="mu-role" name="role_id" required>
        @foreach ($roles as $role)
          <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="mu-status">Status</label>
      <select class="form-control" id="mu-status" name="is_active">
        <option value="1">Aktif</option>
        <option value="0">Nonaktif</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan User</button>
  </form>
</section>

@endsection
