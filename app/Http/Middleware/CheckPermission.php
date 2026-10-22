<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    /**
     * Izinkan akses kalau user punya salah satu dari permission yang disebut di route.
     * Contoh: ->middleware('permission:purchase.create')
     */
    public function handle(Request $request, Closure $next, ...$slugs)
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyPermission($slugs)) {
            abort(403, 'Anda tidak punya akses ke menu ini.');
        }

        return $next($request);
    }
}