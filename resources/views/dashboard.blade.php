@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Dashboard</h1>
        <span class="text-sm text-muted">{{ now()->translatedFormat('d F Y') }}</span>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white border border-line rounded-xl p-4">
            <div class="text-xs text-muted">Total Penjualan</div>
            <div class="text-xl font-bold mt-1">Rp{{ number_format($totalSales, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white border border-line rounded-xl p-4">
            <div class="text-xs text-muted">Transaksi</div>
            <div class="text-xl font-bold mt-1">{{ $totalTransactions }}</div>
        </div>
        <div class="bg-white border border-line rounded-xl p-4">
            <div class="text-xs text-muted">Rata-rata</div>
            <div class="text-xl font-bold mt-1">Rp{{ number_format($avgTransaction, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white border border-line rounded-xl p-4">
            <div class="text-xs text-muted">Shift</div>
            <div class="text-sm font-bold mt-1">{{ $shift ? '<span class="text-success">● Aktif</span>' : '<span class="text-muted">—</span>' }}</div>
            <div class="text-xs text-muted mt-0.5">{{ $shift ? $shift->shift_number : '—' }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white border border-line rounded-xl p-5">
            <h2 class="font-semibold mb-4">Penjualan 7 Hari Terakhir</h2>
            <div class="h-56"><canvas id="dailyChart"></canvas></div>
        </div>
        <div class="bg-white border border-line rounded-xl p-5">
            <h2 class="font-semibold mb-4">Top Produk · Bulan Ini</h2>
            @if($topProducts->isNotEmpty())
                <div class="h-56"><canvas id="topProductsChart"></canvas></div>
            @else
                <div class="text-sm text-muted text-center py-10">Belum ada penjualan bulan ini.</div>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white border border-line rounded-xl p-5">
            <h2 class="font-semibold mb-4">Metode Pembayaran</h2>
            <div class="space-y-3">
                @foreach(['CASH' => 'Tunai', 'QRIS' => 'QRIS', 'DEBIT' => 'Debit', 'CREDIT' => 'Kartu Kredit'] as $code => $label)
                    @php($total = $byMethod[$code] ?? 0)
                    <div class="flex items-center gap-3">
                        <span class="w-24 text-sm text-muted">{{ $label }}</span>
                        <div class="flex-1 bg-background rounded-full h-2.5 overflow-hidden">
                            <div class="h-full bg-primary rounded-full transition" style="width: {{ $totalSales ? ($total / $totalSales * 100) : 0 }}%"></div>
                        </div>
                        <span class="text-sm font-medium w-24 text-right">{{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white border border-line rounded-xl p-5">
            <h2 class="font-semibold mb-4">Shift</h2>
            @if($shift)
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-muted">Nomor</span><span class="font-medium">{{ $shift->shift_number }}</span></div>
                    <div class="flex justify-between"><span class="text-muted">Kasir</span><span>{{ $shift->user->name }}</span></div>
                    <div class="flex justify-between"><span class="text-muted">Dibuka</span><span>{{ $shift->opened_at->format('H:i') }}</span></div>
                    <div class="flex justify-between"><span class="text-muted">Kas awal</span><span>{{ number_format($shift->opening_cash, 0, ',', '.') }}</span></div>
                </div>
            @else
                <div class="text-sm text-muted text-center py-4">Tidak ada shift aktif.</div>
            @endif
            @if(session('user_role') !== 'CASHIER')
                <a href="{{ route('shift.current') }}" class="mt-3 block text-center text-sm text-primary font-medium">Lihat semua shift →</a>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dailyEl = document.getElementById('dailyChart');
    if (dailyEl) {
        new Chart(dailyEl, {
            type: 'line',
            data: {
                labels: @json($labels),
                datasets: [{ label: 'Rupiah', data: @json($values), borderColor: '#5F7D68', backgroundColor: 'rgba(95,125,104,.15)', fill: true, tension: .3 }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { ticks: { callback: v => 'Rp' + new Intl.NumberFormat('id-ID').format(v) } } },
            },
        });
    }
    const topEl = document.getElementById('topProductsChart');
    if (topEl) {
        new Chart(topEl, {
            type: 'bar',
            data: {
                labels: @json($topProducts->pluck('name')),
                datasets: [{ label: 'Terjual', data: @json($topProducts->pluck('qty')), backgroundColor: ['#5F7D68', '#7D8F69', '#A3B18A', '#C5C0A6', '#D6D3C8'] }],
            },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
            },
        });
    }
});
</script>
@endsection