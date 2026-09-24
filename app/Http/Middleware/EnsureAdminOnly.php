<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('user_role') !== 'ADMIN') {
            return redirect()->route('pos.index')->withErrors([
                'access' => 'Fitur ini khusus Administrator.',
            ]);
        }

        return $next($request);
    }
}