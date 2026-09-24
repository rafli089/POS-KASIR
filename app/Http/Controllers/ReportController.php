<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function products(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $rows = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', 'COMPLETED')
            ->whereDate('transactions.created_at', '>=', $dateFrom)
            ->whereDate('transactions.created_at', '<=', $dateTo)
            ->selectRaw('transaction_items.product_name, SUM(transaction_items.quantity) as qty, SUM(transaction_items.subtotal) as revenue')
            ->groupBy('transaction_items.product_name')
            ->orderByDesc('revenue')
            ->get();

        $totalRevenue = $rows->sum('revenue');

        return view('reports.products', compact('rows', 'totalRevenue', 'dateFrom', 'dateTo'));
    }

    public function daily(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $days = Transaction::query()
            ->where('status', 'COMPLETED')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as tx_count, SUM(grand_total) as revenue')
            ->groupBy('day')
            ->orderByDesc('day')
            ->get();

        $totalRevenue = $days->sum('revenue');

        return view('reports.daily', compact('days', 'totalRevenue', 'dateFrom', 'dateTo'));
    }
}