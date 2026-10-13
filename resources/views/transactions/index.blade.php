@extends('layouts.app')

@section('title', 'Riwayat Transaksi — TaniRaya ERP')
@section('page-title', 'Riwayat Transaksi')
@section('page-subtitle', 'Semua aktivitas hari ini')

@section('content')

<div class="list-view is-active">
  @forelse ($transactions as $t)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $t['title'] }}</span>
        <span class="list-card__meta">{{ $t['meta'] }} &middot; {{ $t['time']->format('H:i') }}</span>
      </div>
      <div class="list-card__side">
        @if ($t['type'] === 'purchase')
          <span class="badge badge--approved">Pembelian</span>
        @elseif ($t['type'] === 'usage')
          <span class="badge badge--rejected">Pemakaian</span>
        @else
          <span class="badge badge--pending">Pre-Order</span>
        @endif
        <span class="list-card__value">{{ $t['value'] }}</span>
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada transaksi hari ini.</p>
  @endforelse
</div>

@endsection