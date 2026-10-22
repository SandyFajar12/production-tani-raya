@extends('layouts.app')

@section('title', 'Riwayat Transaksi Kebutuhan — TaniRaya ERP')
@section('page-title', 'Riwayat Transaksi')
@section('page-subtitle', 'Semua aktivitas kebutuhan kebun hari ini')
@section('back-url', route('needs.index'))

@section('content')

<div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th>Waktu</th>
        <th>Jenis</th>
        <th>Item</th>
        <th>Terkait</th>
        <th class="num">Nilai</th>
      </tr>
    </thead>
    <tbody>
    @forelse ($transactions as $t)
      <tr>
        <td style="white-space:nowrap;">{{ $t['time']->format('H:i') }}</td>
        <td>
          @if ($t['type'] === 'purchase')
            <span class="badge badge--approved">Pembelian</span>
          @elseif ($t['type'] === 'usage')
            <span class="badge badge--rejected">Pemakaian</span>
          @else
            <span class="badge badge--pending">Pre-Order</span>
          @endif
        </td>
        <td>{{ $t['title'] }}</td>
        <td>{{ $t['meta'] }}</td>
        <td class="num">{{ $t['value'] }}</td>
      </tr>
    @empty
      <tr><td colspan="5" class="empty-state">Belum ada transaksi hari ini.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

@endsection