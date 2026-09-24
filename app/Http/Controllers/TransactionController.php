<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\Shift;
use App\Models\ShiftActivity;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $userId = session('user_id');
        $role = session('user_role');

        $query = Transaction::with(['paymentMethod', 'shift', 'user'])
            ->when($request->input('q'), function ($q, $keyword) {
                $q->where('transaction_number', 'like', "%{$keyword}%");
            })
            ->when($request->input('date_from'), function ($q, $d) {
                $q->whereDate('created_at', '>=', $d);
            })
            ->when($request->input('date_to'), function ($q, $d) {
                $q->whereDate('created_at', '<=', $d);
            })
            ->when($request->input('payment_method'), function ($q, $id) {
                $q->where('payment_method_id', $id);
            })
            ->when($request->input('status'), function ($q, $status) {
                $q->where('status', $status);
            });

        if ($role === 'CASHIER') {
            $query->where('user_id', $userId);
        }

        if ($request->input('cashier_id')) {
            $query->where('user_id', $request->input('cashier_id'));
        }

        $transactions = $query->latest()->paginate(20)->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    public function show(Transaction $transaction): View
    {
        $transaction->load(['items.product', 'paymentMethod', 'shift', 'user', 'receipt']);

        $shiftId = session('shift_id');
        $canVoid = $transaction->status === Transaction::STATUS_COMPLETED
            && $shiftId
            && $transaction->shift_id === $shiftId;

        return view('transactions.show', compact('transaction', 'canVoid'));
    }

    public function void(Request $request, Transaction $transaction): \Illuminate\Http\RedirectResponse
    {
        if ($transaction->status !== Transaction::STATUS_COMPLETED) {
            return back()->withErrors(['void' => 'Transaksi ini tidak dapat dibatalkan.']);
        }

        $shiftId = session('shift_id');

        if ($transaction->shift_id !== $shiftId) {
            return back()->withErrors(['void' => 'Transaksi hanya dapat dibatalkan dalam shift yang sama.']);
        }

        $userId = session('user_id');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($transaction, $userId, $validated) {
            $transaction->update(['status' => Transaction::STATUS_VOID]);

            ShiftActivity::create([
                'shift_id' => $transaction->shift_id,
                'user_id' => $userId,
                'activity_type' => ShiftActivity::TYPE_TRANSACTION_VOID,
                'reference_type' => 'transaction',
                'reference_id' => $transaction->id,
                'description' => "Transaction #{$transaction->transaction_number} dibatalkan — {$validated['reason']}",
            ]);
        });

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Transaksi dibatalkan.');
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'tax' => ['nullable', 'integer', 'min:0'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payment_amount' => ['required', 'integer', 'min:0'],
        ]);

        $userId = session('user_id');
        $shiftId = session('shift_id');

        if (! $shiftId) {
            return response()->json(['message' => 'Shift belum dibuka.'], 422);
        }

        $shift = Shift::find($shiftId);

        if (! $shift || $shift->status !== Shift::STATUS_OPEN) {
            return response()->json(['message' => 'Shift tidak aktif.'], 422);
        }

        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $shift, $userId, $request) {
            $items = \App\Models\Product::whereIn('id', collect($validated['items'])->pluck('product_id'))->get()->keyBy('id');

            $subtotal = 0;
            foreach ($validated['items'] as $row) {
                $product = $items[$row['product_id']];
                $subtotal += $product->price * $row['quantity'];
            }

            $discount = $validated['discount'] ?? 0;
            $tax = $validated['tax'] ?? 0;
            $grandTotal = max(0, $subtotal - $discount + $tax);

            if ($validated['payment_amount'] < $grandTotal) {
                throw new \DomainException('Uang bayar lebih kecil dari total.');
            }

            $change = $validated['payment_amount'] - $grandTotal;

            $transaction = Transaction::create([
                'shift_id' => $shift->id,
                'user_id' => $userId,
                'transaction_number' => Transaction::generateTransactionNumber(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'grand_total' => $grandTotal,
                'payment_method_id' => $validated['payment_method_id'],
                'payment_amount' => $validated['payment_amount'],
                'change_amount' => $change,
                'status' => Transaction::STATUS_COMPLETED,
            ]);

            foreach ($validated['items'] as $row) {
                $product = $items[$row['product_id']];
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $row['quantity'],
                    'unit_price' => $product->price,
                    'discount' => 0,
                    'subtotal' => $product->price * $row['quantity'],
                ]);
            }

            Receipt::create([
                'transaction_id' => $transaction->id,
                'receipt_number' => $transaction->transaction_number,
                'printed_at' => now(),
                'print_count' => 1,
                'last_printed_by' => $userId,
            ]);

            ShiftActivity::create([
                'shift_id' => $shift->id,
                'user_id' => $userId,
                'activity_type' => ShiftActivity::TYPE_TRANSACTION_CREATED,
                'reference_type' => 'transaction',
                'reference_id' => $transaction->id,
                'description' => "Transaction #{$transaction->transaction_number} — Total Rp".number_format($grandTotal, 0, ',', '.'),
            ]);

            ShiftActivity::create([
                'shift_id' => $shift->id,
                'user_id' => $userId,
                'activity_type' => ShiftActivity::TYPE_PAYMENT_COMPLETED,
                'reference_type' => 'transaction',
                'reference_id' => $transaction->id,
                'description' => 'Payment completed — '.$transaction->paymentMethod->name,
            ]);

            return $transaction;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'transaction' => $result->load(['paymentMethod', 'items']),
            ], 201);
        }

        return redirect()->route('transactions.show', $result->id)
            ->with('success', 'Transaksi berhasil dibuat.');
    }
}