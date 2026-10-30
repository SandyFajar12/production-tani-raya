@extends('layouts.app')

@section('title', 'Pemakaian Solar — TaniRaya ERP')
@section('page-title', 'Pemakaian Solar')
@section('page-subtitle', 'Riwayat & catat perjalanan armada')
@section('back-url', route('fuel.index'))

@section('content')

@php
  $iEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  $iEdit = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>';
  $iBan = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>';
  $fmt = fn ($v) => \App\Models\PemakaianSolar\Stock::fmt($v);
  $typeLabels = \App\Models\PemakaianSolar\Trip::TYPES;
  $statusLabels = \App\Models\PemakaianSolar\Trip::STATUSES;
  $canWrite = auth()->user()->hasPermission('fuel_trip.create');
@endphp

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Riwayat</button>
  @permission('fuel_trip.create')
  <button class="tab-btn" data-tab-target="viewForm">+ Catat Pemakaian</button>
  @endpermission
</div>

<section id="viewList" class="view is-active">
  <div class="form-group">
    <label class="form-label" for="tr-fleet">Armada</label>
    <select class="form-control" id="tr-fleet" onchange="window.location.href = updateQueryParam('fleet_id', this.value)">
      <option value="">Semua Armada</option>
      @foreach ($allFleets as $f)
        <option value="{{ $f->id }}" {{ (string) request('fleet_id') === (string) $f->id ? 'selected' : '' }}>{{ $f->name }} — {{ $f->code }}</option>
      @endforeach
    </select>
  </div>

  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari armada / kode / keterangan perjalanan...'])

  @if (request('date') === 'today')
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12.5px; color:var(--color-text-muted);">
      <span>Menampilkan perjalanan hari ini saja</span>
      <a href="{{ route('solar.trips.index') }}" class="section__link">Lihat semua →</a>
    </div>
  @endif

  <div class="table-wrap">
    <table class="data-table data-table--wide">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Armada</th>
          <th>Jenis</th>
          <th class="num">Dibeli</th>
          <th class="num">Di-stock</th>
          <th class="num">Sisa Standby</th>
          <th class="num">Pemakaian</th>
          <th>Keterangan Perjalanan</th>
          <th class="col-aksi">Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($trips as $t)
        @php
          $st = $t->status;
          $dash = $st !== 'done';
          $fillSum = $st === 'ongoing' ? (float) ($fillSums[$t->id] ?? 0) : (float) $t->fill_qty;
          $isLatest = ($latestIds[$t->fleet_id] ?? null) == $t->id;
          $fleetLabel = ($t->fleet->name ?? '-').' — '.($t->fleet->code ?? '-');
          $dateLabel = \Carbon\Carbon::parse($t->trip_date)->format('d M Y');

          $detail = [
            'Tanggal' => $dateLabel,
            'Jenis Catatan' => $typeLabels[$t->entry_type] ?? $t->entry_type,
            'Status' => $statusLabels[$st] ?? $st,
            'Armada' => $fleetLabel,
            'Solar Awal di Armada' => $fmt($t->opening_qty).' L',
            'Solar Dibeli' => $dash ? '-' : $fmt($t->purchased_qty).' L',
            'Isi Tangki selama Perjalanan' => $fmt($fillSum).' L',
            'Solar di-Stock' => $dash ? '-' : $fmt($t->stocked_qty).' L',
            'Kebun Tempat Stok' => $t->garden->name ?? '-',
            'Sisa Standby Armada' => $dash ? '-' : $fmt($t->remaining_qty).' L',
            'Pemakaian Perjalanan' => $dash ? '-' : $fmt($t->used_qty).' L',
            'SPBU / Supplier' => $t->supplier->name ?? '-',
            'Harga per Liter' => $t->unit_price !== null ? 'Rp'.number_format($t->unit_price, 0, ',', '.') : '-',
            'Total Pembelian' => $t->total_price !== null ? 'Rp'.number_format($t->total_price, 0, ',', '.') : '-',
            'Keterangan Perjalanan' => $t->notes ?: '-',
            'Dicatat Oleh' => ($t->recorder->name ?? '-').' · '.$t->created_at->format('d M Y H:i'),
            'Terakhir Diubah Oleh' => $t->updated_by ? (($t->updater->name ?? '-').' · '.$t->updated_at->format('d M Y H:i')) : '-',
          ];

          if ($st === 'cancelled') {
            $detail += [
              'Dibatalkan Oleh' => $t->canceller->name ?? '-',
              'Waktu Pembatalan' => $t->cancelled_at ? $t->cancelled_at->format('d M Y H:i') : '-',
              'Alasan Pembatalan' => $t->cancel_reason ?: '-',
            ];
          }
        @endphp
        <tr @if ($st === 'cancelled') style="color:var(--color-text-faint);" @endif>
          <td style="white-space:nowrap;">{{ $dateLabel }}</td>
          <td>{{ $t->fleet->name ?? '-' }}<span class="sub">{{ $t->fleet->code ?? '' }}</span></td>
          <td>
            <span class="badge badge--{{ $t->entry_type === 'usage' ? 'inactive' : 'approved' }}">{{ $typeLabels[$t->entry_type] ?? $t->entry_type }}</span>
            @if ($st !== 'done')
              <span class="sub" style="color:var(--c-rose-dark); font-weight:400; font-size:11px;">{{ $st === 'ongoing' ? 'Masih dalam perjalanan' : 'Dibatalkan' }}</span>
            @endif
          </td>
          <td class="num">{{ $dash ? '-' : $fmt($t->purchased_qty).' L' }}</td>
          <td class="num">{{ $dash ? '-' : $fmt($t->stocked_qty).' L' }}@if (! $dash && $t->garden)<span class="sub">{{ $t->garden->name }}</span>@endif</td>
          <td class="num">{{ $dash ? '-' : $fmt($t->remaining_qty).' L' }}</td>
          <td class="num">{{ $dash ? '-' : $fmt($t->used_qty).' L' }}</td>
          <td title="{{ $t->notes }}">{{ \Illuminate\Support\Str::limit((string) $t->notes, 50, '...') ?: '-' }}</td>
          <td class="col-aksi">
            <div class="action-group">
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="showDetailModal(this)"
                data-detail-title="Detail Pemakaian Solar"
                data-detail="{{ json_encode($detail, JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iEye !!}</button>

              @if ($canWrite && $st === 'ongoing')
                <button type="button" class="btn btn-sm btn-secondary" title="Lengkapi perjalanan" aria-label="Lengkapi perjalanan"
                  onclick="openCompleteModal(this)"
                  data-url="{{ route('solar.trips.complete', $t) }}"
                  data-fleet="{{ $fleetLabel }}"
                  data-type="{{ $t->entry_type }}"
                  data-opening="{{ $t->opening_qty }}"
                  data-fill="{{ $fillSum }}"
                  data-notes="{{ $t->notes }}">{!! $iEdit !!}</button>
              @endif

              @if ($canWrite && ($st === 'ongoing' || ($st === 'done' && $isLatest)))
                <button type="button" class="btn btn-sm btn-secondary btn-icon--danger" title="Batalkan" aria-label="Batalkan"
                  onclick="openCancelModal(this)"
                  data-url="{{ route('solar.trips.cancel', $t) }}"
                  data-label="{{ $fleetLabel }} · {{ $dateLabel }}"
                  data-done="{{ $st === 'done' ? 1 : 0 }}">{!! $iBan !!}</button>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="9" class="empty-state">Belum ada riwayat pemakaian solar.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @include('partials.pagination', ['paginator' => $trips])
