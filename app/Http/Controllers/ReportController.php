<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    private function range(Request $request): array
    {
        return [
            $request->input('date_from', now()->startOfMonth()->toDateString()),
            $request->input('date_to', now()->toDateString()),
        ];
    }

    public function products(Request $request): View
    {
        [$dateFrom, $dateTo] = $this->range($request);

        $rows = $this->productRows($dateFrom, $dateTo);

        $totalRevenue = $rows->sum('revenue');

        return view('reports.products', compact('rows', 'totalRevenue', 'dateFrom', 'dateTo'));
    }

    public function daily(Request $request): View
    {
        [$dateFrom, $dateTo] = $this->range($request);

        $days = $this->dailyRows($dateFrom, $dateTo);

        $totalRevenue = $days->sum('revenue');

        return view('reports.daily', compact('days', 'totalRevenue', 'dateFrom', 'dateTo'));
    }

    private function productRows(string $dateFrom, string $dateTo)
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', 'COMPLETED')
            ->whereDate('transactions.created_at', '>=', $dateFrom)
            ->whereDate('transactions.created_at', '<=', $dateTo)
            ->selectRaw('transaction_items.product_name, SUM(transaction_items.quantity) as qty, SUM(transaction_items.subtotal) as revenue')
            ->groupBy('transaction_items.product_name')
            ->orderByDesc('revenue')
            ->get();
    }

    private function dailyRows(string $dateFrom, string $dateTo)
    {
        return Transaction::query()
            ->where('status', 'COMPLETED')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as tx_count, SUM(grand_total) as revenue')
            ->groupBy('day')
            ->orderByDesc('day')
            ->get();
    }

    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        [$dateFrom, $dateTo] = $this->range($request);
        $type = $request->input('type', 'daily');

        $filename = "report-{$type}-{$dateFrom}-{$dateTo}.csv";

        return response()->streamDownload(function () use ($type, $dateFrom, $dateTo) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM agar terbuka benar di Excel

            $stream = fopen('php://output', 'w');

            if ($type === 'products') {
                fputcsv($stream, ['Produk', 'Terjual', 'Pendapatan']);
                foreach ($this->productRows($dateFrom, $dateTo) as $row) {
                    fputcsv($stream, [$row->product_name, $row->qty, $row->revenue]);
                }
            } else {
                fputcsv($stream, ['Tanggal', 'Transaksi', 'Pendapatan']);
                foreach ($this->dailyRows($dateFrom, $dateTo) as $row) {
                    fputcsv($stream, [$row->day, $row->tx_count, $row->revenue]);
                }
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function print(Request $request): View
    {
        [$dateFrom, $dateTo] = $this->range($request);
        $type = $request->input('type', 'daily');

        if ($type === 'products') {
            $rows = $this->productRows($dateFrom, $dateTo);
            $title = 'Laporan Penjualan Produk';
            $columns = ['Produk', 'Terjual', 'Pendapatan'];
            $cell = fn ($row) => [$row->product_name, $row->qty, number_format($row->revenue, 0, ',', '.')];
        } else {
            $rows = $this->dailyRows($dateFrom, $dateTo);
            $title = 'Laporan Harian';
            $columns = ['Tanggal', 'Transaksi', 'Pendapatan'];
            $cell = fn ($row) => [\Carbon\Carbon::parse($row->day)->format('d M Y'), $row->tx_count, number_format($row->revenue, 0, ',', '.')];
        }

        $totalRevenue = $rows->sum('revenue');

        return view('reports.print', compact('rows', 'columns', 'cell', 'title', 'totalRevenue', 'dateFrom', 'dateTo'));
    }
}