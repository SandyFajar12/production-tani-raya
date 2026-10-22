@extends('layouts.app')

@section('title', 'Pre-Order Kebutuhan — TaniRaya ERP')
@section('page-title', 'Pre-Order')
@section('page-subtitle', 'Pengadaan Kebutuhan Kebun')
@section('back-url', route('needs.index'))

@section('content')

@php
  $me = auth()->user();
  $canReview = $me->hasPermission('need_preorder.review');
  $canApprove = $me->hasPermission('need_preorder.approve');

  $statusLabel = [
    'pending' => 'Diajukan', 'forwarded' => 'Menunggu Owner', 'approved' => 'Disetujui Owner',
    'purchased' => 'Dibeli', 'shipped' => 'Dikirim', 'rejected' => 'Ditolak',
  ];
  $statusBadge = [
    'pending' => 'pending', 'forwarded' => 'pending', 'approved' => 'approved',
    'purchased' => 'active', 'shipped' => 'active', 'rejected' => 'rejected',
  ];

  $iconEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  $iconCheck = '<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';
  $iconCross = '<svg viewBox="0 0 24 24" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
  $iconArrow = '<svg viewBox="0 0 24 24" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
@endphp

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Daftar Pengajuan</button>
  @permission('need_preorder.create')
  <button class="tab-btn" data-tab-target="viewForm">+ Ajukan Baru</button>
  @endpermission
</div>

<section id="viewList" class="view is-active">
  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari nama item...'])

  @if (request('status') === 'waiting')
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12.5px; color:var(--color-text-muted);">
      <span>Menampilkan pengajuan yang menunggu tindakan Anda saja</span>
      <a href="{{ route('kebun.preorders.index') }}" class="section__link">Lihat semua →</a>
    </div>
  @endif

  <div class="list-view is-active">
  @forelse ($preorders as $po)
    @php
      $bought = (int) ($purchasedSums[$po->id] ?? 0);
      $sources = $stocksByItem->get($po->item_id, collect())
          ->where('garden_id', '!=', $po->garden_id)
          ->map(fn ($g) => ['id' => $g->garden_id, 'name' => $g->garden->name ?? '-', 'qty' => $g->quantity])
          ->values();
    @endphp
    <div class="list-card">
      <div class="list-card__main">
        <span class="list-card__title">{{ $po->item->name ?? '-' }} — {{ $po->quantity }} {{ $po->unit->name ?? '' }}</span>
        <span class="list-card__meta">Tujuan: {{ $po->garden->name ?? '-' }} &middot; Diajukan oleh {{ $po->requester->name ?? '-' }} &middot; {{ $po->created_at->format('d M Y') }}</span>
        @if ($po->status === 'approved')
          <span class="list-card__meta">Dibeli {{ $bought }} dari {{ $po->approved_quantity }}</span>
        @elseif ($po->status === 'purchased')
          <span class="list-card__meta">Terkirim {{ $po->shipped_quantity }} dari {{ $po->quantity }}</span>
        @endif
      </div>
      <div class="list-card__side">
        <span class="badge badge--{{ $statusBadge[$po->status] ?? 'pending' }}">{{ $statusLabel[$po->status] ?? $po->status }}@if ($po->status === 'approved') ({{ $po->approved_quantity }})@endif</span>

        <div class="action-group">
        <button type="button" class="btn btn-sm btn-secondary" style="width:auto; height:28px; padding:0 10px;"
          onclick="showDetailModal(this)"
          data-detail-title="Detail Pre-Order"
          data-detail="{{ json_encode([
            'Kode' => $po->code,
            'Item' => $po->item->name ?? '-',
            'Jumlah Diajukan' => $po->quantity.' '.($po->unit->name ?? ''),
            'Kebun Tujuan' => $po->garden->name ?? '-',
            'Tgl Dibutuhkan' => $po->needed_date ? \Carbon\Carbon::parse($po->needed_date)->format('d M Y') : '-',
            'Alasan' => $po->reason ?? '-',
            'Status' => $statusLabel[$po->status] ?? $po->status,
            'Jumlah Disetujui' => $po->approved_quantity ?? '-',
            'Sudah Dibeli' => $po->approved_quantity ? $bought.' dari '.$po->approved_quantity : '-',
            'Sudah Dikirim' => $po->shipped_quantity.' dari '.$po->quantity,
            'Diajukan Oleh' => $po->requester->name ?? '-',
            'Tgl Pengajuan' => $po->created_at->format('d M Y H:i'),
            'Diproses Oleh' => $po->approver->name ?? '-',
            'Catatan' => $po->approval_notes ?? '-',
          ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iconEye !!}</button>

        @if ($po->status === 'pending' && $canReview)
          <button type="button" class="btn btn-sm btn-primary btn-icon--success" title="Stok Ada (kirim dari stok)" aria-label="Stok Ada"
            onclick="openShipPopup(this)"
            data-url="{{ route('kebun.preorders.ship', $po) }}"
            data-code="{{ $po->code }}"
            data-qty="{{ $po->quantity }}"
            data-sources="{{ json_encode($sources, JSON_HEX_APOS | JSON_HEX_QUOT) }}">{!! $iconCheck !!}</button>
          <form method="POST" action="{{ route('kebun.preorders.forward', $po) }}" onsubmit="return confirmDelete('Stok tidak ada? Pengajuan ini akan diteruskan ke Owner untuk persetujuan pembelian.')">
            @csrf
            <button type="submit" class="btn btn-sm btn-secondary btn-icon--info" title="Teruskan ke Owner" aria-label="Teruskan ke Owner">{!! $iconArrow !!}</button>
          </form>
          <form method="POST" action="{{ route('kebun.preorders.reject', $po) }}" onsubmit="return confirmDelete('Apakah kamu yakin ingin menolak pengajuan ini?')">
            @csrf
            <button type="submit" class="btn btn-sm btn-secondary btn-icon--danger" title="Tolak" aria-label="Tolak">{!! $iconCross !!}</button>
          </form>
        @elseif ($po->status === 'forwarded' && $canApprove)
          <button type="button" class="btn btn-sm btn-primary btn-icon--success" title="Setujui" aria-label="Setujui"
            onclick="openApprovePopup(this)"
            data-po-url="{{ route('kebun.preorders.approve', $po) }}"
            data-po-code="{{ $po->code }}"
            data-po-qty="{{ $po->quantity }}">{!! $iconCheck !!}</button>
          <form method="POST" action="{{ route('kebun.preorders.reject', $po) }}" onsubmit="return confirmDelete('Apakah kamu yakin ingin menolak pengajuan ini?')">
            @csrf
            <button type="submit" class="btn btn-sm btn-secondary btn-icon--danger" title="Tolak" aria-label="Tolak">{!! $iconCross !!}</button>
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

