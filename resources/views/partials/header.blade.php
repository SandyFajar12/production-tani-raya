@hasSection('page-title')
<header class="app-header app-header--sub">
  <div class="app-header__row">
    <a href="@yield('back-url', route('dashboard'))" class="icon-btn" aria-label="Kembali">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    </a>
    <div class="app-header__titles">
      <div class="app-header__title">@yield('page-title')</div>
      <div class="app-header__sub">@yield('page-subtitle')</div>
    </div>
    <span style="width:36px"></span>
  </div>
</header>
@else
<header class="app-header">
  <div class="app-header__row">
    <div class="brand">
      <div class="brand__text">
        <strong>TaniRaya</strong>
        <span>ERP Sparepart</span>
      </div>
    </div>

    <div class="header-actions" style="position:relative;">
      <button type="button" class="user-menu-trigger" onclick="toggleUserMenu(event)">
        @if (auth()->user()->avatar)
          <img src="{{ route('profile.avatar', auth()->user()->avatar) }}" class="user-menu-trigger__avatar" alt="">
        @else
          <span class="user-menu-trigger__avatar user-menu-trigger__avatar--default">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.9-2.2 4.9-4.9S14.7 2.2 12 2.2 7.1 4.4 7.1 7.1 9.3 12 12 12zm0 2.5c-3.3 0-9.8 1.6-9.8 4.9v2.4h19.6v-2.4c0-3.3-6.5-4.9-9.8-4.9z"/></svg>
          </span>
        @endif
        <span class="user-menu-trigger__text">
          <strong>{{ auth()->user()->name }}</strong>
          <span>{{ auth()->user()->role->name ?? '-' }}</span>
        </span>
      </button>

      <div class="user-menu-dropdown" id="userMenuDropdown">
        <div class="user-menu-dropdown__header">
          @if (auth()->user()->avatar)
            <img src="{{ route('profile.avatar', auth()->user()->avatar) }}" class="user-menu-dropdown__avatar" alt="">
          @else
            <span class="user-menu-dropdown__avatar user-menu-dropdown__avatar--default">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.9-2.2 4.9-4.9S14.7 2.2 12 2.2 7.1 4.4 7.1 7.1 9.3 12 12 12zm0 2.5c-3.3 0-9.8 1.6-9.8 4.9v2.4h19.6v-2.4c0-3.3-6.5-4.9-9.8-4.9z"/></svg>
            </span>
          @endif
          <strong>{{ auth()->user()->name }}</strong>
          <span>{{ auth()->user()->role->name ?? '-' }}</span>
        </div>

        <a href="{{ route('profile.edit') }}" class="user-menu-dropdown__item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Profil User
        </a>

        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="user-menu-dropdown__item user-menu-dropdown__item--danger">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
          </button>
        </form>
      </div>
    </div>
  </div>
</header>
@endif
