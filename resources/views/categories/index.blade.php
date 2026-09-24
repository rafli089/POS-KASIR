@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Kategori</h1>
        <a href="{{ route('categories.create') }}" class="px-4 py-2 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">+ Kategori</a>
    </div>

    <div class="bg-white border border-line rounded-xl overflow-hidden">
        @if($categories->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-muted">Belum ada kategori.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted text-xs border-b border-line">
                        <th class="px-5 py-2.5 font-medium">Nama</th>
                        <th class="px-5 py-2.5 font-medium">Urutan</th>
                        <th class="px-5 py-2.5 font-medium">Produk</th>
                        <th class="px-5 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr class="border-b border-line/50 last:border-0 hover:bg-background/50">
                            <td class="px-5 py-2.5 font-medium">{{ $category->name }}</td>
                            <td class="px-5 py-2.5">{{ $category->sort_order }}</td>
                            <td class="px-5 py-2.5">{{ $category->products->count() }}</td>
                            <td class="px-5 py-2.5 text-right space-x-2">
                                <a href="{{ route('categories.edit', $category) }}" class="text-primary text-xs hover:underline font-medium">Edit</a>
                                <form method="POST" action="{{ route('categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Hapus kategori ini?')">
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

    <div>{{ $categories->links() }}</div>
</div>
@endsection