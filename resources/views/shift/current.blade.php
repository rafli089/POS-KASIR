@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Shift Aktif</h1>
        <a href="{{ route('shift.close') }}" class="px-4 py-2 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">Tutup Shift</a>
    </div>

    <div class="bg-white border border-line rounded-xl p-5 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <div class="text-xs text-muted mb-1">Nomor Shift</div>
            <div class="font-medium">{{ $shift->shift_number }}</div>
        </div>
        <div>
            <div class="text-xs text-muted mb-1">Kasir</div>
            <div class="font-medium">{{ $shift->user->name }}</div>
        </div>
        <div>
            <div class="text-xs text-muted mb-1">Dibuka</div>
            <div class="font-medium">{{ $shift->opened_at->format('d M Y · H:i') }}</div>
        </div>
        <div>
            <div class="text-xs text-muted mb-1">Kas Awal</div>
            <div class="font-medium">Rp{{ number_format($shift->opening_cash, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="bg-white border border-line rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold">Kas</h2>
            <span class="text-sm text-muted">Diharapkan <span class="font-medium text-ink">Rp{{ number_format($summary['expected_cash'], 0, ',', '.') }}</span></span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm mb-4">
            <div class="bg-background rounded-lg px-3 py-2.5 flex justify-between"><span class="text-muted">Awal</span><span class="font-medium">Rp{{ number_format($shift->opening_cash, 0, ',', '.') }}</span></div>
            <div class="bg-background rounded-lg px-3 py-2.5 flex justify-between"><span class="text-muted">Masuk</span><span class="font-medium text-success">+Rp{{ number_format($cashIn, 0, ',', '.') }}</span></div>
            <div class="bg-background rounded-lg px-3 py-2.5 flex justify-between"><span class="text-muted">Keluar</span><span class="font-medium text-error">−Rp{{ number_format($cashOut, 0, ',', '.') }}</span></div>
            <div class="bg-background rounded-lg px-3 py-2.5 flex justify-between"><span class="text-muted">Penjualan tunai</span><span class="font-medium">Rp{{ number_format($summary['cash_sales'], 0, ',', '.') }}</span></div>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('shift.cash-in') }}" class="flex gap-2">
                @csrf
                <input type="number" name="amount" min="1" required placeholder="Nominal kas masuk"
                       class="rounded-lg border border-line px-3 py-2 text-sm w-48">
                <button class="px-4 py-2 rounded-lg bg-success text-surface text-sm font-medium hover:opacity-90 transition">Kas Masuk</button>
            </form>
            <form method="POST" action="{{ route('shift.cash-out') }}" class="flex gap-2">
                @csrf
                <input type="number" name="amount" min="1" required placeholder="Nominal kas keluar"
                       class="rounded-lg border border-line px-3 py-2 text-sm w-48">
                <button class="px-4 py-2 rounded-lg border border-error text-error text-sm font-medium hover:bg-error hover:text-surface transition">Kas Keluar</button>
            </form>
        </div>
    </div>

    <div class="bg-white border border-line rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-line font-semibold text-sm">Transaksi ({{ $transactions->count() }})</div>
        @if($transactions->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-muted">Belum ada transaksi. Transaksi yang dibuat selama shift akan muncul di sini.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted text-xs border-b border-line">
                        <th class="px-5 py-2.5 font-medium">Nomor</th>
                        <th class="px-5 py-2.5 font-medium">Waktu</th>
                        <th class="px-5 py-2.5 font-medium">Metode</th>
                        <th class="px-5 py-2.5 font-medium text-right">Total</th>
                        <th class="px-5 py-2.5 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                        <tr class="border-b border-line/50 last:border-0">
                            <td class="px-5 py-2.5"><a href="{{ route('transactions.show', $tx) }}" class="hover:text-primary">{{ $tx->transaction_number }}</a></td>
                            <td class="px-5 py-2.5 text-muted">{{ $tx->created_at->format('H:i:s') }}</td>
                            <td class="px-5 py-2.5">{{ $tx->paymentMethod->name }}</td>
                            <td class="px-5 py-2.5 text-right font-medium">Rp{{ number_format($tx->grand_total, 0, ',', '.') }}</td>
                            <td class="px-5 py-2.5"><span class="px-2 py-0.5 rounded-md text-xs bg-success/10 text-success font-medium">{{ $tx->status }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection