@extends('layouts.app')

@section('title', 'Laporan Pemakaian Kebutuhan — TaniRaya ERP')
@section('page-title', 'Laporan Pemakaian')
@section('page-subtitle', 'Pemakaian kebutuhan kebun per lokasi kebun')
@section('back-url', route('needs.index'))

@section('content')

<form method="GET" action="{{ route('kebun.reports.index') }}">
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
    <label class="form-label" for="garden_id">Lokasi Kebun</label>
    <select class="form-control" id="garden_id" name="garden_id">
      <option value="">Semua Kebun</option>
      @foreach ($gardens as $g)
        <option value="{{ $g->id }}" {{ (string) $gardenId === (string) $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
      @endforeach
    </select>
  </div>

  <button type="submit" class="btn btn-secondary btn-block">Tampilkan Preview</button>
</form>

<section class="section">
  <div class="section__head">
    <h2 class="section__title">Preview ({{ $rows->total() }} pemakaian)</h2>
    @if ($rows->total() > 0)
      <a href="{{ route('kebun.reports.export', ['start_date' => $start, 'end_date' => $end, 'garden_id' => $gardenId, 'search' => $search]) }}" class="section__link">⬇ Export Excel</a>
    @endif
  </div>

  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari kebun / kode / item / catatan...'])

  <div class="table-wrap">
    <table class="data-table data-table--wide">
      <thead>
        <tr>
          <th>Kebun</th>
          <th>Kode</th>
          <th>Item</th>
          <th>Tanggal Pemakaian</th>
          <th class="num">Qty</th>
          <th class="num">Stok Terakhir</th>
          <th>Lama Sejak Pemakaian Sebelumnya</th>
          <th>Catatan</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($rows as $r)
        <tr>
          <td>{{ $r['garden'] }}</td>
          <td style="white-space:nowrap;">{{ $r['item_code'] }}</td>
          <td>{{ $r['item'] }}</td>
          <td style="white-space:nowrap;">{{ $r['date'] }}</td>
          <td class="num">{{ $r['quantity'] }}{{ $r['unit'] ? ' '.$r['unit'] : '' }}</td>
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
    <p class="demo-note">Lama sejak pemakaian sebelumnya dihitung dari pemakaian item yang sama di kebun yang sama. Tampil "-" kalau belum pernah ada pemakaian sebelumnya.</p>
  @endif
</section>

@endsection