<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->input('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->input('q'), fn ($q, $kw) => $q->where('name', 'like', "%{$kw}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'pin' => ['required', 'digits_between:4,6'],
            'role' => ['required', 'in:CASHIER,MANAGER,ADMIN'],
        ]);

        User::create([
            'name' => $validated['name'],
            'pin_hash' => $validated['pin'],
            'role' => $validated['role'],
            'status' => User::STATUS_ACTIVE,
        ]);

        return redirect()->route('users.index')->with('success', "User {$validated['name']} dibuat.");
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'pin' => ['nullable', 'digits_between:4,6'],
            'role' => ['required', 'in:CASHIER,MANAGER,ADMIN'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ]);

        $user->name = $validated['name'];
        $user->role = $validated['role'];
        $user->status = $validated['status'];

        if (! empty($validated['pin'])) {
            $user->pin_hash = $validated['pin'];
        }

        $user->save();

        return redirect()->route('users.index')->with('success', "User {$validated['name']} diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === (int) session('user_id')) {
            return back()->withErrors(['user' => 'Tidak dapat menghapus akun sendiri.']);
        }

        if ($user->shifts()->exists() || $user->transactions()->exists()) {
            return back()->withErrors(['user' => "User {$user->name} punya riwayat shift/transaksi — set status INACTIVE saja."]);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', "User {$user->name} dihapus.");
    }
}