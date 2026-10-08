@extends('layouts.app')

@section('title', 'Master User — TaniRaya ERP')
@section('page-title', 'Master User')
@section('page-subtitle', 'Login & hak akses')

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar User</button>
  @permission('user.manage')
  <button class="tab-btn" data-tab-target="viewForm" onclick="resetFormToCreate('#formTambahUser', '{{ route('users.store') }}', 'Simpan User')">+ Tambah User</button>
  @endpermission
</div>

@permission('role.manage')
<div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
  <a href="{{ route('roles.index') }}" class="section__link">Kelola Role &amp; Permission →</a>
</div>
@endpermission

<section id="viewList" class="view is-active">
  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari nama / email user...'])

  <div class="list-view is-active">
  @forelse ($users as $u)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $u->name }}</span>
        <span class="list-card__meta">{{ $u->email }}</span>
      </div>
      <div class="list-card__side">
        <span class="badge badge--role-{{ $u->role && $u->role->slug === 'admin' ? 'admin' : ($u->role && $u->role->slug === 'approver' ? 'approver' : 'staff') }}">{{ $u->role->name ?? '-' }}</span>
        <span class="badge badge--{{ $u->is_active ? 'active' : 'inactive' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span>
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="showDetailModal(this)"
          data-detail-title="Detail User"
          data-detail="{{ json_encode([
            'Nama' => $u->name,
            'Email' => $u->email,
            'Role' => $u->role->name ?? '-',
            'Status' => $u->is_active ? 'Aktif' : 'Nonaktif',
            'Login Terakhir' => $u->last_login_at ? $u->last_login_at->format('d M Y H:i') : 'Belum pernah',
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}">Detail</button>
        @permission('user.manage')
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="openEditForm(this, '#formTambahUser')"
          data-update-url="{{ route('users.update', $u) }}"
          data-edit="{{ json_encode([
            'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone, 'role_id' => $u->role_id, 'is_active' => $u->is_active ? 1 : 0,
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}">Edit</button>
        @endpermission
        @permission('user.manage')
        @if ($u->id !== auth()->id())
          <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirmDelete('Hapus user {{ $u->name }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;">Hapus</button>
          </form>
        @endif
        @endpermission
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada data user.</p>
  @endforelse
  </div>

  @include('partials.pagination', ['paginator' => $users])
</section>

@permission('user.manage')
<section id="viewForm" class="view">
  <div id="userFormAlert" class="alert-warning" role="alert" style="display:none;">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
    <span class="alert-text"></span>
    <button type="button" class="alert-close" aria-label="Tutup">&times;</button>
  </div>

  <form id="formTambahUser" method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data">
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
      <label class="form-label" for="mu-phone">No. Telepon</label>
      <input class="form-control" id="mu-phone" name="phone" value="{{ old('phone') }}" placeholder="08xx-xxxx-xxxx">
    </div>

    <div class="form-group">
      <label class="form-label" for="mu-password">Kata Sandi Awal</label>
      <input class="form-control" id="mu-password" name="password" type="password" placeholder="minimal 6 karakter" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="mu-role">Role / Hak Akses</label>
      <select class="form-control" id="mu-role" name="role_id" required>
        @foreach ($roles as $role)
          <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
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

    <div class="form-group">
      <label class="form-label" for="mu-avatar">Foto Profil (opsional)</label>
      <input class="form-control" id="mu-avatar" name="avatar" type="file" accept="image/*">
    </div>

        <button type="submit" class="btn btn-primary btn-block" data-form-submit>Simpan User</button>
      </form>
    </section>
    @endpermission

@endsection

@section('scripts')
<script>
(function () {
  const form = document.getElementById('formTambahUser');
  const alertBox = document.getElementById('userFormAlert');
  if (!form || !alertBox) return;

  const alertText = alertBox.querySelector('.alert-text');
  const showAlert = (msg) => { alertText.textContent = msg; alertBox.style.display = 'flex'; };
  const hideAlert = () => { alertBox.style.display = 'none'; };

  alertBox.querySelector('.alert-close').addEventListener('click', hideAlert);

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideAlert();

    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
        credentials: 'same-origin'
      });
      const data = await res.json().catch(() => ({}));

      if (res.ok) {
        window.location.reload();
        return;
      }

      if (res.status === 422 && data.errors) {
        showAlert(Object.values(data.errors)[0][0]);
      } else if (res.status === 419) {
        showAlert('Sesi habis, silakan muat ulang halaman lalu coba lagi.');
      } else {
        showAlert('Terjadi kesalahan, coba lagi.');
      }
    } catch (err) {
      showAlert('Tidak dapat terhubung ke server, coba lagi.');
    }

    btn.disabled = false;
  });
})();
</script>
@endsection
