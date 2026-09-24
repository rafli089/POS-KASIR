<?php

namespace App\Http\Controllers;

use App\Models\ModifierGroup;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModifierController extends Controller
{
    public function index(): View
    {
        return view('modifiers.index', [
            'groups' => ModifierGroup::with('modifiers')->orderBy('display_order')->get(),
            'products' => Product::with('modifierGroups:id')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'groups' => ['nullable', 'array'],
            'groups.*' => ['array'],
            'groups.*.*' => ['integer', 'distinct'],
        ]);

        $groups = collect($validated['groups'] ?? []);

        foreach (Product::pluck('id') as $productId) {
            Product::find($productId)->modifierGroups()->sync($groups->get($productId, []));
        }

        return redirect()->route('modifiers.index')->with('success', 'Asosiasi modifier produk disimpan.');
    }
}