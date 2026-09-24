@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <h1 class="text-xl font-semibold">Struk Checker</h1>

    <form method="GET" class="bg-white border border-line rounded-xl p-4 grid grid-cols-2 md:grid-cols-5 gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="No. transaksi..."
               class="rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
        <select name="payment_method" class="rounded-lg border border-line px-3 py-2 text-sm bg-white">
            <option value="">Semua metode</option>
            @foreach(\App\Models\PaymentMethod::all() as $method)
                <option value="{{ $method->id }}" {{ request('payment_method') == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
            @endforeach
        </select>
        <button class="px-4 py-2 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">Cari</button>
    </form>

    <div class="bg-white border border-line rounded-xl overflow-hidden">
        @if($transactions->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-muted">
                Transaksi tidak ditemukan. Coba gunakan nomor transaksi atau filter tanggal yang berbeda.
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted text-xs border-b border-line">
                        <th class="px-5 py-2.5 font-medium">Nomor Transaksi</th>
                        <th class="px-5 py-2.5 font-medium">Waktu</th>
                        <th class="px-5 py-2.5 font-medium">Kasir</th>
                        <th class="px-5 py-2.5 font-medium">Metode</th>
                        <th class="px-5 py-2.5 font-medium text-right">Total</th>
                        <th class="px-5 py-2.5 font-medium">Status</th>
                        <th class="px-5 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                        <tr class="border-b border-line/50 last:border-0 hover:bg-background/50">
                            <td class="px-5 py-2.5 font-medium">{{ $tx->transaction_number }}</td>
                            <td class="px-5 py-2.5 text-muted">{{ $tx->created_at->format('d M Y · H:i') }}</td>
                            <td class="px-5 py-2.5">{{ $tx->user->name }}</td>
                            <td class="px-5 py-2.5">{{ $tx->paymentMethod->name }}</td>
                            <td class="px-5 py-2.5 text-right font-medium">Rp{{ number_format($tx->grand_total, 0, ',', '.') }}</td>
                            <td class="px-5 py-2.5"><span class="px-2 py-0.5 rounded-md text-xs font-medium {{ $tx->status === 'COMPLETED' ? 'bg-success/10 text-success' : 'bg-error/10 text-error' }}">{{ $tx->status }}</span></td>
                            <td class="px-5 py-2.5 text-right">
                                <a href="{{ route('transactions.show', $tx) }}" class="text-primary text-xs hover:underline font-medium">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div>{{ $transactions->links() }}</div>
</div>
@endsection