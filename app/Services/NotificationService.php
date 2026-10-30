<?php

namespace App\Services;

use App\Models\Garden;
use App\Models\KebutuhanKebun\Item as KebunItem;
use App\Models\KebutuhanKebun\Preorder as KebunPreorder;
use App\Models\Preorder;
use App\Models\Sparepart;
use App\Models\User;
use App\Notifications\AppNotification;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /** Notifikasi yang lebih tua dari ini (hari) dihapus otomatis */
    private const KEEP_DAYS = 30;

    /* ------------------------- Peristiwa ------------------------- */

    /** Pre-order baru -> Admin Pusat (hak approval awal) */
    public static function preorderCreated(Preorder $po): void
    {
        self::safely(function () use ($po) {
            $po->loadMissing(['sparepart', 'garden', 'requester']);

            self::send(
                self::usersByPermission(['preorder.review']),
                'preorder',
                'Pre-order baru '.$po->code,
                ($po->requester->name ?? 'PJ Site').' mengajukan '.self::item($po).'.',
                route('preorders.index', ['status' => 'waiting'], false)
            );
        });
    }

    /** Diteruskan ke Owner -> Owner (hak approval Owner) */
    public static function preorderForwarded(Preorder $po): void
    {
        self::safely(function () use ($po) {
            $po->loadMissing(['sparepart', 'garden']);

            self::send(
                self::usersByPermission(['preorder.approve']),
                'preorder',
                'Menunggu persetujuan Anda: '.$po->code,
                self::item($po).'. Stok di pusat tidak ada.',
                route('preorders.index', ['status' => 'waiting'], false)
            );
        });
    }

    /** Disetujui Owner -> pemohon dan pemegang hak Pembelian */
    public static function preorderApproved(Preorder $po): void
    {
        self::safely(function () use ($po) {
            $po->loadMissing(['sparepart', 'garden']);

            $recipients = self::usersByPermission(['purchase.create'])
                ->merge(self::requesterOf($po));

            self::send(
                $recipients,
                'preorder',
                'Pre-order '.$po->code.' disetujui',
                self::item($po).'. Jumlah disetujui '.$po->approved_quantity.', siap dibeli.',
                function (User $user) {
                    return $user->hasAnyPermission(['purchase.view', 'purchase.create'])
                        ? route('purchases.index', [], false)
                        : route('preorders.index', [], false);
                }
            );
        });
    }

    /** Ditolak -> pemohon */
    public static function preorderRejected(Preorder $po): void
    {
        self::safely(function () use ($po) {
            $po->loadMissing(['sparepart', 'garden']);
            $notes = trim((string) $po->approval_notes);

            self::send(
                self::requesterOf($po),
                'preorder',
                'Pre-order '.$po->code.' ditolak',
                self::item($po).' ditolak.'.($notes !== '' ? ' Catatan: '.$notes : ''),
                route('preorders.index', [], false)
            );
        });
    }

    /** Dikirim -> pemohon */
    public static function preorderShipped(Preorder $po): void
    {
        self::safely(function () use ($po) {
            $po->loadMissing(['sparepart', 'garden']);

            self::send(
                self::requesterOf($po),
                'preorder',
                'Pre-order '.$po->code.' dikirim',
                ($po->sparepart->name ?? '-').' ×'.$po->shipped_quantity.' sudah dikirim ke '.($po->garden->name ?? '-').'.',
                route('preorders.index', [], false)
            );
        });
    }

    /** Stok baru melewati batas minimum di satu kebun -> Admin Pusat dan Owner */
    public static function lowStock(Sparepart $sparepart, int $gardenId, int $qty): void
    {
        self::safely(function () use ($sparepart, $gardenId, $qty) {
            $sparepart->loadMissing('unit');
            $garden = Garden::find($gardenId);

            self::send(
                self::usersByRole(['admin-pusat', 'owner']),
                'stock',
                'Stok menipis: '.$sparepart->name,
                ($garden->name ?? '-').' tinggal '.$qty.' '.($sparepart->unit->name ?? '').' (minimum '.$sparepart->min_stock.').',
                function (User $user) {
                    if ($user->hasAnyPermission(['stock.view', 'stock.adjust'])) {
                        return route('stocks.index', ['low_stock' => 1], false);
                    }
                    if ($user->hasAnyPermission(['sparepart.view', 'sparepart.manage'])) {
                        return route('spareparts.index', ['low_stock' => 1], false);
                    }

                    return null;
                }
            );
        });
    }

    /* ------------------------- Pemakaian Solar ------------------------- */

    /** Stok solar di satu kebun turun sampai batas minimum -> Admin Pusat dan Owner */
    public static function lowFuelStock(int $gardenId, float $qty, float $min): void
    {
        self::safely(function () use ($gardenId, $qty, $min) {
            $garden = Garden::find($gardenId);

            self::send(
                self::usersByRole(['admin-pusat', 'owner']),
                'stock',
                'Stok solar menipis: '.($garden->name ?? '-'),
                'Stok solar tinggal '.\App\Models\PemakaianSolar\Stock::fmt($qty).' L (minimum '.\App\Models\PemakaianSolar\Stock::fmt($min).' L).',
                function (User $user) {
                    if ($user->hasAnyPermission(['fuel_stock.view', 'fuel_stock.adjust'])) {
                        return route('solar.stocks.index', ['low_stock' => 1], false);
                    }

                    return null;
                }
            );
        });
    }

    /** Solar standby armada turun di bawah batas minimum -> Admin Pusat dan Owner */
    public static function lowFuelStandby(\App\Models\Fleet $fleet, float $qty, float $min): void
    {
        self::safely(function () use ($fleet, $qty, $min) {
            self::send(
                self::usersByRole(['admin-pusat', 'owner']),
                'stock',
                'Solar standby rendah: '.$fleet->name,
                $fleet->name.' ('.$fleet->code.') tersisa '.\App\Models\PemakaianSolar\Stock::fmt($qty).' L di tangki (minimum standby '.\App\Models\PemakaianSolar\Stock::fmt($min).' L).',
                function (User $user) {
                    if ($user->hasAnyPermission(['fuel_balance.view', 'fuel_balance.adjust'])) {
                        return route('solar.balances.index', ['low' => 1], false);
                    }

                    return null;
                }
            );
        });
    }

    /* ------------------- Pre-order Kebutuhan Kebun ------------------- */

    /** Pre-order kebutuhan baru -> Admin Pusat (hak review) */
    public static function kebunPreorderCreated(KebunPreorder $po): void
    {
        self::safely(function () use ($po) {
            $po->loadMissing(['item', 'garden', 'requester']);

            self::send(
                self::usersByPermission(['need_preorder.review']),
                'preorder',
                'Pre-order kebutuhan baru '.$po->code,
                ($po->requester->name ?? 'PJ Site').' mengajukan '.self::kebunItemText($po).'.',
                route('kebun.preorders.index', ['status' => 'waiting'], false)
            );
        });
    }

    /** Diteruskan ke Owner -> Owner (hak approve) */
    public static function kebunPreorderForwarded(KebunPreorder $po): void
    {
        self::safely(function () use ($po) {
            $po->loadMissing(['item', 'garden']);

            self::send(
                self::usersByPermission(['need_preorder.approve']),
                'preorder',
                'Menunggu persetujuan Anda: '.$po->code,
                self::kebunItemText($po).'. Stok di pusat tidak ada.',
                route('kebun.preorders.index', ['status' => 'waiting'], false)
            );
        });
    }

    /** Disetujui Owner -> pemohon dan pemegang hak Pembelian */
    public static function kebunPreorderApproved(KebunPreorder $po): void
    {
        self::safely(function () use ($po) {
            $po->loadMissing(['item', 'garden']);

            $recipients = self::usersByPermission(['need_purchase.create'])
                ->merge(self::requesterOf($po));

            self::send(
                $recipients,
                'preorder',
                'Pre-order '.$po->code.' disetujui',
                self::kebunItemText($po).'. Jumlah disetujui '.$po->approved_quantity.', siap dibeli.',
                function (User $user) {
                    return $user->hasAnyPermission(['need_purchase.view', 'need_purchase.create'])
                        ? route('kebun.purchases.index', [], false)
                        : route('kebun.preorders.index', [], false);
                }
            );
        });
    }

    /** Ditolak -> pemohon */
    public static function kebunPreorderRejected(KebunPreorder $po): void
    {
        self::safely(function () use ($po) {
            $po->loadMissing(['item', 'garden']);
            $notes = trim((string) $po->approval_notes);

            self::send(
                self::requesterOf($po),
                'preorder',
                'Pre-order '.$po->code.' ditolak',
                self::kebunItemText($po).' ditolak.'.($notes !== '' ? ' Catatan: '.$notes : ''),
                route('kebun.preorders.index', [], false)
            );
        });
    }

    /** Dikirim -> pemohon */
    public static function kebunPreorderShipped(KebunPreorder $po): void
    {
        self::safely(function () use ($po) {
            $po->loadMissing(['item', 'garden']);

            self::send(
                self::requesterOf($po),
                'preorder',
                'Pre-order '.$po->code.' dikirim',
                ($po->item->name ?? '-').' ×'.$po->shipped_quantity.' sudah dikirim ke '.($po->garden->name ?? '-').'.',
                route('kebun.preorders.index', [], false)
            );
        });
    }

    protected static function kebunItemText(KebunPreorder $po): string
    {
        return ($po->item->name ?? '-').' ×'.$po->quantity.' untuk '.($po->garden->name ?? '-');
    }

    /** Stok kebutuhan kebun melewati batas minimum di satu kebun -> Admin Pusat dan Owner */
    public static function lowStockKebun(KebunItem $item, int $gardenId, int $qty): void
    {
        self::safely(function () use ($item, $gardenId, $qty) {
            $item->loadMissing('unit');
            $garden = Garden::find($gardenId);

            self::send(
                self::usersByRole(['admin-pusat', 'owner']),
                'stock',
                'Stok menipis: '.$item->name,
                ($garden->name ?? '-').' tinggal '.$qty.' '.($item->unit->name ?? '').' (minimum '.$item->min_stock.').',
                function (User $user) {
                    if ($user->hasAnyPermission(['need_stock.view', 'need_stock.adjust'])) {
                        return route('kebun.stocks.index', ['low_stock' => 1], false);
                    }
                    if ($user->hasAnyPermission(['need_item.view', 'need_item.manage'])) {
                        return route('kebun.items.index', [], false);
                    }

                    return null;
                }
            );
        });
    }

    /* ------------------------- Bantuan ------------------------- */

    protected static function item(Preorder $po): string
    {
        return ($po->sparepart->name ?? '-').' ×'.$po->quantity.' untuk '.($po->garden->name ?? '-');
    }

    protected static function usersByPermission(array $slugs)
    {
        return User::where('is_active', true)
            ->whereHas('role.permissions', function ($q) use ($slugs) {
                $q->whereIn('slug', $slugs);
            })
            ->get();
    }

    protected static function usersByRole(array $slugs)
    {
        return User::where('is_active', true)
            ->whereHas('role', function ($q) use ($slugs) {
                $q->whereIn('slug', $slugs);
            })
            ->get();
    }

    protected static function requesterOf(Preorder $po)
    {
        return User::where('id', $po->requested_by)->where('is_active', true)->get();
    }

    /**
     * Buat notifikasi untuk tiap user. Pelaku (user yang sedang login) tidak ikut menerima.
     * $url boleh string, null, atau closure(User): ?string.
     */
    protected static function send($users, string $kind, string $title, string $body, $url = null): void
    {
        $actorId = auth()->id();

        foreach ($users->unique('id') as $user) {
            if ($actorId && (int) $user->id === (int) $actorId) {
                continue;
            }

            try {
                $target = $url instanceof Closure ? $url($user) : $url;
                $user->notify(new AppNotification($kind, $title, $body, $target));
            } catch (\Throwable $e) {
                Log::warning('Notifikasi gagal dibuat untuk user '.$user->id.': '.$e->getMessage());
            }
        }

        self::cleanup();
    }

    /** Hapus notifikasi lama. Paling sering sekali sehari (tanpa penjadwal di hosting). */
    protected static function cleanup(): void
    {
        try {
            if (Cache::add('notif_cleanup_'.now()->format('Ymd'), 1, now()->addDay())) {
                DB::table('notifications')
                    ->where('created_at', '<', now()->subDays(self::KEEP_DAYS))
                    ->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('Pembersihan notifikasi gagal: '.$e->getMessage());
        }
    }

    /** Kegagalan notifikasi tidak boleh menggagalkan proses utama */
    protected static function safely(Closure $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            Log::warning('Notifikasi gagal: '.$e->getMessage());
        }
    }
}