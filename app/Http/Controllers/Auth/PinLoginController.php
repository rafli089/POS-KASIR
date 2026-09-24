<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftActivity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PinLoginController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('user_id')) {
            return redirect()->route('pos.index');
        }

        return view('auth.login', [
            'users' => User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'pin' => ['required', 'digits_between:4,6'],
        ]);

        $user = User::where('id', $validated['user_id'])
            ->where('status', User::STATUS_ACTIVE)
            ->firstOrFail();

        if (! Hash::check($validated['pin'], $user->pin_hash)) {
            return back()->withErrors([
                'pin' => 'PIN salah. Silakan coba lagi.',
            ])->onlyInput('user_id');
        }

        $request->session()->regenerate();
        $request->session()->put('user_id', $user->id);
        $request->session()->put('user_role', $user->role);
        $request->session()->put('login_time', now()->toDateTimeString());
        $request->session()->forget('shift_id');

        ShiftActivity::create([
            'shift_id' => null,
            'user_id' => $user->id,
            'activity_type' => ShiftActivity::TYPE_LOGIN,
            'description' => "Kasir {$user->name} login.",
        ]);

        $shift = Shift::currentForUser($user->id);
        if ($shift) {
            $request->session()->put('shift_id', $shift->id);
        }

        return redirect()->intended($shift ? route('pos.index') : route('shift.open'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('user_id');
        $shift = Shift::currentForUser($userId);

        if ($shift) {
            ShiftActivity::create([
                'shift_id' => $shift->id,
                'user_id' => $userId,
                'activity_type' => ShiftActivity::TYPE_LOGOUT,
                'description' => 'Kasir logout.',
            ]);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}