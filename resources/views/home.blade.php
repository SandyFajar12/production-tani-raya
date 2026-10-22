@extends('layouts.app')

@section('title', 'Halaman Awal — TaniRaya ERP')
@section('brand-sub', 'Halaman Awal')

@section('content')

<section class="section">
  <div class="section__head">
    <h2 class="section__title">Halo, {{ auth()->user()->name }}</h2>
  </div>
  <p style="font-size:13px; color:var(--color-text-muted); margin:0 0 16px;">Pilih modul yang ingin dibuka.</p>

  <div class="module-grid">

    <a href="{{ route('dashboard') }}" class="module-card module-card--blue">
      <span class="module-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
      </span>
      <span>
        <span class="module-card__title">Stok Sparepart</span>
        <span class="module-card__desc">Stok, pre-order, pembelian, pemakaian</span>
      </span>
    </a>

    <a href="{{ route('needs.index') }}" class="module-card module-card--green">
      <span class="module-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      </span>
      <span>
        <span class="module-card__title">Kebutuhan Kebun</span>
        <span class="module-card__desc">Kebutuhan operasional kebun</span>
      </span>
    </a>

    <a href="{{ route('fuel.index') }}" class="module-card module-card--amber">
      <span class="module-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
      </span>
      <span>
        <span class="module-card__title">Pemakaian Solar</span>
        <span class="module-card__desc">Laporan pemakaian solar</span>
      </span>
    </a>

    @if (auth()->user()->hasAnyPermission(['user.view', 'user.manage']))
    <a href="{{ route('users.index') }}" class="module-card module-card--teal">
      <span class="module-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </span>
      <span>
        <span class="module-card__title">Master User</span>
        <span class="module-card__desc">Akun dan hak akses</span>
      </span>
    </a>
    @endif

  </div>
</section>

@endsection