<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $userId = session('user_id');
        $role = session('user_role');

        $today = now()->startOfDay();

        $query = Transaction::where('transactions.status', 'COMPLETED')
            ->whereDate('transactions.created_at', '>=', $today);

        if ($role === 'CASHIER') {
            $query->where('transactions.user_id', $userId);
        }

        $totalSales = (clone $query)->sum('grand_total');
        $totalTransactions = (clone $query)->count();
        $avgTransaction = $totalTransactions > 0 ? floor($totalSales / $totalTransactions) : 0;

        $byMethod = (clone $query)
            ->join('payment_methods', 'payment_methods.id', '=', 'transactions.payment_method_id')
            ->selectRaw('payment_methods.code, SUM(transactions.grand_total) as total')
            ->groupBy('payment_methods.code')
            ->pluck('total', 'code');

        $shift = Shift::currentForUser($userId);

        // 7 hari terakhir
        $daily = (clone $query)
            ->whereDate('transactions.created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('date(transactions.created_at) as day, SUM(transactions.grand_total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $labels[] = $d->translatedFormat('d M');
            $values[] = (int) ($daily[$d->format('Y-m-d')] ?? 0);
        }

        // top produk bulan ini
        $topProductsQuery = TransactionItem::join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', 'COMPLETED')
            ->whereDate('transactions.created_at', '>=', now()->startOfMonth());

        if ($role === 'CASHIER') {
            $topProductsQuery->where('transactions.user_id', $userId);
        }

        $topProducts = $topProductsQuery
            ->selectRaw('transaction_items.product_name, SUM(transaction_items.quantity) as qty')
            ->groupBy('transaction_items.product_id', 'transaction_items.product_name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['name' => $r->product_name, 'qty' => (int) $r->qty]);

        return view('dashboard', compact(
            'totalSales',
            'totalTransactions',
            'avgTransaction',
            'byMethod',
            'shift',
            'labels',
            'values',
            'topProducts'
        ));
    }
}