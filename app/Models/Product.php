<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'sku', 'price', 'cost_price', 'stock', 'status', 'image',
    ];

    protected $casts = [
        'price' => 'integer',
        'cost_price' => 'integer',
        'stock' => 'integer',
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function modifierGroups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'product_modifiers')->withPivot('display_order')->orderBy('product_modifiers.display_order');
    }
}