@extends('layouts.app')

@section('title', 'Laporan Pemakaian — TaniRaya ERP')
@section('page-title', 'Laporan Pemakaian')
@section('page-subtitle', 'Pemakaian sparepart per armada')

@section('content')

<form method="GET" action="{{ route('reports.index') }}">
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

<section class="section">
  <div class="section__head">
    <h2 class="section__title">Preview ({{ $rows->total() }} pemakaian)</h2>
    @if ($rows->total() > 0)
      <a href="{{ route('reports.export', ['start_date' => $start, 'end_date' => $end, 'fleet_id' => $fleetId, 'search' => $search]) }}" class="section__link">⬇ Export Excel</a>
    @endif
  </div>

  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari armada / sparepart / kebun / catatan...'])

  <div class="table-wrap">
    <table class="data-table data-table--wide">
      <thead>
        <tr>
          <th>Armada</th>
          <th>Sparepart</th>
          <th>Tanggal Pemakaian</th>
          <th class="num">Qty</th>
          <th>Lokasi Kebun</th>
          <th class="num">Stok Terakhir</th>
          <th>Lama Sejak Penggantian Sebelumnya</th>
          <th>Catatan</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($rows as $r)
        <tr>
          <td>{{ $r['fleet'] }}</td>
          <td>{{ $r['sparepart'] }}</td>
          <td style="white-space:nowrap;">{{ $r['date'] }}</td>
          <td class="num">{{ $r['quantity'] }}{{ $r['unit'] ? ' '.$r['unit'] : '' }}</td>
          <td>{{ $r['garden'] }}</td>
          <td class="num">{{ $r['balance'] !== null ? $r['balance'].($r['unit'] ? ' '.$r['unit'] : '') : '-' }}</td>
          <td style="white-space:nowrap;">{{ $r['days'] !== null ? $r['days'].' hari' : '-' }}</td>
          <td title="{{ $r['notes'] }}">{{ $r['notes_short'] !== '' ? $r['notes_short'] : '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="8" class="empty-state">Tidak ada pemakaian di rentang tanggal ini.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @include('partials.pagination', ['paginator' => $rows])

  @if ($rows->total() > 0)
    <p class="demo-note">Lama pakai dihitung dari pemakaian sparepart yang sama pada armada yang sama. Tampil "-" kalau belum pernah ada pemakaian sebelumnya.</p>
  @endif
</section>

@endsection