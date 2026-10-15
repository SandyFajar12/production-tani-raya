@extends('layouts.app')

@section('title', 'Kartu Stok — TaniRaya ERP')
@section('page-title', 'Kartu Stok')
@section('page-subtitle', 'Riwayat pergerakan stok')
@section('back-url', auth()->user()->hasAnyPermission(['stock.view', 'stock.adjust']) ? route('stocks.index') : route('dashboard'))

@section('content')

@php
  $iEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  $typeTitle = [
    'purchase' => 'Pembelian', 'usage' => 'Pemakaian', 'adjustment' => 'Penyesuaian Stok',
    'transfer_in' => 'Transfer Masuk', 'transfer_out' => 'Transfer Keluar',
  ];
  $adjLabel = [
    'opname' => 'Stok Opname', 'damaged_lost' => 'Barang Rusak / Hilang',
    'return_to_supplier' => 'Retur ke Supplier', 'other' => 'Lainnya', 'transfer' => 'Transfer Stok',
  ];
@endphp

<div class="form-group">
  <label class="form-label" for="ks-filter">Pilih Sparepart</label>
  <select class="form-control" id="ks-filter" onchange="goLedger()">
    @foreach ($spareparts as $sp)
      <option value="{{ $sp->id }}" {{ $selected && $selected->id === $sp->id ? 'selected' : '' }}>{{ $sp->name }} ({{ $sp->code }})</option>
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
        @php
          $detail = null;
          $detailTitle = 'Detail';

          if (in_array($m->type, ['adjustment', 'transfer_in', 'transfer_out']) && $adjustments->has($m->reference_id)) {
            $adj = $adjustments[$m->reference_id];
            if ($adj->type === 'transfer') {
              $detailTitle = 'Detail Transfer Stok';
              $detail = [
                '__photo__' => $adj->photo_path ? route('stocks.photo', $adj->photo_path) : null,
                'Dari Kebun' => $adj->garden->name ?? '-',
                'Ke Kebun' => $adj->toGarden->name ?? '-',
                'Jumlah Dikirim' => $adj->physical_qty,
                'Catatan' => $adj->reason ?? '-',
                'Lokasi' => $adj->location_label ?? '-',
                'Dilakukan Oleh' => $adj->adjuster->name ?? '-',
              ];
            } else {
              $detailTitle = 'Detail Penyesuaian Stok';
              $detail = [
                '__photo__' => $adj->photo_path ? route('stocks.photo', $adj->photo_path) : null,
                'Jenis' => $adjLabel[$adj->type] ?? ucfirst($adj->type),
                'Kebun' => $adj->garden->name ?? '-',
                'Stok Sebelum' => $adj->system_qty_before,
                'Stok Fisik' => $adj->physical_qty,
                'Selisih' => $adj->difference,
                'Catatan' => $adj->reason ?? '-',
                'Lokasi' => $adj->location_label ?? '-',
                'Koordinat' => ($adj->latitude && $adj->longitude) ? $adj->latitude.', '.$adj->longitude : '-',
                'Dilakukan Oleh' => $adj->adjuster->name ?? '-',
              ];
            }
          } elseif ($m->type === 'purchase' && $purchases->has($m->reference_id)) {
            $pur = $purchases[$m->reference_id];
            $detailTitle = 'Detail Pembelian';
            $detail = [
              'Supplier' => $pur->supplier->name ?? '-',
              'No. Invoice' => $pur->invoice_number ?? '-',
              'Jumlah' => $pur->quantity,
              'Total' => 'Rp'.number_format($pur->total_price, 0, ',', '.'),
              'Diterima di' => $pur->receivedGarden->name ?? '-',
              'Kebun Tujuan' => $pur->targetGarden->name ?? '-',
              'Dicatat Oleh' => $pur->recorder->name ?? '-',
            ];
          } elseif ($m->type === 'usage' && $usages->has($m->reference_id)) {
            $use = $usages[$m->reference_id];
            $detailTitle = 'Detail Pemakaian';
            $detail = [
              'Armada' => $use->fleet->name ?? '-',
              'Lokasi Kebun' => $use->garden->name ?? '-',
              'Jumlah' => $use->quantity,
              'Catatan' => $use->notes ?? '-',
              'Dicatat Oleh' => $use->recorder->name ?? '-',
            ];
          }
        @endphp
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
            @if ($detail)
              <div class="action-group">
                <button type="button" class="btn btn-sm btn-secondary"
                  onclick="showDetailModal(this)"
                  data-detail-title="{{ $detailTitle }}"
                  data-detail="{{ json_encode($detail, JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iEye !!}</button>
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
    window.location.href = '{{ route('stocks.ledger') }}?sparepart_id=' + sp + (g ? '&garden_id=' + g : '');
  }
</script>
@endsection