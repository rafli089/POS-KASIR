@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto space-y-4">
    <h1 class="text-xl font-semibold">Pengaturan</h1>

    <form method="POST" action="{{ route('settings.update') }}" class="bg-white border border-line rounded-xl p-5 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1.5">Service Charge (%)</label>
            <input type="number" name="service_charge_percent" min="0" max="100"
                   value="{{ old('service_charge_percent', $serviceChargePct) }}" required
                   class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            <p class="text-xs text-muted mt-1.5">Persentase dihitung dari subtotal transaksi. Dikenakan otomatis di kasir dan struk.</p>
            @error('service_charge_percent')<p class="text-xs text-error mt-1.5">{{ $message }}</p>@enderror
        </div>

        <button class="w-full py-2.5 rounded-lg bg-primary text-surface text-sm font-medium hover:bg-black transition">Simpan</button>
    </form>
</div>
@endsection