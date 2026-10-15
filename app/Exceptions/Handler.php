<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Sesi/halaman kedaluwarsa (419): jangan tampilkan halaman error, arahkan dengan pesan yang jelas
        $expired = function ($request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi habis, silakan muat ulang halaman lalu coba lagi.'], 419);
            }

            if (! auth()->check()) {
                return redirect()->route('login')->withErrors(['email' => 'Sesi Anda sudah habis, silakan login lagi.']);
            }

            return redirect()->back()->with('status', 'Halaman sudah kedaluwarsa, silakan ulangi.');
        };

        $this->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) use ($expired) {
            return $expired($request);
        });

        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) use ($expired) {
            if ($e->getStatusCode() === 419) {
                return $expired($request);
            }
        });
    }
}
