<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    const STATUS_OPEN = 'OPEN';
    const STATUS_CLOSED = 'CLOSED';

    protected $fillable = [
        'user_id', 'shift_number', 'opened_at', 'closed_at',
        'opening_cash', 'expected_cash', 'actual_cash', 'cash_difference', 'status',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'integer',
        'expected_cash' => 'integer',
        'actual_cash' => 'integer',
        'cash_difference' => 'integer',
    ];

    public static function generateShiftNumber(): string
    {
        $count = static::whereDate('opened_at', today())->count() + 1;

        return 'SHIFT-'.now()->format('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ShiftActivity::class);
    }

    public static function currentForUser(int $userId): ?self
    {
        return static::where('user_id', $userId)->where('status', self::STATUS_OPEN)->latest('opened_at')->first();
    }
}