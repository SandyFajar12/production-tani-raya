@extends('layouts.app')

@section('title', 'Pre-Order — TaniRaya ERP')
@section('page-title', 'Pre-Order')
@section('page-subtitle', 'Pengadaan Sparepart')

@section('content')

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Pengajuan</button>
  @permission('preorder.create')
  <button class="tab-btn" data-tab-target="viewForm">+ Ajukan Baru</button>
  @endpermission
</div>

<section id="viewList" class="view is-active">
  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari nama sparepart...'])

  @if (request('status') === 'pending')
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12.5px; color:var(--color-text-muted);">
      <span>Menampilkan pengajuan yang menunggu persetujuan saja</span>
      <a href="{{ route('preorders.index') }}" class="section__link">Lihat semua →</a>
    </div>
  @endif

  <div class="list-view is-active">
  @forelse ($preorders as $po)
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $po->sparepart->name ?? '-' }} — {{ $po->quantity }} {{ $po->unit->name ?? '' }}</span>
        <span class="list-card__meta">{{ $po->fleet->name ?? '-' }} &middot; Diajukan oleh {{ $po->requester->name ?? '-' }} &middot; {{ $po->created_at->format('d M Y') }}</span>
      </div>
      <div class="list-card__side">
        @if ($po->status === 'pending')
          <span class="badge badge--pending">Menunggu</span>
        @elseif ($po->status === 'approved')
          <span class="badge badge--approved">Disetujui ({{ $po->approved_quantity ?? $po->quantity }})</span>
        @elseif ($po->status === 'purchased')
          <span class="badge badge--active">Dibeli</span>
        @else
          <span class="badge badge--rejected">Ditolak</span>
        @endif
        <div class="action-group">
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
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        @if ($po->status === 'pending' && auth()->user()->hasPermission('preorder.approve'))
          <button type="button" class="btn btn-sm btn-primary btn-icon--success" title="Setujui" aria-label="Setujui"
            onclick="openApprovePopup(this)"
            data-po-url="{{ route('preorders.approve', $po) }}"
            data-po-code="{{ $po->code }}"
            data-po-qty="{{ $po->quantity }}"><svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg></button>
          <form method="POST" action="{{ route('preorders.reject', $po) }}" onsubmit="return confirmDelete('Apakah kamu yakin ingin menolak pengajuan ini?')">
            @csrf
            <button type="submit" class="btn btn-sm btn-secondary btn-icon--danger" title="Tolak" aria-label="Tolak"><svg viewBox="0 0 24 24" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
          </form>
        @endif
        </div>
      </div>
    </div>
  @empty
    <p class="empty-state">Belum ada pengajuan pre-order.</p>
  @endforelse
  </div>

  @include('partials.pagination', ['paginator' => $preorders])
</section>

@permission('preorder.create')
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
@endpermission

<div class="modal-overlay" id="approveModalOverlay">
  <div class="modal-box">
    <div class="modal-box__header">
      <span class="modal-box__title">Setujui <span id="approvePoLabel"></span></span>
      <button type="button" class="modal-box__close" onclick="closeApprovePopup()">&times;</button>
    </div>
    <form id="approveForm" method="POST" action="">
      @csrf
      <div class="form-group">
        <label class="form-label" for="approveQtyInput">Jumlah Disetujui</label>
        <input class="form-control" type="number" min="1" id="approveQtyInput" name="approved_quantity" required>
      </div>
      <div style="display:flex; justify-content:flex-end; margin-top:14px;">
        <button type="submit" class="btn btn-primary" style="width:auto; padding:0 24px;">OK</button>
      </div>
    </form>
  </div>
</div>          
          
@endsection
