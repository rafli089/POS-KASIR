@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-xl font-semibold">Semua Shift</h1>
        <form method="GET" class="flex gap-2 text-sm">
            <select name="status" class="rounded-lg border border-line px-3 py-2" onchange="this.form.submit()">
                <option value="">Semua status</option>
                <option value="OPEN" @selected(request('status') === 'OPEN')>Aktif</option>
                <option value="CLOSED" @selected(request('status') === 'CLOSED')>Ditutup</option>
            </select>
            <select name="cashier_id" class="rounded-lg border border-line px-3 py-2" onchange="this.form.submit()">
                <option value="">Semua kasir</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected((string) request('cashier_id') === (string) $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="bg-white border border-line rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-muted text-xs border-b border-line">
                    <th class="px-5 py-2.5 font-medium">Nomor</th>
                    <th class="px-5 py-2.5 font-medium">Kasir</th>
                    <th class="px-5 py-2.5 font-medium">Dibuka</th>
                    <th class="px-5 py-2.5 font-medium">Ditutup</th>
                    <th class="px-5 py-2.5 font-medium">Status</th>
                    <th class="px-5 py-2.5 font-medium text-right">Penjualan</th>
                    <th class="px-5 py-2.5 font-medium text-right">Selisih kas</th>
                    <th class="px-5 py-2.5 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shifts as $shift)
                    <tr class="border-b border-line/50 last:border-0 hover:bg-background/50">
                        <td class="px-5 py-2.5 font-medium">{{ $shift->shift_number }}</td>
                        <td class="px-5 py-2.5">{{ $shift->user->name }}</td>
                        <td class="px-5 py-2.5 text-muted">{{ $shift->opened_at->format('d M Y · H:i') }}</td>
                        <td class="px-5 py-2.5 text-muted">{{ $shift->closed_at?->format('d M Y · H:i') ?? '—' }}</td>
                        <td class="px-5 py-2.5">
                            @if($shift->status === \App\Models\Shift::STATUS_OPEN)
                                <span class="px-2 py-0.5 rounded-md text-xs bg-success/10 text-success font-medium">Aktif</span>
                            @else
                                <span class="px-2 py-0.5 rounded-md text-xs bg-background text-muted font-medium">Ditutup</span>
                            @endif
                        </td>
                        <td class="px-5 py-2.5 text-right font-medium">Rp{{ number_format($shift->transactions()->where('status', 'COMPLETED')->sum('grand_total'), 0, ',', '.') }}</td>
                        <td class="px-5 py-2.5 text-right {{ ($shift->cash_difference ?? 0) < 0 ? 'text-error' : 'text-muted' }}">
                            {{ $shift->status === \App\Models\Shift::STATUS_CLOSED ? 'Rp'.number_format($shift->cash_difference, 0, ',', '.') : '—' }}
                        </td>
                        <td class="px-5 py-2.5 text-right">
                            <a href="{{ route('shift.report', ['shift' => $shift->id]) }}" class="text-primary hover:underline text-xs">Laporan</a>
                            @if($shift->status === \App\Models\Shift::STATUS_OPEN)
                                <form method="POST" action="{{ route('shifts.close.quick', $shift) }}" class="inline"
                                      onsubmit="return confirm('Tutup {{ $shift->shift_number }} (kasir {{ $shift->user->name }})? Kas aktual dianggap sesuai kas harapan.')">
                                    @csrf
                                    <button class="ml-3 px-3 py-1.5 rounded-lg bg-primary text-surface text-xs font-medium hover:bg-black transition">Tutup</button>
                                </form>
                                <a href="{{ route('shifts.close', $shift) }}" class="ml-2 text-primary hover:underline text-xs">Hitung kas</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-12 text-center text-sm text-muted">Belum ada shift.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $shifts->links() }}
</div>
@endsection
