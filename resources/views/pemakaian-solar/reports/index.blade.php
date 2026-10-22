@extends('layouts.app')

@section('title', 'Laporan Pemakaian Solar — TaniRaya ERP')
@section('page-title', 'Laporan Pemakaian Solar')
@section('page-subtitle', 'Pemakaian solar per armada & catatan perjalanan')
@section('back-url', route('fuel.index'))

@section('content')

@php
  $fmt = fn ($v) => \App\Models\PemakaianSolar\Stock::fmt($v);
@endphp

<form method="GET" action="{{ route('solar.reports.index') }}">
  <input type="hidden" name="search" value="{{ $search }}">
  <input type="hidden" name="per_page" value="{{ request('per_page', 20) }}">

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

  <div class="form-group">
    <label class="form-label" for="fleet_id">Armada</label>
    <select class="form-control" id="fleet_id" name="fleet_id">
      <option value="">Semua Armada</option>
      @foreach ($fleets as $f)
        <option value="{{ $f->id }}" {{ (string) $fleetId === (string) $f->id ? 'selected' : '' }}>{{ $f->name }} — {{ $f->code }}</option>
      @endforeach
    </select>
  </div>

  <button type="submit" class="btn btn-secondary btn-block">Tampilkan Preview</button>
</form>

@if ($rows->total() > 0)
<section class="section">
  <div class="section__head">
    <h2 class="section__title">Ringkasan per Armada</h2>
  </div>

  <div class="table-wrap">
    <table class="data-table" data-no-number>
      <thead>
        <tr>
          <th>Armada</th>
          <th class="num">Perjalanan</th>
          <th class="num">Total Dibeli</th>
          <th class="num">Total Di-stock</th>
          <th class="num">Total Pemakaian</th>
          <th class="num">Rata-rata / Perjalanan</th>
        </tr>
      </thead>
      <tbody>
      @foreach ($summary as $s)
        <tr>
          <td>{{ $s['fleet'] }}<span class="sub">{{ $s['fleet_code'] }}</span></td>
          <td class="num">{{ $s['trips'] }}</td>
          <td class="num">{{ $fmt($s['purchased']) }} L</td>
          <td class="num">{{ $fmt($s['stocked']) }} L</td>
          <td class="num"><strong>{{ $fmt($s['used']) }} L</strong></td>
          <td class="num">{{ $fmt($s['avg']) }} L</td>
        </tr>
      @endforeach
        <tr style="background:var(--color-surface); font-weight:800;">
          <td>TOTAL</td>
          <td class="num">{{ $totals['trips'] }}</td>
          <td class="num">{{ $fmt($totals['purchased']) }} L</td>
          <td class="num">{{ $fmt($totals['stocked']) }} L</td>
          <td class="num">{{ $fmt($totals['used']) }} L</td>
          <td class="num">{{ $fmt($totals['avg']) }} L</td>
        </tr>
      </tbody>
    </table>
  </div>
</section>
@endif

<section class="section">
  <div class="section__head">
    <h2 class="section__title">Rincian ({{ $rows->total() }} perjalanan)</h2>
    @if ($rows->total() > 0)
      <a href="{{ route('solar.reports.export', ['start_date' => $start, 'end_date' => $end, 'fleet_id' => $fleetId, 'search' => $search]) }}" class="section__link">⬇ Export Excel</a>
    @endif
  </div>

  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari armada / kode / kebun / keterangan...'])

  <div class="table-wrap">
    <table class="data-table data-table--wide">
      <thead>
        <tr>
          <th>Armada</th>
          <th>Tanggal</th>
          <th class="num">Solar Awal</th>
          <th class="num">Dibeli</th>
          <th class="num">Di-stock</th>
          <th class="num">Sisa Standby</th>
          <th class="num">Pemakaian</th>
          <th>Keterangan Perjalanan</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($rows as $r)
        <tr>
          <td>{{ $r['fleet'] }}<span class="sub">{{ $r['fleet_code'] }}</span></td>
          <td style="white-space:nowrap;">{{ $r['date'] }}</td>
          <td class="num">{{ $fmt($r['opening']) }} L</td>
          <td class="num">{{ $fmt($r['purchased']) }} L</td>
          <td class="num">{{ $fmt($r['stocked']) }} L<span class="sub">{{ $r['garden'] }}</span></td>
          <td class="num">{{ $fmt($r['remaining']) }} L</td>
          <td class="num"><strong>{{ $fmt($r['used']) }} L</strong></td>
          <td style="white-space:normal; min-width:220px;">{{ $r['notes'] !== '' ? $r['notes'] : '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="8" class="empty-state">Tidak ada perjalanan di rentang tanggal ini.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @include('partials.pagination', ['paginator' => $rows])

  @if ($rows->total() > 0)
    <p class="demo-note">Pemakaian dihitung dari solar awal di armada + solar dibeli − solar di-stock − sisa solar standby. Ringkasan dihitung dari semua perjalanan yang cocok dengan filter, bukan hanya halaman ini.</p>
  @endif
</section>

@endsection