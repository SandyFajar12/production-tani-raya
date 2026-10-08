@extends('layouts.app')

@section('title', 'Profil Saya — TaniRaya ERP')
@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Ubah data akun Anda sendiri')
@section('back-url', route('dashboard'))

@section('content')

<div style="display:flex; justify-content:center; margin-bottom:20px;">
  @if (auth()->user()->avatar)
    <img src="{{ route('profile.avatar', auth()->user()->avatar) }}" style="width:88px; height:88px; border-radius:50%; object-fit:cover;" alt="">
  @else
    <span style="width:88px; height:88px; border-radius:50%; background:var(--color-surface); display:flex; align-items:center; justify-content:center;">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="var(--color-text-faint)"><path d="M12 12c2.7 0 4.9-2.2 4.9-4.9S14.7 2.2 12 2.2 7.1 4.4 7.1 7.1 9.3 12 12 12zm0 2.5c-3.3 0-9.8 1.6-9.8 4.9v2.4h19.6v-2.4c0-3.3-6.5-4.9-9.8-4.9z"/></svg>
    </span>
  @endif
</div>

<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
  @csrf

  <div class="form-group">
    <label class="form-label" for="pf-nama">Nama Lengkap</label>
    <input class="form-control" id="pf-nama" name="name" value="{{ old('name', auth()->user()->name) }}" required>
  </div>

  <div class="form-group">
    <label class="form-label" for="pf-email">Email</label>
    <input class="form-control" id="pf-email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required>
  </div>

  <div class="form-group">
    <label class="form-label" for="pf-phone">No. Telepon</label>
    <input class="form-control" id="pf-phone" name="phone" value="{{ old('phone', auth()->user()->phone) }}" placeholder="08xx-xxxx-xxxx">
  </div>

  <div class="form-group">
    <label class="form-label" for="pf-password">Kata Sandi Baru</label>
    <input class="form-control" id="pf-password" name="password" type="password" placeholder="kosongkan jika tidak ingin ganti">
  </div>

  <div class="form-group">
    <label class="form-label" for="pf-avatar">Foto Profil</label>
    <input class="form-control" id="pf-avatar" name="avatar" type="file" accept="image/*">
  </div>

  <div class="form-group">
    <label class="form-label">Role</label>
    <input class="form-control" value="{{ auth()->user()->role->name ?? '-' }}" disabled style="color:var(--color-text-muted);">
  </div>

  <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan</button>
</form>

@endsection