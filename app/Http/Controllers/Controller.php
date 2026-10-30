<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /** Modul yang memakai halaman master bersama (dipakai filter supplier dan tombol kembali) */
    protected const MODUL_LIST = [
        'sparepart' => 'Stok Sparepart',
        'kebutuhan_kebun' => 'Kebutuhan Kebun',
        'pemakaian_solar' => 'Pemakaian Solar',
    ];

    /** Modul asal halaman master bersama, dari ?modul= (default: sparepart) */
    protected function currentModul(): string
    {
        $modul = request('modul');

        return is_string($modul) && isset(self::MODUL_LIST[$modul]) ? $modul : 'sparepart';
    }

    /** Izin master bersama untuk modul asal: izin bawaan (sparepart) dibaca sebagai izin modul tersebut */
    protected function applyModulPermissions(string $modul): void
    {
        $maps = [
            'kebutuhan_kebun' => [
                'garden.view' => 'need_garden.view',
                'garden.manage' => 'need_garden.manage',
                'supplier.view' => 'need_supplier.view',
                'supplier.manage' => 'need_supplier.manage',
            ],
            'pemakaian_solar' => [
                'garden.view' => 'fuel_garden.view',
                'garden.manage' => 'fuel_garden.manage',
                'fleet.view' => 'fuel_fleet.view',
                'fleet.manage' => 'fuel_fleet.manage',
                'supplier.view' => 'fuel_supplier.view',
                'supplier.manage' => 'fuel_supplier.manage',
            ],
        ];

        if (isset($maps[$modul]) && auth()->check()) {
            auth()->user()->mapPermissions($maps[$modul]);
        }
    }

    /**
     * Cek izin master bersama untuk modul asal.
     * $master: garden | supplier | fleet. $kind: view | manage (manage sudah mencakup view).
     */
    protected function authorizeMaster(string $modul, string $master, string $kind): void
    {
        $this->applyModulPermissions($modul);
        $user = auth()->user();

        $allowed = $kind === 'manage'
            ? $user->hasPermission($master.'.manage')
            : ($user->hasPermission($master.'.view') || $user->hasPermission($master.'.manage'));

        abort_unless($allowed, 403, 'Anda tidak punya akses ke menu ini.');
    }

    /** Tujuan tombol kembali di halaman master bersama */
    protected function modulBackUrl(string $modul): string
    {
        switch ($modul) {
            case 'kebutuhan_kebun':
                return route('needs.index');
            case 'pemakaian_solar':
                return route('fuel.index');
            default:
                return route('dashboard');
        }
    }

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
