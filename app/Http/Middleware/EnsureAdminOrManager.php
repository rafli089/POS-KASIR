<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOrManager
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = session('user_role');

        if (! in_array($role, ['ADMIN', 'MANAGER'])) {
            return redirect()->route('pos.index')->withErrors([
                'access' => 'Anda tidak memiliki akses ke halaman ini.',
            ]);
        }

        return $next($request);
    }
}
