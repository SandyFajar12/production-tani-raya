@extends('layouts.app')

@section('title', 'Pemakaian Solar — TaniRaya ERP')
@section('page-title', 'Pemakaian Solar')
@section('page-subtitle', 'Riwayat & catat perjalanan armada')
@section('back-url', route('fuel.index'))

@section('content')

@php
  $iEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  $fmt = fn ($v) => \App\Models\PemakaianSolar\Stock::fmt($v);
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
        <tr>
          <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($t->trip_date)->format('d M Y') }}</td>
          <td>{{ $t->fleet->name ?? '-' }}<span class="sub">{{ $t->fleet->code ?? '' }}</span></td>
          <td class="num">{{ $fmt($t->purchased_qty) }} L</td>
          <td class="num">{{ $fmt($t->stocked_qty) }} L<span class="sub">{{ $t->garden->name ?? '' }}</span></td>
          <td class="num">{{ $fmt($t->remaining_qty) }} L</td>
          <td class="num">{{ $fmt($t->used_qty) }} L</td>
          <td title="{{ $t->notes }}">{{ \Illuminate\Support\Str::limit((string) $t->notes, 50, '...') ?: '-' }}</td>
          <td class="col-aksi">
            <div class="action-group">
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="showDetailModal(this)"
                data-detail-title="Detail Pemakaian Solar"
                data-detail="{{ json_encode([
                  'Tanggal' => \Carbon\Carbon::parse($t->trip_date)->format('d M Y'),
                  'Armada' => ($t->fleet->name ?? '-').' — '.($t->fleet->code ?? '-'),
                  'Solar Awal di Armada' => $fmt($t->opening_qty).' L',
                  'Solar Dibeli' => $fmt($t->purchased_qty).' L',
                  'Solar di-Stock' => $fmt($t->stocked_qty).' L',
                  'Kebun Tempat Stok' => $t->garden->name ?? '-',
                  'Sisa Standby Armada' => $fmt($t->remaining_qty).' L',
                  'Pemakaian Perjalanan' => $fmt($t->used_qty).' L',
                  'SPBU / Supplier' => $t->supplier->name ?? '-',
                  'Harga per Liter' => $t->unit_price !== null ? 'Rp'.number_format($t->unit_price, 0, ',', '.') : '-',
                  'Total Pembelian' => $t->total_price !== null ? 'Rp'.number_format($t->total_price, 0, ',', '.') : '-',
                  'Keterangan Perjalanan' => $t->notes ?: '-',
                  'Dicatat Oleh' => $t->recorder->name ?? '-',
                ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iEye !!}</button>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="8" class="empty-state">Belum ada riwayat pemakaian solar.</td></tr>
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
      <textarea class="form-control" id="tp-catatan" name="notes" rows="4" placeholder="contoh: beli solar ke SPBU, antar buah sawit ke kebun AC, kembali ke kebun AB">{{ old('notes') }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan Pemakaian</button>
  </form>
</section>
@endpermission

@endsection

@section('scripts')
@permission('fuel_trip.create')
<script>
const BALANCES = @json($balanceData);

(function () {
  const form = document.getElementById('tripForm');
  if (!form) return;

  const fleet = form.querySelector('[name="fleet_id"]');
  const buy = form.querySelector('[name="purchased_qty"]');
  const stock = form.querySelector('[name="stocked_qty"]');
  const rem = form.querySelector('[name="remaining_qty"]');
  const garden = form.querySelector('[name="garden_id"]');
  const box = document.getElementById('calcBox');

  function num(el) {
    const v = parseFloat(el.value);
    return isNaN(v) ? 0 : v;
  }

  function fmt(n) {
    return (Math.round(n * 100) / 100).toString().replace('.', ',');
  }

  function refresh() {
    garden.required = num(stock) > 0;

    if (!fleet.value) {
      box.textContent = 'Pilih armada untuk melihat perhitungan.';
      return;
    }

    const opening = parseFloat(BALANCES[fleet.value] || 0);
    const available = opening + num(buy);
    const used = Math.round((available - num(stock) - num(rem)) * 100) / 100;

    let html = 'Solar awal di armada: <strong>' + fmt(opening) + ' L</strong><br>' +
      'Tersedia (awal + dibeli): <strong>' + fmt(available) + ' L</strong><br>';

    if (used < 0) {
      html += '<span style="color:var(--c-rose-dark); font-weight:700;">Angka tidak cocok: di-stock + sisa standby melebihi solar yang tersedia.</span>';
    } else {
      html += 'Pemakaian perjalanan: <strong>' + fmt(used) + ' L</strong>';
    }

    box.innerHTML = html;
  }

  [fleet, buy, stock, rem].forEach(function (el) {
    el.addEventListener('input', refresh);
    el.addEventListener('change', refresh);
  });

  document.addEventListener('DOMContentLoaded', refresh); // jalan setelah draf dipulihkan
  refresh();
})();
</script>
@endpermission
@endsection