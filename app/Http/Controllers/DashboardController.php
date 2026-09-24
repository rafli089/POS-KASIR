<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Transaction;
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

        return view('dashboard', compact(
            'totalSales',
            'totalTransactions',
            'avgTransaction',
            'byMethod',
            'shift'
        ));
    }
}