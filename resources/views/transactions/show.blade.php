@extends('layouts.app')

@section('content')
<div x-data="{ printing: false, reprintDialog: false, authOpen: false, authAction: '', authStep: 'request' }" x-init="if (new URLSearchParams(window.location.search).has('print')) setTimeout(() => { printing = true; window.print(); }, 300)">
    @php
        $voidStep = $voidAuth?->isValid() ? 'otp' : ($voidAuth?->isPending() ? 'waiting' : 'request');
        $refundStep = $refundAuth?->isValid() ? 'otp' : ($refundAuth?->isPending() ? 'waiting' : 'request');
    @endphp
    <div class="no-print mb-4 flex flex-wrap gap-2 items-center">
        <a href="{{ url()->previous() }}" class="px-4 py-2 rounded-lg border border-line text-sm hover:border-primary transition">Kembali</a>
        <button @click="reprintDialog = true" class="px-4 py-2 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">Cetak Ulang Struk</button>
        @if($pendingAuth && $isApprover)
            <form method="POST" action="{{ route('transactions.approve', $transaction) }}" class="inline"
                  onsubmit="return confirm('Setujui permintaan {{ $pendingAuth->action }} dari {{ $pendingAuth->requester->name }}? Kode OTP akan dibuat.')">
                @csrf
                <button class="px-4 py-2 rounded-lg bg-success text-surface text-sm font-medium hover:opacity-90 transition">
                    Setujui &amp; Beri OTP ({{ $pendingAuth->action }})
                </button>
            </form>
        @endif
        <span class="ml-auto text-xs text-muted self-center">Struk dicetak {{ $transaction->receipt?->print_count ?? 0 }}×</span>
        @if($canVoid)
            <button type="button" @click="authAction='void'; authStep='{{ $voidStep }}'; authOpen=true"
                    class="px-4 py-2 rounded-lg border border-error text-error text-sm hover:bg-error hover:text-surface transition">
                Batalkan Transaksi
            </button>
        @endif
        @if($canRefund)
            <button type="button" @click="authAction='refund'; authStep='{{ $refundStep }}'; authOpen=true"
                    class="px-4 py-2 rounded-lg border border-warning text-warning text-sm hover:bg-warning hover:text-surface transition">
                Refund
            </button>
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
                                @if($item->modifiers)
                                    <div class="text-xs text-muted italic">
                                        {{ collect($item->modifiers)->map(fn ($m) => $m['group'].': '.$m['name'])->implode(' · ') }}
                                    </div>
                                @endif
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
                @if($transaction->service_charge > 0)
                    <div class="flex justify-between"><span class="text-muted">Layanan</span><span>+{{ number_format($transaction->service_charge, 0, ',', '.') }}</span></div>
                @endif
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
            @if($item->modifiers)
                <div class="text-xs" style="margin-left:8px">{{ collect($item->modifiers)->map(fn ($m) => $m['group'].': '.$m['name'])->implode(', ') }}</div>
            @endif
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
        @if($transaction->service_charge)<div class="flex justify-between"><span>Layanan</span><span>+{{ number_format($transaction->service_charge, 0, ',', '.') }}</span></div>@endif
        @if($transaction->tax)<div class="flex justify-between"><span>Pajak</span><span>+{{ number_format($transaction->tax, 0, ',', '.') }}</span></div>@endif
        <div class="flex justify-between font-bold text-sm py-1"><span>TOTAL</span><span>{{ number_format($transaction->grand_total, 0, ',', '.') }}</span></div>
        <div class="flex justify-between"><span>{{ $transaction->paymentMethod->name }}</span><span>{{ number_format($transaction->payment_amount, 0, ',', '.') }}</span></div>
        @if($transaction->change_amount > 0)
            <div class="flex justify-between"><span>Kembalian</span><span>{{ number_format($transaction->change_amount, 0, ',', '.') }}</span></div>
        @endif
        <div class="text-center mt-3 border-t border-black pt-1.5">Terima kasih!</div>
        <div class="text-center">Sampai jumpa kembali ☕</div>
    </div>

    {{-- Void/Refund authorization modal --}}
    <div x-show="authOpen" x-cloak x-transition
         class="fixed inset-0 z-50 bg-black/40 grid place-items-center p-4"
         @keydown.escape.window="authOpen=false">
        <div class="bg-white rounded-2xl w-full max-w-sm p-6 shadow-xl" @click.outside="authOpen=false">
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-xl bg-warning/10 grid place-items-center text-lg">🔐</div>
                <div>
                    <h3 class="font-semibold" x-text="authAction==='void' ? 'Batalkan Transaksi?' : 'Refund?'"></h3>
                    <p class="text-xs text-muted">{{ $transaction->transaction_number }}</p>
                </div>
            </div>

            {{-- Step: request (no auth yet or already rejected/used/expired) --}}
            <div x-show="authStep==='request'" class="mt-4 text-sm">
                <p class="mb-3 text-muted">Permintaan void/refund harus disetujui MANAGER/ADMIN. Isi kode transaksi dan alasan, lalu tunggu kode OTP.</p>
                <form method="POST" action="{{ route('transactions.authorize', $transaction) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="action" :value="authAction">
                    <input type="text" name="transaction_code" required placeholder="Kode transaksi: {{ $transaction->transaction_number }}"
                           class="w-full rounded-lg border border-line px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <textarea name="reason" rows="2" required placeholder="Alasan (wajib)"
                              class="w-full rounded-lg border border-line px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                    <div x-show="authAction==='refund'">
                        <input type="number" name="amount" min="1" max="{{ $transaction->grand_total }}" placeholder="Nominal refund (maks Rp{{ number_format($transaction->grand_total, 0, ',', '.') }})"
                               class="w-full rounded-lg border border-line px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <button class="w-full py-3 rounded-lg bg-primary text-surface text-sm font-semibold hover:bg-black transition">Kirim Permintaan</button>
                </form>
            </div>

            {{-- Step: waiting for approval --}}
            <div x-show="authStep==='waiting'" class="mt-4 text-center text-sm text-muted py-6 space-y-2">
                <div class="text-2xl">⏳</div>
                <p>Permintaan sedang menunggu persetujuan MANAGER/ADMIN.<br>Setelah disetujui, kode OTP akan diminta di sini.</p>
                <button @click="authOpen=false" class="mt-2 px-4 py-2 rounded-lg border border-line text-sm">Tutup</button>
            </div>

            {{-- Step: enter OTP from approver --}}
            <div x-show="authStep==='otp'" class="mt-4 text-sm">
                <p x-show="authAction==='void'" class="mb-3 text-muted">
                    Disetujui. Alasan: <span class="font-medium text-ink">{{ $voidAuth?->reason }}</span>
                </p>
                <p x-show="authAction==='refund'" class="mb-3 text-muted">
                    Disetujui. Nominal refund: <span class="font-medium text-ink">Rp{{ number_format($refundAuth?->amount ?? 0, 0, ',', '.') }}</span>
                    <span class="block">Alasan: {{ $refundAuth?->reason }}</span>
                </p>
                <form method="POST"
                      :action="authAction==='void' ? '{{ route('transactions.void', $transaction) }}' : '{{ route('transactions.refund', $transaction) }}'"
                      class="space-y-3">
                    @csrf
                    <input type="text" name="otp" inputmode="numeric" maxlength="6" required autocomplete="one-time-code"
                           placeholder="Kode OTP 6 digit"
                           class="w-full rounded-lg border border-line px-3 py-2.5 text-center text-xl font-bold tracking-[0.4em] focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <button class="w-full py-3 rounded-lg bg-primary text-surface text-sm font-semibold hover:bg-black transition">Validasi &amp; Proses</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Reprint confirmation modal --}}
    <div x-show="reprintDialog" x-cloak x-transition
         class="fixed inset-0 z-50 bg-black/40 grid place-items-center p-4"
         @keydown.escape.window="reprintDialog=false">
        <div class="bg-white rounded-2xl w-full max-w-sm p-6 shadow-xl" @click.outside="reprintDialog=false">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 grid place-items-center text-lg">🖨️</div>
                <h3 class="font-semibold">Cetak Ulang Struk?</h3>
            </div>
            <p class="text-sm text-muted mb-4">
                Struk untuk <span class="font-medium text-ink">{{ $transaction->transaction_number }}</span>
                akan dicetak lagi (sudah dicetak <span class="font-medium">{{ $transaction->receipt?->print_count ?? 0 }}×</span>).
            </p>
            <div class="flex gap-2">
                <button @click="reprintDialog=false" class="flex-1 py-2.5 rounded-lg border border-line text-sm">Batal</button>
                <form method="POST" action="{{ route('transactions.reprint', $transaction) }}" class="flex-1">
                    @csrf
                    <button class="w-full py-2.5 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">Ya, Cetak</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection