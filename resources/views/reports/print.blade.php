<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} — {{ $dateFrom }} s/d {{ $dateTo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #1F1F1F;
            max-width: 210mm;
            margin: 0 auto;
            padding: 12mm;
        }
        .center { text-align: center; }
        .title { font-size: 18px; font-weight: 700; }
        .muted { color: #6B6B6B; font-size: 12px; }
        .dashed { border-top: 1px dashed #999; margin: 6mm 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 4mm; }
        th { text-align: left; border-bottom: 2px solid #333; padding: 2mm 1mm; font-size: 11px; text-transform: uppercase; }
        td { padding: 2mm 1mm; border-bottom: 1px solid #E5E5E5; }
        tr:last-child td { border-bottom: none; }
        .r { text-align: right; }
        .total-row { font-weight: 700; }
        .actions { margin: 8mm 0; text-align: center; }
        .actions button, .actions a {
            display: inline-block; padding: 3mm 8mm; margin: 1mm;
            border: 1px solid #999; border-radius: 2mm; background: #fff;
            text-decoration: none; color: #1F1F1F; font-size: 13px; cursor: pointer;
        }
        @media print {
            .actions { display: none; }
            body { padding: 8mm 0; }
        }
    </style>
</head>
<body>
    <div class="center">
        <div class="title">{{ strtoupper(config('app.name')) }}</div>
        <div class="muted">{{ $title }}</div>
        <div class="muted">{{ \Carbon\Carbon::parse($dateFrom)->translatedFormat('d F Y') }} — {{ \Carbon\Carbon::parse($dateTo)->translatedFormat('d F Y') }}</div>
    </div>

    <div class="dashed"></div>

    @if($rows->isEmpty())
        <div class="center muted">Tidak ada data pada rentang tanggal ini.</div>
    @else
        <table>
            <thead>
                <tr>
                    @foreach($columns as $col)
                        <th>{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach($cell($row) as $i => $value)
                            <td class="{{ $i > 0 ? 'r' : '' }}">{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="dashed"></div>
    <div class="row">Total pendapatan: <strong>Rp {{ number_format($totalRevenue, 0, ',', '.') }}</strong></div>
    <div class="center muted" style="margin-top:6mm">Dicetak {{ now()->translatedFormat('d F Y H:i') }}</div>

    <div class="actions">
        <button onclick="window.print()">🖨 Cetak / Simpan PDF</button>
        <a href="{{ url()->previous() }}">← Kembali</a>
    </div>

    <script>
        window.onload = function () {
            if (window.matchMedia('print').matches) return;
            window.print();
        };
    </script>
</body>
</html>