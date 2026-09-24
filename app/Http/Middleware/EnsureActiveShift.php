<?php

namespace App\Http\Middleware;

use App\Models\Shift;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveShift
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $shiftId = $request->session()->get('shift_id');

        if (! $shiftId || ! Shift::where('id', $shiftId)->where('status', Shift::STATUS_OPEN)->exists()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Shift belum dibuka.'], 422);
            }

            return redirect()->route('shift.open')->withErrors([
                'shift' => 'Shift belum dibuka.',
            ]);
        }

        return $next($request);
    }
}