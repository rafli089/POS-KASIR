@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Produk</h1>
        <a href="{{ route('products.create') }}" class="px-4 py-2 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">+ Produk</a>
    </div>

    <div class="bg-white border border-line rounded-xl overflow-hidden">
        @if($products->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-muted">Belum ada produk. Tambahkan produk pertama.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted text-xs border-b border-line">
                        <th class="px-5 py-2.5 font-medium">Nama</th>
                        <th class="px-5 py-2.5 font-medium">SKU</th>
                        <th class="px-5 py-2.5 font-medium">Kategori</th>
                        <th class="px-5 py-2.5 font-medium text-right">Harga</th>
                        <th class="px-5 py-2.5 font-medium text-right">Stok</th>
                        <th class="px-5 py-2.5 font-medium">Status</th>
                        <th class="px-5 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr class="border-b border-line/50 last:border-0 hover:bg-background/50">
                            <td class="px-5 py-2.5 font-medium">{{ $product->name }}</td>
                            <td class="px-5 py-2.5 text-muted">{{ $product->sku }}</td>
                            <td class="px-5 py-2.5">{{ $product->category?->name ?? '—' }}</td>
                            <td class="px-5 py-2.5 text-right">Rp{{ number_format($product->price, 0, ',', '.') }}</td>
                            <td class="px-5 py-2.5 text-right {{ $product->stock !== null && $product->stock === 0 ? 'text-error' : 'text-muted' }}">
                                {{ $product->stock !== null ? number_format($product->stock, 0, ',', '.') : '∞' }}
                            </td>
                            <td class="px-5 py-2.5">
                                @if($product->status === 'ACTIVE')
                                    <span class="px-2 py-0.5 rounded-md text-xs bg-success/10 text-success font-medium">ACTIVE</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-xs bg-line text-muted font-medium">INACTIVE</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-right space-x-2">
                                <a href="{{ route('products.edit', $product) }}" class="text-primary text-xs hover:underline font-medium">Edit</a>
                                <form method="POST" action="{{ route('products.destroy', $product) }}" class="inline" onsubmit="return confirm('Hapus produk ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-error text-xs hover:underline font-medium">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div>{{ $products->links() }}</div>
</div>
@endsection