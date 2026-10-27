@extends('layouts.app')

@section('title', 'Pemakaian Solar — TaniRaya ERP')
@section('brand-sub', 'Pemakaian Solar')

@section('content')

@php
  $u = auth()->user();
  $can = fn (...$slugs) => $u->hasAnyPermission($slugs);

  $menus = [
    ['perm' => ['fuel_trip.view', 'fuel_trip.create'], 'url' => route('solar.trips.index'), 'color' => 'rose', 'label' => 'Pemakaian',
     'icon' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>'],
    ['perm' => ['fuel_fill.view', 'fuel_fill.create'], 'url' => route('solar.fills.index'), 'color' => 'amber', 'label' => 'Isi Tangki',
     'icon' => '<path d="M3 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"/><path d="M3 22h12"/><path d="M15 9h2a2 2 0 0 1 2 2v5a1.5 1.5 0 0 0 3 0V9l-3-3"/><path d="M6 8h6"/>'],
    ['perm' => ['fuel_balance.view', 'fuel_balance.adjust'], 'url' => route('solar.balances.index'), 'color' => 'teal', 'label' => 'Saldo Armada',
     'icon' => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
    ['perm' => ['fuel_stock.view', 'fuel_stock.ledger', 'fuel_stock.adjust'], 'url' => $u->hasAnyPermission(['fuel_stock.view', 'fuel_stock.adjust']) ? route('solar.stocks.index') : route('solar.stocks.ledger'), 'color' => 'blue', 'label' => 'Stok Solar',
     'icon' => '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>'],
    ['perm' => ['fuel_report.view'], 'url' => route('solar.reports.index'), 'color' => 'slate', 'label' => 'Laporan Pemakaian',
     'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'],
    ['perm' => ['fuel_fleet.view', 'fuel_fleet.manage'], 'url' => route('fleets.index', ['modul' => 'pemakaian_solar']), 'color' => 'indigo', 'label' => 'Master Armada',
     'icon' => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
    ['perm' => ['fuel_garden.view', 'fuel_garden.manage'], 'url' => route('gardens.index', ['modul' => 'pemakaian_solar']), 'color' => 'green', 'label' => 'Master Kebun',
     'icon' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>'],
    ['perm' => ['fuel_supplier.view', 'fuel_supplier.manage'], 'url' => route('suppliers.index', ['modul' => 'pemakaian_solar']), 'color' => 'fuchsia', 'label' => 'Master Supplier',
     'icon' => '<path d="M5 21V7l7-4 7 4v14"/><path d="M3 21h18"/><path d="M9 9h1"/><path d="M9 13h1"/><path d="M14 9h1"/><path d="M14 13h1"/><path d="M9 21v-4h6v4"/>'],
  ];

  $visibleMenus = array_filter($menus, fn ($m) => $u->hasAnyPermission($m['perm']));
@endphp

@php $fmtL = fn ($v) => \App\Models\PemakaianSolar\Stock::fmt($v); @endphp

<section class="section">
  <div class="section__head">
    <h2 class="section__title">Ringkasan Hari Ini</h2>
  </div>
  <div class="summary-grid">

    <a href="{{ $can('fuel_trip.view', 'fuel_trip.create') ? route('solar.trips.index', ['date' => 'today']) : '#' }}" class="summary-card summary-card--green {{ $can('fuel_trip.view', 'fuel_trip.create') ? '' : 'is-disabled' }}">
      <span class="summary-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
      </span>
      <div class="summary-card__body">
        <span class="summary-card__label">Solar Dibeli</span>
        <span class="summary-card__value">{{ $fmtL($summary['purchased_today']) }} L</span>
      </div>
    </a>

    <a href="{{ $can('fuel_trip.view', 'fuel_trip.create') ? route('solar.trips.index', ['date' => 'today']) : '#' }}" class="summary-card summary-card--rose {{ $can('fuel_trip.view', 'fuel_trip.create') ? '' : 'is-disabled' }}">
      <span class="summary-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
      </span>
      <div class="summary-card__body">
        <span class="summary-card__label">Pemakaian</span>
        <span class="summary-card__value">{{ $fmtL($summary['used_today']) }} L</span>
      </div>
    </a>

    <a href="{{ $can('fuel_stock.view', 'fuel_stock.adjust') ? route('solar.stocks.index', ['low_stock' => 1]) : '#' }}" class="summary-card summary-card--amber {{ $can('fuel_stock.view', 'fuel_stock.adjust') ? '' : 'is-disabled' }}">
      <span class="summary-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </span>
      <div class="summary-card__body">
        <span class="summary-card__label">Stok Kebun Menipis</span>
        <span class="summary-card__value">{{ $summary['low_stock_count'] }} kebun</span>
      </div>
    </a>

    <a href="{{ $can('fuel_balance.view', 'fuel_balance.adjust') ? route('solar.balances.index', ['low' => 1]) : '#' }}" class="summary-card summary-card--blue {{ $can('fuel_balance.view', 'fuel_balance.adjust') ? '' : 'is-disabled' }}">
      <span class="summary-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      </span>
      <div class="summary-card__body">
        <span class="summary-card__label">Standby Armada Rendah</span>
        <span class="summary-card__value">{{ $summary['low_standby_count'] }} armada</span>
      </div>
    </a>

    <a href="{{ count($visibleMenus) ? route('solar.transactions.index') : '#' }}" class="summary-card summary-card--violet {{ count($visibleMenus) ? '' : 'is-disabled' }}">
      <span class="summary-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11H1l8-8v8zm0 0v8l8-8H9z"/><path d="M14 3h7v7"/></svg>
      </span>
      <div class="summary-card__body">
        <span class="summary-card__label">Transaksi Hari Ini</span>
        <span class="summary-card__value">{{ $summary['transactions_today'] }} aktivitas</span>
      </div>
    </a>

  </div>
</section>

<section class="section">
  <div class="section__head">
    <h2 class="section__title">Menu Utama</h2>
  </div>

  @if (count($visibleMenus))
  <div class="menu-grid">
    @foreach ($visibleMenus as $m)
      <a href="{{ $m['url'] }}" class="menu-item">
        <span class="menu-item__icon menu-item__icon--{{ $m['color'] }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $m['icon'] !!}</svg>
        </span>
        <span class="menu-item__label">{{ $m['label'] }}</span>
      </a>
    @endforeach
  </div>
  @else
    <p class="empty-state">Anda belum memiliki akses di modul ini.</p>
  @endif
</section>

@endsection