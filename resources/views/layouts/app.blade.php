<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'TaniRaya ERP')</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body
  data-flash-status="{{ session('status') }}"
>
<div class="app-shell">

  @include('partials.header')

  <main class="app-main">
    @if ($errors->any())
      <div class="alert-warning" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
        <span class="alert-text">{{ $errors->first() }}</span>
        <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Tutup">&times;</button>
      </div>
    @endif

    @yield('content')
  </main>

  @include('partials.footer')

</div>

<script src="{{ asset('js/main.js') }}"></script>
@yield('scripts')
</body>
</html>
