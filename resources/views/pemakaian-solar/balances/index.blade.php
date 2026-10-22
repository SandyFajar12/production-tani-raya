@extends('layouts.app')

@section('title', 'Saldo Solar Armada — TaniRaya ERP')
@section('page-title', 'Saldo Solar Armada')
@section('page-subtitle', 'Solar yang ada di tangki tiap armada')
@section('back-url', route('fuel.index'))

@section('content')

@php
  $iEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  $fmt = fn ($v) => \App\Models\PemakaianSolar\Stock::fmt($v);
@endphp

<div class="tabs">
  <button class="tab-btn is-active" data-tab-target="viewList">Saldo Terkini</button>
  @permission('fuel_balance.adjust')
  <button class="tab-btn" data-tab-target="viewForm">+ Koreksi Saldo</button>
  <button class="tab-btn" data-tab-target="viewMin">Atur Standby Minimum</button>
  @endpermission
</div>

<section id="viewList" class="view is-active">
  @if (request('low'))
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12.5px; color:var(--color-text-muted);">
      <span>Menampilkan armada dengan standby rendah saja</span>
      <a href="{{ route('solar.balances.index') }}" class="section__link">Lihat semua →</a>
    </div>
  @endif

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Armada</th>
          <th class="num">Solar di Armada</th>
          <th class="num">Standby Min.</th>
          <th>Keterangan</th>
          <th>Perjalanan Terakhir</th>
          <th class="col-aksi">Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($fleets as $f)
        @php
          $b = $balances->get($f->id);
          $qty = (float) ($b->balance ?? 0);
          $min = (float) ($b->min_standby ?? 0);
          $last = $lastTrips[$f->id] ?? null;

          if ($qty <= 0) { $label = 'Kosong'; $badge = 'rejected'; }
          elseif ($min > 0 && $qty < $min) { $label = 'Rendah'; $badge = 'pending'; }
          else { $label = 'Aman'; $badge = 'active'; }

          $detail = [
            'Armada' => $f->name.' — '.$f->code,
            'Tipe' => $f->type,
            'Solar di Armada' => $fmt($qty).' L',
            'Standby Minimum' => $min > 0 ? $fmt($min).' L' : 'Belum diatur',
            'Status' => $label,
            'Perjalanan Terakhir' => $last ? \Carbon\Carbon::parse($last)->format('d M Y') : 'Belum ada',
            'Saldo Diperbarui' => $b ? $b->updated_at->format('d M Y H:i') : '-',
          ];
        @endphp
        <tr>
          <td>{{ $f->name }}<span class="sub">{{ $f->code }}@if ($f->status === 'nonaktif') · Nonaktif @endif</span></td>
          <td class="num">{{ $fmt($qty) }} L</td>
          <td class="num">{{ $min > 0 ? $fmt($min).' L' : '-' }}</td>
          <td><span class="badge badge--{{ $badge }}">{{ $label }}</span></td>
          <td style="white-space:nowrap;">{{ $last ? \Carbon\Carbon::parse($last)->format('d M Y') : '-' }}</td>
          <td class="col-aksi">
            <div class="action-group">
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="showDetailModal(this)"
                data-detail-title="Detail Saldo Armada"
                data-detail="{{ json_encode($detail, JSON_HEX_APOS | JSON_HEX_QUOT) }}" title="Detail" aria-label="Detail">{!! $iEye !!}</button>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="empty-state">Belum ada data armada.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <section class="section">
    <div class="section__head">
      <h2 class="section__title">Riwayat Koreksi Saldo (20 terakhir)</h2>
    </div>

    <div class="table-wrap">
      <table class="data-table data-table--wide" data-no-number>
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Armada</th>
            <th class="num">Sebelum</th>
            <th class="num">Sesudah</th>
            <th class="num">Selisih</th>
            <th>Alasan</th>
            <th>Dilakukan Oleh</th>
          </tr>
        </thead>
        <tbody>
        @forelse ($history as $h)
          @php $diff = (float) $h->difference; @endphp
          <tr>
            <td style="white-space:nowrap;">{{ $h->created_at->format('d M Y H:i') }}</td>
            <td>{{ $h->fleet->name ?? '-' }}</td>
            <td class="num">{{ $fmt($h->balance_before) }} L</td>
            <td class="num">{{ $fmt($h->balance_after) }} L</td>
            <td class="num">{{ $diff > 0 ? '+' : '' }}{{ $fmt($diff) }} L</td>
            <td>{{ $h->reason ?: '-' }}</td>
            <td>{{ $h->adjuster->name ?? '-' }}</td>
          </tr>
        @empty
          <tr><td colspan="7" class="empty-state">Belum ada koreksi saldo.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </section>
