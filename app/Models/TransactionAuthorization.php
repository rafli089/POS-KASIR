<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionAuthorization extends Model
{
    use HasFactory;

    const ACTION_VOID = 'VOID';
    const ACTION_REFUND = 'REFUND';

    const STATUS_PENDING = 'PENDING';
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_REJECTED = 'REJECTED';
    const STATUS_USED = 'USED';

    const OTP_TTL_MINUTES = 10;

    protected $fillable = [
        'transaction_id', 'requested_by', 'action', 'reason', 'amount', 'status',
        'otp_hash', 'otp_expires_at', 'approved_by', 'approved_at', 'used_by', 'used_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'otp_expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function transaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function requester(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_APPROVED
            && $this->otp_expires_at !== null
            && $this->otp_expires_at->isFuture();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}