@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <h1 class="text-xl font-semibold">Laporan Shift</h1>

    <div class="bg-white border border-line rounded-xl p-5">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><div class="text-xs text-muted mb-1">Shift</div><div class="font-medium">{{ $shift->shift_number }}</div></div>
            <div><div class="text-xs text-muted mb-1">Kasir</div><div class="font-medium">{{ $shift->user->name }}</div></div>
            <div><div class="text-xs text-muted mb-1">Buka</div><div class="font-medium">{{ $shift->opened_at->format('d M Y H:i') }}</div></div>
            <div><div class="text-xs text-muted mb-1">Tutup</div><div class="font-medium">{{ $shift->closed_at?->format('d M Y H:i') ?? '—' }}</div></div>
        </div>
    </div>

    <div class="bg-white border border-line rounded-xl p-5">
        <h2 class="text-sm font-semibold mb-3">Penjualan</h2>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-muted">Total transaksi</span><span class="font-medium">{{ $summary['total_transactions'] }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Penjualan kotor</span><span>Rp{{ number_format($summary['gross_sales'], 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Diskon</span><span>Rp{{ number_format($summary['discount'], 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Pajak</span><span>Rp{{ number_format($summary['tax'], 0, ',', '.') }}</span></div>
            <div class="flex justify-between text-base font-semibold pt-2 border-t border-line"><span>Penjualan bersih</span><span>Rp{{ number_format($summary['net_sales'], 0, ',', '.') }}</span></div>
        </div>
    </div>

    <div class="bg-white border border-line rounded-xl p-5">
        <h2 class="text-sm font-semibold mb-3">Metode Pembayaran</h2>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="bg-background rounded-lg px-3 py-2.5 flex justify-between"><span class="text-muted">Tunai</span><span class="font-medium">Rp{{ number_format($summary['cash_sales'], 0, ',', '.') }}</span></div>
            <div class="bg-background rounded-lg px-3 py-2.5 flex justify-between"><span class="text-muted">QRIS</span><span class="font-medium">Rp{{ number_format($summary['qris_sales'], 0, ',', '.') }}</span></div>
            <div class="bg-background rounded-lg px-3 py-2.5 flex justify-between"><span class="text-muted">Debit</span><span class="font-medium">Rp{{ number_format($summary['debit_sales'], 0, ',', '.') }}</span></div>
            <div class="bg-background rounded-lg px-3 py-2.5 flex justify-between"><span class="text-muted">Kartu kredit</span><span class="font-medium">Rp{{ number_format($summary['credit_sales'], 0, ',', '.') }}</span></div>
        </div>
    </div>

    <div class="bg-white border border-line rounded-xl p-5">
        <h2 class="text-sm font-semibold mb-3">Rekonsiliasi Kas</h2>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-muted">Kas awal</span><span>Rp{{ number_format($shift->opening_cash, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Kas yang diharapkan</span><span>Rp{{ number_format($shift->expected_cash, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Kas aktual</span><span>Rp{{ number_format($shift->actual_cash, 0, ',', '.') }}</span></div>
            <div class="flex justify-between font-medium {{ $shift->cash_difference < 0 ? 'text-error' : ($shift->cash_difference > 0 ? 'text-success' : '') }}">
                <span>Selisih kas</span>
                <span>{{ $shift->cash_difference >= 0 ? '+' : '−' }}Rp{{ number_format(abs($shift->cash_difference), 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection