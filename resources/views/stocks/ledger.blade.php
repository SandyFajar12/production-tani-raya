@extends('layouts.app')

@section('title', 'Kartu Stok — TaniRaya ERP')
@section('page-title', 'Kartu Stok')
@section('page-subtitle', 'Riwayat pergerakan stok')
@section('back-url', route('stocks.index'))

@section('content')

<div class="form-group">
  <label class="form-label" for="ks-filter">Pilih Sparepart</label>
  <select class="form-control" id="ks-filter" onchange="window.location.href='{{ route('stocks.ledger') }}?sparepart_id=' + this.value">
    @foreach ($spareparts as $sp)
      <option value="{{ $sp->id }}" {{ $selected && $selected->id === $sp->id ? 'selected' : '' }}>{{ $sp->name }} ({{ $sp->code }})</option>
    @endforeach
  </select>
</div>

<section class="section">
  <div class="section__head">
    <h2 class="section__title">Riwayat — {{ $selected->name ?? '-' }}</h2>
  </div>

  <div class="list-view is-active">
    @forelse ($movements as $m)
      <div class="list-card">
        <div class="list-card__main">
          <span class="list-card__title">
            @if ($m->type === 'purchase') Pembelian
            @elseif ($m->type === 'usage') Pemakaian
            @else Penyesuaian Stok
            @endif
          </span>
          <span class="list-card__meta">{{ $m->created_at->format('d M Y H:i') }}</span>
        </div>
        <div class="list-card__side">
          @if ($m->type === 'purchase')
            <span class="badge badge--approved">Masuk</span>
          @elseif ($m->type === 'usage')
            <span class="badge badge--rejected">Keluar</span>
          @else
            <span class="badge badge--pending">Opname</span>
          @endif
          <span class="list-card__value">{{ $m->quantity_change > 0 ? '+' : '' }}{{ $m->quantity_change }}</span>
          <span class="list-card__meta">Saldo: {{ $m->balance_after }}</span>
        </div>
      </div>
    @empty
      <p class="empty-state">Belum ada riwayat pergerakan stok untuk sparepart ini.</p>
    @endforelse
  </div>
</section>

@endsection
