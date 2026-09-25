<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\ShiftActivity;
use App\Models\Transaction;
use App\Models\TransactionAuthorization;
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
        $sameShift = $shiftId && $transaction->shift_id === $shiftId;

        $canVoid = $transaction->status === Transaction::STATUS_COMPLETED && $sameShift;
        $canRefund = $transaction->status === Transaction::STATUS_COMPLETED && $sameShift;

        $voidAuth = $transaction->authorizations()->where('action', TransactionAuthorization::ACTION_VOID)->latest()->first();
        $refundAuth = $transaction->authorizations()->where('action', TransactionAuthorization::ACTION_REFUND)->latest()->first();
        $pendingAuth = $transaction->authorizations()->where('status', TransactionAuthorization::STATUS_PENDING)->latest()->first();
        $isApprover = in_array(session('user_role'), ['ADMIN', 'MANAGER']);

        return view('transactions.show', compact(
            'transaction', 'canVoid', 'canRefund', 'voidAuth', 'refundAuth', 'pendingAuth', 'isApprover'
        ));
    }

    public function reprint(Request $request, Transaction $transaction): \Illuminate\Http\RedirectResponse
    {
        $receipt = $transaction->receipt;

        if ($receipt) {
            $receipt->increment('print_count');
            $receipt->update([
                'printed_at' => now(),
                'last_printed_by' => session('user_id'),
            ]);
        }

        return redirect()->route('transactions.show', $transaction)->withQuery(['print' => 1]);
    }

    public function refund(Request $request, Transaction $transaction): \Illuminate\Http\RedirectResponse
    {
        if ($transaction->status !== Transaction::STATUS_COMPLETED) {
            return back()->withErrors(['refund' => 'Transaksi ini sudah direfund atau dibatalkan.']);
        }

        if ($transaction->shift_id !== session('shift_id')) {
            return back()->withErrors(['refund' => 'Refund hanya dapat dilakukan dalam shift yang sama.']);
        }

        $auth = $this->consumeAuthorization($transaction, TransactionAuthorization::ACTION_REFUND, $request->input('otp'));

        if (! $auth) {
            return redirect()->route('transactions.show', $transaction)
                ->withErrors(['refund' => 'Kode OTP tidak valid. Mintakan approval dari MANAGER/ADMIN terlebih dahulu.']);
        }

        $amount = $auth->amount;

        \Illuminate\Support\Facades\DB::transaction(function () use ($transaction, $auth, $amount) {
            $transaction->update(['status' => Transaction::STATUS_REFUNDED]);

            ShiftActivity::create([
                'shift_id' => $transaction->shift_id,
                'user_id' => $auth->used_by,
                'activity_type' => ShiftActivity::TYPE_TRANSACTION_REFUND,
                'reference_type' => 'transaction',
                'reference_id' => $transaction->id,
                'reference_amount' => $amount,
                'description' => "Refund #{$transaction->transaction_number} — {$auth->reason}",
            ]);
        });

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Transaksi direfund. Status menjadi REFUNDED.');
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

        $auth = $this->consumeAuthorization($transaction, TransactionAuthorization::ACTION_VOID, $request->input('otp'));

        if (! $auth) {
            return redirect()->route('transactions.show', $transaction)
                ->withErrors(['void' => 'Kode OTP tidak valid. Mintakan approval dari MANAGER/ADMIN terlebih dahulu.']);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($transaction, $auth) {
            $transaction->update(['status' => Transaction::STATUS_VOID]);

            foreach ($transaction->items as $item) {
                $product = \App\Models\Product::find($item->product_id);
                if ($product?->stock !== null) {
                    $product->increment('stock', $item->quantity);
                }
            }

            ShiftActivity::create([
                'shift_id' => $transaction->shift_id,
                'user_id' => $auth->used_by,
                'activity_type' => ShiftActivity::TYPE_TRANSACTION_VOID,
                'reference_type' => 'transaction',
                'reference_id' => $transaction->id,
                'description' => "Transaction #{$transaction->transaction_number} dibatalkan — {$auth->reason}",
            ]);
        });

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Transaksi dibatalkan.');
    }

    public function requestAuthorization(\Illuminate\Http\Request $request, Transaction $transaction): \Illuminate\Http\RedirectResponse
    {
        if ($transaction->status !== Transaction::STATUS_COMPLETED) {
            return back()->withErrors(['authorize' => 'Transaksi ini sudah diproses.']);
        }

        if ($transaction->shift_id !== session('shift_id')) {
            return back()->withErrors(['authorize' => 'Hanya transaksi dalam shift yang sama.']);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:VOID,REFUND'],
            'transaction_code' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'integer', 'min:1', 'max:' . $transaction->grand_total],
        ]);

        if ($validated['transaction_code'] !== $transaction->transaction_number) {
            return back()->withErrors(['authorize' => 'Kode transaksi tidak sesuai.']);
        }

        if ($validated['action'] === TransactionAuthorization::ACTION_REFUND && empty($validated['amount'])) {
            return back()->withErrors(['authorize' => 'Nominal refund wajib diisi.']);
        }

        $existing = $transaction->authorizations()
            ->whereIn('status', [TransactionAuthorization::STATUS_PENDING, TransactionAuthorization::STATUS_APPROVED])
            ->where('action', $validated['action'])
            ->first();

        if ($existing) {
            return back()->withErrors(['authorize' => 'Permintaan untuk transaksi ini sudah ada.']);
        }

        TransactionAuthorization::create([
            'transaction_id' => $transaction->id,
            'requested_by' => session('user_id'),
            'action' => $validated['action'],
            'reason' => $validated['reason'],
            'amount' => $validated['action'] === TransactionAuthorization::ACTION_REFUND ? (int) $validated['amount'] : null,
            'status' => TransactionAuthorization::STATUS_PENDING,
        ]);

        return back()->with('success', 'Permintaan dikirim. Tunggu approval MANAGER/ADMIN untuk kode OTP.');
    }

    public function approveAuthorization(\Illuminate\Http\Request $request, Transaction $transaction): \Illuminate\Http\RedirectResponse
    {
        $auth = $transaction->authorizations()
            ->where('status', TransactionAuthorization::STATUS_PENDING)
            ->latest()
            ->first();

        if (! $auth) {
            return back()->withErrors(['authorize' => 'Tidak ada permintaan pending untuk transaksi ini.']);
        }

        $otp = TransactionAuthorization::generateOtp();

        $auth->update([
            'status' => TransactionAuthorization::STATUS_APPROVED,
            'otp_hash' => \Illuminate\Support\Facades\Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(TransactionAuthorization::OTP_TTL_MINUTES),
            'approved_by' => session('user_id'),
            'approved_at' => now(),
        ]);

        session()->flash('authorization_otp', $otp);

        return redirect()->route('transactions.show', $transaction)
            ->with('success', "Permintaan disetujui. Kode OTP untuk diinput kasir: {$otp} (berlaku " . TransactionAuthorization::OTP_TTL_MINUTES . ' menit).');
    }

    private function consumeAuthorization(Transaction $transaction, string $action, ?string $otp): ?TransactionAuthorization
    {
        if (! $otp) {
            return null;
        }

        $auth = $transaction->authorizations()
            ->where('action', $action)
            ->where('status', TransactionAuthorization::STATUS_APPROVED)
            ->orderByDesc('approved_at')
            ->first();

        if (! $auth || ! $auth->isValid() || ! \Illuminate\Support\Facades\Hash::check($otp, $auth->otp_hash)) {
            return null;
        }

        $auth->update([
            'status' => TransactionAuthorization::STATUS_USED,
            'used_by' => session('user_id'),
            'used_at' => now(),
        ]);

        return $auth;
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.modifiers.*.group' => ['required', 'string', 'max:100'],
            'items.*.modifiers.*.name' => ['required', 'string', 'max:100'],
            'items.*.modifiers.*.price_modifier' => ['required', 'integer', 'min:0'],
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

        $items = \App\Models\Product::whereIn('id', collect($validated['items'])->pluck('product_id'))->get()->keyBy('id');

        foreach ($validated['items'] as $row) {
            $product = $items[$row['product_id']];
            if ($product->stock !== null && $product->stock < $row['quantity']) {
                return response()->json([
                    'message' => "Stok {$product->name} tidak cukup (sisa {$product->stock}).",
                ], 422);
            }
        }

        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $shift, $userId, $request, $items) {

            $subtotal = 0;
            foreach ($validated['items'] as $row) {
                $product = $items[$row['product_id']];
                $modTotal = collect($row['modifiers'] ?? [])->sum('price_modifier');
                $subtotal += ($product->price + $modTotal) * $row['quantity'];
            }

            $discount = $validated['discount'] ?? 0;
            $tax = $validated['tax'] ?? 0;
            $pct = (int) (Setting::value(Setting::KEY_SERVICE_CHARGE_PERCENT, 0) ?? 0);
            $serviceCharge = (int) round($subtotal * $pct / 100);
            $grandTotal = max(0, $subtotal - $discount + $tax + $serviceCharge);

            if ($validated['payment_amount'] < $grandTotal) {
                throw new \Illuminate\Http\Exceptions\HttpResponseException(
                    response()->json(['message' => 'Uang bayar lebih kecil dari total.'], 422)
                );
            }

            $change = $validated['payment_amount'] - $grandTotal;

            $transaction = Transaction::create([
                'shift_id' => $shift->id,
                'user_id' => $userId,
                'transaction_number' => Transaction::generateTransactionNumber(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'service_charge' => $serviceCharge,
                'grand_total' => $grandTotal,
                'payment_method_id' => $validated['payment_method_id'],
                'payment_amount' => $validated['payment_amount'],
                'change_amount' => $change,
                'status' => Transaction::STATUS_COMPLETED,
            ]);

            foreach ($validated['items'] as $row) {
                $product = $items[$row['product_id']];
                if ($product->stock !== null) {
                    $product->decrement('stock', $row['quantity']);
                }
                $mods = $row['modifiers'] ?? [];
                $unitPrice = $product->price + collect($mods)->sum('price_modifier');
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $row['quantity'],
                    'unit_price' => $unitPrice,
                    'discount' => 0,
                    'notes' => $row['notes'] ?? null,
                    'modifiers' => $mods ?: null,
                    'subtotal' => $unitPrice * $row['quantity'],
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