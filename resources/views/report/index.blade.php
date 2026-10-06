@extends('layouts.app')

@section('title', 'Laporan Transaksi — TaniRaya ERP')
@section('page-title', 'Laporan Transaksi')
@section('page-subtitle', 'Preview & export berdasarkan rentang tanggal')

@section('content')

<form method="GET" action="{{ route('reports.index') }}">
  <div class="date-range-row">
    <div class="form-group">
      <label class="form-label" for="start_date">Dari Tanggal</label>
      <input class="form-control" type="date" id="start_date" name="start_date" value="{{ $start }}">
    </div>
    <div class="form-group">
      <label class="form-label" for="end_date">Sampai Tanggal</label>
      <input class="form-control" type="date" id="end_date" name="end_date" value="{{ $end }}">
    </div>
  </div>
  <button type="submit" class="btn btn-secondary btn-block">Tampilkan Preview</button>
</form>

<section class="section">
  <div class="section__head">
    <h2 class="section__title">Preview ({{ $transactions->count() }} transaksi)</h2>
    @if ($transactions->count() > 0)
      <a href="{{ route('reports.export', ['start_date' => $start, 'end_date' => $end]) }}" class="section__link">⬇ Export CSV</a>
    @endif
  </div>

  <div class="list-view is-active">
    @forelse ($transactions as $t)
      <div class="list-card">
        <div class="list-card__main">
          <span class="list-card__title">{{ $t['sparepart'] }}</span>
          <span class="list-card__meta">{{ $t['type'] }} &middot; {{ $t['related'] }} &middot; {{ \Carbon\Carbon::parse($t['date'])->format('d M Y') }}</span>
        </div>
        <div class="list-card__side">
          <span class="list-card__value">
            {{ $t['quantity'] }}@if ($t['value']) &middot; Rp{{ number_format($t['value'], 0, ',', '.') }}@endif
          </span>
          <span class="list-card__meta">{{ $t['recorded_by'] }}</span>
        </div>
      </div>
    @empty
      <p class="empty-state">Tidak ada transaksi di rentang tanggal ini.</p>
    @endforelse
  </div>
</section>

@endsection