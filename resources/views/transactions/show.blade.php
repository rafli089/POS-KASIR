@extends('layouts.app')

@section('content')
<div x-data="{ printing: false }" x-init="if (new URLSearchParams(window.location.search).has('print')) setTimeout(() => { printing = true; window.print(); }, 300)">
    <div class="no-print mb-4 flex flex-wrap gap-2">
        <a href="{{ url()->previous() }}" class="px-4 py-2 rounded-lg border border-line text-sm hover:border-primary transition">Kembali</a>
        <button @click="window.print()" class="px-4 py-2 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">Cetak Struk</button>
        <button onclick="fetch('{{ route('transactions.store') }}')"
                class="hidden">{{-- placeholder --}}</button>
        <span class="ml-auto text-xs text-muted self-center">Struk dicetak {{ $transaction->receipt?->print_count ?? 0 }}×</span>
                @if($canVoid ?? false)
                    <form method="POST" action="{{ route('transactions.void', $transaction) }}" id="void-form" class="inline">
                        @csrf
                        <input type="hidden" name="reason" id="void-reason">
                        <button type="button" onclick="const r = prompt('Alasan pembatalan (wajib):'); if (r) { document.getElementById('void-reason').value = r; document.getElementById('void-form').submit(); }"
                                class="px-4 py-2 rounded-lg border border-error text-error text-sm hover:bg-error hover:text-surface transition">
                            Batalkan Transaksi
                        </button>
                    </form>
                @endif
                @if($canRefund ?? false)
                    <form method="POST" action="{{ route('transactions.refund', $transaction) }}" id="refund-form" class="inline">
                        @csrf
                        <input type="hidden" name="amount" id="refund-amount">
                        <input type="hidden" name="reason" id="refund-reason">
                        <button type="button" onclick="const amt = prompt('Nominal refund (maks Rp{{ $transaction->grand_total }}):'); const rn = prompt('Alasan refund (wajib):'); if (amt && rn) { document.getElementById('refund-amount').value = amt; document.getElementById('refund-reason').value = rn; document.getElementById('refund-form').submit(); }"
                                class="px-4 py-2 rounded-lg border border-warning text-warning text-sm hover:bg-warning hover:text-surface transition">
                            Refund
                        </button>
                    </form>
                @endif
    </div>

    <div class="grid lg:grid-cols-2 gap-4 no-print">
        <div class="bg-white border border-line rounded-xl p-5">
            <h2 class="font-semibold mb-4">{{ $transaction->transaction_number }}</h2>
            <div class="space-y-1.5 text-sm mb-4">
                <div class="flex justify-between"><span class="text-muted">Waktu</span><span>{{ $transaction->created_at->format('d M Y · H:i:s') }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Kasir</span><span>{{ $transaction->user->name }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Shift</span><span>{{ $transaction->shift->shift_number }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Metode</span><span>{{ $transaction->paymentMethod->name }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Status</span><span class="font-medium {{ $transaction->status === 'COMPLETED' ? 'text-success' : ($transaction->status === 'REFUNDED' ? 'text-warning' : 'text-error') }}">{{ $transaction->status }}</span></div>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted text-xs border-t border-line">
                        <th class="py-2 font-medium">Item</th>
                        <th class="py-2 font-medium text-center">Qty</th>
                        <th class="py-2 font-medium text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaction->items as $item)
                        <tr class="border-t border-line/50">
                            <td class="py-2">
                                <div>{{ $item->product_name }}</div>
                                <div class="text-xs text-muted">{{ number_format($item->unit_price, 0, ',', '.') }}</div>
                                @if($item->notes)
                                    <div class="text-xs text-warning italic">📝 {{ $item->notes }}</div>
                                @endif
                            </td>
                            <td class="py-2 text-center">{{ $item->quantity }}</td>
                            <td class="py-2 text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4 pt-3 border-t border-line space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-muted">Subtotal</span><span>{{ number_format($transaction->subtotal, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Diskon</span><span>−{{ number_format($transaction->discount, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Pajak</span><span>+{{ number_format($transaction->tax, 0, ',', '.') }}</span></div>
                <div class="flex justify-between font-semibold text-base pt-2 border-t border-line"><span>Total</span><span>{{ number_format($transaction->grand_total, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Bayar</span><span>{{ number_format($transaction->payment_amount, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Kembalian</span><span>{{ number_format($transaction->change_amount, 0, ',', '.') }}</span></div>
            </div>
        </div>

        <div class="bg-white border border-line rounded-xl p-5 hidden lg:block">
            <h2 class="font-semibold mb-4">Aktivitas</h2>
            <div class="space-y-3">
                @foreach(\App\Models\ShiftActivity::where('reference_type', 'transaction')->where('reference_id', $transaction->id)->latest('created_at')->get() as $activity)
                    <div class="text-sm">
                        <div class="text-xs text-muted">{{ $activity->created_at->format('H:i:s') }} · {{ $activity->user->name }}</div>
                        <div class="font-medium">{{ $activity->activity_type }}</div>
                        @if($activity->description)<div class="text-muted">{{ $activity->description }}</div>@endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Thermal receipt --}}
    <div id="receipt" class="hidden print:block mx-auto bg-white text-black text-[12px] leading-tight p-4 max-w-[300px] font-mono">
        <div class="text-center mb-2">
            <div class="font-bold text-sm">{{ config('app.name') }}</div>
            <div class="mt-0.5">{{ $transaction->created_at->format('d/m/Y H:i') }}</div>
            <div class="mt-0.5 font-semibold">{{ $transaction->transaction_number }}</div>
        </div>
        <div class="border-t border-black my-2 pt-1">Kasir: {{ $transaction->user->name }}</div>
        @foreach($transaction->items as $item)
            <div class="flex justify-between">
                <span>{{ $item->product_name }}</span>
                <span>{{ $item->quantity }}×</span>
            </div>
            @if($item->notes)
                <div class="text-xs text-right" style="margin-left:8px">📝 {{ $item->notes }}</div>
            @endif
            <div class="flex justify-between text-right">
                <span></span>
                <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
            </div>
        @endforeach
        <div class="border-t border-black mt-2 pt-1 flex justify-between"><span>Subtotal</span><span>{{ number_format($transaction->subtotal, 0, ',', '.') }}</span></div>
        @if($transaction->discount)<div class="flex justify-between"><span>Diskon</span><span>−{{ number_format($transaction->discount, 0, ',', '.') }}</span></div>@endif
        @if($transaction->tax)<div class="flex justify-between"><span>Pajak</span><span>+{{ number_format($transaction->tax, 0, ',', '.') }}</span></div>@endif
        <div class="flex justify-between font-bold text-sm py-1"><span>TOTAL</span><span>{{ number_format($transaction->grand_total, 0, ',', '.') }}</span></div>
        <div class="flex justify-between"><span>{{ $transaction->paymentMethod->name }}</span><span>{{ number_format($transaction->payment_amount, 0, ',', '.') }}</span></div>
        @if($transaction->change_amount > 0)
            <div class="flex justify-between"><span>Kembalian</span><span>{{ number_format($transaction->change_amount, 0, ',', '.') }}</span></div>
        @endif
        <div class="text-center mt-3 border-t border-black pt-1.5">Terima kasih!</div>
        <div class="text-center">Sampai jumpa kembali ☕</div>
    </div>
</div>
@endsection