</section>

@permission('fuel_trip.create')
<section id="viewForm" class="view">
<form method="POST" action="{{ route('solar.trips.store') }}" data-safe-form="solar-trip" id="tripForm">
    @csrf

    <div class="form-group">
      <label class="form-label" for="tp-jenis">Jenis Catatan</label>
      <select class="form-control" id="tp-jenis" name="entry_type" required>
        @foreach ($typeLabels as $key => $label)
          <option value="{{ $key }}" {{ old('entry_type', 'purchase') === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
      </select>
      <span style="display:block; margin-top:6px; font-size:12px; color:var(--color-text-muted);">Beli Solar &amp; Pemakaian: perjalanan yang membeli solar di SPBU dan/atau menyimpan stok ke kebun. Pemakaian Saja: perjalanan tanpa beli solar, cukup isi sisa solar di tangki setelah dicek PJ.</span>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="tp-fleet">Armada</label>
        <select class="form-control" id="tp-fleet" name="fleet_id" required>
          <option value="">Pilih armada…</option>
          @foreach ($fleets as $f)
            <option value="{{ $f->id }}" {{ old('fleet_id') == $f->id ? 'selected' : '' }}>{{ $f->name }} — {{ $f->code }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="tp-tanggal">Tanggal</label>
        <input class="form-control" id="tp-tanggal" name="trip_date" type="date" value="{{ old('trip_date', date('Y-m-d')) }}" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="tp-beli">Solar Dibeli (liter)</label>
        <input class="form-control" id="tp-beli" name="purchased_qty" type="number" min="0" step="0.01" inputmode="decimal" value="{{ old('purchased_qty', 0) }}" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="tp-stock">Solar di-Stock (liter)</label>
        <input class="form-control" id="tp-stock" name="stocked_qty" type="number" min="0" step="0.01" inputmode="decimal" value="{{ old('stocked_qty', 0) }}" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="tp-kebun">Stok Disimpan di Kebun <span style="font-weight:500;">(wajib kalau ada solar di-stock)</span></label>
      <select class="form-control" id="tp-kebun" name="garden_id">
        <option value="">— tidak ada stok —</option>
        @foreach ($gardens as $g)
          <option value="{{ $g->id }}" {{ old('garden_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="tp-sisa">Sisa Solar Standby di Armada (liter)</label>
      <label class="form-check" style="margin-bottom:8px;">
        <input type="checkbox" id="tp-jalan" name="in_progress" value="1" {{ old('in_progress') ? 'checked' : '' }}>
        Masih dalam perjalanan
      </label>
      <input class="form-control" id="tp-sisa" name="remaining_qty" type="number" min="0" step="0.01" inputmode="decimal" value="{{ old('remaining_qty') }}" required>
    </div>

    <div id="calcBox" style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:12px; padding:12px 14px; margin-bottom:14px; font-size:13px; line-height:1.7;">
      Pilih armada untuk melihat perhitungan.
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="tp-supplier">SPBU / Supplier (opsional)</label>
        <select class="form-control" id="tp-supplier" name="supplier_id">
          <option value="">— tidak dicatat —</option>
          @foreach ($suppliers as $s)
            <option value="{{ $s->id }}" {{ old('supplier_id', $suppliers->count() === 1 ? $suppliers->first()->id : null) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="tp-harga">Harga per Liter (Rp, opsional)</label>
        <input class="form-control" id="tp-harga" name="unit_price" type="text" inputmode="numeric" data-currency autocomplete="off" value="{{ old('unit_price') }}">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="tp-catatan">Keterangan Perjalanan</label>
      <textarea class="form-control" id="tp-catatan" name="notes" rows="4" placeholder="contoh: beli solar ke SPBU, antar buah sawit ke kebun AC, kembali ke kebun AB. Kalau lebih dari 1 perjalanan, tulis rute dan jumlah perjalanannya">{{ old('notes') }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan Pemakaian</button>
  </form>
</section>

{{-- Popup: lengkapi perjalanan yang masih berjalan --}}
<div class="modal-overlay" id="completeModalOverlay">
  <div class="modal-box">
    <div class="modal-box__header">
      <span class="modal-box__title">Lengkapi Perjalanan</span>
      <button type="button" class="modal-box__close" onclick="closeCompleteModal()">&times;</button>
    </div>
    <p id="cmFleet" style="font-size:13px; font-weight:700; margin:0 0 8px;"></p>
    <form id="completeForm" method="POST" action="">
      @csrf
      <div id="cmInfo" style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:12px; padding:10px 12px; margin-bottom:12px; font-size:13px; line-height:1.7;"></div>

      <div id="cmPurchaseFields">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="cm-beli">Solar Dibeli (liter)</label>
            <input class="form-control" id="cm-beli" name="purchased_qty" type="number" min="0" step="0.01" inputmode="decimal" value="0" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="cm-stock">Solar di-Stock (liter)</label>
            <input class="form-control" id="cm-stock" name="stocked_qty" type="number" min="0" step="0.01" inputmode="decimal" value="0" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="cm-kebun">Stok Disimpan di Kebun <span style="font-weight:500;">(wajib kalau ada solar di-stock)</span></label>
          <select class="form-control" id="cm-kebun" name="garden_id">
            <option value="">— tidak ada stok —</option>
            @foreach ($gardens as $g)
              <option value="{{ $g->id }}">{{ $g->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="cm-supplier">SPBU / Supplier (opsional)</label>
            <select class="form-control" id="cm-supplier" name="supplier_id">
              <option value="">— tidak dicatat —</option>
              @foreach ($suppliers as $s)
                <option value="{{ $s->id }}" {{ $suppliers->count() === 1 ? 'selected' : '' }}>{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="cm-harga">Harga per Liter (Rp)</label>
            <input class="form-control" id="cm-harga" name="unit_price" type="text" inputmode="numeric" data-currency autocomplete="off">
          </div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="cm-sisa">Sisa Solar Standby di Armada (liter)</label>
        <input class="form-control" id="cm-sisa" name="remaining_qty" type="number" min="0" step="0.01" inputmode="decimal" required>
      </div>

      <div id="cmCalc" style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:12px; padding:10px 12px; margin-bottom:12px; font-size:13px; line-height:1.7;"></div>

      <div class="form-group">
        <label class="form-label" for="cm-catatan">Keterangan Perjalanan</label>
        <textarea class="form-control" id="cm-catatan" name="notes" rows="3"></textarea>
      </div>

      <div style="display:flex; justify-content:flex-end; margin-top:14px;">
        <button type="submit" class="btn btn-primary" style="width:auto; padding:0 24px;">Simpan</button>
      </div>
    </form>
  </div>
</div>

{{-- Popup: batalkan catatan --}}
<div class="modal-overlay" id="cancelModalOverlay">
  <div class="modal-box">
    <div class="modal-box__header">
      <span class="modal-box__title">Batalkan Catatan</span>
      <button type="button" class="modal-box__close" onclick="closeCancelModal()">&times;</button>
    </div>
    <p id="cnLabel" style="font-size:13px; font-weight:700; margin:0 0 8px;"></p>
    <form id="cancelForm" method="POST" action="">
      @csrf
      <p id="cnNote" style="font-size:12.5px; color:var(--color-text-muted); margin:0 0 12px; line-height:1.5;"></p>
      <div class="form-group">
        <label class="form-label" for="cn-alasan">Alasan Pembatalan</label>
        <textarea class="form-control" id="cn-alasan" name="cancel_reason" rows="3" maxlength="500" required placeholder="contoh: salah pilih jenis catatan"></textarea>
      </div>
      <div style="display:flex; justify-content:flex-end; margin-top:14px;">
        <button type="submit" class="btn btn-primary" style="width:auto; padding:0 24px;">Batalkan Catatan</button>
      </div>
    </form>
  </div>
</div>
@endpermission

@endsection

@section('scripts')
@permission('fuel_trip.create')
<script>
const BALANCES = @json($balanceData);
const ONGOING = @json($ongoingData);

function fmtL(n) {
  return (Math.round(n * 100) / 100).toString().replace('.', ',');
}

/* ---------- Form Catat Pemakaian ---------- */
(function () {
  const form = document.getElementById('tripForm');
  if (!form) return;

  const type = form.querySelector('[name="entry_type"]');
  const fleet = form.querySelector('[name="fleet_id"]');
  const buy = form.querySelector('[name="purchased_qty"]');
  const stock = form.querySelector('[name="stocked_qty"]');
  const rem = form.querySelector('[name="remaining_qty"]');
  const garden = form.querySelector('[name="garden_id"]');
  const supplier = form.querySelector('[name="supplier_id"]');
  const price = form.querySelector('[name="unit_price"]');
  const jalan = document.getElementById('tp-jalan');
  const box = document.getElementById('calcBox');

  function num(el) {
    const v = parseFloat(el.value);
    return isNaN(v) ? 0 : v;
  }

  function refresh() {
    const isUsage = type.value === 'usage';
    const going = jalan.checked;
    const hideBuy = isUsage || going;

    // Isian yang tidak dipakai disembunyikan dan tidak dikirim
    [buy, stock, garden, supplier, price].forEach(function (el) { el.disabled = hideBuy; });
    rem.disabled = going;
    buy.closest('.form-row').style.display = hideBuy ? 'none' : '';
    garden.closest('.form-group').style.display = hideBuy ? 'none' : '';
    supplier.closest('.form-row').style.display = hideBuy ? 'none' : '';
    garden.required = !hideBuy && num(stock) > 0;

    if (!fleet.value) {
      box.textContent = 'Pilih armada untuk melihat perhitungan.';
      return;
    }

    const opening = parseFloat(BALANCES[fleet.value] || 0);
    let html = '';

    if (ONGOING[fleet.value]) {
      html += '<span style="color:var(--c-rose-dark); font-weight:700;">Armada ini masih dalam perjalanan (berangkat ' + ONGOING[fleet.value] + '). Lengkapi atau batalkan perjalanan itu dulu lewat Riwayat.</span><br>';
    }

    html += 'Solar awal di armada: <strong>' + fmtL(opening) + ' L</strong><br>';

    if (going) {
      html += 'Perjalanan dicatat saat berangkat. Sisa solar dan pemakaian diisi lewat tombol Edit di Riwayat setelah armada sampai.';
      box.innerHTML = html;
      return;
    }

    const available = opening + (isUsage ? 0 : num(buy));
    const used = Math.round((available - (isUsage ? 0 : num(stock)) - num(rem)) * 100) / 100;

    if (!isUsage) {
      html += 'Tersedia (awal + dibeli): <strong>' + fmtL(available) + ' L</strong><br>';
    }

    if (used < 0) {
      html += '<span style="color:var(--c-rose-dark); font-weight:700;">' + (isUsage
        ? 'Sisa standby lebih besar dari solar awal. Tangki tidak bisa bertambah tanpa isi tangki atau pembelian.'
        : 'Angka tidak cocok: di-stock + sisa standby melebihi solar yang tersedia.') + '</span>';
    } else {
      html += 'Pemakaian perjalanan: <strong>' + fmtL(used) + ' L</strong>';
    }

    box.innerHTML = html;
  }

  [type, fleet, buy, stock, rem, jalan].forEach(function (el) {
    el.addEventListener('input', refresh);
    el.addEventListener('change', refresh);
  });

  document.addEventListener('DOMContentLoaded', refresh); // jalan setelah draf dipulihkan
  refresh();
})();

/* ---------- Popup lengkapi perjalanan ---------- */
var completeState = { opening: 0, fills: 0, usage: false };

function refreshComplete() {
  var f = document.getElementById('completeForm');
  var num = function (n) { var v = parseFloat(f.querySelector('[name="' + n + '"]').value); return isNaN(v) ? 0 : v; };
  var box = document.getElementById('cmCalc');
  var buy = completeState.usage ? 0 : num('purchased_qty');
  var stk = completeState.usage ? 0 : num('stocked_qty');
  var rem = num('remaining_qty');
  var available = completeState.opening + completeState.fills + buy;
  var used = Math.round((available - stk - rem) * 100) / 100;

  f.querySelector('[name="garden_id"]').required = !completeState.usage && stk > 0;

  if (f.querySelector('[name="remaining_qty"]').value === '') {
    box.textContent = 'Isi sisa solar di tangki untuk melihat pemakaian.';
  } else if (used < 0) {
    box.innerHTML = '<span style="color:var(--c-rose-dark); font-weight:700;">Angka tidak cocok: sisa dan stok melebihi solar yang tersedia (' + fmtL(available) + ' L).</span>';
  } else {
    box.innerHTML = 'Tersedia: <strong>' + fmtL(available) + ' L</strong><br>Pemakaian perjalanan: <strong>' + fmtL(used) + ' L</strong>';
  }
}

function openCompleteModal(btn) {
  var f = document.getElementById('completeForm');
  var usage = btn.getAttribute('data-type') === 'usage';
  var opening = parseFloat(btn.getAttribute('data-opening')) || 0;
  var fills = parseFloat(btn.getAttribute('data-fill')) || 0;

  completeState = { opening: opening, fills: fills, usage: usage };

  f.action = btn.getAttribute('data-url');
  document.getElementById('cmFleet').textContent = btn.getAttribute('data-fleet');
  document.getElementById('cmInfo').innerHTML =
    'Solar saat berangkat: <strong>' + fmtL(opening) + ' L</strong><br>' +
    'Isi tangki selama perjalanan: <strong>' + fmtL(fills) + ' L</strong>';

  var pf = document.getElementById('cmPurchaseFields');
  pf.style.display = usage ? 'none' : '';
  Array.prototype.forEach.call(pf.querySelectorAll('input, select'), function (el) { el.disabled = usage; });

  f.querySelector('[name="purchased_qty"]').value = '0';
  f.querySelector('[name="stocked_qty"]').value = '0';
  f.querySelector('[name="garden_id"]').value = '';
  f.querySelector('[name="unit_price"]').value = '';
  f.querySelector('[name="remaining_qty"]').value = '';
  f.querySelector('[name="notes"]').value = btn.getAttribute('data-notes') || '';

  refreshComplete();
  document.getElementById('completeModalOverlay').classList.add('is-open');
}

function closeCompleteModal() {
  document.getElementById('completeModalOverlay').classList.remove('is-open');
}

/* ---------- Popup batalkan ---------- */
function openCancelModal(btn) {
  var done = btn.getAttribute('data-done') === '1';

  document.getElementById('cancelForm').action = btn.getAttribute('data-url');
  document.getElementById('cnLabel').textContent = btn.getAttribute('data-label');
  document.getElementById('cnNote').textContent = done
    ? 'Catatan ini sudah selesai. Kalau dibatalkan, stok kebun yang pernah ditambahkan dari perjalanan ini dikurangi lagi dan saldo armada dikembalikan ke kondisi sebelum perjalanan. Data tidak dihapus, hanya diberi tanda Dibatalkan.'
    : 'Perjalanan ini masih berjalan. Kalau dibatalkan, tidak ada stok atau saldo yang berubah. Data tidak dihapus, hanya diberi tanda Dibatalkan.';
  document.getElementById('cn-alasan').value = '';

  document.getElementById('cancelModalOverlay').classList.add('is-open');
}

function closeCancelModal() {
  document.getElementById('cancelModalOverlay').classList.remove('is-open');
}

/* ---------- Pasang pendengar popup + kunci tombol agar tidak terkirim dua kali ---------- */
(function () {
  var cf = document.getElementById('completeForm');
  if (cf) {
    ['input', 'change'].forEach(function (ev) { cf.addEventListener(ev, refreshComplete); });
  }

  ['completeForm', 'cancelForm'].forEach(function (id) {
    var f = document.getElementById(id);
    if (!f) return;
    f.addEventListener('submit', function () {
      var b = f.querySelector('button[type="submit"]');
      setTimeout(function () { b.disabled = true; b.textContent = 'Menyimpan...'; }, 0);
    });
  });
})();
</script>
@endpermission
@endsection