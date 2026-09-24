<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $userId = session('user_id');
        $existing = Shift::currentForUser($userId);

        if ($existing) {
            return redirect()->route('shift.current');
        }

        return view('shift.open', [
            'now' => now(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = session('user_id');

        if (Shift::currentForUser($userId)) {
            return back()->withErrors(['opening_cash' => 'Shift sudah aktif.']);
        }

        $validated = $request->validate([
            'opening_cash' => ['required', 'integer', 'min:0'],
        ]);

        $shift = DB::transaction(function () use ($userId, $validated) {
            $shift = Shift::create([
                'user_id' => $userId,
                'shift_number' => Shift::generateShiftNumber(),
                'opening_cash' => $validated['opening_cash'],
                'status' => Shift::STATUS_OPEN,
            ]);

            ShiftActivity::create([
                'shift_id' => $shift->id,
                'user_id' => $userId,
                'activity_type' => ShiftActivity::TYPE_OPEN_SHIFT,
                'description' => 'Open Shift — Opening Cash Rp'.number_format($validated['opening_cash'], 0, ',', '.'),
            ]);

            return $shift;
        });

        session()->put('shift_id', $shift->id);

        return redirect()->route('pos.index')->with('success', 'Shift berhasil dibuka.');
    }

    public function current(Request $request): View|RedirectResponse
    {
        $userId = session('user_id');
        $shift = Shift::currentForUser($userId);

        if (! $shift) {
            return redirect()->route('shift.open');
        }

        return view('shift.current', [
            'shift' => $shift,
            'activities' => ShiftActivity::where('shift_id', $shift->id)->latest('created_at')->get(),
            'transactions' => $shift->transactions()->with('paymentMethod')->latest()->get(),
            'cashIn' => ShiftActivity::where('shift_id', $shift->id)->where('activity_type', ShiftActivity::TYPE_CASH_IN)->sum('reference_amount'),
            'cashOut' => ShiftActivity::where('shift_id', $shift->id)->where('activity_type', ShiftActivity::TYPE_CASH_OUT)->sum('reference_amount'),
            'summary' => $this->buildSummary($shift),
        ]);
    }

    public function activity(Request $request): View|RedirectResponse
    {
        $userId = session('user_id');
        $shift = Shift::currentForUser($userId);

        if (! $shift) {
            return redirect()->route('shift.open');
        }

        $activities = ShiftActivity::where('shift_id', $shift->id)
            ->latest('created_at')
            ->get();

        return view('shift.activity', compact('shift', 'activities'));
    }

    public function report(Request $request): View|RedirectResponse
    {
        $shift = $this->findShiftForReport($request);

        if (! $shift) {
            return redirect()->route('shift.open');
        }

        return view('shift.report', [
            'shift' => $shift,
            'summary' => $this->buildSummary($shift),
        ]);
    }

    public function printReport(Request $request): View|RedirectResponse
    {
        $shift = $this->findShiftForReport($request);

        if (! $shift) {
            return redirect()->route('shift.open');
        }

        return view('shift.print', [
            'shift' => $shift,
            'summary' => $this->buildSummary($shift),
            'transactions' => $shift->transactions()->with('paymentMethod')->latest()->get(),
            'cashIn' => ShiftActivity::where('shift_id', $shift->id)->where('activity_type', ShiftActivity::TYPE_CASH_IN)->sum('reference_amount'),
            'cashOut' => ShiftActivity::where('shift_id', $shift->id)->where('activity_type', ShiftActivity::TYPE_CASH_OUT)->sum('reference_amount'),
        ]);
    }

    public function close(Request $request): View|RedirectResponse
    {
        $userId = session('user_id');
        $shift = Shift::currentForUser($userId);

        if (! $shift) {
            return redirect()->route('shift.open');
        }

        if ($shift->transactions()->where('status', Shift::STATUS_OPEN)->exists()) {
            return back()->withErrors(['close' => 'Masih ada transaksi pending.']);
        }

        return view('shift.close', [
            'shift' => $shift,
            'summary' => $this->buildSummary($shift),
        ]);
    }

    public function closeShift(Request $request): RedirectResponse
    {
        $userId = session('user_id');
        $shift = Shift::currentForUser($userId);

        if (! $shift) {
            return redirect()->route('shift.open')->withErrors(['close' => 'Tidak ada shift aktif.']);
        }

        $validated = $request->validate([
            'actual_cash' => ['required', 'integer', 'min:0'],
        ]);

        $summary = $this->buildSummary($shift);
        $expected = $summary['expected_cash'];
        $actual = $validated['actual_cash'];
        $difference = $actual - $expected;

        DB::transaction(function () use ($shift, $userId, $expected, $actual, $difference) {
            $shift->update([
                'closed_at' => now(),
                'expected_cash' => $expected,
                'actual_cash' => $actual,
                'cash_difference' => $difference,
                'status' => Shift::STATUS_CLOSED,
            ]);

            ShiftActivity::create([
                'shift_id' => $shift->id,
                'user_id' => $userId,
                'activity_type' => ShiftActivity::TYPE_CLOSE_SHIFT,
                'description' => 'Close Shift — Expected Rp'.number_format($expected, 0, ',', '.')
                    .', Actual Rp'.number_format($actual, 0, ',', '.')
                    .', Selisih Rp'.number_format($difference, 0, ',', '.'),
            ]);
        });

        session()->forget('shift_id');

        return redirect()->route('shift.report', ['shift' => $shift->id])
            ->with('success', 'Shift berhasil ditutup.');
    }

    public function cashIn(Request $request): RedirectResponse
    {
        return $this->recordCashMovement($request, ShiftActivity::TYPE_CASH_IN, 'Kas Masuk');
    }

    public function cashOut(Request $request): RedirectResponse
    {
        return $this->recordCashMovement($request, ShiftActivity::TYPE_CASH_OUT, 'Kas Keluar');
    }

    private function recordCashMovement(Request $request, string $type, string $label): RedirectResponse
    {
        $shift = Shift::currentForUser(session('user_id'));

        if (! $shift) {
            return redirect()->route('shift.open');
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        ShiftActivity::create([
            'shift_id' => $shift->id,
            'user_id' => session('user_id'),
            'activity_type' => $type,
            'reference_amount' => $validated['amount'],
            'description' => "{$label} Rp".number_format($validated['amount'], 0, ',', '.'),
        ]);

        return back()->with('success', "{$label} tercatat.");
    }

    private function findShiftForReport(Request $request): ?Shift
    {
        if ($id = $request->input('shift')) {
            $shift = Shift::find($id);
            $user = $request->session()->get('user_id');

            if ($shift && ($shift->user_id == $user || session('user_role') !== 'CASHIER')) {
                return $shift;
            }

            return null;
        }

        return Shift::currentForUser(session('user_id'));
    }

    private function buildSummary(Shift $shift): array
    {
        $transactions = $shift->transactions()->where('transactions.status', 'COMPLETED');

        $totalTransactions = (clone $transactions)->count();
        $grossSales = (clone $transactions)->sum('transactions.subtotal');
        $discount = (clone $transactions)->sum('transactions.discount');
        $tax = (clone $transactions)->sum('transactions.tax');
        $netSales = (clone $transactions)->sum('transactions.grand_total');

        $byMethod = (clone $transactions)
            ->join('payment_methods', 'payment_methods.id', '=', 'transactions.payment_method_id')
            ->selectRaw('payment_methods.code, SUM(transactions.grand_total) as total')
            ->groupBy('payment_methods.code')
            ->pluck('total', 'code');

        $cashSales = (int) ($byMethod['CASH'] ?? 0);
        $cashIn = (int) ShiftActivity::where('shift_id', $shift->id)->where('activity_type', ShiftActivity::TYPE_CASH_IN)->sum('reference_amount');
        $cashOut = (int) ShiftActivity::where('shift_id', $shift->id)->where('activity_type', ShiftActivity::TYPE_CASH_OUT)->sum('reference_amount');
        $expectedCash = $shift->opening_cash + $cashSales + $cashIn - $cashOut;

        return [
            'total_transactions' => $totalTransactions,
            'gross_sales' => $grossSales,
            'discount' => $discount,
            'tax' => $tax,
            'net_sales' => $netSales,
            'cash_sales' => $cashSales,
            'qris_sales' => (int) ($byMethod['QRIS'] ?? 0),
            'debit_sales' => (int) ($byMethod['DEBIT'] ?? 0),
            'credit_sales' => (int) ($byMethod['CREDIT'] ?? 0),
            'expected_cash' => $expectedCash,
        ];
    }
}