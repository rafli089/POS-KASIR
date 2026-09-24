<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    use HasFactory;

    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_VOID = 'VOID';
    const STATUS_REFUNDED = 'REFUNDED';
    const STATUS_PENDING = 'PENDING';

    protected $fillable = [
        'shift_id', 'user_id', 'transaction_number', 'subtotal', 'discount', 'tax',
        'service_charge', 'grand_total', 'payment_method_id', 'payment_amount', 'change_amount', 'status',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'discount' => 'integer',
        'tax' => 'integer',
        'service_charge' => 'integer',
        'grand_total' => 'integer',
        'payment_amount' => 'integer',
        'change_amount' => 'integer',
    ];

    public static function generateTransactionNumber(): string
    {
        $count = static::whereDate('created_at', today())->count() + 1;

        return 'POS-'.now()->format('Ymd').'-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }

    public function shift(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function receipt(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Receipt::class);
    }
}