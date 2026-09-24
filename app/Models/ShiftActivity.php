<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftActivity extends Model
{
    const TYPE_LOGIN = 'LOGIN';
    const TYPE_LOGOUT = 'LOGOUT';
    const TYPE_OPEN_SHIFT = 'OPEN_SHIFT';
    const TYPE_CLOSE_SHIFT = 'CLOSE_SHIFT';
    const TYPE_TRANSACTION_CREATED = 'TRANSACTION_CREATED';
    const TYPE_PAYMENT_COMPLETED = 'PAYMENT_COMPLETED';
    const TYPE_RECEIPT_PRINTED = 'RECEIPT_PRINTED';
    const TYPE_RECEIPT_REPRINTED = 'RECEIPT_REPRINTED';
    const TYPE_TRANSACTION_VOID = 'TRANSACTION_VOID';
    const TYPE_CASH_ADJUSTMENT = 'CASH_ADJUSTMENT';

    public $timestamps = false;

    protected $fillable = [
        'shift_id', 'user_id', 'activity_type',
        'reference_type', 'reference_id', 'description', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function shift(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}