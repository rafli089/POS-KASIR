@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto bg-white rounded-2xl border border-line shadow-sm p-8">
    <h1 class="text-xl font-semibold mb-6">Edit Produk</h1>

    <form method="POST" action="{{ route('products.update', $product) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-medium mb-1.5">Nama</label>
            <input name="name" value="{{ old('name', $product->name) }}" required
                   class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            @error('name')<p class="text-xs text-error mt-1.5">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1.5">SKU</label>
                <input name="sku" value="{{ old('sku', $product->sku) }}" required
                       class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                @error('sku')<p class="text-xs text-error mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">Kategori</label>
                <select name="category_id" class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm bg-white">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $category->id === $product->category_id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1.5">Harga jual (Rp)</label>
                <input type="number" name="price" min="0" value="{{ old('price', $product->price) }}" required
                       class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                @error('price')<p class="text-xs text-error mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">Harga modal (Rp)</label>
                <input type="number" name="cost_price" min="0" value="{{ old('cost_price', $product->cost_price) }}"
                       class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1.5">Stok</label>
                <input type="number" name="stock" min="0" value="{{ old('stock', $product->stock) }}" placeholder="Kosong = unlimited"
                       class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                @error('stock')<p class="text-xs text-error mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">Status</label>
                <select name="status" class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm bg-white">
                    <option value="ACTIVE" {{ $product->status === 'ACTIVE' ? 'selected' : '' }}>Aktif</option>
                    <option value="INACTIVE" {{ $product->status === 'INACTIVE' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2 pt-2">
            <a href="{{ route('products.index') }}" class="px-4 py-2.5 rounded-lg border border-line text-sm hover:border-ink transition">Batal</a>
            <button class="flex-1 py-2.5 rounded-lg bg-primary text-surface text-sm font-semibold hover:bg-black transition">Simpan</button>
        </div>
    </form>
</div>
@endsection