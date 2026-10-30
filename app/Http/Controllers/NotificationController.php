<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    private const PER_PAGE = 15;

    /** Jumlah badge + token CSRF terbaru (supaya halaman lama tidak memegang token basi) */
    public function count(Request $request)
    {
        return response()->json([
            'count' => $request->user()->unseenNotificationCount(),
            'csrf' => csrf_token(),
        ]);
    }

    /** Daftar notifikasi milik user sendiri, terbaru di atas */
    public function index(Request $request)
    {
        $user = $request->user();
        $since = $this->resolveSince($request, $user);

        $page = $user->notifications()->simplePaginate(self::PER_PAGE);

        $items = $page->getCollection()->map(function ($n) use ($since) {
            $data = $n->data ?: [];

            return [
                'id' => $n->id,
                'kind' => $data['kind'] ?? 'info',
                'title' => $data['title'] ?? 'Notifikasi',
                'body' => $data['body'] ?? '',
                'url' => $data['url'] ?? null,
                'is_new' => $since === null || $n->created_at->gt($since),
                'is_read' => $n->read_at !== null,
                'created_at' => $n->created_at->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'items' => $items,
            'has_more' => $page->hasMorePages(),
            'next_page' => $page->currentPage() + 1,
            'since' => $since ? $since->toIso8601String() : 'none',
        ]);
    }

    /** Lonceng dibuka: badge kembali 0 */
    public function seen(Request $request)
    {
        DB::table('users')
            ->where('id', $request->user()->id)
            ->update(['notifications_seen_at' => now()]);

        return response()->json(['count' => 0]);
    }

    /** Tandai satu notifikasi sudah dibaca (hanya milik sendiri) */
    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json(['url' => $notification->data['url'] ?? null]);
    }

    /**
     * Batas "Baru" vs "Sebelumnya": waktu lonceng terakhir dibuka SEBELUM sekarang.
     * Halaman berikutnya membawa nilai yang sama lewat ?since= supaya pengelompokan tetap konsisten.
     */
    private function resolveSince(Request $request, $user): ?Carbon
    {
        $param = $request->query('since');

        if ($param === 'none') {
            return null;
        }

        if ($param) {
            try {
                return Carbon::parse($param);
            } catch (\Throwable $e) {
                // abaikan, pakai nilai dari database
            }
        }

        return $user->notifications_seen_at;
    }
}