</section>

@permission('fuel_balance.adjust')
<section id="viewForm" class="view">
<form method="POST" action="{{ route('solar.balances.adjust') }}" data-safe-form="solar-balance" id="balanceForm">
    @csrf

    <div class="form-group">
      <label class="form-label" for="sb-fleet">Armada</label>
      <select class="form-control" id="sb-fleet" name="fleet_id" required>
        <option value="">Pilih armada…</option>
        @foreach ($formFleets as $f)
          <option value="{{ $f->id }}" {{ old('fleet_id') == $f->id ? 'selected' : '' }}>{{ $f->name }} — {{ $f->code }}</option>
        @endforeach
      </select>
      <span id="balanceHint" style="display:block; margin-top:6px; font-size:12px; color:var(--color-text-muted);"></span>
    </div>

    <div class="form-group">
      <label class="form-label" for="sb-saldo">Saldo Solar Aktual di Armada (liter)</label>
      <input class="form-control" id="sb-saldo" name="balance" type="number" min="0" step="0.01" inputmode="decimal" value="{{ old('balance') }}" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="sb-alasan">Alasan / Catatan</label>
      <textarea class="form-control" id="sb-alasan" name="reason" rows="3" placeholder="contoh: saldo awal, hasil cek tangki, selisih pengukuran" required>{{ old('reason') }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Simpan Koreksi</button>
  </form>
</section>

<section id="viewMin" class="view">
  <form method="POST" action="{{ route('solar.balances.minimum') }}" data-safe-form="solar-min-standby" data-no-draft>
    @csrf

    <div class="form-group">
      <label class="form-label" for="mn-fleet">Armada</label>
      <select class="form-control" id="mn-fleet" name="fleet_id" required>
        <option value="">Pilih armada…</option>
        @foreach ($formFleets as $f)
          <option value="{{ $f->id }}">{{ $f->name }} — {{ $f->code }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="mn-min">Standby Minimum (liter)</label>
      <input class="form-control" id="mn-min" name="min_standby" type="number" min="0" step="0.01" inputmode="decimal" required>
    </div>

    <p class="demo-note" style="text-align:left; margin:0 0 14px;">Isi 0 untuk mematikan notifikasi untuk armada ini. Notifikasi dikirim ke Admin Pusat dan Owner saat solar standby turun di bawah batas ini setelah perjalanan atau koreksi saldo. Nilai yang sedang berlaku terlihat di kolom Standby Min. pada daftar.</p>

    <button type="submit" class="btn btn-primary btn-block">Simpan Standby Minimum</button>
  </form>
</section>
@endpermission

@endsection

@section('scripts')
@permission('fuel_balance.adjust')
<script>
const BALANCES = @json($balanceData);

(function () {
  const fleet = document.getElementById('sb-fleet');
  const hint = document.getElementById('balanceHint');
  if (!fleet) return;

  function refresh() {
    if (!fleet.value) { hint.textContent = ''; return; }
    const v = parseFloat(BALANCES[fleet.value] || 0);
    hint.textContent = 'Saldo di sistem saat ini: ' + (Math.round(v * 100) / 100).toString().replace('.', ',') + ' L';
  }

  fleet.addEventListener('change', refresh);
  document.addEventListener('DOMContentLoaded', refresh);
  refresh();
})();
</script>
@endpermission
@endsection