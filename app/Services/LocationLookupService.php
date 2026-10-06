<?php

namespace App\Services;

class LocationLookupService
{
    /**
     * Referensi titik kabupaten/kota se-Sumatera Selatan (perkiraan, bukan batas presisi).
     * Tambah/sesuaikan baris di sini kalau area operasional meluas.
     */
    protected function referencePoints(): array
    {
        return [
            // ---- Sumatera Barat (kabupaten/kota) ----
            ['label' => 'Kota Padang', 'lat' => -0.9492, 'lng' => 100.3543],
            ['label' => 'Kota Bukittinggi', 'lat' => -0.3055, 'lng' => 100.3692],
            ['label' => 'Kota Padang Panjang', 'lat' => -0.4653, 'lng' => 100.4206],
            ['label' => 'Kota Pariaman', 'lat' => -0.6278, 'lng' => 100.1206],
            ['label' => 'Kota Payakumbuh', 'lat' => -0.2283, 'lng' => 100.6322],
            ['label' => 'Kota Sawahlunto', 'lat' => -0.6833, 'lng' => 100.7833],
            ['label' => 'Kota Solok', 'lat' => -0.8, 'lng' => 100.65],
            ['label' => 'Kab. Agam', 'lat' => -0.2167, 'lng' => 100.05],
            ['label' => 'Kab. Dharmasraya', 'lat' => -1.2167, 'lng' => 101.5667],
            ['label' => 'Kab. Kepulauan Mentawai', 'lat' => -2.1833, 'lng' => 99.6167],
            ['label' => 'Kab. Lima Puluh Kota', 'lat' => -0.15, 'lng' => 100.65],
            ['label' => 'Kab. Padang Pariaman', 'lat' => -0.5667, 'lng' => 100.2167],
            ['label' => 'Kab. Pasaman', 'lat' => 0.2833, 'lng' => 99.9333],
            ['label' => 'Kab. Pasaman Barat', 'lat' => 0.1167, 'lng' => 99.7],
            ['label' => 'Kab. Pesisir Selatan', 'lat' => -1.5833, 'lng' => 100.9667],
            ['label' => 'Kab. Sijunjung', 'lat' => -0.6833, 'lng' => 100.9667],
            ['label' => 'Kab. Solok', 'lat' => -0.9167, 'lng' => 100.85],
            ['label' => 'Kab. Solok Selatan', 'lat' => -1.4, 'lng' => 101.3167],
            ['label' => 'Kab. Tanah Datar', 'lat' => -0.45, 'lng' => 100.5667],

            // ---- Provinsi sekitar (kota besar, buat jaga-jaga area luar Sumbar) ----
            ['label' => 'Pekanbaru (Riau)', 'lat' => 0.5071, 'lng' => 101.4478],
            ['label' => 'Dumai (Riau)', 'lat' => 1.6667, 'lng' => 101.45],
            ['label' => 'Kota Jambi (Jambi)', 'lat' => -1.6, 'lng' => 103.6167],
            ['label' => 'Medan (Sumatera Utara)', 'lat' => 3.5952, 'lng' => 98.6722],
            ['label' => 'Padang Sidempuan (Sumatera Utara)', 'lat' => 1.3758, 'lng' => 99.2683],
            ['label' => 'Sibolga (Sumatera Utara)', 'lat' => 1.7427, 'lng' => 98.7792],
            ['label' => 'Kota Bengkulu (Bengkulu)', 'lat' => -3.8, 'lng' => 102.2667],
        ];
    }

    protected function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public function nearestLabel(float $lat, float $lng): string
    {
        $nearest = null;
        $minDistance = null;

        foreach ($this->referencePoints() as $point) {
            $distance = $this->distanceKm($lat, $lng, $point['lat'], $point['lng']);
            if ($minDistance === null || $distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $point;
            }
        }

        return $nearest ? $nearest['label'].' (±'.round($minDistance).' km)' : 'Lokasi tidak diketahui';
    }
}