@permission('need_preorder.create')
<section id="viewForm" class="view">
<form method="POST" action="{{ route('kebun.preorders.store') }}" data-safe-form="kebun-preorder">
    @csrf

    <div class="form-group">
      <label class="form-label" for="po-item">Nama Item</label>
      <select class="form-control" id="po-item" name="item_id" required>
        @foreach ($items as $it)
          <option value="{{ $it->id }}" {{ old('item_id') == $it->id ? 'selected' : '' }}>{{ $it->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="po-qty">Jumlah</label>
      <input class="form-control" id="po-qty" name="quantity" type="number" min="1" value="{{ old('quantity') }}" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="po-kebun">Kebun Tujuan</label>
      <select class="form-control" id="po-kebun" name="garden_id" required>
        <option value="">Pilih kebun tujuan…</option>
        @foreach ($gardens as $g)
          <option value="{{ $g->id }}" {{ old('garden_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="po-tanggal">Tanggal Dibutuhkan</label>
      <input class="form-control" id="po-tanggal" name="needed_date" type="date" value="{{ old('needed_date') }}">
    </div>

    <div class="form-group">
      <label class="form-label" for="po-catatan">Alasan / Catatan</label>
      <textarea class="form-control" id="po-catatan" name="reason" rows="3" placeholder="contoh: stok pupuk habis, persiapan pemupukan">{{ old('reason') }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Ajukan Pre-Order</button>
  </form>
</section>
@endpermission

{{-- Popup Owner: jumlah disetujui --}}
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

{{-- Popup Admin Pusat: kirim dari stok --}}
<div class="modal-overlay" id="shipModalOverlay">
  <div class="modal-box">
    <div class="modal-box__header">
      <span class="modal-box__title">Kirim dari Stok <span id="shipPoLabel"></span></span>
      <button type="button" class="modal-box__close" onclick="closeShipPopup()">&times;</button>
    </div>
    <form id="shipForm" method="POST" action="">
      @csrf
      <div class="form-group">
        <label class="form-label" for="shipSource">Diambil dari</label>
        <select class="form-control" id="shipSource" name="source_garden_id" required></select>
      </div>
      <div class="form-group">
        <label class="form-label" for="shipQty">Jumlah Dikirim</label>
        <input class="form-control" type="number" min="1" id="shipQty" name="quantity" required>
        <span id="shipHint" style="display:block; margin-top:6px; font-size:12px; color:var(--color-text-muted);"></span>
      </div>
      <div style="display:flex; justify-content:flex-end; margin-top:14px;">
        <button type="submit" class="btn btn-primary" style="width:auto; padding:0 24px;">OK</button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
function openShipPopup(btn) {
  var sources = [];
  try { sources = JSON.parse(btn.getAttribute('data-sources') || '[]'); } catch (e) {}

  if (!sources.length) {
    showToast('Stok item ini tidak ada di kebun lain. Gunakan tombol panah untuk meneruskan ke Owner.');
    return;
  }

  var need = parseInt(btn.getAttribute('data-qty'), 10) || 1;
  var sel = document.getElementById('shipSource');
  var qty = document.getElementById('shipQty');
  var hint = document.getElementById('shipHint');

  document.getElementById('shipForm').action = btn.getAttribute('data-url');
  document.getElementById('shipPoLabel').textContent = btn.getAttribute('data-code');

  sel.innerHTML = '';
  sources.forEach(function (s) {
    var o = document.createElement('option');
    o.value = s.id;
    o.textContent = s.name + ' (stok ' + s.qty + ')';
    o.setAttribute('data-qty', s.qty);
    sel.appendChild(o);
  });

  function refresh() {
    var avail = parseInt(sel.options[sel.selectedIndex].getAttribute('data-qty'), 10) || 0;
    var max = Math.min(need, avail);
    qty.max = max;
    if (!qty.value || parseInt(qty.value, 10) > max) qty.value = max;
    hint.textContent = 'Diajukan: ' + need + ' · Stok di kebun asal: ' + avail;
  }

  sel.onchange = function () { qty.value = ''; refresh(); };
  qty.value = '';
  refresh();

  document.getElementById('shipModalOverlay').classList.add('is-open');
}

function closeShipPopup() {
  document.getElementById('shipModalOverlay').classList.remove('is-open');
}

// Kunci tombol OK setelah diklik supaya tidak terkirim dua kali
['approveForm', 'shipForm'].forEach(function (id) {
  var f = document.getElementById(id);
  if (!f) return;
  f.addEventListener('submit', function () {
    var b = f.querySelector('button[type="submit"]');
    setTimeout(function () { b.disabled = true; b.textContent = 'Menyimpan...'; }, 0);
  });
});
</script>
@endsection