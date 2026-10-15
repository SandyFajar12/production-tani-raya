<?php

namespace App\Services;

use App\Models\Garden;
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