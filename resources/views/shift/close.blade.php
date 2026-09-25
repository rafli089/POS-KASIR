@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto space-y-4">
    <h1 class="text-xl font-semibold">Tutup Shift · {{ $shift->shift_number }}</h1>

    <div class="bg-white border border-line rounded-xl p-5">
        <h2 class="text-sm font-semibold mb-3">Ringkasan Penjualan</h2>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-muted">Total transaksi</span><span class="font-medium">{{ $summary['total_transactions'] }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Penjualan kotor</span><span class="font-medium">Rp{{ number_format($summary['gross_sales'], 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Diskon</span><span class="font-medium">{{ number_format($summary['discount'], 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Pajak</span><span class="font-medium">{{ number_format($summary['tax'], 0, ',', '.') }}</span></div>
            <div class="flex justify-between text-base font-semibold pt-2 border-t border-line"><span>Penjualan bersih</span><span>Rp{{ number_format($summary['net_sales'], 0, ',', '.') }}</span></div>
        </div>
    </div>

    <div class="bg-white border border-line rounded-xl p-5">
        <div class="space-y-2 text-sm mb-4">
            <div class="flex justify-between"><span class="text-muted">Kas awal</span><span>Rp{{ number_format($shift->opening_cash, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Penjualan tunai</span><span>Rp{{ number_format($summary['cash_sales'], 0, ',', '.') }}</span></div>
            <div class="flex justify-between font-medium text-base"><span>Kas yang diharapkan</span><span>Rp{{ number_format($summary['expected_cash'], 0, ',', '.') }}</span></div>
        </div>

        <form method="POST" action="{{ $closeAction ?? route('shift.close.store') }}">
            @csrf
            <div class="mb-3 text-sm text-muted">
                Shift <span class="font-medium text-ink">{{ $shift->shift_number }}</span>
                · Kasir <span class="font-medium">{{ $shift->user->name }}</span>
                · Dibuka {{ $shift->opened_at->format('d M Y · H:i') }}
            </div>
            <label for="actual_cash" class="block text-sm font-medium mb-1.5">Kas aktual (Rp)</label>
            <input id="actual_cash" type="number" name="actual_cash" min="0" value="{{ $summary['expected_cash'] }}" required
                   class="w-full rounded-lg border border-line px-3.5 py-2.5 text-lg font-semibold focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            @error('actual_cash')
                <p class="text-xs text-error mt-1.5">{{ $message }}</p>
            @enderror
            <p class="text-xs text-muted mt-1.5 mb-5">Hitung uang di laci kas secara fisik, lalu isi nominalnya.</p>

            <button class="w-full py-3 rounded-lg bg-primary text-surface text-sm font-semibold hover:bg-black transition">Konfirmasi Tutup Shift</button>
        </form>
    </div>
    <div class="text-center mt-4">
        <a href="{{ $backTarget ?? route('shift.current') }}" class="text-xs text-muted hover:text-ink transition">Kembali</a>
    </div>
</div>
@endsection