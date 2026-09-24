<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Shift {{ $shift->shift_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #1F1F1F;
            width: 72mm;
            margin: 0 auto;
            padding: 4mm;
        }
        .center { text-align: center; }
        .title { font-size: 14px; font-weight: bold; margin-top: 2mm; }
        .muted { color: #6B6B6B; }
        .dashed { border-top: 1px dashed #999; margin: 3mm 0; }
        .row { display: flex; justify-content: space-between; margin: 1mm 0; }
        .row.total { font-weight: bold; border-top: 1px solid #333; margin-top: 2mm; padding-top: 2mm; }
        table { width: 100%; border-collapse: collapse; margin-top: 2mm; }
        th, td { text-align: left; padding: 0.8mm 0; }
        th { border-bottom: 1px solid #999; font-size: 11px; }
        td { font-size: 11px; }
        .r { text-align: right; }
        .actions { margin: 6mm 0; text-align: center; }
        .actions button, .actions a {
            display: inline-block; padding: 3mm 6mm; margin: 1mm;
            border: 1px solid #999; border-radius: 2mm; background: #fff;
            text-decoration: none; color: #1F1F1F; font-family: inherit; font-size: 12px; cursor: pointer;
        }
        @media print {
            .actions { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="center">
        <div class="title">{{ strtoupper(config('app.name')) }}</div>
        <div class="muted">Laporan Shift {{ $shift->shift_number }}</div>
        <div class="muted">{{ $shift->opened_at->format('d M Y H:i') }} — {{ $shift->closed_at?->format('d M Y H:i') ?? 'berlangsung' }}</div>
        <div class="muted">Kasir: {{ $shift->user->name }}</div>
    </div>

    <div class="dashed"></div>

    <div class="row"><span>Total transaksi</span><span>{{ $summary['total_transactions'] }}</span></div>
    <div class="row"><span>Penjualan kotor</span><span>Rp {{ number_format($summary['gross_sales'], 0, ',', '.') }}</span></div>
    <div class="row"><span>Diskon</span><span>Rp {{ number_format($summary['discount'], 0, ',', '.') }}</span></div>
    <div class="row"><span>Pajak</span><span>Rp {{ number_format($summary['tax'], 0, ',', '.') }}</span></div>
    <div class="row total"><span>Penjualan bersih</span><span>Rp {{ number_format($summary['net_sales'], 0, ',', '.') }}</span></div>

    <div class="dashed"></div>

    <div class="row"><span>Tunai</span><span>Rp {{ number_format($summary['cash_sales'], 0, ',', '.') }}</span></div>
    <div class="row"><span>QRIS</span><span>Rp {{ number_format($summary['qris_sales'], 0, ',', '.') }}</span></div>
    <div class="row"><span>Debit</span><span>Rp {{ number_format($summary['debit_sales'], 0, ',', '.') }}</span></div>
    <div class="row"><span>Kartu kredit</span><span>Rp {{ number_format($summary['credit_sales'], 0, ',', '.') }}</span></div>

    <div class="dashed"></div>

    <div class="row"><span>Kas awal</span><span>Rp {{ number_format($shift->opening_cash, 0, ',', '.') }}</span></div>
    <div class="row"><span>Kas masuk</span><span>Rp {{ number_format($cashIn, 0, ',', '.') }}</span></div>
    <div class="row"><span>Kas keluar</span><span>Rp {{ number_format($cashOut, 0, ',', '.') }}</span></div>
    <div class="row"><span>Kas diharapkan</span><span>Rp {{ number_format($shift->expected_cash, 0, ',', '.') }}</span></div>
    <div class="row"><span>Kas aktual</span><span>Rp {{ number_format($shift->actual_cash, 0, ',', '.') }}</span></div>
    <div class="row total">
        <span>Selisih {{ $shift->cash_difference >= 0 ? '+' : '−' }}</span>
        <span>Rp {{ number_format(abs($shift->cash_difference), 0, ',', '.') }}</span>
    </div>

    @if($transactions->isNotEmpty())
        <div class="dashed"></div>
        <table>
            <thead>
                <tr>
                    <th>Jam</th>
                    <th>No</th>
                    <th class="r">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $tx)
                    <tr>
                        <td>{{ $tx->created_at->format('H:i') }}</td>
                        <td>{{ $tx->transaction_number }}</td>
                        <td class="r">{{ number_format($tx->grand_total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="dashed"></div>
    <div class="center muted">{{ $shift->closed_at ? 'Dicetak ' . now()->format('d M Y H:i') : 'Shift masih berlangsung' }}</div>

    <div class="actions">
        <button onclick="window.print()">🖨 Cetak</button>
        <a href="{{ route('shift.report', ['shift' => $shift->id]) }}">← Kembali</a>
    </div>

    <script>
        window.onload = function () {
            if (window.matchMedia('print').matches) return;
            window.print();
        };
    </script>
</body>
</html>