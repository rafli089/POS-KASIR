@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white rounded-2xl border border-line shadow-sm p-8">
    <div class="mb-6">
        <h1 class="text-xl font-semibold">Buka Shift</h1>
        <p class="text-sm text-muted mt-1">Mulai shift untuk mulai melayani transaksi.</p>
    </div>

    <div class="bg-background rounded-xl px-4 py-3 text-sm mb-6 space-y-1">
        <div class="flex justify-between"><span class="text-muted">Kasir</span><span class="font-medium">{{\App\Models\User::find(session('user_id'))->name }}</span></div>
        <div class="flex justify-between"><span class="text-muted">Waktu</span><span class="font-medium">{{ $now->format('d M Y · H:i') }}</span></div>
    </div>

    <form method="POST" action="{{ route('shift.open.store') }}">
        @csrf
        <label for="opening_cash" class="block text-sm font-medium mb-1.5">Kas awal (Rp)</label>
        <input id="opening_cash" type="number" name="opening_cash" min="0" value="0" required
               class="w-full rounded-lg border border-line px-3.5 py-2.5 text-lg font-semibold focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
        @error('opening_cash')
            <p class="text-xs text-error mt-1.5">{{ $message }}</p>
        @enderror
        <p class="text-xs text-muted mt-1.5 mb-6">Nominal uang tunai di laci kas saat shift dimulai.</p>

        <button class="w-full py-3 rounded-lg bg-primary text-surface text-sm font-semibold hover:bg-black transition">Buka Shift</button>
    </form>
</div>
@endsection