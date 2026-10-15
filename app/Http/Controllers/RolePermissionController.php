<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    /** Modul yang tampil di halaman Role & Permission */
    private const MODULES = [
        'stok' => 'Stok Sparepart',
        'user' => 'Master User',
    ];

    /** Judul kelompok menu (berdasarkan awalan slug), urutan mengikuti urutan permission di database */
    private const GROUPS = [
        'stock' => 'Update Stok',
        'preorder' => 'Pre-Order',
        'purchase' => 'Pembelian',
        'usage' => 'Pemakaian',
        'report' => 'Laporan Pemakaian',
        'sparepart' => 'Master Sparepart',
        'fleet' => 'Master Armada',
        'supplier' => 'Master Supplier',
        'garden' => 'Master Kebun',
        'user' => 'Master User',
        'role' => 'Role & Permission',
    ];

    /** Hak "ubah" otomatis membutuhkan hak "lihat" di menu yang sama */
    private const REQUIRES = [
        'stock.adjust' => 'stock.view',
        'preorder.create' => 'preorder.view',
        'preorder.review' => 'preorder.view',
        'preorder.approve' => 'preorder.view',
        'purchase.create' => 'purchase.view',
        'usage.create' => 'usage.view',
        'sparepart.manage' => 'sparepart.view',
        'fleet.manage' => 'fleet.view',
        'supplier.manage' => 'supplier.view',
        'garden.manage' => 'garden.view',
        'user.manage' => 'user.view',
    ];

    public function index()
    {
        $roles = Role::withCount('users')->orderBy('id')->get();
        $activeRole = Role::with('permissions')->find(request('role', $roles->first()->id ?? null));

        $modules = self::MODULES;
        $activeModule = in_array(request('module'), array_keys($modules), true)
            ? request('module')
            : array_key_first($modules);

        $groups = Permission::where('module', $activeModule)->orderBy('id')->get()
            ->groupBy(function ($p) {
                return explode('.', $p->slug)[0];
            });

        $groupTitles = self::GROUPS;
        $requires = self::REQUIRES;

        return view('roles.index', compact('roles', 'activeRole', 'modules', 'activeModule', 'groups', 'groupTitles', 'requires'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'module' => ['required', 'in:'.implode(',', array_keys(self::MODULES))],
        ]);

        if ($role->slug === 'superadmin') {
            return back()->withErrors(['role' => 'Hak akses SuperAdmin selalu penuh dan tidak bisa diubah.']);
        }

        $permissions = Permission::where('module', $request->input('module'))->get();
        $moduleIds = $permissions->pluck('id')->all();
        $slugToId = $permissions->pluck('id', 'slug')->all();
        $idToSlug = array_flip($slugToId);

        $checked = array_values(array_intersect(array_map('intval', $request->input('permissions', [])), $moduleIds));

        // Hak ubah otomatis membawa hak lihat
        foreach ($checked as $id) {
            $slug = $idToSlug[$id] ?? null;
            if ($slug && isset(self::REQUIRES[$slug]) && isset($slugToId[self::REQUIRES[$slug]])) {
                $checked[] = $slugToId[self::REQUIRES[$slug]];
            }
        }
        $checked = array_values(array_unique($checked));

        // Hanya modul yang sedang dibuka yang diganti, hak akses modul lain dipertahankan
        $others = array_diff($role->permissions()->pluck('permissions.id')->all(), $moduleIds);

        $role->permissions()->sync(array_merge($others, $checked));

        return back()->with('status', 'Perubahan hak akses untuk role '.$role->name.' tersimpan.');
    }
}