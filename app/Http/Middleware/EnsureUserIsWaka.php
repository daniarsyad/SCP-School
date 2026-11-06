<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsWaka
{
    /**
     * Handle an incoming request.
     * Memastikan user yang login memiliki role Waka Kurikulum.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isWaka()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: Hanya Waka Kurikulum yang diizinkan.',
                ], 403);
            }

            abort(403, 'Akses ditolak: Anda tidak memiliki hak akses Waka Kurikulum.');
        }

        return $next($request);
    }
}

