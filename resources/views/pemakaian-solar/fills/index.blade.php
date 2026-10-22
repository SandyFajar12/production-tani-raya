@extends('layouts.app')

@section('title', 'Isi Tangki Armada — TaniRaya ERP')
@section('page-title', 'Isi Tangki Armada')
@section('page-subtitle', 'Solar dari stok kebun ke armada')
@section('back-url', route('fuel.index'))

@section('content')

@php
  $iEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  $fmt = fn ($v) => \App\Models\PemakaianSolar\Stock::fmt($v);
@endphp

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Riwayat</button>
  @permission('fuel_fill.create')
  <button class="tab-btn" data-tab-target="viewForm">+ Isi Tangki</button>
  @endpermission
</div>

<section id="viewList" class="view is-active">
  <div class="form-group">
    <label class="form-label" for="fl-fleet">Armada</label>
    <select class="form-control" id="fl-fleet" onchange="window.location.href = updateQueryParam('fleet_id', this.value)">
      <option value="">Semua Armada</option>
      @foreach ($allFleets as $f)
        <option value="{{ $f->id }}" {{ (string) request('fleet_id') === (string) $f->id ? 'selected' : '' }}>{{ $f->name }} — {{ $f->code }}</option>
      @endforeach
    </select>
  </div>

  @include('partials.list-toolbar', ['searchPlaceholder' => 'Cari armada / kebun / catatan...'])

  @if (request('date') === 'today')
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12.5px; color:var(--color-text-muted);">
      <span>Menampilkan isi tangki hari ini saja</span>
      <a href="{{ route('solar.fills.index') }}" class="section__link">Lihat semua →</a>
    </div>
  @endif

  <div class="table-wrap">
    <table class="data-table data-table--wide">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Armada</th>
          <th>Dari Kebun</th>
          <th class="num">Jumlah</th>
          <th class="num">Saldo Armada</th>
          <th>Catatan</th>
          <th class="col-aksi">Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($fills as $fl)
        <tr>
          <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($fl->fill_date)->format('d M Y') }}</td>
          <td>{{ $fl->fleet->name ?? '-' }}<span class="sub">{{ $fl->fleet->code ?? '' }}</span></td>
          <td>{{ $fl->garden->name ?? '-' }}</td>
          <td class="num">{{ $fmt($fl->quantity) }} L</td>
          <td class="num">{{ $fmt($fl->balance_before) }} → {{ $fmt($fl->balance_after) }} L</td>
          <td title="{{ $fl->notes }}">{{ \Illuminate\Support\Str::limit((string) $fl->notes, 40, '...') ?: '-' }}</td>
          <td class="col-aksi">
            <div class="action-group">
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="showDetailModal(this)"
                data-detail-title="Detail Isi Tangki"
                data-detail="{{ json_encode([
                  'Tanggal' => \Carbon\Carbon::parse($fl->fill_date)->format('d M Y'),
                  'Armada' => ($fl->fleet->name ?? '-').' — '.($fl->fleet->code ?? '-'),
                  'Kebun Sumber' => $fl->garden->name ?? '-',
                  'Jumlah Diisi' => $fmt($fl->quantity).' L',
                  'Saldo Armada Sebelum' => $fmt($fl->balance_before).' L',
                  'Saldo Armada Sesudah' => $fmt($fl->balance_after).' L',
                  'Catatan' => $fl->notes ?: '-',
                  'Dicatat Oleh' => $fl->recorder->name ?? '-',
                ], JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iEye !!}</button>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="empty-state">Belum ada riwayat isi tangki.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @include('partials.pagination', ['paginator' => $fills])
</section>

@permission('fuel_fill.create')
<section id="viewForm" class="view">
<form method="POST" action="{{ route('solar.fills.store') }}" data-safe-form="solar-fill" id="fillForm">
    @csrf

    <div class="form-group">
      <label class="form-label" for="fi-garden">Ambil dari Kebun</label>
      <select class="form-control" id="fi-garden" name="garden_id" required>
        <option value="">Pilih kebun…</option>
        @foreach ($gardens as $g)
          <option value="{{ $g->id }}" {{ old('garden_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="fi-fleet">Armada yang Diisi</label>
      <select class="form-control" id="fi-fleet" name="fleet_id" required>
        <option value="">Pilih armada…</option>
        @foreach ($fleets as $f)
          <option value="{{ $f->id }}" {{ old('fleet_id') == $f->id ? 'selected' : '' }}>{{ $f->name }} — {{ $f->code }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="fi-qty">Jumlah (liter)</label>
        <input class="form-control" id="fi-qty" name="quantity" type="number" min="0.01" step="0.01" inputmode="decimal" value="{{ old('quantity') }}" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="fi-tanggal">Tanggal</label>
        <input class="form-control" id="fi-tanggal" name="fill_date" type="date" value="{{ old('fill_date', date('Y-m-d')) }}" required>
      </div>
    </div>

    <div id="fillCalc" style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:12px; padding:12px 14px; margin-bottom:14px; font-size:13px; line-height:1.7;">
      Pilih kebun dan armada untuk melihat perhitungan.
    </div>

    <div class="form-group">
      <label class="form-label" for="fi-catatan">Catatan</label>
      <textarea class="form-control" id="fi-catatan" name="notes" rows="3" placeholder="opsional, contoh: isi untuk perjalanan ke SPBU">{{ old('notes') }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan Isi Tangki</button>
  </form>
</section>
@endpermission

@endsection

@section('scripts')
@permission('fuel_fill.create')
<script>
const STOCKS = @json($stockData);
const BALANCES = @json($balanceData);

(function () {
  const form = document.getElementById('fillForm');
  if (!form) return;

  const garden = form.querySelector('[name="garden_id"]');
  const fleet = form.querySelector('[name="fleet_id"]');
  const qty = form.querySelector('[name="quantity"]');
  const box = document.getElementById('fillCalc');

  function fmt(n) {
    return (Math.round(n * 100) / 100).toString().replace('.', ',');
  }

  function refresh() {
    qty.removeAttribute('max');

    if (!garden.value || !fleet.value) {
      box.textContent = 'Pilih kebun dan armada untuk melihat perhitungan.';
      return;
    }

    const stock = parseFloat(STOCKS[garden.value] || 0);
    const balance = parseFloat(BALANCES[fleet.value] || 0);
    const v = parseFloat(qty.value);
    const amount = isNaN(v) ? 0 : v;
    qty.max = stock;

    let html = 'Stok solar di kebun: <strong>' + fmt(stock) + ' L</strong><br>' +
      'Saldo solar di armada: <strong>' + fmt(balance) + ' L</strong><br>';

    if (amount > stock) {
      html += '<span style="color:var(--c-rose-dark); font-weight:700;">Jumlah melebihi stok solar di kebun ini.</span>';
    } else {
      html += 'Setelah diisi: stok kebun <strong>' + fmt(stock - amount) + ' L</strong>, saldo armada <strong>' + fmt(balance + amount) + ' L</strong>';
    }

    box.innerHTML = html;
  }

  [garden, fleet, qty].forEach(function (el) {
    el.addEventListener('input', refresh);
    el.addEventListener('change', refresh);
  });

  document.addEventListener('DOMContentLoaded', refresh); // jalan setelah draf dipulihkan
  refresh();
})();
</script>
@endpermission
@endsection