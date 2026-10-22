@extends('layouts.app')

@section('title', 'Kartu Stok Kebutuhan — TaniRaya ERP')
@section('page-title', 'Kartu Stok')
@section('page-subtitle', 'Riwayat pergerakan stok kebutuhan kebun')
@section('back-url', auth()->user()->hasAnyPermission(['need_stock.view', 'need_stock.adjust']) ? route('kebun.stocks.index') : route('needs.index'))

@section('content')

@php
  $iEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  $typeTitle = [
    'purchase' => 'Pembelian', 'usage' => 'Pemakaian', 'adjustment' => 'Penyesuaian Stok',
    'transfer_in' => 'Transfer Masuk', 'transfer_out' => 'Transfer Keluar',
  ];
@endphp

<div class="form-group">
  <label class="form-label" for="ks-filter">Pilih Item</label>
  <select class="form-control" id="ks-filter" onchange="goLedger()">
    @foreach ($items as $it)
      <option value="{{ $it->id }}" {{ $selected && $selected->id === $it->id ? 'selected' : '' }}>{{ $it->name }} ({{ $it->code }})</option>
    @endforeach
  </select>
</div>

<div class="form-group">
  <label class="form-label" for="ks-garden">Kebun</label>
  <select class="form-control" id="ks-garden" onchange="goLedger()">
    <option value="">Semua Kebun</option>
    @foreach ($gardens as $g)
      <option value="{{ $g->id }}" {{ (string) $gardenId === (string) $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
    @endforeach
  </select>
</div>

<section class="section">
  <div class="section__head">
    <h2 class="section__title">Riwayat — {{ $selected->name ?? '-' }}</h2>
  </div>

  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari kebun / jenis (masuk, keluar, transfer)...'])

  <div class="table-wrap">
    <table class="data-table data-table--wide">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Jenis</th>
          <th>Kebun</th>
          <th class="num">Qty</th>
          <th class="num">Saldo Kebun</th>
          <th>Keterangan</th>
          <th class="col-aksi">Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($movements as $m)
        @php $d = $details[$m->id] ?? null; @endphp
        <tr>
          <td style="white-space:nowrap;">{{ $m->created_at->format('d M Y H:i') }}</td>
          <td>{{ $typeTitle[$m->type] ?? $m->type }}</td>
          <td>{{ $m->garden->name ?? '-' }}</td>
          <td class="num">{{ $m->quantity_change > 0 ? '+' : '' }}{{ $m->quantity_change }}</td>
          <td class="num">{{ $m->balance_after }}</td>
          <td>
            @if (in_array($m->type, ['purchase', 'transfer_in']))
              <span class="badge badge--approved">Masuk</span>
            @elseif (in_array($m->type, ['usage', 'transfer_out']))
              <span class="badge badge--rejected">Keluar</span>
            @else
              <span class="badge badge--pending">Opname</span>
            @endif
          </td>
          <td class="col-aksi">
            @if ($d)
              <div class="action-group">
                <button type="button" class="btn btn-sm btn-secondary"
                  onclick="showDetailModal(this)"
                  data-detail-title="{{ $d['title'] }}"
                  data-detail="{{ json_encode($d['rows'], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iEye !!}</button>
              </div>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="empty-state">Belum ada riwayat pergerakan stok untuk pilihan ini.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @include('partials.pagination', ['paginator' => $movements])
</section>

@endsection

@section('scripts')
<script>
  function goLedger() {
    var sp = document.getElementById('ks-filter').value;
    var g = document.getElementById('ks-garden').value;
    window.location.href = '{{ route('kebun.stocks.ledger') }}?item_id=' + sp + (g ? '&garden_id=' + g : '');
  }
</script>
@endsection