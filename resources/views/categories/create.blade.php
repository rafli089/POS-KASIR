@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white rounded-2xl border border-line shadow-sm p-8">
    <h1 class="text-xl font-semibold mb-6">Tambah Kategori</h1>

    <form method="POST" action="{{ route('categories.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1.5">Nama</label>
            <input name="name" value="{{ old('name') }}" required
                   class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            @error('name')<p class="text-xs text-error mt-1.5">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium mb-1.5">Urutan tampil</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                   class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="flex gap-2 pt-2">
            <a href="{{ route('categories.index') }}" class="px-4 py-2.5 rounded-lg border border-line text-sm hover:border-ink transition">Batal</a>
            <button class="flex-1 py-2.5 rounded-lg bg-primary text-surface text-sm font-semibold hover:bg-black transition">Simpan</button>
        </div>
    </form>
</div>
@endsection