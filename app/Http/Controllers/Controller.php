<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Ambil jumlah data per halaman dari query string (?per_page=20),
     * dipakai semua controller yang nampilin list dengan pagination.
     */
    protected function perPage(): int
    {
        $allowed = [10, 20, 50, 100];
        $perPage = (int) request('per_page', 20);

        return in_array($perPage, $allowed) ? $perPage : 20;
    }
}
