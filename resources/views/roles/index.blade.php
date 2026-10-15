@extends('layouts.app')

@section('title', 'Kelola Role & Permission — TaniRaya ERP')
@section('page-title', 'Kelola Role & Permission')
@section('page-subtitle', 'Atur menu yang tampil per role')
@section('back-url', route('users.index'))

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Role</button>
  <button class="tab-btn" data-tab-target="viewForm">Atur Menu</button>
</div>

<section id="viewList" class="view list-view is-active">
  @foreach ($roles as $role)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $role->name }}</span>
        <span class="list-card__meta">{{ $role->description }} &middot; {{ $role->users_count }} user</span>
      </div>
      <div class="list-card__side">
        <a href="{{ route('roles.index', ['role' => $role->id, 'module' => $activeModule]) }}#atur" class="btn btn-sm btn-secondary" style="width:auto; height:30px; padding:0 12px;">Kelola Akses</a>
      </div>
    </div>
  @endforeach
</section>

<section id="viewForm" class="view">
  @if ($activeRole)
    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="rp-role">Role</label>
        <select class="form-control" id="rp-role" onchange="goRoleModule()">
          @foreach ($roles as $role)
            <option value="{{ $role->id }}" {{ $role->id === $activeRole->id ? 'selected' : '' }}>{{ $role->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="rp-module">Modul</label>
        <select class="form-control" id="rp-module" onchange="goRoleModule()">
          @foreach ($modules as $key => $label)
            <option value="{{ $key }}" {{ $key === $activeModule ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div>
    </div>

    @if ($activeRole->slug === 'superadmin')
      <p class="empty-state">SuperAdmin selalu memiliki semua akses dan tidak bisa diubah.</p>
    @else
      <form method="POST" action="{{ route('roles.update', $activeRole) }}">
        @csrf @method('PUT')
        <input type="hidden" name="module" value="{{ $activeModule }}">

        @php $activePermissionIds = $activeRole->permissions->pluck('id')->all(); @endphp

        @foreach ($groups as $prefix => $items)
          <div class="list-card" style="display:block;">
            <span class="list-card__title">{{ $groupTitles[$prefix] ?? ucfirst($prefix) }}</span>
            <div style="margin-top:10px; display:flex; flex-direction:column; gap:10px;">
              @foreach ($items as $perm)
                <label class="form-check">
                  <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                    data-slug="{{ $perm->slug }}"
                    @if (isset($requires[$perm->slug])) data-requires="{{ $requires[$perm->slug] }}" @endif
                    {{ in_array($perm->id, $activePermissionIds) ? 'checked' : '' }}>
                  {{ $perm->name }}
                </label>
              @endforeach
            </div>
          </div>
        @endforeach

        <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan Akses</button>
      </form>
    @endif
  @else
    <p class="empty-state">Belum ada role.</p>
  @endif
</section>

<p class="demo-note">Menu muncul kalau minimal satu hak dicentang. Bagian yang tidak dicentang hilang dan tidak bisa dibuka lewat alamat langsung. Mencentang hak ubah otomatis mencentang hak lihat.</p>

@endsection

@section('scripts')
<script>
  function goRoleModule() {
    var r = document.getElementById('rp-role').value;
    var m = document.getElementById('rp-module').value;
    window.location.href = '{{ route('roles.index') }}?role=' + r + '&module=' + m + '#atur';
  }

  // Hak ubah membawa hak lihat; mematikan hak lihat mematikan hak ubah yang bergantung padanya
  document.addEventListener('change', function (e) {
    var cb = e.target;
    if (!cb.matches || !cb.matches('input[data-slug]')) return;

    if (cb.checked && cb.dataset.requires) {
      var need = document.querySelector('input[data-slug="' + cb.dataset.requires + '"]');
      if (need) need.checked = true;
    }
    if (!cb.checked) {
      document.querySelectorAll('input[data-requires="' + cb.dataset.slug + '"]').forEach(function (d) { d.checked = false; });
    }
  });

  if (window.location.search.includes('role=') || window.location.hash === '#atur') {
    document.addEventListener('DOMContentLoaded', () => {
      switchView('viewForm', document.querySelector('[data-tab-target="viewForm"]'));
    });
  }
</script>
@endsection