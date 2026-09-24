<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modifier extends Model
{
    public $fillable = ['modifier_group_id', 'name', 'price_modifier', 'display_order'];

    protected $casts = [
        'price_modifier' => 'integer',
    ];

    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }
}