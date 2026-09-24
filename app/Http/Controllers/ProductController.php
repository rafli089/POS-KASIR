<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::with('category:id,name')->latest()->paginate(20);
        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = Category::where('status', 'ACTIVE')->orderBy('sort_order')->get();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'sku' => ['required', 'string', 'max:30', 'unique:products,sku'],
            'price' => ['required', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ]);

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Produk dibuat.');
    }

    public function edit(Product $product): View
    {
        $categories = Category::where('status', 'ACTIVE')->orderBy('sort_order')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'sku' => ['required', 'string', 'max:30', 'unique:products,sku,' . $product->id],
            'price' => ['required', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ]);

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Produk diperbarui.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Produk dihapus.');
    }
}