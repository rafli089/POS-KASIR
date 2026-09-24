<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModifierGroup extends Model
{
    public $fillable = ['name', 'required', 'display_order'];

    public function modifiers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Modifier::class)->orderBy('display_order');
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_modifiers')->withPivot('display_order');
    }
}