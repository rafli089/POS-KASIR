@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <h1 class="text-xl font-semibold">Modifier Produk</h1>
    <p class="text-sm text-muted">Pilih grup modifier yang tersedia per produk (Ukuran, Ekstra, dsb). Opsi dengan harga tambahan otomatis dihitung di kasir.</p>

    <form method="POST" action="{{ route('modifiers.update') }}">
        @csrf
        <div class="bg-white border border-line rounded-xl overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-muted text-xs border-b border-line">
                        <th class="px-5 py-2.5 font-medium min-w-48">Produk</th>
                        @foreach($groups as $group)
                            <th class="px-5 py-2.5 font-medium whitespace-nowrap">
                                {{ $group->name }}@if($group->required) <span class="text-error">*</span>@endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr class="border-b border-line/50 last:border-0 hover:bg-background/50">
                            <td class="px-5 py-2.5 font-medium">{{ $product->name }}</td>
                            @foreach($groups as $group)
                                <td class="px-5 py-2.5">
                                    <input type="checkbox"
                                           name="groups[{{ $product->id }}][{{ $group->id }}]"
                                           value="{{ $group->id }}"
                                           {{ $product->modifierGroups->contains('id', $group->id) ? 'checked' : '' }}
                                           class="accent-primary">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button class="mt-4 px-4 py-2 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">Simpan</button>
    </form>
</div>
@endsection