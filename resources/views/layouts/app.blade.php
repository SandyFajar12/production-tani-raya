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
  data-flash-error="{{ $errors->any() ? $errors->first() : '' }}"
>
<div class="app-shell">

  @include('partials.header')

  <main class="app-main">
    @yield('content')
  </main>

  @include('partials.footer')

</div>

<script src="{{ asset('js/main.js') }}"></script>
@yield('scripts')
</body>
</html>
