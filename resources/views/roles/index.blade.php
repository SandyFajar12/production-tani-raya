@extends('layouts.app')

@section('title', 'Kelola Role & Permission — TaniRaya ERP')
@section('page-title', 'Kelola Role & Permission')
@section('page-subtitle', 'Atur hak akses per role')
@section('back-url', route('users.index'))

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Role</button>
  <button class="tab-btn" data-tab-target="viewForm">Atur Permission</button>
</div>

<section id="viewList" class="view list-view is-active">
  @foreach ($roles as $role)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $role->name }}</span>
        <span class="list-card__meta">{{ $role->description }} &middot; {{ $role->users_count }} user</span>
      </div>
      <div class="list-card__side">
        <a href="{{ route('roles.index', ['role' => $role->id]) }}#atur" class="btn btn-sm btn-secondary" style="width:auto; height:30px; padding:0 12px;">Kelola Akses</a>
      </div>
    </div>
  @endforeach
</section>

<section id="viewForm" class="view">
  @if ($activeRole)
  <form method="POST" action="{{ route('roles.update', $activeRole) }}">
    @csrf @method('PUT')

    <div class="form-group">
      <label class="form-label" for="rp-role">Role yang Diatur</label>
      <select class="form-control" id="rp-role" onchange="window.location.href='{{ route('roles.index') }}?role=' + this.value">
        @foreach ($roles as $role)
          <option value="{{ $role->id }}" {{ $role->id === $activeRole->id ? 'selected' : '' }}>{{ $role->name }}</option>
        @endforeach
      </select>
    </div>

    @php $activePermissionIds = $activeRole->permissions->pluck('id')->all(); @endphp

    @foreach ($permissions as $module => $modulePermissions)
      <div class="list-card" style="display:block;">
        <span class="list-card__title">{{ ucfirst($module) }}</span>
        <div style="margin-top:10px; display:flex; flex-direction:column; gap:10px;">
          @foreach ($modulePermissions as $perm)
            <label class="form-check">
              <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" {{ in_array($perm->id, $activePermissionIds) ? 'checked' : '' }}>
              {{ $perm->name }}
            </label>
          @endforeach
        </div>
      </div>
    @endforeach

    <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan Akses</button>
  </form>
  @else
    <p class="empty-state">Belum ada role. Tambahkan role langsung lewat database.</p>
  @endif
</section>

<p class="demo-note">Centang menentukan menu &amp; aksi apa saja yang boleh diakses role tersebut.</p>

@endsection

@section('scripts')
<script>
  if (window.location.search.includes('role=') || window.location.hash === '#atur') {
    document.addEventListener('DOMContentLoaded', () => {
      switchView('viewForm', document.querySelector('[data-tab-target="viewForm"]'));
    });
  }
</script>
@endsection
