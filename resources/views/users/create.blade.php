@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto space-y-4">
    <h1 class="text-xl font-semibold">Tambah User</h1>

    <form method="POST" action="{{ route('users.store') }}" class="bg-white border border-line rounded-xl p-5 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1.5">Nama</label>
            <input name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            @error('name')<p class="text-xs text-error mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1.5">PIN (4–6 digit)</label>
            <input name="pin" type="password" inputmode="numeric" required class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            @error('pin')<p class="text-xs text-error mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1.5">Peran</label>
            <select name="role" class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm bg-white">
                @foreach(['CASHIER', 'MANAGER', 'ADMIN'] as $r)
                    <option value="{{ $r }}" @selected(old('role') === $r)>{{ $r }}</option>
                @endforeach
            </select>
            @error('role')<p class="text-xs text-error mt-1.5">{{ $message }}</p>@enderror
        </div>

        <button class="w-full py-2.5 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">Simpan</button>
    </form>
</div>
@endsection