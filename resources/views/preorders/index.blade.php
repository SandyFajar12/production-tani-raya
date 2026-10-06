@extends('layouts.app')

@section('title', 'Pre-Order — TaniRaya ERP')
@section('page-title', 'Pre-Order')
@section('page-subtitle', 'Pengadaan Sparepart')

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Pengajuan</button>
  <button class="tab-btn" data-tab-target="viewForm">+ Ajukan Baru</button>
</div>

@include('partials.per-page-selector')

<section id="viewList" class="view list-view is-active">
  @forelse ($preorders as $po)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $po->sparepart->name ?? '-' }} — {{ $po->quantity }} {{ $po->unit->name ?? '' }}</span>
        <span class="list-card__meta">{{ $po->fleet->name ?? '-' }} &middot; Diajukan oleh {{ $po->requester->name ?? '-' }} &middot; {{ $po->created_at->format('d M Y') }}</span>
      </div>
      <div class="list-card__side">
        @if ($po->status === 'pending')
          <span class="badge badge--pending">Menunggu</span>
          @if (auth()->user()->hasPermission('preorder.approve'))
            <div style="display:flex; gap:6px; margin-top:4px;">
              <form method="POST" action="{{ route('preorders.approve', $po) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary" style="width:auto; height:30px; padding:0 10px;">Setujui</button>
              </form>
              <form method="POST" action="{{ route('preorders.reject', $po) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-secondary" style="width:auto; height:30px; padding:0 10px;">Tolak</button>
              </form>
            </div>
          @endif
        @elseif ($po->status === 'approved')
          <span class="badge badge--approved">Disetujui</span>
        @else
          <span class="badge badge--rejected">Ditolak</span>
        @endif
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px; margin-top:4px;"
          onclick="showDetailModal(this)"
          data-detail-title="Detail Pre-Order"
          data-detail="{{ json_encode([
            'Kode' => $po->code,
            'Sparepart' => $po->sparepart->name ?? '-',
            'Jumlah' => $po->quantity.' '.($po->unit->name ?? ''),
            'Armada' => $po->fleet->name ?? '-',
            'Tgl Dibutuhkan' => $po->needed_date ? \Carbon\Carbon::parse($po->needed_date)->format('d M Y') : '-',
            'Alasan' => $po->reason ?? '-',
            'Status' => ucfirst($po->status),
            'Diajukan Oleh' => $po->requester->name ?? '-',
            'Tgl Pengajuan' => $po->created_at->format('d M Y H:i'),
            'Diproses Oleh' => $po->approver->name ?? '-',
            'Catatan Approval' => $po->approval_notes ?? '-',
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}">Detail</button>
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada pengajuan pre-order.</p>
  @endforelse
</section>

@include('partials.pagination', ['paginator' => $preorders])

<section id="viewForm" class="view">
<form method="POST" action="{{ route('preorders.store') }}" data-safe-form="preorder">
    @csrf

    <div class="form-group">
      <label class="form-label" for="po-sparepart">Nama Sparepart</label>
      <select class="form-control" id="po-sparepart" name="sparepart_id" required>
        @foreach ($spareparts as $sp)
          <option value="{{ $sp->id }}" {{ old('sparepart_id') == $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="po-qty">Jumlah</label>
      <input class="form-control" id="po-qty" name="quantity" type="number" min="1" value="{{ old('quantity') }}" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="po-armada">Unit Armada Terkait</label>
      <select class="form-control" id="po-armada" name="fleet_id">
        <option value="">— Tidak terkait armada —</option>
        @foreach ($fleets as $f)
          <option value="{{ $f->id }}" {{ old('fleet_id') == $f->id ? 'selected' : '' }}>{{ $f->name }} — {{ $f->code }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="po-tanggal">Tanggal Dibutuhkan</label>
      <input class="form-control" id="po-tanggal" name="needed_date" type="date" value="{{ old('needed_date') }}">
    </div>

    <div class="form-group">
      <label class="form-label" for="po-catatan">Alasan / Catatan</label>
      <textarea class="form-control" id="po-catatan" name="reason" rows="3" placeholder="contoh: stok habis, komponen aus">{{ old('reason') }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Ajukan Pre-Order</button>
  </form>
</section>

@endsection
