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

          @if ($m->type === 'adjustment' && $adjustments->has($m->reference_id))
            @php $adj = $adjustments[$m->reference_id]; @endphp
            <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px; margin-top:4px;"
              onclick="showDetailModal(this)"
              data-detail-title="Detail Penyesuaian Stok"
              data-detail="{{ json_encode([
                '__photo__' => $adj->photo_path ? route('stocks.photo', $adj->photo_path) : null,
                'Jenis' => ucfirst($adj->type),
                'Stok Sebelum' => $adj->system_qty_before,
                'Stok Fisik' => $adj->physical_qty,
                'Selisih' => $adj->difference,
                'Catatan' => $adj->reason ?? '-',
                'Lokasi' => $adj->location_label ?? '-',
                'Koordinat' => ($adj->latitude && $adj->longitude) ? $adj->latitude.', '.$adj->longitude : '-',
                'Dilakukan Oleh' => $adj->adjuster->name ?? '-',
              ], JSON_HEX_APOS | JSON_HEX_QUOT) }}">Detail</button>
          @elseif ($m->type === 'purchase' && $purchases->has($m->reference_id))
            @php $pur = $purchases[$m->reference_id]; @endphp
            <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px; margin-top:4px;"
              onclick="showDetailModal(this)"
              data-detail-title="Detail Pembelian"
              data-detail="{{ json_encode([
                'Supplier' => $pur->supplier->name ?? '-',
                'No. Invoice' => $pur->invoice_number ?? '-',
                'Jumlah' => $pur->quantity,
                'Total' => 'Rp'.number_format($pur->total_price, 0, ',', '.'),
                'Dicatat Oleh' => $pur->recorder->name ?? '-',
              ], JSON_HEX_APOS | JSON_HEX_QUOT) }}">Detail</button>
          @elseif ($m->type === 'usage' && $usages->has($m->reference_id))
            @php $use = $usages[$m->reference_id]; @endphp
            <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px; margin-top:4px;"
              onclick="showDetailModal(this)"
              data-detail-title="Detail Pemakaian"
              data-detail="{{ json_encode([
                'Armada' => $use->fleet->name ?? '-',
                'Jumlah' => $use->quantity,
                'Catatan' => $use->notes ?? '-',
                'Dicatat Oleh' => $use->recorder->name ?? '-',
              ], JSON_HEX_APOS | JSON_HEX_QUOT) }}">Detail</button>
          @endif
        </div>
      </div>
    @empty
      <p class="empty-state">Belum ada riwayat pergerakan stok untuk sparepart ini.</p>
    @endforelse
  </div>
</section>

@endsection
