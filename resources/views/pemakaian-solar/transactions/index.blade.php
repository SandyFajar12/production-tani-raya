@extends('layouts.app')

@section('title', 'Riwayat Transaksi Solar — TaniRaya ERP')
@section('page-title', 'Riwayat Transaksi')
@section('page-subtitle', 'Semua aktivitas solar hari ini')
@section('back-url', route('fuel.index'))

@section('content')

<div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th>Waktu</th>
        <th>Jenis</th>
        <th>Armada / Kebun</th>
        <th>Terkait</th>
        <th class="num">Nilai</th>
      </tr>
    </thead>
    <tbody>
    @forelse ($transactions as $t)
      <tr>
        <td style="white-space:nowrap;">{{ $t['time']->format('H:i') }}</td>
        <td>
          @if ($t['type'] === 'trip')
            <span class="badge badge--rejected">Pemakaian</span>
          @elseif ($t['type'] === 'fill')
            <span class="badge badge--approved">Isi Tangki</span>
          @elseif ($t['type'] === 'adjustment')
            <span class="badge badge--pending">Penyesuaian Stok</span>
          @else
            <span class="badge badge--inactive">Koreksi Saldo</span>
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