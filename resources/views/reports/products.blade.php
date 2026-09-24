@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Laporan Produk</h1>
        <div class="flex items-center gap-2">
            <span class="text-sm text-muted">Rp{{ number_format($totalRevenue, 0, ',', '.') }} total</span>
            <a href="{{ route('reports.export', ['type' => 'products', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
               class="px-4 py-2 rounded-lg border border-line text-sm font-medium hover:bg-background transition">⬇ Excel</a>
            <a href="{{ route('reports.print', ['type' => 'products', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
               target="_blank" class="px-4 py-2 rounded-lg border border-line text-sm font-medium hover:bg-background transition">🖨 PDF</a>
        </div>
    </div>

    <form method="GET" class="bg-white border border-line rounded-xl p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-muted mb-1">Dari</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs text-muted mb-1">Sampai</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <button class="px-4 py-2 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">Tampilkan</button>
    </form>

    <div class="bg-white border border-line rounded-xl overflow-hidden">
        @if($rows->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-muted">Tidak ada data penjualan pada rentang ini.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted text-xs border-b border-line">
                        <th class="px-5 py-2.5 font-medium">Produk</th>
                        <th class="px-5 py-2.5 font-medium text-right">Terjual</th>
                        <th class="px-5 py-2.5 font-medium text-right">Pendapatan</th>
                        <th class="px-5 py-2.5 font-medium text-right">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr class="border-b border-line/50 last:border-0 hover:bg-background/50">
                            <td class="px-5 py-2.5 font-medium">{{ $row->product_name }}</td>
                            <td class="px-5 py-2.5 text-right">{{ $row->qty }}</td>
                            <td class="px-5 py-2.5 text-right">Rp{{ number_format($row->revenue, 0, ',', '.') }}</td>
                            <td class="px-5 py-2.5 text-right text-muted">{{ $totalRevenue ? round($row->revenue / $totalRevenue * 100, 1) : 0 }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection