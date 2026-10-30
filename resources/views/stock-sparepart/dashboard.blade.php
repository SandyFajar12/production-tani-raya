@extends('layouts.app')

@section('title', 'Dashboard — TaniRaya ERP')
@section('brand-sub', 'Stok Sparepart')

@section('content')

@php
  $u = auth()->user();
  $can = fn (...$slugs) => $u->hasAnyPermission($slugs);

  $menus = [
    ['perm' => ['stock.view', 'stock.ledger', 'stock.adjust'], 'route' => $u->hasAnyPermission(['stock.view', 'stock.adjust']) ? 'stocks.index' : 'stocks.ledger', 'color' => 'blue', 'label' => 'Update Stok',
     'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>'],
    ['perm' => ['preorder.view', 'preorder.create', 'preorder.review', 'preorder.approve'], 'route' => 'preorders.index', 'color' => 'violet', 'label' => 'Pre-Order',
     'icon' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>'],
    ['perm' => ['usage.view', 'usage.create'], 'route' => 'usages.index', 'color' => 'rose', 'label' => 'Pemakaian',
     'icon' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>'],
    ['perm' => ['purchase.view', 'purchase.create'], 'route' => 'purchases.index', 'color' => 'green', 'label' => 'Pembelian',
     'icon' => '<circle cx="9" cy="21" r="1"/><circle cx="17" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>'],
    ['perm' => ['report.view'], 'route' => 'reports.index', 'color' => 'slate', 'label' => 'Laporan Pemakaian',
     'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'],
    ['perm' => ['sparepart.view', 'sparepart.manage'], 'route' => 'spareparts.index', 'color' => 'amber', 'label' => 'Master Sparepart',
     'icon' => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8z"/><path d="M3.27 6.96 12 12l8.73-5.04M12 22.08V12"/>'],
    ['perm' => ['fleet.view', 'fleet.manage'], 'route' => 'fleets.index', 'color' => 'indigo', 'label' => 'Master Armada',
     'icon' => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
    ['perm' => ['supplier.view', 'supplier.manage'], 'route' => 'suppliers.index', 'color' => 'fuchsia', 'label' => 'Master Supplier',
     'icon' => '<path d="M5 21V7l7-4 7 4v14"/><path d="M3 21h18"/><path d="M9 9h1"/><path d="M9 13h1"/><path d="M14 9h1"/><path d="M14 13h1"/><path d="M9 21v-4h6v4"/>'],
    ['perm' => ['garden.view', 'garden.manage'], 'route' => 'gardens.index', 'color' => 'green', 'label' => 'Master Kebun',
     'icon' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>'],
  ];

  $visibleMenus = array_filter($menus, fn ($m) => $u->hasAnyPermission($m['perm']));
@endphp

<section class="section">
  <div class="section__head">
    <h2 class="section__title">Ringkasan Hari Ini</h2>
  </div>
  <div class="summary-grid">

    <a href="{{ $can('purchase.view', 'purchase.create') ? route('purchases.index', ['date' => 'today']) : '#' }}" class="summary-card summary-card--green {{ $can('purchase.view', 'purchase.create') ? '' : 'is-disabled' }}">
      <span class="summary-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h2l.4 2M7 13h10l3-8H5.4M7 13L5.4 5M7 13l-2.3 4.6A1 1 0 0 0 5.6 19H17"/><circle cx="9" cy="21" r="1"/><circle cx="17" cy="21" r="1"/></svg>
      </span>
      <div class="summary-card__body">
        <span class="summary-card__label">Pembelian</span>
        <span class="summary-card__value">Rp{{ number_format($summary['purchase_today'], 0, ',', '.') }}</span>
      </div>
    </a>

    <a href="{{ $can('usage.view', 'usage.create') ? route('usages.index', ['date' => 'today']) : '#' }}" class="summary-card summary-card--rose {{ $can('usage.view', 'usage.create') ? '' : 'is-disabled' }}">
      <span class="summary-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
      </span>
      <div class="summary-card__body">
        <span class="summary-card__label">Pemakaian</span>
        <span class="summary-card__value">{{ $summary['usage_today'] }} item</span>
      </div>
    </a>

    <a href="{{ $can('stock.view', 'stock.adjust') ? route('stocks.index', ['low_stock' => 1]) : '#' }}" class="summary-card summary-card--amber {{ $can('stock.view', 'stock.adjust') ? '' : 'is-disabled' }}">
      <span class="summary-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </span>
      <div class="summary-card__body">
        <span class="summary-card__label">Stok Menipis</span>
        <span class="summary-card__value">{{ $summary['low_stock_count'] }} item</span>
      </div>
    </a>

    <a href="{{ count($visibleMenus) ? route('transactions.index') : '#' }}" class="summary-card summary-card--blue {{ count($visibleMenus) ? '' : 'is-disabled' }}">
      <span class="summary-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11H1l8-8v8zm0 0v8l8-8H9z"/><path d="M14 3h7v7"/></svg>
      </span>
      <div class="summary-card__body">
        <span class="summary-card__label">Transaksi</span>
        <span class="summary-card__value">{{ $summary['transactions_today'] }} hari ini</span>
      </div>
    </a>

    <a href="{{ $can('preorder.view', 'preorder.create', 'preorder.review', 'preorder.approve') ? route('preorders.index', ['status' => 'waiting']) : '#' }}" class="summary-card summary-card--violet {{ $can('preorder.view', 'preorder.create', 'preorder.review', 'preorder.approve') ? '' : 'is-disabled' }}">
      <span class="summary-card__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      </span>
      <div class="summary-card__body">
        <span class="summary-card__label">Pre-Order Menunggu Tindakan Anda</span>
        <span class="summary-card__value">{{ $summary['preorder_pending'] }} pengajuan</span>
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
      <a href="{{ route($m['route']) }}" class="menu-item">
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