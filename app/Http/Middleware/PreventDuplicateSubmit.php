<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PreventDuplicateSubmit
{
    /**
     * Tolak kiriman form yang kode uniknya (_submit_token) sudah pernah diproses,
     * supaya transaksi tidak tercatat dua kali saat sinyal putus-nyambung.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->input('_submit_token');

        if (! $request->isMethod('post') || ! is_string($token) || $token === '') {
            return $next($request);
        }

        $key = 'submit_token:'.auth()->id().':'.$token;

        if (! Cache::add($key, true, now()->addHours(6))) {
            $message = 'Data ini sudah tersimpan sebelumnya, kiriman ganda diabaikan.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409);
            }

            return redirect()->back()->with('status', $message);
        }

        $response = $next($request);

        // Gagal (validasi/error): lepas kode supaya bisa dikirim ulang setelah diperbaiki
        if ($response->getStatusCode() >= 400 || $request->session()->has('errors')) {
            Cache::forget($key);
        }

        return $response;
    }
}